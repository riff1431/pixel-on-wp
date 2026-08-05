export function renderEcommerce(container, state) {
  const config = state.config?.ecommerce || {};
  const isWaEnabled = config.wa_enabled !== undefined ? config.wa_enabled === '1' : true;
  const waTemplate = config.wa_template || 'Hello {customer_name}, your order #{order_id} for {order_total} is confirmed! We will ship it to: {shipping_address}';
  const waCountryCode = config.wa_country_code || '';

  container.innerHTML = '';
  const wrapper = document.createElement('div');
  wrapper.style.display = 'flex';
  wrapper.style.flexDirection = 'column';
  wrapper.style.height = '100%';
  wrapper.style.position = 'relative';

  // Header
  const stickyHeader = document.createElement('div');
  stickyHeader.className = 'pp-card';
  stickyHeader.style.padding = '24px';
  stickyHeader.style.marginBottom = '24px';
  stickyHeader.style.borderRadius = 'var(--pp-radius-md)';
  stickyHeader.style.border = '1px solid var(--pp-border)';
  stickyHeader.style.background = 'var(--pp-surface)';
  
  stickyHeader.innerHTML = `
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
      <div>
        <h2 style="margin:0 0 8px 0; font-size: 20px; font-weight: 600; color: var(--pp-text-main);">eCommerce Tools</h2>
        <p style="margin:0; color: var(--pp-text-muted); font-size: 14px; max-width: 600px;">
          Manage eCommerce extensions and modular tools like WhatsApp Messaging.
        </p>
      </div>
      <button id="btn-save-ecommerce" class="pp-btn" style="height: 40px; padding: 0 24px; background: var(--pp-primary); color: #fff; border: none; border-radius: var(--pp-radius-sm); cursor: pointer;">
        <span class="btn-text">Save Settings</span>
        <span class="btn-spinner pp-hidden" style="display: none;">
          Saving...
        </span>
      </button>
    </div>
  `;

  const msgContainer = document.createElement('div');
  msgContainer.id = 'ecommerce-msg-container';
  msgContainer.style.display = 'none';
  msgContainer.style.marginBottom = '20px';
  msgContainer.style.padding = '12px 20px';
  msgContainer.style.borderRadius = 'var(--pp-radius-sm)';
  
  wrapper.appendChild(msgContainer);
  wrapper.appendChild(stickyHeader);

  // Settings Grid
  const grid = document.createElement('div');
  grid.className = 'pp-layout-grid';
  grid.style.cssText = 'grid-template-columns: 1fr; gap: 24px; display: grid;';

  // WhatsApp Card
  const waCard = document.createElement('div');
  waCard.className = 'pp-card';
  waCard.style.padding = '24px';
  waCard.style.background = 'var(--pp-surface)';
  waCard.style.border = '1px solid var(--pp-border)';
  waCard.style.borderRadius = 'var(--pp-radius-md)';
  
  waCard.innerHTML = `
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
      <div>
        <h3 style="margin: 0 0 4px 0; font-size: 18px; color: var(--pp-text-main);">WhatsApp Quick Order Messaging</h3>
        <p style="margin: 0; color: var(--pp-text-muted); font-size: 14px;">Enable quick WhatsApp messaging directly from WooCommerce Orders.</p>
      </div>
      <label class="pp-switch" style="transform: scale(1.1); display: inline-block; position: relative; width: 40px; height: 24px;">
        <input type="checkbox" id="pp-wa-enable" ${isWaEnabled ? 'checked' : ''} style="opacity: 0; width: 0; height: 0;">
        <span class="pp-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px;"></span>
      </label>
    </div>

    <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border-light); padding: 20px; border-radius: 8px;">
      <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--pp-text-main); font-size: 14px;">Country Code Auto-Fallback</label>
        <input type="text" id="pp-wa-country-code" value="${waCountryCode}" placeholder="e.g. 880 (Auto-detects from billing country if empty)" style="width: 100%; max-width: 400px; padding: 10px 14px; border: 1px solid var(--pp-border); border-radius: var(--pp-radius-sm); font-size: 14px;">
        <p style="margin: 6px 0 0 0; font-size: 12px; color: var(--pp-text-muted);">Optional. Overrides local numbers if the international code is missing.</p>
      </div>

      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--pp-text-main); font-size: 14px;">Custom Message Template</label>
        <textarea id="pp-wa-template" rows="4" style="width: 100%; padding: 10px 14px; border: 1px solid var(--pp-border); border-radius: var(--pp-radius-sm); font-size: 14px; font-family: inherit;">${waTemplate}</textarea>
        <p style="margin: 6px 0 0 0; font-size: 12px; color: var(--pp-text-muted);">
          <strong>Available tags:</strong> {customer_name}, {order_id}, {product_names}, {order_total}, {currency}, {shipping_address}
        </p>
      </div>

      <div style="margin-top: 20px; padding: 12px; background: rgba(245, 158, 11, 0.1); border-left: 3px solid #f59e0b; border-radius: 4px;">
        <p style="margin: 0; font-size: 13px; color: #b45309;">
          <strong>Note on Bulk Action:</strong> Sending messages in bulk will open sequential new tabs. 
          Your browser might block multiple popups initially. Please allow popups for your domain when prompted.
        </p>
      </div>
    </div>
  `;
  const feedCard = document.createElement('div');
  feedCard.className = 'pp-card';
  feedCard.style.padding = '24px';
  feedCard.style.background = 'var(--pp-surface)';
  feedCard.style.border = '1px solid var(--pp-border)';
  feedCard.style.borderRadius = 'var(--pp-radius-md)';
  
  feedCard.innerHTML = `
    <div style="margin-bottom: 24px;">
      <h3 style="margin: 0 0 4px 0; font-size: 18px; color: var(--pp-text-main);">Multi-Channel Product Feeds</h3>
      <p style="margin: 0; color: var(--pp-text-muted); font-size: 14px;">Generate accurate product catalogs for Google, Meta, TikTok, and Pinterest.</p>
    </div>

    <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border-light); padding: 20px; border-radius: 8px;">
      <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--pp-text-main); font-size: 14px;">Select Platform Format</label>
        <select id="pp-feed-platform" style="width: 100%; max-width: 400px; padding: 10px 14px; border: 1px solid var(--pp-border); border-radius: var(--pp-radius-sm); font-size: 14px; background: #fff;">
          <option value="google">Google Merchant Center (XML)</option>
          <option value="meta">Meta / Facebook Catalog (CSV)</option>
          <option value="tiktok">TikTok Shop Catalog (JSON)</option>
          <option value="pinterest">Pinterest Dynamic Ads (XML)</option>
        </select>
      </div>

      <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--pp-text-main); font-size: 14px;">Feed File Name</label>
        <input type="text" id="pp-feed-name" value="product-feed" placeholder="e.g. google-feed-1" style="width: 100%; max-width: 400px; padding: 10px 14px; border: 1px solid var(--pp-border); border-radius: var(--pp-radius-sm); font-size: 14px;">
      </div>

      <div style="margin-bottom: 24px;">
        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--pp-text-main); font-size: 14px;">Stock Filter</label>
        <select id="pp-feed-stock" style="width: 100%; max-width: 400px; padding: 10px 14px; border: 1px solid var(--pp-border); border-radius: var(--pp-radius-sm); font-size: 14px; background: #fff;">
          <option value="all">All Products</option>
          <option value="instock">In-Stock Only</option>
        </select>
      </div>
      
      <button id="btn-generate-feed" class="pp-btn" style="height: 40px; padding: 0 24px; background: #0f172a; color: #fff; border: none; border-radius: var(--pp-radius-sm); cursor: pointer; display: flex; align-items: center; gap: 8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        <span class="btn-text">Generate & Download Feed</span>
        <span class="btn-spinner pp-hidden" style="display: none;">Generating...</span>
      </button>

      <div id="pp-feed-result" style="display: none; margin-top: 16px; padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
        <p style="margin: 0 0 8px 0; color: #15803d; font-weight: 600;">Feed Generated Successfully!</p>
        <p style="margin: 0; font-size: 13px; color: #475569;">Public Live URL for auto-sync:</p>
        <a id="pp-feed-url" href="#" target="_blank" style="font-size: 13px; color: #2563eb; word-break: break-all;"></a>
      </div>
    </div>
  `;

  grid.appendChild(feedCard);
  grid.appendChild(waCard);
  wrapper.appendChild(grid);
  container.appendChild(wrapper);

  // Styling for the toggle (if not already global)
  if (!document.getElementById('pp-toggle-style')) {
    const style = document.createElement('style');
    style.id = 'pp-toggle-style';
    style.innerHTML = `
      .pp-switch input:checked + .pp-slider { background-color: var(--pp-primary); }
      .pp-switch input:checked + .pp-slider:before { transform: translateX(16px); }
      .pp-switch .pp-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    `;
    document.head.appendChild(style);
  }

  // Save Event Listener
  const saveBtn = document.getElementById('btn-save-ecommerce');
  saveBtn.addEventListener('click', async () => {
    const btnText = saveBtn.querySelector('.btn-text');
    const btnSpinner = saveBtn.querySelector('.btn-spinner');
    
    btnText.style.display = 'none';
    btnSpinner.style.display = 'inline-block';
    saveBtn.disabled = true;
    msgContainer.style.display = 'none';

    const formData = new FormData();
    formData.append('action', 'pixelonwp_save_ecommerce_settings');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('wa_enabled', document.getElementById('pp-wa-enable').checked ? '1' : '0');
    formData.append('wa_template', document.getElementById('pp-wa-template').value);
    formData.append('wa_country_code', document.getElementById('pp-wa-country-code').value);

    try {
      const response = await fetch(window.pixelonwp_admin_vars.ajaxurl, {
        method: 'POST',
        body: formData
      });
      const result = await response.json();

      if (result.success) {
        msgContainer.textContent = result.data.message || 'Settings saved successfully.';
        msgContainer.style.background = 'rgba(34, 197, 94, 0.1)';
        msgContainer.style.border = '1px solid rgba(34, 197, 94, 0.3)';
        msgContainer.style.color = 'var(--pp-success)';
      } else {
        throw new Error(result.data.message || 'Error saving settings');
      }
    } catch (error) {
      msgContainer.textContent = error.message;
      msgContainer.style.background = 'rgba(239, 68, 68, 0.1)';
      msgContainer.style.border = '1px solid rgba(239, 68, 68, 0.3)';
      msgContainer.style.color = 'var(--pp-danger)';
    } finally {
      msgContainer.style.display = 'block';
      btnText.style.display = 'inline-block';
      btnSpinner.style.display = 'none';
      saveBtn.disabled = false;
    }
  });

  // Feed Generate Event Listener
  const feedBtn = document.getElementById('btn-generate-feed');
  feedBtn.addEventListener('click', async () => {
    const btnText = feedBtn.querySelector('.btn-text');
    const btnSpinner = feedBtn.querySelector('.btn-spinner');
    const resultDiv = document.getElementById('pp-feed-result');
    const urlLink = document.getElementById('pp-feed-url');
    
    btnText.style.display = 'none';
    btnSpinner.style.display = 'inline-block';
    feedBtn.disabled = true;
    resultDiv.style.display = 'none';

    const platform = document.getElementById('pp-feed-platform').value;
    const feedName = document.getElementById('pp-feed-name').value || 'product-feed';
    const stockFilter = document.getElementById('pp-feed-stock').value;

    const formData = new FormData();
    formData.append('action', 'pixelonwp_generate_feed');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('platform', platform);
    formData.append('feed_name', feedName);
    formData.append('stock_filter', stockFilter);

    try {
      const response = await fetch(window.pixelonwp_admin_vars.ajaxurl, {
        method: 'POST',
        body: formData
      });
      const result = await response.json();

      if (result.success && result.data && result.data.url) {
        urlLink.href = result.data.url;
        urlLink.textContent = result.data.url;
        resultDiv.style.display = 'block';
        
        // Trigger download
        const a = document.createElement('a');
        a.href = result.data.url;
        a.download = result.data.file_name;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      } else {
        throw new Error(result.data?.message || 'Error generating feed');
      }
    } catch (error) {
      alert(error.message);
    } finally {
      btnText.style.display = 'inline-block';
      btnSpinner.style.display = 'none';
      feedBtn.disabled = false;
    }
  });
}
