import { showToast } from '../components/toaster.js';

export function renderSettings(container, state) {
  // Read config safely
  const datalayerEnabled = state.config?.events_builder?.datalayer_enabled !== undefined ? 
        (state.config.events_builder.datalayer_enabled === true || state.config.events_builder.datalayer_enabled === '1' || state.config.events_builder.datalayer_enabled === 'true') : true;
  const userDataEnabled = state.config?.events_builder?.user_data_enabled !== undefined ? 
        (state.config.events_builder.user_data_enabled === true || state.config.events_builder.user_data_enabled === '1' || state.config.events_builder.user_data_enabled === 'true') : true;

  // Outer Wrapper for styling
  container.innerHTML = '';
  const wrapper = document.createElement('div');
  wrapper.style.display = 'flex';
  wrapper.style.flexDirection = 'column';
  wrapper.style.height = '100%';
  wrapper.style.position = 'relative';

  // Sticky Top Bar
  const stickyHeader = document.createElement('div');
  stickyHeader.className = 'pp-card';
  stickyHeader.style.padding = '24px';
  stickyHeader.style.marginBottom = '24px';
  stickyHeader.style.borderRadius = 'var(--pp-radius-md)';
  stickyHeader.style.border = '1px solid var(--pp-border)';
  stickyHeader.style.background = 'var(--pp-surface)';
  
  stickyHeader.innerHTML = `
    <div style="display: flex; justify-content: flex-end; align-items: flex-start; margin-bottom: 24px;">
      <button id="btn-save-events" class="pp-btn" style="height: 40px; padding: 0 24px;">
        <span class="btn-text">Save Settings</span>
        <span class="btn-spinner pp-hidden">
          <svg class="pp-spin" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </span>
      </button>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px; padding-top: 24px; border-top: 1px solid var(--pp-border);">
      <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.02); padding: 16px 20px; border-radius: var(--pp-radius-sm); border: 1px solid var(--pp-border-light);">
        <div>
          <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px; color: var(--pp-text-main);">Enable eCommerce DataLayer</div>
          <div style="font-size: 13px; color: var(--pp-text-muted);">Outputs a structured standard window.dataLayer object on your frontend.</div>
        </div>
        <label class="pp-switch" style="transform: scale(1.1);">
          <input type="checkbox" id="pp-datalayer-enable" ${datalayerEnabled ? 'checked' : ''}>
          <span class="pp-slider"></span>
        </label>
      </div>
      
      <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.02); padding: 16px 20px; border-radius: var(--pp-radius-sm); border: 1px solid var(--pp-border-light);">
        <div>
          <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px; color: var(--pp-text-main);">Enable User Data in DataLayer</div>
          <div style="font-size: 13px; color: var(--pp-text-muted);">Include hashed personal user data (like email and phone) in the dataLayer output for Advanced Matching.</div>
        </div>
        <label class="pp-switch" style="transform: scale(1.1);">
          <input type="checkbox" id="pp-user-data-enable" ${userDataEnabled ? 'checked' : ''}>
          <span class="pp-slider"></span>
        </label>
      </div>
    </div>
  `;
  wrapper.appendChild(stickyHeader);

  // Form Tracking Section
  const formTrackingData = state.config?.form_tracking || {};
  const formsList = [
    { id: 'wpforms', label: 'WPForms' },
    { id: 'contact_form_7', label: 'Contact Form 7' },
    { id: 'gravity_forms', label: 'Gravity Forms' },
    { id: 'fluent_forms', label: 'Fluent Forms' },
    { id: 'formidable_forms', label: 'Formidable Forms' },
    { id: 'ninja_forms', label: 'Ninja Forms' },
    { id: 'forminator', label: 'Forminator' },
    { id: 'jetformbuilder', label: 'JetFormBuilder' },
    { id: 'metform', label: 'MetForm' },
    { id: 'kali_forms', label: 'Kali Forms' },
    { id: 'optinmonster', label: 'OptinMonster' },
    { id: 'bloom', label: 'Bloom' },
    { id: 'thrive_leads', label: 'Thrive Leads' },
    { id: 'mailpoet', label: 'MailPoet' },
    { id: 'hustle', label: 'Hustle' },
    { id: 'icegram', label: 'Icegram' },
    { id: 'sumo', label: 'Sumo' },
    { id: 'elementor', label: 'Elementor Forms' }
  ];

  const formSection = document.createElement('div');
  formSection.className = 'pp-card';
  formSection.style.padding = '24px';
  formSection.style.marginBottom = '24px';
  formSection.style.borderRadius = 'var(--pp-radius-md)';
  formSection.style.border = '1px solid var(--pp-border)';
  formSection.style.background = 'var(--pp-surface)';
  
  let formHtml = `
    <h3 style="margin:0 0 8px 0; font-size: 18px; font-weight: 600; color: var(--pp-text-main);">Form DataLayer & Tracking</h3>
    <p style="margin:0 0 24px 0; color: var(--pp-text-muted); font-size: 14px;">
      Enable DataLayer tracking for specific form plugins. When enabled, form submissions will trigger DataLayer events automatically.
    </p>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
  `;

  formsList.forEach(form => {
    const isEnabled = formTrackingData[form.id] === '1' || formTrackingData[form.id] === true;
    formHtml += `
      <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.02); padding: 12px 16px; border-radius: var(--pp-radius-sm); border: 1px solid var(--pp-border-light);">
        <div style="font-weight: 500; font-size: 14px; color: var(--pp-text-main);">${form.label}</div>
        <label class="pp-switch">
          <input type="checkbox" class="form-tracking-toggle" data-form-id="${form.id}" ${isEnabled ? 'checked' : ''}>
          <span class="pp-slider"></span>
        </label>
      </div>
    `;
  });

  formHtml += `</div>`;
  formSection.innerHTML = formHtml;
  wrapper.appendChild(formSection);

  const msgContainer = document.createElement('div');
  msgContainer.id = 'builder-msg-container';
  msgContainer.style.display = 'none';
  wrapper.insertBefore(msgContainer, stickyHeader);

  container.appendChild(wrapper);

  // Save logic
  const saveBtn = document.getElementById('btn-save-events');
  saveBtn.addEventListener('click', async () => {
    saveBtn.disabled = true;
    saveBtn.querySelector('.btn-spinner').classList.remove('pp-hidden');
    msgContainer.style.display = 'none';

    const isEnabled = document.getElementById('pp-datalayer-enable').checked;
    const isUserEnabled = document.getElementById('pp-user-data-enable').checked;

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_save_events_builder');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('datalayer_enabled', isEnabled ? '1' : '0');
    formData.append('user_data_enabled', isUserEnabled ? '1' : '0');

    // Collect Form Tracking Settings
    const formSettings = {};
    document.querySelectorAll('.form-tracking-toggle').forEach(el => {
      formSettings[el.getAttribute('data-form-id')] = el.checked ? '1' : '0';
    });
    formData.append('form_tracking', JSON.stringify(formSettings));

    try {
      // Save events
      const response = await fetch(window.pixelonwp_admin_vars.ajaxurl, {
        method: 'POST',
        body: formData
      });
      const result = await response.json();

      if (result.success) {
        msgContainer.style.display = 'block';
        msgContainer.className = 'pp-alert pp-alert-success';
        msgContainer.style.marginBottom = '24px';
        msgContainer.textContent = 'DataLayer settings saved successfully!';
        showToast({ message: 'DataLayer and Form Tracking settings saved successfully!', type: 'success', title: 'Settings Saved' });
        if (state.config && state.config.events_builder) {
          state.config.events_builder.datalayer_enabled = isEnabled;
          state.config.events_builder.user_data_enabled = isUserEnabled;
        }
        if (state.config) {
          state.config.form_tracking = formSettings;
        }
        if (window.pixelonwp_admin_vars.config && window.pixelonwp_admin_vars.config.events_builder) {
          window.pixelonwp_admin_vars.config.events_builder.datalayer_enabled = isEnabled;
          window.pixelonwp_admin_vars.config.events_builder.user_data_enabled = isUserEnabled;
        }
        if (window.pixelonwp_admin_vars.config) {
          window.pixelonwp_admin_vars.config.form_tracking = formSettings;
        }
        
        // Auto hide after 3 seconds
        setTimeout(() => { msgContainer.style.display = 'none'; }, 3000);
      } else {
        throw new Error(result.data?.message || 'Server error');
      }
    } catch (err) {
      msgContainer.style.display = 'block';
      msgContainer.className = 'pp-alert pp-alert-danger';
      msgContainer.style.marginBottom = '24px';
      msgContainer.textContent = err.message || 'Network error occurred while saving configuration.';
    } finally {
      saveBtn.disabled = false;
      saveBtn.querySelector('.btn-spinner').classList.add('pp-hidden');
    }
  });
}
