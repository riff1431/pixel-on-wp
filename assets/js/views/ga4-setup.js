export function renderGA4Setup(container, state) {
  let activeTab = 'general';
  let customEvents = window.pixelonwp_admin_vars?.config?.ga4_custom_events || state.config?.ga4_custom_events || [];

  const header = document.createElement('div');
  header.className = 'pp-view-header';
  header.innerHTML = `
    <h2>Google Analytics 4 Setup</h2>
    <p>Configure your GA4 Measurement ID and manage Custom Events with dynamic parameter mapping.</p>
  `;
  container.appendChild(header);

  // Tab Navigation Bar
  const navBar = document.createElement('div');
  navBar.className = 'pp-tabs-container';
  navBar.style.marginBottom = '24px';
  navBar.style.borderBottom = '1px solid var(--pp-border)';
  navBar.innerHTML = `
    <button class="pp-tab active" data-tab="general" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-primary); border-bottom: 2px solid var(--pp-primary); font-weight: 600; cursor: pointer;">General Settings</button>
    <button class="pp-tab" data-tab="custom-events" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-text-muted); font-weight: 500; cursor: pointer;">Custom Events</button>
  `;
  container.appendChild(navBar);

  // Main Content Area
  const contentArea = document.createElement('div');
  contentArea.className = 'pp-ga4-content';
  container.appendChild(contentArea);

  // Tab switcher click listeners
  navBar.querySelectorAll('.pp-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      navBar.querySelectorAll('.pp-tab').forEach(t => {
        t.classList.remove('active');
        t.style.color = 'var(--pp-text-muted)';
        t.style.borderBottom = 'none';
        t.style.fontWeight = '500';
      });
      tab.classList.add('active');
      tab.style.color = 'var(--pp-primary)';
      tab.style.borderBottom = '2px solid var(--pp-primary)';
      tab.style.fontWeight = '600';
      
      activeTab = tab.dataset.tab;
      renderTabContent();
    });
  });

  const renderTabContent = () => {
    contentArea.innerHTML = '';
    if (activeTab === 'general') {
      renderGeneralTab();
    } else if (activeTab === 'custom-events') {
      renderCustomEventsTab();
    }
  };

  // --- TAB 1: GENERAL SETTINGS ---
  const renderGeneralTab = () => {
    const card = document.createElement('div');
    card.className = 'pp-card';
    card.style.maxWidth = '600px';
    card.style.animation = 'fadeInUp 0.3s ease-out forwards';
    
    const ga4Id = window.pixelonwp_admin_vars?.config?.ga4_id || state.config?.ga4_id || '';

    card.innerHTML = `
      <div style="margin-bottom: 24px;">
        <label class="pp-label" style="display: flex; align-items: center; gap: 8px;">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--pp-primary)"><circle cx="12" cy="12" r="10"></circle><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
          GA4 Measurement ID
        </label>
        <input type="text" id="input-ga4-id" class="pp-input" placeholder="e.g. G-XXXXXXX" value="${ga4Id}" />
        <p style="color: var(--pp-text-muted); font-size: 0.85rem; margin-top: 8px;">
          Enter your Google Analytics 4 Measurement ID. Leave blank to disable GA4 injection.
        </p>
      </div>
      
      <div id="ga4-msg-container" style="display: none; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; font-size: 0.9rem;"></div>

      <button id="btn-save-ga4" class="pp-btn" style="width: 100%;">
        <span class="btn-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
        </span>
        <span class="btn-text">Save GA4 Configuration</span>
        <span class="btn-spinner pp-hidden">
          <svg class="pp-spin" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
        </span>
      </button>
    `;

    contentArea.appendChild(card);

    const btnSave = document.getElementById('btn-save-ga4');
    const msgContainer = document.getElementById('ga4-msg-container');

    btnSave.addEventListener('click', async () => {
      btnSave.disabled = true;
      btnSave.style.opacity = '0.7';
      btnSave.querySelector('.btn-icon').classList.add('pp-hidden');
      btnSave.querySelector('.btn-spinner').classList.remove('pp-hidden');
      msgContainer.style.display = 'none';

      const newGa4Id = document.getElementById('input-ga4-id').value.trim();

      const formData = new FormData();
      formData.append('action', 'PixelOnWP_save_ga4');
      formData.append('nonce', window.pixelonwp_admin_vars.nonce);
      formData.append('ga4_id', newGa4Id);

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
          msgContainer.textContent = 'GA4 configuration saved successfully!';
          
          if (state.config) {
            state.config.ga4_id = newGa4Id;
          }
          if (window.pixelonwp_admin_vars.config) {
            window.pixelonwp_admin_vars.config.ga4_id = newGa4Id;
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
  };

  // --- TAB 2: CUSTOM EVENTS BUILDER ---
  const renderCustomEventsTab = () => {
    const card = document.createElement('div');
    card.className = 'pp-card';
    card.style.animation = 'fadeInUp 0.3s ease-out forwards';
    
    let rowsHtml = '';
    if (customEvents.length === 0) {
      rowsHtml = `<tr><td colspan="5" style="padding: 32px; text-align: center; color: var(--pp-text-muted);">No custom events configured. Click "+ Add New Custom Event" below to start.</td></tr>`;
    } else {
      customEvents.forEach(evt => {
        const triggers = { click: 'Element Click', submit: 'Form Submit', visibility: 'Visibility', page_view: 'Page View' };
        const clientStatus = evt.client_enabled ? '<span class="pp-badge pp-badge-success">Browser</span>' : '<span class="pp-badge pp-badge-neutral">Disabled</span>';
        const serverStatus = evt.server_enabled ? '<span class="pp-badge pp-badge-success">Server</span>' : '<span class="pp-badge pp-badge-neutral">Disabled</span>';
        rowsHtml += `
          <tr style="border-bottom: 1px solid var(--pp-border-light);">
            <td style="padding: 14px 16px; font-weight: 600; color: var(--pp-text-main); font-family: monospace;">${evt.name}</td>
            <td style="padding: 14px 16px;"><span class="pp-badge pp-badge-neutral">${triggers[evt.trigger_type] || evt.trigger_type}</span></td>
            <td style="padding: 14px 16px; font-family: monospace; font-size: 12px; color: var(--pp-primary);">${evt.selector || '<span style="color: var(--pp-text-muted);">N/A (Page View)</span>'}</td>
            <td style="padding: 14px 16px; display: flex; gap: 6px; align-items: center;">${clientStatus} ${serverStatus}</td>
            <td style="padding: 14px 16px; text-align: right;">
              <button class="pp-btn-outline edit-evt-btn" data-id="${evt.id}" style="padding: 4px 10px; min-height: 28px; font-size: 12px; margin-right: 6px;">Edit</button>
              <button class="pp-btn delete-evt-btn" data-id="${evt.id}" style="padding: 4px 10px; min-height: 28px; font-size: 12px; background: var(--pp-danger); border-color: var(--pp-danger);">Delete</button>
            </td>
          </tr>
        `;
      });
    }

    card.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; font-size: 16px; color: var(--pp-text-heading);">GA4 Custom Events list</h3>
        <button id="btn-add-custom-evt" class="pp-btn pp-btn-primary">+ Add New Custom Event</button>
      </div>
      <div class="pp-table-container">
        <table class="pp-table" style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="border-bottom: 1px solid var(--pp-border); text-align: left; background: rgba(0,0,0,0.02);">
              <th style="padding: 12px 16px; color: var(--pp-text-muted); font-size: 12px;">Event Name</th>
              <th style="padding: 12px 16px; color: var(--pp-text-muted); font-size: 12px;">Trigger Type</th>
              <th style="padding: 12px 16px; color: var(--pp-text-muted); font-size: 12px;">CSS Selector</th>
              <th style="padding: 12px 16px; color: var(--pp-text-muted); font-size: 12px;">Execution Channels</th>
              <th style="padding: 12px 16px; color: var(--pp-text-muted); font-size: 12px; text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>
    `;

    contentArea.appendChild(card);

    // Bind actions
    card.querySelector('#btn-add-custom-evt').addEventListener('click', () => openEventModal());
    card.querySelectorAll('.edit-evt-btn').forEach(btn => {
      btn.addEventListener('click', () => openEventModal(btn.dataset.id));
    });
    card.querySelectorAll('.delete-evt-btn').forEach(btn => {
      btn.addEventListener('click', () => deleteEvent(btn.dataset.id));
    });
  };

  // --- MODAL DIALOG ---
  const openEventModal = (evtId = '') => {
    const existing = evtId ? customEvents.find(e => e.id === evtId) : null;
    const modalId = 'pp-ga4-custom-event-modal';
    
    let modal = document.getElementById(modalId);
    if (modal) modal.remove();

    modal = document.createElement('div');
    modal.id = modalId;
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(15, 23, 42, 0.6)';
    modal.style.zIndex = '99999';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.backdropFilter = 'blur(4px)';

    modal.innerHTML = `
      <div style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 style="margin: 0 0 16px 0; font-size: 18px; color: var(--pp-text-heading);">${existing ? 'Edit Custom GA4 Event' : 'Create Custom GA4 Event'}</h3>
        
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <div>
            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">Event Name *</label>
            <input type="text" id="evt-name" class="pp-input" value="${existing ? existing.name : ''}" placeholder="e.g. click_download_brochure (snake_case)">
            <p style="color: var(--pp-text-muted); font-size: 11px; margin-top: 4px;">Must contain only lowercase letters, numbers, and underscores (strict GA4 format).</p>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
              <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">Trigger Type</label>
              <select id="evt-trigger" class="pp-select">
                <option value="click" ${existing && existing.trigger_type === 'click' ? 'selected' : ''}>Click Element</option>
                <option value="submit" ${existing && existing.trigger_type === 'submit' ? 'selected' : ''}>Form Submit</option>
                <option value="visibility" ${existing && existing.trigger_type === 'visibility' ? 'selected' : ''}>Element Visibility (IntersectionObserver)</option>
                <option value="page_view" ${existing && existing.trigger_type === 'page_view' ? 'selected' : ''}>Page View</option>
              </select>
            </div>
            <div>
              <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">CSS Selector</label>
              <input type="text" id="evt-selector" class="pp-input" value="${existing ? existing.selector : ''}" placeholder="e.g. .brochure-link, #contact-form">
            </div>
          </div>

          <div>
            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">Execution Channels</label>
            <div style="display: flex; gap: 24px; padding: 12px; background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border-light); border-radius: 6px;">
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; font-size: 13px;">
                <input type="checkbox" id="evt-client-enabled" ${!existing || existing.client_enabled ? 'checked' : ''}> Client-Side (gtag.js)
              </label>
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; font-size: 13px;">
                <input type="checkbox" id="evt-server-enabled" ${existing && existing.server_enabled ? 'checked' : ''}> Server-Side (Measurement Protocol)
              </label>
            </div>
          </div>

          <!-- Dynamic Parameters Repeater -->
          <div>
            <label style="display: flex; justify-content: space-between; font-weight: 600; margin-bottom: 8px; font-size: 13px; align-items: center;">
              Dynamic Parameters
              <button id="btn-add-param" class="pp-btn-outline" style="min-height: 24px; padding: 2px 8px; font-size: 11px;">+ Add Parameter</button>
            </label>
            <div id="params-repeater-container" style="display: flex; flex-direction: column; gap: 10px;">
              <!-- Parameter Rows Mount Here -->
            </div>
          </div>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
          <button id="btn-close-modal" class="pp-btn pp-btn-outline">Cancel</button>
          <button id="btn-save-evt" class="pp-btn pp-btn-primary">Save Event</button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    const repeater = modal.querySelector('#params-repeater-container');

    const addParamRow = (key = '', valType = 'static', valSource = '') => {
      const row = document.createElement('div');
      row.className = 'param-row';
      row.style.display = 'grid';
      row.style.gridTemplateColumns = '2fr 2fr 3fr auto';
      row.style.gap = '8px';
      row.style.alignItems = 'center';

      row.innerHTML = `
        <input type="text" class="param-key pp-input" style="padding: 6px;" value="${key}" placeholder="Key (e.g. form_id)">
        <select class="param-val-type pp-select">
          <option value="static" ${valType === 'static' ? 'selected' : ''}>Fixed Text</option>
          <option value="innerText" ${valType === 'innerText' ? 'selected' : ''}>DOM Element Text</option>
          <option value="attribute" ${valType === 'attribute' ? 'selected' : ''}>DOM Element Attribute</option>
          <option value="input" ${valType === 'input' ? 'selected' : ''}>DOM Element Value</option>
          <option value="query_param" ${valType === 'query_param' ? 'selected' : ''}>URL Query Param</option>
          <option value="js_var" ${valType === 'js_var' ? 'selected' : ''}>JS Variable</option>
        </select>
        <input type="text" class="param-val-src pp-input" style="padding: 6px; display: ${valType === 'innerText' ? 'none' : 'block'};" value="${valSource}" placeholder="${valType === 'static' ? 'e.g. static_value' : 'e.g. #selector or parameter_name'}">
        <button class="btn-remove-param" style="border: none; background: transparent; color: var(--pp-danger); font-size: 18px; cursor: pointer; padding: 4px;">&times;</button>
      `;

      const select = row.querySelector('.param-val-type');
      const input = row.querySelector('.param-val-src');

      select.addEventListener('change', () => {
        if (select.value === 'innerText') {
          input.style.display = 'none';
        } else {
          input.style.display = 'block';
          if (select.value === 'static') input.placeholder = 'e.g. static_value';
          else if (select.value === 'attribute') input.placeholder = 'e.g. data-price';
          else if (select.value === 'input') input.placeholder = 'e.g. #input-selector';
          else if (select.value === 'query_param') input.placeholder = 'e.g. query_param_name';
          else if (select.value === 'js_var') input.placeholder = 'e.g. window.someGlobalObj';
        }
      });

      row.querySelector('.btn-remove-param').addEventListener('click', () => row.remove());
      repeater.appendChild(row);
    };

    if (existing && existing.parameters && existing.parameters.length > 0) {
      existing.parameters.forEach(p => addParamRow(p.key, p.value_type, p.value_source));
    } else {
      addParamRow();
    }

    modal.querySelector('#btn-add-param').addEventListener('click', () => addParamRow());
    modal.querySelector('#btn-close-modal').addEventListener('click', () => modal.remove());

    modal.querySelector('#btn-save-evt').addEventListener('click', async () => {
      const rawName = modal.querySelector('#evt-name').value.trim();
      const selector = modal.querySelector('#evt-selector').value.trim();
      const trigger = modal.querySelector('#evt-trigger').value;
      const clientEnabled = modal.querySelector('#evt-client-enabled').checked;
      const serverEnabled = modal.querySelector('#evt-server-enabled').checked;

      if (!rawName) {
        alert('Event Name is required.');
        return;
      }
      if (trigger !== 'page_view' && !selector) {
        alert('CSS Selector is required for element interactions.');
        return;
      }

      // Enforce strict GA4 format (lowercase, alphanumeric, underscore)
      const name = rawName.toLowerCase().replace(/[^a-zA-Z0-9_]/g, '_');

      const paramsData = [];
      modal.querySelectorAll('.param-row').forEach(row => {
        const k = row.querySelector('.param-key').value.trim();
        const type = row.querySelector('.param-val-type').value;
        const src = row.querySelector('.param-val-src').value.trim();
        if (k) {
          paramsData.push({
            key: k.toLowerCase().replace(/[^a-zA-Z0-9_]/g, '_'),
            value_type: type,
            value_source: src
          });
        }
      });

      const updatedEvent = {
        id: existing ? existing.id : 'ga4_evt_' + Math.random().toString(36).substr(2, 9),
        name,
        trigger_type: trigger,
        selector,
        client_enabled: clientEnabled,
        server_enabled: serverEnabled,
        parameters: paramsData
      };

      if (existing) {
        customEvents = customEvents.map(e => e.id === existing.id ? updatedEvent : e);
      } else {
        customEvents.push(updatedEvent);
      }

      await saveCustomEventsToServer();
      modal.remove();
      renderTabContent();
    });
  };

  const deleteEvent = async (id) => {
    if (!confirm('Are you sure you want to delete this custom event?')) return;
    customEvents = customEvents.filter(e => e.id !== id);
    await saveCustomEventsToServer();
    renderTabContent();
  };

  const saveCustomEventsToServer = async () => {
    const formData = new FormData();
    formData.append('action', 'PixelOnWP_save_ga4_custom_events');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('events', JSON.stringify(customEvents));

    try {
      const response = await fetch(window.pixelonwp_admin_vars.ajaxurl, {
        method: 'POST',
        body: formData
      });
      const result = await response.json();
      if (result.success) {
        customEvents = result.data.events;
        if (window.pixelonwp_admin_vars.config) {
          window.pixelonwp_admin_vars.config.ga4_custom_events = customEvents;
        }
        if (state.config) {
          state.config.ga4_custom_events = customEvents;
        }
      } else {
        alert('Server Error: ' + (result.data?.message || 'Unknown error'));
      }
    } catch (e) {
      alert('Network Error occurred while saving.');
    }
  };

  renderTabContent();
}
