export function renderCampaignBuilder(container, state) {
  const content = document.createElement('div');
  content.innerHTML = `
    <div style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: var(--pp-radius-lg); padding: 24px; margin-bottom: 24px;">
      <h3 style="margin-top: 0;">Instant Campaign Builder</h3>
      <p style="color: var(--pp-text-muted);">Select a WooCommerce product to instantly generate full ad copy and video scripts using Gemini AI.</p>
      
      <div style="display: flex; gap: 16px; align-items: center; margin-top: 20px;">
        <select id="ai-product-select" style="flex: 1; max-width: 300px; padding: 10px; border-radius: 6px; border: 1px solid var(--pp-border);">
          <option value="">Loading products...</option>
        </select>
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
      const select = document.getElementById('ai-product-select');
      const btn = document.getElementById('ai-generate-ad-btn');
      
      if (json.success && json.data.length > 0) {
        select.innerHTML = '<option value="">-- Select a Product --</option>' + json.data.map(p => `<option value="${p.id}">${p.name} (${p.price})</option>`).join('');
        select.addEventListener('change', (e) => {
          btn.disabled = !e.target.value;
        });
      } else {
        select.innerHTML = '<option value="">No WooCommerce products found</option>';
      }
    } catch (e) {
      document.getElementById('ai-product-select').innerHTML = '<option value="">Error loading products</option>';
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
        alert('Failed to generate ad copy.');
      }
    } catch (err) {
      alert('Error communicating with AI engine.');
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
