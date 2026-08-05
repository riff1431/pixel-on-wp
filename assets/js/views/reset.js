import { showToast } from '../components/toaster.js';

export function renderReset(container, state) {
  const header = document.createElement('div');
  header.className = 'pp-view-header';
  header.innerHTML = `
    <h2>Clear All History & Data</h2>
    <p>Permanently delete all tracking events, CAPI settings, and integration configurations.</p>
  `;

  const resetLayout = document.createElement('div');
  resetLayout.className = 'pp-card';
  resetLayout.style.maxWidth = '600px';
  resetLayout.style.margin = '40px auto';
  resetLayout.style.padding = '32px';
  resetLayout.style.textAlign = 'center';
  
  resetLayout.innerHTML = `
    <div style="margin-bottom: 24px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="none" stroke="var(--pp-danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.4));"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
    </div>
    <h3 style="color: var(--pp-text-main); font-size: 20px; margin-bottom: 12px;">Danger Zone: Factory Reset</h3>
    <p style="color: var(--pp-text-muted); font-size: 14px; margin-bottom: 32px; line-height: 1.6;">
      This action will <strong>permanently erase</strong> all your PixelOnWP configurations, including Meta Pixel IDs, Access Tokens, DataLayer event settings, and all logged history from the database. 
      <br><br><span style="color: var(--pp-danger); font-weight: 600;">This cannot be undone!</span>
    </p>
    
    <div id="step-1">
      <button id="btn-initiate-reset" class="pp-btn" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); color: var(--pp-danger); font-size: 15px; padding: 12px 24px; border-radius: 8px; width: 100%;">Initiate Factory Reset</button>
    </div>
    
    <div id="step-2" style="display: none; background: rgba(0,0,0,0.2); padding: 24px; border-radius: 8px; border: 1px solid var(--pp-border-strong); margin-top: 24px; text-align: left;">
      <label style="display: block; font-size: 13px; color: var(--pp-text-main); margin-bottom: 8px; font-weight: 600;">To confirm, please type <strong style="color: var(--pp-danger);">DELETE</strong> in the box below:</label>
      <input type="text" id="confirm-delete-input" class="pp-input" placeholder="Type DELETE" style="width: 100%; margin-bottom: 16px;">
      <button id="btn-final-reset" class="pp-btn" style="background: var(--pp-danger); color: #fff; width: 100%; opacity: 0.5; cursor: not-allowed;" disabled>Permanently Delete Everything</button>
    </div>
  `;

  container.appendChild(header);
  container.appendChild(resetLayout);

  const btnInitiate = resetLayout.querySelector('#btn-initiate-reset');
  const step2 = resetLayout.querySelector('#step-2');
  const inputConfirm = resetLayout.querySelector('#confirm-delete-input');
  const btnFinal = resetLayout.querySelector('#btn-final-reset');

  btnInitiate.addEventListener('click', () => {
    btnInitiate.style.display = 'none';
    step2.style.display = 'block';
    inputConfirm.focus();
  });

  inputConfirm.addEventListener('input', (e) => {
    if (e.target.value === 'DELETE') {
      btnFinal.disabled = false;
      btnFinal.style.opacity = '1';
      btnFinal.style.cursor = 'pointer';
      btnFinal.style.boxShadow = '0 0 15px rgba(239, 68, 68, 0.5)';
    } else {
      btnFinal.disabled = true;
      btnFinal.style.opacity = '0.5';
      btnFinal.style.cursor = 'not-allowed';
      btnFinal.style.boxShadow = 'none';
    }
  });

  btnFinal.addEventListener('click', async () => {
    if (inputConfirm.value !== 'DELETE') return;

    btnFinal.innerHTML = 'Deleting Data...';
    btnFinal.disabled = true;
    inputConfirm.disabled = true;

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_clear_all_data');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const result = await res.json();
      
      if (result.success) {
        btnFinal.innerHTML = 'Successfully Deleted!';
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
