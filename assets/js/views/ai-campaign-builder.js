export function renderCampaignBuilder(container, state) {
  const content = document.createElement('div');
  content.innerHTML = `
    <div style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: var(--pp-radius-lg); padding: 24px; margin-bottom: 24px;">
      <h3 style="margin-top: 0;">Instant Campaign Builder</h3>
      <p style="color: var(--pp-text-muted);">Select a WooCommerce product to instantly generate full ad copy and video scripts using Gemini AI.</p>
      
      <div style="display: flex; gap: 16px; align-items: center; margin-top: 20px;">
        <div id="ai-product-select-wrapper" style="flex: 1; max-width: 360px;">
          <select id="ai-product-select" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--pp-border);">
            <option value="">Loading products...</option>
          </select>
        </div>
        <select id="ai-platform-select" style="flex: 1; max-width: 200px; padding: 10px; border-radius: 6px; border: 1px solid var(--pp-border);">
          <option value="meta">Meta Ads (FB/IG)</option>
          <option value="tiktok">TikTok Ads</option>
          <option value="google">Google Ads</option>
        </select>
        <button id="ai-generate-ad-btn" class="pp-btn pp-btn-primary" disabled>Generate Campaign</button>
      </div>
    </div>
    
    <div id="ai-ad-results" style="display: none; gap: 24px; flex-direction: column;"></div>
  `;

  container.appendChild(content);

  // Fetch products
  const fetchProducts = async () => {
    const formData = new FormData();
    formData.append('action', 'pixelonwp_get_wc_products');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    
    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const json = await res.json();
      const rawSelect = document.getElementById('ai-product-select');
      const btn = document.getElementById('ai-generate-ad-btn');
      
      if (json.success && json.data.length > 0) {
        window.pixelonwp_products_map = {};
        json.data.forEach(p => { window.pixelonwp_products_map[p.id] = p; });

        // Build Custom Select Component replacing native select programmatically
        const wrapper = document.createElement('div');
        wrapper.id = 'ai-product-custom-dropdown';
        wrapper.style.position = 'relative';
        wrapper.style.flex = '1';
        wrapper.style.maxWidth = '360px';

        const placeholderImg = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="%23aaa" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.id = 'ai-product-select';
        hiddenInput.value = '';
        wrapper.appendChild(hiddenInput);

        const trigger = document.createElement('div');
        trigger.id = 'ai-dropdown-trigger';
        trigger.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 8px 14px; border-radius: 8px; border: 1px solid var(--pp-border); background: #fff; cursor: pointer; user-select: none; transition: border-color 0.2s;';
        
        const selectedContainer = document.createElement('div');
        selectedContainer.id = 'ai-dropdown-selected';
        selectedContainer.style.cssText = 'display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--pp-text-muted);';
        selectedContainer.innerHTML = '<span>-- Select a Product --</span>';
        trigger.appendChild(selectedContainer);

        const arrow = document.createElement('span');
        arrow.style.cssText = 'font-size: 12px; color: var(--pp-text-muted);';
        arrow.textContent = '▼';
        trigger.appendChild(arrow);

        wrapper.appendChild(trigger);

        const menu = document.createElement('div');
        menu.id = 'ai-dropdown-menu';
        menu.style.cssText = 'display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: #fff; border: 1px solid var(--pp-border); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 280px; overflow-y: auto; z-index: 100; padding: 6px 0;';

        json.data.forEach(p => {
          const item = document.createElement('div');
          item.className = 'ai-dropdown-item';
          item.setAttribute('data-id', p.id);
          item.style.cssText = 'display: flex; align-items: center; gap: 12px; padding: 10px 14px; cursor: pointer; transition: background 0.15s; font-size: 14px; border-bottom: 1px solid rgba(0,0,0,0.03);';

          const img = document.createElement('img');
          let imgSrc = (p.image && typeof p.image === 'string' && p.image.indexOf('<img') === -1) ? p.image : placeholderImg;
          img.src = imgSrc;
          img.style.cssText = 'width: 36px; height: 36px; object-fit: cover; border-radius: 6px; border: 1px solid var(--pp-border); background: #f7f7f7; flex-shrink: 0;';
          img.onerror = () => { img.src = placeholderImg; };
          item.appendChild(img);

          const infoBox = document.createElement('div');
          infoBox.style.cssText = 'flex: 1; min-width: 0;';

          const titleBox = document.createElement('div');
          titleBox.style.cssText = 'font-weight: 600; color: var(--pp-text-main); line-height: 1.3; margin-bottom: 2px;';
          let titleText = (p.name || 'Untitled Product').replace(/['"]\s*\/>/g, '').replace(/['"]\s*>/g, '').trim();
          titleBox.textContent = titleText;
          infoBox.appendChild(titleBox);

          const priceBox = document.createElement('div');
          priceBox.style.cssText = 'font-size: 12px; color: var(--pp-primary); font-weight: 600;';
          
          // Decode HTML entities (such as &#2547; or &nbsp;)
          const decodeEntities = (str) => {
            const txt = document.createElement('textarea');
            txt.innerHTML = str || '';
            return txt.value;
          };
          
          priceBox.textContent = decodeEntities(p.price);
          infoBox.appendChild(priceBox);

          item.appendChild(infoBox);

          item.addEventListener('mouseenter', () => { item.style.background = 'rgba(0, 102, 255, 0.05)'; });
          item.addEventListener('mouseleave', () => { item.style.background = 'transparent'; });

          item.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = p.id;
            hiddenInput.value = id;
            btn.disabled = false;

            selectedContainer.innerHTML = '';
            
            const selImg = document.createElement('img');
            selImg.src = imgSrc;
            selImg.style.cssText = 'width: 28px; height: 28px; object-fit: cover; border-radius: 4px; border: 1px solid var(--pp-border);';
            selImg.onerror = () => { selImg.src = placeholderImg; };
            selectedContainer.appendChild(selImg);

            const selName = document.createElement('span');
            selName.style.cssText = 'font-weight: 600; color: var(--pp-text-main);';
            selName.textContent = titleText;
            selectedContainer.appendChild(selName);

            const selPrice = document.createElement('span');
            selPrice.style.cssText = 'font-size: 12px; color: var(--pp-primary); font-weight: bold;';
            selPrice.textContent = `(${p.price})`;
            selectedContainer.appendChild(selPrice);

            menu.style.display = 'none';
          });

          menu.appendChild(item);
        });

        wrapper.appendChild(menu);
        const selectContainer = document.getElementById('ai-product-select-wrapper');
        if (selectContainer) {
          selectContainer.innerHTML = '';
          selectContainer.appendChild(wrapper);
        } else if (rawSelect && rawSelect.parentNode) {
          rawSelect.parentNode.replaceChild(wrapper, rawSelect);
        }

        trigger.addEventListener('click', (e) => {
          e.stopPropagation();
          const isOpen = menu.style.display === 'block';
          menu.style.display = isOpen ? 'none' : 'block';
        });

        document.addEventListener('click', () => {
          if (menu) menu.style.display = 'none';
        });
      } else {
        const sel = document.getElementById('ai-product-select');
        if (sel && sel.tagName === 'SELECT') {
          sel.innerHTML = '<option value="">No WooCommerce products found</option>';
        }
      }
    } catch (e) {
      const sel = document.getElementById('ai-product-select');
      if (sel && sel.tagName === 'SELECT') {
        sel.innerHTML = '<option value="">Error loading products</option>';
      }
    }
  };

  fetchProducts();
  
  let currentRegenCount = 0;

  const generateCampaign = async (isRegen = false) => {
    const productId = document.getElementById('ai-product-select').value;
    if (!productId) return;

    if (!isRegen) {
      currentRegenCount = 0;
    } else {
      currentRegenCount++;
    }

    const btn = document.getElementById('ai-generate-ad-btn');
    const originalText = btn.innerText;
    btn.innerText = isRegen ? 'Regenerating...' : 'Generating...';
    btn.disabled = true;

    // Also disable regen button if it exists
    const regenBtn = document.getElementById('ai-regen-ad-btn');
    if (regenBtn) {
        regenBtn.innerText = 'Regenerating...';
        regenBtn.disabled = true;
    }

    const formData = new FormData();
    formData.append('action', 'pixelonwp_generate_ad_copy');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('product_id', productId);
    formData.append('platform', document.getElementById('ai-platform-select').value);
    formData.append('regen_count', currentRegenCount);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const json = await res.json();

      if (json.success && json.data) {
        renderResults(json.data);
      } else {
        const resultsContainer = document.getElementById('ai-ad-results');
        resultsContainer.style.display = 'flex';
        const msg = json.data && json.data.message ? json.data.message : 'Failed to generate ad copy. Please check your API configuration.';
        resultsContainer.innerHTML = `
          <div style="background: #fff5f5; border: 1px solid #feb2b2; border-radius: var(--pp-radius-md); padding: 20px; color: #c53030;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
              <span style="font-size: 20px;">⚠️</span>
              <strong style="font-size: 16px;">AI Generation Error</strong>
            </div>
            <p style="margin: 0 0 14px 0; font-size: 14px; line-height: 1.5; color: #742a2a;">${msg}</p>
            <a href="#ai-engine" onclick="window.location.hash = 'ai-engine'; location.reload();" class="pp-btn" style="background: #e53e3e; color: #fff; text-decoration: none; display: inline-block; padding: 8px 16px; font-size: 13px; border-radius: 6px;">Configure API Keys</a>
          </div>
        `;
      }
    } catch (err) {
      const resultsContainer = document.getElementById('ai-ad-results');
      resultsContainer.style.display = 'flex';
      resultsContainer.innerHTML = `
        <div style="background: #fff5f5; border: 1px solid #feb2b2; border-radius: var(--pp-radius-md); padding: 20px; color: #c53030;">
          <strong>Error communicating with AI engine.</strong> Please check your internet connection or server logs.
        </div>
      `;
    } finally {
      btn.innerText = 'Generate Campaign';
      btn.disabled = false;
      if (regenBtn) {
          regenBtn.innerText = '🔄 Regenerate Better Strategy';
          regenBtn.disabled = false;
      }
    }
  };

  document.getElementById('ai-generate-ad-btn').addEventListener('click', () => generateCampaign(false));

  const renderResults = (data) => {
    const resultsContainer = document.getElementById('ai-ad-results');
    resultsContainer.style.display = 'flex';
    
    const createCopyBlock = (title, contentObj, isRecommended = false) => {
      const borderColor = isRecommended ? 'var(--pp-primary)' : 'var(--pp-border)';
      const bgStyle = isRecommended ? 'background: rgba(0, 102, 255, 0.03);' : 'background: var(--pp-surface);';
      const badgeHtml = isRecommended ? '<span style="background: var(--pp-primary); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-left: 12px;">★ RECOMMENDED</span>' : '';

      let html = `<div style="${bgStyle} border: 2px solid ${borderColor}; border-radius: var(--pp-radius-md); padding: 20px;">
        <div style="display: flex; align-items: center; margin-bottom: 16px;">
          <h4 style="margin: 0; font-size: 18px; color: ${isRecommended ? 'var(--pp-primary)' : 'var(--pp-text-main)'};">${title}</h4>
          ${badgeHtml}
        </div>
        <div style="display: grid; gap: 12px;">
      `;

      let fullText = '';
      
      if (typeof contentObj === 'string') {
        fullText = contentObj;
        
        let sectionsHTML = '';
        // Split by double newline followed by an all-caps heading (which can include spaces, numbers, &, /)
        const sections = contentObj.split(/\n\n(?=[A-Z0-9 &\/]+(?:\n|$))/);
        
        sections.forEach(section => {
          const lines = section.trim().split('\n');
          if (lines.length > 0) {
            let heading = lines[0];
            let content = lines.slice(1).join('\n');
            
            // If the heading is not all caps or the content is empty, just render the whole block
            if (content.trim() === '' || !/^[A-Z0-9 &\/]+$/.test(heading.trim())) {
              sectionsHTML += `
                <div style="margin-bottom: 12px;">
                  <div style="font-size: 14px; background: rgba(0,0,0,0.02); padding: 12px; border-radius: 6px; border: 1px solid var(--pp-border); white-space: pre-wrap; line-height: 1.6; color: var(--pp-text-main);">${section.trim()}</div>
                </div>
              `;
            } else {
              sectionsHTML += `
                <div style="margin-bottom: 12px;">
                  <div style="font-size: 11px; color: var(--pp-primary); text-transform: uppercase; font-weight: 700; margin-bottom: 6px; letter-spacing: 0.5px;">${heading}</div>
                  <div style="font-size: 14px; background: #fff; padding: 14px; border-radius: 6px; border: 1px solid var(--pp-border); white-space: pre-wrap; line-height: 1.6; color: var(--pp-text-main); box-shadow: 0 1px 3px rgba(0,0,0,0.02);">${content}</div>
                </div>
              `;
            }
          }
        });

        html += `<div>${sectionsHTML}</div>`;
      } else {
        for (const [key, val] of Object.entries(contentObj)) {
          fullText += `${key.toUpperCase()}:\n${val}\n\n`;
          html += `
            <div>
              <div style="font-size: 11px; color: var(--pp-text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">${key.replace(/_/g, ' ')}</div>
              <div style="font-size: 14px; background: rgba(0,0,0,0.02); padding: 10px; border-radius: 4px; border: 1px solid var(--pp-border); white-space: pre-wrap;">${val}</div>
            </div>
          `;
        }
      }
      
      html += `</div><button class="pp-btn copy-btn" style="margin-top: 16px; width: 100%;" data-clipboard="${encodeURIComponent(fullText)}">Copy to Clipboard</button></div>`;
      return html;
    };

    let html = '';
    
    // Sort so 'recommended' always appears at the top
    const entries = Object.entries(data).filter(([k]) => k !== 'is_demo');
    entries.sort((a, b) => {
        if (a[0].toLowerCase().includes('recommended')) return -1;
        if (b[0].toLowerCase().includes('recommended')) return 1;
        return 0;
    });

    for (const [key, val] of entries) {
        const isRecommended = key.toLowerCase().includes('recommended');
        const formattedTitle = key.replace(/_/g, ' ').toUpperCase();
        html += createCopyBlock(formattedTitle, val, isRecommended);
    }
    
    html += `
      <button id="ai-regen-ad-btn" class="pp-btn pp-btn-secondary" style="margin-top: 24px; font-weight: bold;">
        🔄 Regenerate Better Strategy
      </button>
    `;

    resultsContainer.innerHTML = html;

    document.getElementById('ai-regen-ad-btn').addEventListener('click', () => generateCampaign(true));

    document.querySelectorAll('.copy-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const text = decodeURIComponent(e.target.dataset.clipboard);
        navigator.clipboard.writeText(text);
        const orig = e.target.innerText;
        e.target.innerText = 'Copied!';
        setTimeout(() => e.target.innerText = orig, 2000);
      });
    });
  };
}
