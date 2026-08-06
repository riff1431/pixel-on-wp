export function renderHeaderFooter(container, state) {

  const hfConfig = state.config?.config?.header_footer || {};
  const hfHeader = hfConfig.header || '';
  const hfBody = hfConfig.body || '';
  const hfFooter = hfConfig.footer || '';

  const dashboardGrid = document.createElement('div');
  dashboardGrid.style.display = 'grid';
  dashboardGrid.style.gridTemplateColumns = '1fr';
  dashboardGrid.style.gap = '24px';
  dashboardGrid.style.marginBottom = '40px';

  // Config Card
  const configCard = document.createElement('div');
  configCard.className = 'pp-card';
  configCard.innerHTML = `
    <div class="pp-card-header">
      <div class="pp-card-title">Code Snippet Injection</div>
    </div>
    <div style="display: flex; flex-direction: column; gap: 24px;">
      
      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: var(--pp-text-main);">Header Code <code>&lt;head&gt;</code></label>
        <p style="font-size: 11px; color: var(--pp-text-muted); margin-bottom: 8px;">Scripts added here will be printed in the <code>&lt;head&gt;</code> section. Ideal for meta tags, CSS, or verification codes.</p>
        <textarea id="hf_header" class="pp-input" style="min-height: 150px; font-family: 'JetBrains Mono', monospace; font-size: 12px; resize: vertical; background: #020617; border-color: var(--pp-border-strong); color: #4ade80;" placeholder="<!-- Paste your header code here -->"></textarea>
      </div>

      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: var(--pp-text-main);">Body Code <code>&lt;body&gt;</code></label>
        <p style="font-size: 11px; color: var(--pp-text-muted); margin-bottom: 8px;">Scripts added here will be printed just after the opening <code>&lt;body&gt;</code> tag. Often used for GTM noscript fallbacks.</p>
        <textarea id="hf_body" class="pp-input" style="min-height: 150px; font-family: 'JetBrains Mono', monospace; font-size: 12px; resize: vertical; background: #020617; border-color: var(--pp-border-strong); color: #4ade80;" placeholder="<!-- Paste your body code here -->"></textarea>
      </div>

      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: var(--pp-text-main);">Footer Code <code>&lt;/body&gt;</code></label>
        <p style="font-size: 11px; color: var(--pp-text-muted); margin-bottom: 8px;">Scripts added here will be printed right before the closing <code>&lt;/body&gt;</code> tag. Best for non-render-blocking tracking scripts.</p>
        <textarea id="hf_footer" class="pp-input" style="min-height: 150px; font-family: 'JetBrains Mono', monospace; font-size: 12px; resize: vertical; background: #020617; border-color: var(--pp-border-strong); color: #4ade80;" placeholder="<!-- Paste your footer code here -->"></textarea>
      </div>

      <div style="margin-top: 8px;">
        <button id="btn-save-hf" class="pp-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
          Save Configuration
        </button>
      </div>
    </div>
  `;

  dashboardGrid.appendChild(configCard);
  container.appendChild(dashboardGrid);

  // Set initial values
  document.getElementById('hf_header').value = hfHeader;
  document.getElementById('hf_body').value = hfBody;
  document.getElementById('hf_footer').value = hfFooter;

  // Save functionality
  const saveBtn = document.getElementById('btn-save-hf');
  saveBtn.addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="pp-spinner"></span> Saving...';
    btn.disabled = true;

    try {
      const fd = new FormData();
      fd.append('action', 'pixelonwp_save_header_footer');
      fd.append('nonce', window.pixelonwp_admin_vars.nonce);
      fd.append('hf_header', document.getElementById('hf_header').value);
      fd.append('hf_body', document.getElementById('hf_body').value);
      fd.append('hf_footer', document.getElementById('hf_footer').value);

      const response = await fetch(window.pixelonwp_admin_vars.ajaxurl, {
        method: 'POST',
        body: fd
      });

      const res = await response.json();
      if (res.success) {
        window.PixelOnWP.showToast('Header & Footer configuration saved successfully!', 'success');
        
        // Update local state
        if (!state.config.config.header_footer) state.config.config.header_footer = {};
        state.config.config.header_footer.header = document.getElementById('hf_header').value;
        state.config.config.header_footer.body = document.getElementById('hf_body').value;
        state.config.config.header_footer.footer = document.getElementById('hf_footer').value;
      } else {
        window.PixelOnWP.showToast(res.data.message || 'Error saving configuration', 'error');
      }
    } catch (err) {
      console.error(err);
      window.PixelOnWP.showToast('Network error while saving.', 'error');
    } finally {
      btn.innerHTML = originalText;
      btn.disabled = false;
    }
  });
}
