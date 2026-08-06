export function renderLicense(container, state) {

  const contentGrid = document.createElement('div');
  contentGrid.className = 'pp-grid-unequal-2-1';
  contentGrid.style.maxWidth = '1000px';

  // License Input Card
  const licenseCard = document.createElement('div');
  licenseCard.className = 'pp-card';
  licenseCard.innerHTML = `
    <div class="pp-card-header">
      <div class="pp-card-title">Enter License Key</div>
    </div>
    
    <div style="margin-bottom: 24px;">
      <p style="color: var(--pp-text-muted); font-size: 14px; margin-bottom: 16px;">
        Your license key was sent to you via email after your purchase. If you cannot find it, please check your spam folder or log into your account.
      </p>
      
      <div style="position: relative; display: flex; align-items: center; max-width: 500px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key" style="position: absolute; left: 16px; color: var(--pp-text-muted);"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
        <input type="text" class="pp-input" placeholder="Enter your license key here (e.g. XXXX-XXXX-XXXX-XXXX)" style="padding-left: 48px; font-family: monospace; font-size: 16px; letter-spacing: 1px;">
      </div>
    </div>

    <button class="pp-btn">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2-1 4-2 7-2 2.5 0 4.5 1 6.5 2a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
      Activate License
    </button>
  `;

  // Status Box
  const statusBox = document.createElement('div');
  statusBox.className = 'pp-card';
  statusBox.style.display = 'flex';
  statusBox.style.flexDirection = 'column';
  statusBox.style.alignItems = 'center';
  statusBox.style.justifyContent = 'center';
  statusBox.style.textAlign = 'center';
  
  // Toggle this boolean to see the Active vs Inactive state in the UI for testing
  const isLicensed = false; 

  if (isLicensed) {
    statusBox.innerHTML = `
      <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--pp-success-bg); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; border: 2px solid rgba(16, 185, 129, 0.2); box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="var(--pp-success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
      </div>
      <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 8px 0; color: var(--pp-text-main);">License Active</h3>
      <p style="font-size: 13px; color: var(--pp-text-muted); margin-bottom: 24px;">Your PixelOnWP Pro license is valid and active.</p>
      
      <div style="width: 100%; text-align: left; background: rgba(0,0,0,0.2); padding: 16px; border-radius: var(--pp-radius-sm); font-size: 13px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span style="color: var(--pp-text-muted);">Plan</span>
          <span style="font-weight: 600; color: var(--pp-primary);">Agency Pro</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span style="color: var(--pp-text-muted);">Expires</span>
          <span style="font-weight: 600;">Lifetime</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span style="color: var(--pp-text-muted);">Support</span>
          <span style="font-weight: 600;">Priority</span>
        </div>
      </div>
      <button class="pp-btn-outline" style="margin-top: 24px; width: 100%; border-color: var(--pp-danger); color: var(--pp-danger);">Deactivate License</button>
    `;
  } else {
    statusBox.innerHTML = `
      <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--pp-warning-bg); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; border: 2px solid rgba(245, 158, 11, 0.2); box-shadow: 0 0 20px rgba(245, 158, 11, 0.2);">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="var(--pp-warning)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
      </div>
      <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 8px 0; color: var(--pp-text-main);">License Inactive</h3>
      <p style="font-size: 13px; color: var(--pp-text-muted); margin-bottom: 24px;">Please activate your license to unlock Server-Side tracking and CAPI integration.</p>
      
      <button class="pp-btn-outline" style="width: 100%; justify-content: center;">
        Upgrade to Pro
      </button>
    `;
  }

  contentGrid.appendChild(licenseCard);
  contentGrid.appendChild(statusBox);

  container.appendChild(contentGrid);
}
