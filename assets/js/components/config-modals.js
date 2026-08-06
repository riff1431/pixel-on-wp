import { showModal } from './modal.js';
import { showToast } from './toaster.js';
import { enhanceAllSelects } from './select.js';

/**
 * Open Meta Pixel & CAPI Configuration Modal
 */
export function openMetaConfigModal() {
  const meta = window.pixelonwp_admin_vars?.config?.meta || {};
  const trackingMode = window.pixelonwp_admin_vars?.config?.config?.facebook_tracking_mode || 'hybrid';

  let pixelsList = [];
  if (Array.isArray(meta.pixels) && meta.pixels.length > 0) {
    pixelsList = meta.pixels.map(p => ({
      id: p.id || 'pixel_1',
      pixelId: p.pixel_id || p.pixelId || '',
      conversionsApiToken: p.capi_token || p.conversionsApiToken || '',
      testEventCode: p.test_code || p.testEventCode || '',
      setupType: p.setup_type || p.setupType || (p.capi_token || p.conversionsApiToken ? 'advanced' : 'basic')
    }));
  } else if (meta.pixel_id || meta.pixelId) {
    pixelsList = [{
      id: 'pixel_1',
      pixelId: meta.pixel_id || meta.pixelId || '',
      conversionsApiToken: meta.capi_token || meta.capiToken || '',
      testEventCode: meta.test_code || meta.testCode || '',
      setupType: (meta.capi_token || meta.capiToken) ? 'advanced' : 'basic'
    }];
  } else {
    pixelsList = [{ id: 'pixel_1', pixelId: '', conversionsApiToken: '', testEventCode: '', setupType: 'basic' }];
  }

  const body = document.createElement('div');
  body.style.padding = '8px 0';

  const renderBlocks = () => {
    let blocksHtml = pixelsList.map((p, idx) => {
      const isAdvanced = p.setupType === 'advanced' || !!p.conversionsApiToken;
      return `
        <div class="modal-meta-block" data-index="${idx}" style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: 12px; padding: 16px; margin-bottom: 14px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--pp-border-light);">
            <strong style="font-size: 13px; color: var(--pp-text-heading);">Pixel Setup #${idx + 1}</strong>
            ${pixelsList.length > 1 ? `<button type="button" class="btn-remove-modal-pixel" data-index="${idx}" style="background: none; border: 1px solid var(--pp-danger); color: var(--pp-danger); border-radius: 4px; padding: 2px 8px; font-size: 11px; cursor: pointer;">Remove</button>` : ''}
          </div>

          <div style="margin-bottom: 12px;">
            <label style="display: block; font-weight: 600; margin-bottom: 4px; font-size: 12px; color: var(--pp-text-heading);">Meta Pixel ID</label>
            <input type="text" class="pp-input modal-pixel-id-input" value="${p.pixelId || ''}" placeholder="e.g. 123456789012345">
          </div>

          <div style="margin-bottom: 12px;">
            <label style="display: block; font-weight: 600; margin-bottom: 4px; font-size: 12px; color: var(--pp-text-heading);">CAPI Access Token (Optional for Server-Side)</label>
            <textarea class="pp-input modal-capi-token-input" style="min-height: 55px; resize: vertical;" placeholder="EAAG...">${p.conversionsApiToken || ''}</textarea>
          </div>

          <div>
            <label style="display: block; font-weight: 600; margin-bottom: 4px; font-size: 12px; color: var(--pp-text-heading);">Test Event Code (Optional)</label>
            <input type="text" class="pp-input modal-test-code-input" value="${p.testEventCode || ''}" placeholder="TEST12345">
          </div>
        </div>
      `;
    }).join('');

    body.innerHTML = `
      <div style="display: flex; flex-direction: column; gap: 16px;">
        <div style="background: linear-gradient(135deg, rgba(24, 119, 242, 0.08) 0%, rgba(225, 29, 72, 0.05) 100%); border: 1px solid rgba(24, 119, 242, 0.2); padding: 14px 16px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          <div>
            <strong style="color: var(--pp-text-heading); font-size: 14px;">Meta Pixel & Conversions API (CAPI)</strong>
            <p style="margin: 2px 0 0 0; font-size: 12px; color: var(--pp-text-muted);">Configure dual-browser and server-side multi-pixel pipeline.</p>
          </div>
        </div>

        <div id="modal-meta-blocks-container">
          ${blocksHtml}
        </div>

        <button type="button" class="pp-btn-outline" id="btn-add-modal-pixel" style="border: 1px dashed var(--pp-primary); color: var(--pp-primary); font-size: 13px; cursor: pointer; padding: 8px 14px; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
          + Add Another Pixel
        </button>

        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-heading);">Tracking Mode</label>
          <select id="modal-meta-mode" class="pp-select">
            <option value="hybrid" ${trackingMode === 'hybrid' ? 'selected' : ''}>Hybrid (Browser + CAPI)</option>
            <option value="server_only" ${trackingMode === 'server_only' ? 'selected' : ''}>Server-Only (AdBlocker Bypass)</option>
            <option value="client_only" ${trackingMode === 'client_only' ? 'selected' : ''}>Browser-Only</option>
          </select>
        </div>
      </div>
    `;

    body.querySelectorAll('.btn-remove-modal-pixel').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = parseInt(btn.dataset.index, 10);
        pixelsList.splice(idx, 1);
        renderBlocks();
      });
    });

    const addBtn = body.querySelector('#btn-add-modal-pixel');
    if (addBtn) {
      addBtn.addEventListener('click', () => {
        const currentBlocks = body.querySelectorAll('.modal-meta-block');
        currentBlocks.forEach((blk, i) => {
          if (pixelsList[i]) {
            pixelsList[i].pixelId = blk.querySelector('.modal-pixel-id-input').value.trim();
            pixelsList[i].conversionsApiToken = blk.querySelector('.modal-capi-token-input').value.trim();
            pixelsList[i].testEventCode = blk.querySelector('.modal-test-code-input').value.trim();
          }
        });
        pixelsList.push({ id: 'pixel_' + (pixelsList.length + 1), pixelId: '', conversionsApiToken: '', testEventCode: '', setupType: 'basic' });
        renderBlocks();
      });
    }
  };

  renderBlocks();

  const footer = document.createElement('div');
  footer.style.display = 'flex';
  footer.style.justifyContent = 'flex-end';
  footer.style.gap = '12px';
  footer.innerHTML = `
    <button class="pp-btn-outline" id="modal-meta-cancel">Cancel</button>
    <button class="pp-btn pp-btn-primary" id="modal-meta-save">Save Meta Settings</button>
  `;

  const modalInstance = showModal({
    title: 'Configure Meta Pixel & CAPI',
    body,
    footer
  });

  enhanceAllSelects(modalInstance.dialog);

  modalInstance.dialog.querySelector('#modal-meta-cancel').addEventListener('click', () => modalInstance.close());

  const btnSave = modalInstance.dialog.querySelector('#modal-meta-save');
  btnSave.addEventListener('click', async () => {
    const blocks = body.querySelectorAll('.modal-meta-block');
    const pixels = [];
    blocks.forEach((blk, idx) => {
      const pid = blk.querySelector('.modal-pixel-id-input').value.trim();
      const token = blk.querySelector('.modal-capi-token-input').value.trim();
      const testCode = blk.querySelector('.modal-test-code-input').value.trim();
      if (pid) {
        pixels.push({
          id: 'pixel_' + (idx + 1),
          pixelId: pid,
          conversionsApiToken: token,
          testEventCode: testCode,
          setupType: token ? 'advanced' : 'basic'
        });
      }
    });

    const mode = modalInstance.dialog.querySelector('#modal-meta-mode').value;

    btnSave.innerHTML = 'Saving...';
    btnSave.disabled = true;

    const dataPayload = {
      pixels: pixels,
      pixelId: pixels[0]?.pixelId || '',
      capiToken: pixels[0]?.conversionsApiToken || '',
      testCode: pixels[0]?.testEventCode || ''
    };

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_save_settings');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('data', JSON.stringify(dataPayload));
    formData.append('platform', 'facebook');
    formData.append('meta_pixel_id', pixels[0]?.pixelId || '');
    formData.append('meta_capi_token', pixels[0]?.conversionsApiToken || '');
    formData.append('meta_test_code', pixels[0]?.testEventCode || '');
    formData.append('facebook_tracking_mode', mode);

    try {
      await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      if (!window.pixelonwp_admin_vars.config.meta) window.pixelonwp_admin_vars.config.meta = {};
      window.pixelonwp_admin_vars.config.meta.pixels = pixels;
      window.pixelonwp_admin_vars.config.meta.pixel_id = pixels[0]?.pixelId || '';
      window.pixelonwp_admin_vars.config.meta.capi_token = pixels[0]?.conversionsApiToken || '';
      window.pixelonwp_admin_vars.config.meta.test_code = pixels[0]?.testEventCode || '';

      showToast({ message: 'Meta Pixel & CAPI settings updated successfully.', type: 'success', title: 'Meta Config Saved' });
      modalInstance.close();
      setTimeout(() => window.location.reload(), 500);
    } catch (e) {
      showToast({ message: 'Failed to save Meta settings.', type: 'error' });
      btnSave.innerHTML = 'Save Meta Settings';
      btnSave.disabled = false;
    }
  });
}

/**
 * Open TikTok Events API Configuration Modal
 */
export function openTikTokConfigModal() {
  const tiktok = window.pixelonwp_admin_vars?.config?.tiktok || {};
  const trackingMode = window.pixelonwp_admin_vars?.config?.config?.tiktok_tracking_mode || 'hybrid';

  const body = document.createElement('div');
  body.style.padding = '8px 0';
  body.innerHTML = `
    <div style="display: flex; flex-direction: column; gap: 16px;">
      <div style="background: linear-gradient(135deg, rgba(0, 0, 0, 0.05) 0%, rgba(225, 29, 72, 0.05) 100%); border: 1px solid rgba(0,0,0,0.15); padding: 14px 16px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#000000"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.02 1.59 4.23.95 1.15 2.27 1.95 3.72 2.23.01 1.29.01 2.58 0 3.87-1.37-.06-2.71-.56-3.81-1.39-.63-.48-1.17-1.09-1.57-1.8v8.34c0 1.25-.26 2.49-.78 3.62-.77 1.63-2.18 2.87-3.87 3.39-1.53.47-3.19.41-4.67-.18-1.74-.71-3.13-2.19-3.75-3.95-.61-1.74-.53-3.69.23-5.36.9-1.93 2.76-3.37 4.9-3.76.01 1.27-.02 2.54-.01 3.81-.88.16-1.69.67-2.19 1.43-.53.84-.6 1.88-.2 2.75.33.72.93 1.27 1.65 1.55.77.29 1.62.24 2.34-.14.73-.42 1.24-1.12 1.41-1.93.07-.46.06-.93.06-1.39V0h2.79z"/></svg>
        <div>
          <strong style="color: var(--pp-text-heading); font-size: 14px;">TikTok Events API & Pixel</strong>
          <p style="margin: 2px 0 0 0; font-size: 12px; color: var(--pp-text-muted);">Track conversions and optimize TikTok Ads campaign ROAS.</p>
        </div>
      </div>

      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-heading);">TikTok Pixel ID</label>
        <input type="text" id="modal-tt-pixel-id" class="pp-input" value="${tiktok.pixel_id || ''}" placeholder="e.g. C1234567890">
      </div>

      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-heading);">Access Token</label>
        <input type="password" id="modal-tt-token" class="pp-input" value="${tiktok.access_token || ''}" placeholder="Enter TikTok Access Token">
      </div>

      <div class="pp-modal-grid">
        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-heading);">Test Event Code</label>
          <input type="text" id="modal-tt-test-code" class="pp-input" value="${tiktok.test_code || ''}" placeholder="TEST12345">
        </div>
        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-heading);">Tracking Mode</label>
          <select id="modal-tt-mode" class="pp-select">
            <option value="hybrid" ${trackingMode === 'hybrid' ? 'selected' : ''}>Hybrid (Browser + Server)</option>
            <option value="server_only" ${trackingMode === 'server_only' ? 'selected' : ''}>Server-Only API</option>
            <option value="client_only" ${trackingMode === 'client_only' ? 'selected' : ''}>Browser Pixel Only</option>
          </select>
        </div>
      </div>
    </div>
  `;

  const footer = document.createElement('div');
  footer.style.display = 'flex';
  footer.style.justifyContent = 'flex-end';
  footer.style.gap = '12px';
  footer.innerHTML = `
    <button class="pp-btn-outline" id="modal-tt-cancel">Cancel</button>
    <button class="pp-btn pp-btn-primary" id="modal-tt-save">Save TikTok Settings</button>
  `;

  const modalInstance = showModal({
    title: 'Configure TikTok Events API',
    body,
    footer
  });

  enhanceAllSelects(modalInstance.dialog);

  modalInstance.dialog.querySelector('#modal-tt-cancel').addEventListener('click', () => modalInstance.close());

  const btnSave = modalInstance.dialog.querySelector('#modal-tt-save');
  btnSave.addEventListener('click', async () => {
    const pixelId = modalInstance.dialog.querySelector('#modal-tt-pixel-id').value.trim();
    const token = modalInstance.dialog.querySelector('#modal-tt-token').value.trim();
    const testCode = modalInstance.dialog.querySelector('#modal-tt-test-code').value.trim();
    const mode = modalInstance.dialog.querySelector('#modal-tt-mode').value;

    btnSave.innerHTML = 'Saving...';
    btnSave.disabled = true;

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_save_settings');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('tiktok_pixel_id', pixelId);
    formData.append('tiktok_access_token', token);
    formData.append('tiktok_test_code', testCode);
    formData.append('tiktok_tracking_mode', mode);

    try {
      await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      showToast({ message: 'TikTok Events API settings updated.', type: 'success', title: 'TikTok Config Saved' });
      modalInstance.close();
      setTimeout(() => window.location.reload(), 500);
    } catch (e) {
      showToast({ message: 'Failed to save TikTok settings.', type: 'error' });
      btnSave.innerHTML = 'Save TikTok Settings';
      btnSave.disabled = false;
    }
  });
}

/**
 * Open GA4 Configuration Modal
 */
export function openGA4ConfigModal() {
  const ga4 = window.pixelonwp_admin_vars?.config?.ga4_config || {};

  const body = document.createElement('div');
  body.style.padding = '8px 0';
  body.innerHTML = `
    <div style="display: flex; flex-direction: column; gap: 16px;">
      <div style="background: linear-gradient(135deg, rgba(249, 171, 0, 0.08) 0%, rgba(245, 158, 11, 0.05) 100%); border: 1px solid rgba(249, 171, 0, 0.25); padding: 14px 16px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#F9AB00"><path d="M17 19h2c.6 0 1-.4 1-1v-4c0-.6-.4-1-1-1h-2c-.6 0-1 .4-1 1v4c0 .6.4 1 1 1zM11 19h2c.6 0 1-.4 1-1V8c0-.6-.4-1-1-1h-2c-.6 0-1 .4-1 1v10c0 .6.4 1 1 1zM5 19h2c.6 0 1-.4 1-1v-6c0-.6-.4-1-1-1H5c-.6 0-1 .4-1 1v6c0 .6.4 1 1 1z"/></svg>
        <div>
          <strong style="color: var(--pp-text-heading); font-size: 14px;">Google Analytics 4 (GA4)</strong>
          <p style="margin: 2px 0 0 0; font-size: 12px; color: var(--pp-text-muted);">Measurement Protocol and e-Commerce events stream.</p>
        </div>
      </div>

      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-heading);">GA4 Measurement ID</label>
        <input type="text" id="modal-ga4-id" class="pp-input" value="${ga4.measurement_id || window.pixelonwp_admin_vars?.config?.ga4_id || ''}" placeholder="e.g. G-XXXXXXXXXX">
      </div>

      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-heading);">Measurement Protocol API Secret</label>
        <input type="password" id="modal-ga4-secret" class="pp-input" value="${ga4.api_secret || ''}" placeholder="Enter GA4 API Secret">
      </div>
    </div>
  `;

  const footer = document.createElement('div');
  footer.style.display = 'flex';
  footer.style.justifyContent = 'flex-end';
  footer.style.gap = '12px';
  footer.innerHTML = `
    <button class="pp-btn-outline" id="modal-ga4-cancel">Cancel</button>
    <button class="pp-btn pp-btn-primary" id="modal-ga4-save">Save GA4 Settings</button>
  `;

  const modalInstance = showModal({
    title: 'Configure Google Analytics 4',
    body,
    footer
  });

  modalInstance.dialog.querySelector('#modal-ga4-cancel').addEventListener('click', () => modalInstance.close());

  const btnSave = modalInstance.dialog.querySelector('#modal-ga4-save');
  btnSave.addEventListener('click', async () => {
    const measId = modalInstance.dialog.querySelector('#modal-ga4-id').value.trim();
    const secret = modalInstance.dialog.querySelector('#modal-ga4-secret').value.trim();

    btnSave.innerHTML = 'Saving...';
    btnSave.disabled = true;

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_save_settings');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('ga4_measurement_id', measId);
    formData.append('ga4_api_secret', secret);

    try {
      await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      showToast({ message: 'Google Analytics 4 settings updated.', type: 'success', title: 'GA4 Config Saved' });
      modalInstance.close();
      setTimeout(() => window.location.reload(), 500);
    } catch (e) {
      showToast({ message: 'Failed to save GA4 settings.', type: 'error' });
      btnSave.innerHTML = 'Save GA4 Settings';
      btnSave.disabled = false;
    }
  });
}

/**
 * Open GTM Server Container Configuration Modal
 */
export function openGTMConfigModal() {
  const gtmId = window.pixelonwp_admin_vars?.config?.gtm_id || '';

  const body = document.createElement('div');
  body.style.padding = '8px 0';
  body.innerHTML = `
    <div style="display: flex; flex-direction: column; gap: 16px;">
      <div style="background: linear-gradient(135deg, rgba(2, 132, 199, 0.08) 0%, rgba(14, 165, 233, 0.05) 100%); border: 1px solid rgba(2, 132, 199, 0.25); padding: 14px 16px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        <div>
          <strong style="color: var(--pp-text-heading); font-size: 14px;">Google Tag Manager (GTM) Container</strong>
          <p style="margin: 2px 0 0 0; font-size: 12px; color: var(--pp-text-muted);">Web Container injection and Server-Side Container routing.</p>
        </div>
      </div>

      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-heading);">GTM Container ID</label>
        <input type="text" id="modal-gtm-id" class="pp-input" value="${gtmId}" placeholder="e.g. GTM-XXXXXXX">
      </div>

      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-heading);">Server Container Domain (Optional)</label>
        <input type="url" id="modal-gtm-server-url" class="pp-input" value="" placeholder="https://gtm.yourdomain.com">
      </div>
    </div>
  `;

  const footer = document.createElement('div');
  footer.style.display = 'flex';
  footer.style.justifyContent = 'flex-end';
  footer.style.gap = '12px';
  footer.innerHTML = `
    <button class="pp-btn-outline" id="modal-gtm-cancel">Cancel</button>
    <button class="pp-btn pp-btn-primary" id="modal-gtm-save">Save GTM Settings</button>
  `;

  const modalInstance = showModal({
    title: 'Configure GTM Server Container',
    body,
    footer
  });

  modalInstance.dialog.querySelector('#modal-gtm-cancel').addEventListener('click', () => modalInstance.close());

  const btnSave = modalInstance.dialog.querySelector('#modal-gtm-save');
  btnSave.addEventListener('click', async () => {
    const idVal = modalInstance.dialog.querySelector('#modal-gtm-id').value.trim();

    btnSave.innerHTML = 'Saving...';
    btnSave.disabled = true;

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_save_settings');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('gtm_id', idVal);

    try {
      await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      showToast({ message: 'GTM Container settings updated.', type: 'success', title: 'GTM Config Saved' });
      modalInstance.close();
      setTimeout(() => window.location.reload(), 500);
    } catch (e) {
      showToast({ message: 'Failed to save GTM settings.', type: 'error' });
      btnSave.innerHTML = 'Save GTM Settings';
      btnSave.disabled = false;
    }
  });
}

/**
 * Open + Add Pixel Platform Selector Modal
 */
export function openAddPixelModal() {
  const body = document.createElement('div');
  body.style.padding = '8px 0';
  body.innerHTML = `
    <p style="margin: 0 0 20px 0; color: var(--pp-text-muted); font-size: 13px;">Select a tracking network or analytics platform to add or configure:</p>
    
    <div class="pp-modal-grid" style="gap: 14px;">
      <div id="add-item-meta" style="background: rgba(24, 119, 242, 0.05); border: 1.5px solid rgba(24, 119, 242, 0.25); padding: 16px; border-radius: 14px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: 10px; background: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.06);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        </div>
        <div>
          <strong style="display: block; font-size: 14px; color: var(--pp-text-heading);">Meta Pixel / CAPI</strong>
          <span style="font-size: 11px; color: var(--pp-text-muted);">Facebook & Instagram</span>
        </div>
      </div>

      <div id="add-item-tiktok" style="background: rgba(0, 0, 0, 0.03); border: 1.5px solid rgba(0,0,0,0.15); padding: 16px; border-radius: 14px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: 10px; background: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.06);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#000"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.02 1.59 4.23.95 1.15 2.27 1.95 3.72 2.23.01 1.29.01 2.58 0 3.87-1.37-.06-2.71-.56-3.81-1.39-.63-.48-1.17-1.09-1.57-1.8v8.34c0 1.25-.26 2.49-.78 3.62-.77 1.63-2.18 2.87-3.87 3.39-1.53.47-3.19.41-4.67-.18-1.74-.71-3.13-2.19-3.75-3.95-.61-1.74-.53-3.69.23-5.36.9-1.93 2.76-3.37 4.9-3.76.01 1.27-.02 2.54-.01 3.81-.88.16-1.69.67-2.19 1.43-.53.84-.6 1.88-.2 2.75.33.72.93 1.27 1.65 1.55.77.29 1.62.24 2.34-.14.73-.42 1.24-1.12 1.41-1.93.07-.46.06-.93.06-1.39V0h2.79z"/></svg>
        </div>
        <div>
          <strong style="display: block; font-size: 14px; color: var(--pp-text-heading);">TikTok Events API</strong>
          <span style="font-size: 11px; color: var(--pp-text-muted);">TikTok Ads Manager</span>
        </div>
      </div>

      <div id="add-item-ga4" style="background: rgba(249, 171, 0, 0.05); border: 1.5px solid rgba(249, 171, 0, 0.25); padding: 16px; border-radius: 14px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: 10px; background: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.06);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#F9AB00"><path d="M17 19h2c.6 0 1-.4 1-1v-4c0-.6-.4-1-1-1h-2c-.6 0-1 .4-1 1v4c0 .6.4 1 1 1zM11 19h2c.6 0 1-.4 1-1V8c0-.6-.4-1-1-1h-2c-.6 0-1 .4-1 1v10c0 .6.4 1 1 1zM5 19h2c.6 0 1-.4 1-1v-6c0-.6-.4-1-1-1H5c-.6 0-1 .4-1 1v6c0 .6.4 1 1 1z"/></svg>
        </div>
        <div>
          <strong style="display: block; font-size: 14px; color: var(--pp-text-heading);">Google Analytics 4</strong>
          <span style="font-size: 11px; color: var(--pp-text-muted);">GA4 MP Stream</span>
        </div>
      </div>

      <div id="add-item-gtm" style="background: rgba(2, 132, 199, 0.05); border: 1.5px solid rgba(2, 132, 199, 0.25); padding: 16px; border-radius: 14px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: 10px; background: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.06);">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>
        <div>
          <strong style="display: block; font-size: 14px; color: var(--pp-text-heading);">GTM Container</strong>
          <span style="font-size: 11px; color: var(--pp-text-muted);">Web & Server Container</span>
        </div>
      </div>
    </div>
  `;

  const modalInstance = showModal({
    title: 'Add New Pixel / Integration',
    body
  });

  body.querySelector('#add-item-meta').addEventListener('click', () => { modalInstance.close(); openMetaConfigModal(); });
  body.querySelector('#add-item-tiktok').addEventListener('click', () => { modalInstance.close(); openTikTokConfigModal(); });
  body.querySelector('#add-item-ga4').addEventListener('click', () => { modalInstance.close(); openGA4ConfigModal(); });
  body.querySelector('#add-item-gtm').addEventListener('click', () => { modalInstance.close(); openGTMConfigModal(); });
}

/**
 * Open Point & Click Visual Event Builder Modal
 */
export function openVisualBuilderModal() {
  const body = document.createElement('div');
  body.style.padding = '8px 0';
  body.innerHTML = `
    <div style="text-align: center; padding: 10px 0;">
      <div style="width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(135deg, rgba(225,29,72,0.12) 0%, rgba(217,70,239,0.12) 100%); color: var(--pp-primary); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
      </div>
      <h3 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 800; color: var(--pp-text-heading);">Point & Click Visual Event Builder</h3>
      <p style="margin: 0 0 24px 0; font-size: 14px; color: var(--pp-text-muted); line-height: 1.6;">
        Launch the live website overlay picker to visually click buttons, forms, and product elements to trigger custom tracking events without coding.
      </p>

      <div style="background: rgba(255,255,255,0.8); border: 1px solid var(--pp-border-strong); border-radius: 14px; padding: 18px; text-align: left; margin-bottom: 24px;">
        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-heading);">Target Page URL</label>
        <input type="url" id="modal-builder-url" class="pp-input" value="${window.location.origin}" style="width: 100%;">
      </div>

      <button id="btn-launch-overlay-picker" class="pp-btn pp-btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-weight: 700;">
        Launch Live Visual Picker
      </button>
    </div>
  `;

  const modalInstance = showModal({
    title: 'Visual Event Builder',
    body
  });

  body.querySelector('#btn-launch-overlay-picker').addEventListener('click', () => {
    const targetUrl = body.querySelector('#modal-builder-url').value.trim() || window.location.origin;
    modalInstance.close();
    showToast({ message: `Opening Visual Builder overlay on ${targetUrl}...`, type: 'info', title: 'Visual Builder Active' });
    window.open(targetUrl + '?pixelonwp_visual_builder=1', '_blank');
  });
}
