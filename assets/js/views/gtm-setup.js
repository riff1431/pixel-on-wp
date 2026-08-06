export function renderGTMSetup(container, state) {

  const card = document.createElement('div');
  card.className = 'pp-card';
  card.style.maxWidth = '600px';
  card.style.animation = 'fadeInUp 0.3s ease-out forwards';
  
  const gtmId = window.pixelonwp_admin_vars?.config?.gtm_id || state.config?.gtm_id || '';

  card.innerHTML = `
    <div style="margin-bottom: 24px;">
      <label class="pp-label" style="display: flex; align-items: center; gap: 8px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--pp-primary)"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        GTM Container ID
      </label>
      <input type="text" id="input-gtm-id" class="pp-input" placeholder="e.g. GTM-XXXXXXX" value="${gtmId}" />
      <p style="color: var(--pp-text-muted); font-size: 0.85rem; margin-top: 8px;">
        Enter your Google Tag Manager Container ID. Leave blank to disable GTM injection.
      </p>
    </div>
    
    <div id="gtm-msg-container" style="display: none; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; font-size: 0.9rem;"></div>

    <button id="btn-save-gtm" class="pp-btn" style="width: 100%;">
      <span class="btn-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
      </span>
      <span class="btn-text">Save GTM Configuration</span>
      <span class="btn-spinner pp-hidden">
        <svg class="pp-spin" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
      </span>
    </button>
  `;

  container.appendChild(card);

  const btnSave = document.getElementById('btn-save-gtm');
  const msgContainer = document.getElementById('gtm-msg-container');

  btnSave.addEventListener('click', async () => {
    btnSave.disabled = true;
    btnSave.style.opacity = '0.7';
    btnSave.querySelector('.btn-icon').classList.add('pp-hidden');
    btnSave.querySelector('.btn-spinner').classList.remove('pp-hidden');
    msgContainer.style.display = 'none';

    const newGtmId = document.getElementById('input-gtm-id').value.trim();

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_save_gtm');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('gtm_id', newGtmId);

    try {
      const response = await fetch(window.pixelonwp_admin_vars.ajaxurl, {
        method: 'POST',
        body: formData
      });
      const result = await response.json();

      if (result.success) {
        msgContainer.style.display = 'block';
        msgContainer.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
        msgContainer.style.border = '1px solid rgba(16, 185, 129, 0.2)';
        msgContainer.style.color = 'var(--pp-success)';
        msgContainer.textContent = 'GTM configuration saved successfully!';
        
        // Update local state
        if (state.config) {
          state.config.gtm_id = newGtmId;
        }
        if (window.pixelonwp_admin_vars.config) {
          window.pixelonwp_admin_vars.config.gtm_id = newGtmId;
        }
      } else {
        throw new Error(result.data?.message || 'Server error');
      }
    } catch (err) {
      msgContainer.style.display = 'block';
      msgContainer.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
      msgContainer.style.border = '1px solid rgba(239, 68, 68, 0.2)';
      msgContainer.style.color = 'var(--pp-danger)';
      msgContainer.textContent = err.message || 'Network error occurred.';
    } finally {
      btnSave.disabled = false;
      btnSave.style.opacity = '1';
      btnSave.querySelector('.btn-icon').classList.remove('pp-hidden');
      btnSave.querySelector('.btn-spinner').classList.add('pp-hidden');
    }
  });
}
