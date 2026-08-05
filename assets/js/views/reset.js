import { showToast } from '../components/toaster.js';

export function renderReset(container, state) {
  container.innerHTML = '';

  const header = document.createElement('div');
  header.className = 'pp-view-header';
  header.innerHTML = `
    <div>
      <h2>Clear All History & Data</h2>
      <p>Permanently reset tracking configurations, CAPI settings, and integration history.</p>
    </div>
  `;

  const resetCard = document.createElement('div');
  resetCard.className = 'pp-card';
  resetCard.style.cssText = `
    max-width: 680px;
    margin: 20px auto 40px;
    padding: 36px 32px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(254, 242, 242, 0.75) 100%);
    border: 1.5px solid rgba(239, 68, 68, 0.25);
    border-radius: 24px;
    box-shadow: 0 20px 45px -10px rgba(239, 68, 68, 0.12), 0 8px 18px -6px rgba(30, 27, 46, 0.04);
    text-align: center;
  `;

  resetCard.innerHTML = `
    <!-- Danger Icon Pod -->
    <div style="width: 76px; height: 76px; margin: 0 auto 20px; background: linear-gradient(135deg, rgba(254, 226, 226, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%); border: 1.5px solid rgba(239, 68, 68, 0.4); border-radius: 22px; display: flex; align-items: center; justify-content: center; box-shadow: 0 14px 32px -6px rgba(239, 68, 68, 0.28), inset 0 1.5px 1.5px rgba(255, 255, 255, 1);">
      <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
        <line x1="12" y1="9" x2="12" y2="13"/>
        <line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
    </div>

    <h3 style="color: var(--pp-text-heading); font-size: 22px; font-weight: 800; margin: 0 0 10px 0; letter-spacing: -0.02em;">Danger Zone: Factory Reset</h3>
    <p style="color: var(--pp-text-muted); font-size: 14px; margin: 0 0 24px 0; line-height: 1.6;">
      This action will <strong>permanently delete</strong> all saved configurations, tokens, custom tracking rules, and database logs. <span style="color: var(--pp-danger); font-weight: 700;">This operation cannot be undone.</span>
    </p>

    <!-- Checklist of items to be deleted -->
    <div style="background: rgba(255, 255, 255, 0.7); border: 1px solid rgba(239, 68, 68, 0.18); border-radius: 16px; padding: 18px 20px; margin-bottom: 28px; text-align: left;">
      <div style="font-size: 12px; font-weight: 700; color: var(--pp-danger); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">What will be erased:</div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px; color: var(--pp-text-main);">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="color: var(--pp-danger);">✕</span> Meta, TikTok, GA4 & CAPI Tokens
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="color: var(--pp-danger);">✕</span> Custom Universal Tracker Rules
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="color: var(--pp-danger);">✕</span> Fraud Prevention Rules & Logs
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="color: var(--pp-danger);">✕</span> DataLayer & Event History
        </div>
      </div>
    </div>

    <!-- Step 1 Button -->
    <div id="step-1">
      <button id="btn-initiate-reset" class="pp-btn" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.12) 0%, rgba(225, 29, 72, 0.18) 100%); border: 1.5px solid rgba(239, 68, 68, 0.4); color: var(--pp-danger); font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 12px; width: 100%; transition: all 0.25s ease;">
        Initiate Factory Reset
      </button>
    </div>

    <!-- Step 2 Confirmation Card -->
    <div id="step-2" style="display: none; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(16px); padding: 24px; border-radius: 18px; border: 1.5px solid rgba(239, 68, 68, 0.35); margin-top: 20px; text-align: left; box-shadow: 0 10px 30px -5px rgba(239, 68, 68, 0.15);">
      <label style="display: block; font-size: 13px; color: var(--pp-text-heading); margin-bottom: 10px; font-weight: 700;">
        To confirm deletion, please type <span style="color: var(--pp-danger); font-weight: 800; background: rgba(239, 68, 68, 0.1); padding: 2px 6px; border-radius: 4px;">DELETE</span> in the box below:
      </label>
      <input type="text" id="confirm-delete-input" class="pp-input" placeholder="Type DELETE" style="width: 100%; margin-bottom: 16px;">
      <button id="btn-final-reset" class="pp-btn pp-btn-danger" style="width: 100%; padding: 12px; font-size: 14px; font-weight: 700; opacity: 0.5; cursor: not-allowed;" disabled>
        Permanently Delete Everything
      </button>
    </div>
  `;

  container.appendChild(header);
  container.appendChild(resetCard);

  const btnInitiate = resetCard.querySelector('#btn-initiate-reset');
  const step2 = resetCard.querySelector('#step-2');
  const inputConfirm = resetCard.querySelector('#confirm-delete-input');
  const btnFinal = resetCard.querySelector('#btn-final-reset');

  btnInitiate.addEventListener('click', () => {
    btnInitiate.style.display = 'none';
    step2.style.display = 'block';
    inputConfirm.focus();
  });

  inputConfirm.addEventListener('input', (e) => {
    if (e.target.value.trim() === 'DELETE') {
      btnFinal.disabled = false;
      btnFinal.style.opacity = '1';
      btnFinal.style.cursor = 'pointer';
      btnFinal.style.boxShadow = '0 8px 24px rgba(239, 68, 68, 0.4)';
    } else {
      btnFinal.disabled = true;
      btnFinal.style.opacity = '0.5';
      btnFinal.style.cursor = 'not-allowed';
      btnFinal.style.boxShadow = 'none';
    }
  });

  btnFinal.addEventListener('click', async () => {
    if (inputConfirm.value.trim() !== 'DELETE') return;

    btnFinal.innerHTML = 'Deleting All Data...';
    btnFinal.disabled = true;
    inputConfirm.disabled = true;

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_clear_all_data');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const result = await res.json();
      
      if (result.success) {
        btnFinal.innerHTML = 'All Data Successfully Erased!';
        btnFinal.style.background = 'var(--pp-success)';
        showToast({ message: 'All PixelOnWP data permanently cleared.', type: 'warning', title: 'Factory Reset Complete' });
        
        setTimeout(() => {
          window.location.hash = 'setup';
          window.location.reload();
        }, 1500);
      } else {
        throw new Error('Failed');
      }
    } catch (e) {
      btnFinal.innerHTML = 'Error Deleting Data';
      btnFinal.style.background = 'var(--pp-danger)';
    }
  });
}
