(function() {
  const urlParams = new URLSearchParams(window.location.search);
  if (!urlParams.has('pixelonwp_visual_builder') && !urlParams.has('wpt_visual_builder')) return;

  function startVisualBuilder() {
    const config = window.pixelonwp_universal_tracker_vars || { rules: [], platforms: {}, context: { business_model: 'Lead-Gen', theme_builder: 'Gutenberg' }, playbooks: {} };
    let rules = config.rules || [];
    const playbooks = config.playbooks || {};
    const activeContext = config.context || { business_model: 'Lead-Gen', theme_builder: 'Gutenberg' };

    // Create Shadow DOM Container
    const host = document.createElement('div');
    host.id = 'pixelonwp-visual-setup-root';
    host.style.cssText = 'position: fixed; top: 20px; right: 20px; width: 380px; z-index: 2147483647;';
    document.body.appendChild(host);

    const shadow = host.attachShadow({ mode: 'open' });
    console.log('PixelOnWP: Visual Setup Tool mounted successfully.');

  // Scoped CSS styles
  const style = document.createElement('style');
  style.textContent = `
    :host {
      --bg-dark: #0f172a;
      --bg-card: #1e293b;
      --border-color: #334155;
      --text-main: #e2e8f0;
      --text-muted: #94a3b8;
      --primary: #38bdf8;
      --primary-hover: #0284c7;
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
      
      all: initial;
    }
    .panel-wrapper {
      width: 100%;
      max-height: 90vh;
      background: var(--bg-dark);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.4);
      color: var(--text-main);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      font-size: 13px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .panel-header {
      background: #1e293b;
      padding: 12px 16px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .panel-tabs {
      display: flex;
      background: #0b0f19;
      border-bottom: 1px solid var(--border-color);
    }
    .panel-tab {
      flex: 1;
      padding: 10px;
      background: transparent;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      text-align: center;
      font-weight: 600;
      font-size: 12px;
      transition: all 0.2s;
    }
    .panel-tab.active {
      color: var(--primary);
      border-bottom: 2px solid var(--primary);
      background: rgba(56, 189, 248, 0.05);
    }
    .panel-body {
      padding: 16px;
      overflow-y: auto;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .input-field {
      width: 100%;
      padding: 8px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      background: var(--bg-card);
      color: white;
      box-sizing: border-box;
    }
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .btn {
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 16px;
      font-weight: bold;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: background 0.2s;
    }
    .btn:hover { background: var(--primary-hover); }
    .btn-secondary { background: transparent; border: 1px solid var(--border-color); color: white; }
    .btn-secondary:hover { background: var(--bg-card); }
    .badge {
      font-size: 10px;
      padding: 2px 6px;
      border-radius: 4px;
      font-weight: bold;
    }
    .badge-success { background: rgba(16,185,129,0.15); color: var(--success); }
    .badge-neutral { background: rgba(148,163,184,0.15); color: var(--text-muted); }

    @media (max-width: 767px) {
      :host {
        position: fixed !important;
        top: auto !important;
        bottom: 0 !important;
        right: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: auto !important;
        max-height: 80vh !important;
        z-index: 2147483647 !important;
      }
      .panel-wrapper {
        width: 100vw !important;
        max-height: 80vh !important;
        border-radius: 16px 16px 0 0 !important;
        border: none !important;
        border-top: 1px solid var(--border-color) !important;
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1), height 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
      }
      .panel-wrapper.is-minimized {
        height: 48px !important;
        max-height: 48px !important;
        overflow: hidden !important;
      }
      .panel-body {
        padding: 12px 14px 24px 14px !important;
        gap: 10px !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
      }
      .form-row {
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        gap: 10px !important;
      }
      .input-field {
        min-height: 44px !important;
        padding: 10px 12px !important;
        font-size: 14px !important;
        box-sizing: border-box;
      }
      .btn {
        min-height: 44px !important;
        padding: 10px 16px !important;
        font-size: 14px !important;
        box-sizing: border-box;
      }
      .mobile-toggle-handle {
        display: block !important;
      }
      .btn-collapse-toggle {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: transform 0.2s ease-in-out !important;
      }
    }
    
    .toast-notification {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #1e293b;
      color: white;
      padding: 14px 20px;
      border-radius: 8px;
      box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      font-size: 13px;
      z-index: 2147483647;
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.2s ease-out, transform 0.2s ease-out;
      max-width: 340px;
      box-sizing: border-box;
    }
    .toast-notification.success { border-left: 4px solid var(--success); }
    .toast-notification.error { border-left: 4px solid var(--danger); }
    .toast-notification.warning { border-left: 4px solid var(--warning); }
    .toast-notification.info { border-left: 4px solid var(--primary); }

    @media (max-width: 480px) {
      .toast-notification {
        left: 20px;
        right: 20px;
        max-width: calc(100vw - 40px);
        bottom: 20px;
      }
    }
  `;
  shadow.appendChild(style);

  const wrapper = document.createElement('div');
  wrapper.className = 'panel-wrapper';
  shadow.appendChild(wrapper);

  // Custom Toast Notification System
  function showNotification(message, type = 'info') {
    const notificationId = 'pixelonwp-visual-notification';
    let notification = shadow.getElementById(notificationId);
    if (notification) notification.remove();

    notification = document.createElement('div');
    notification.id = notificationId;
    notification.className = `toast-notification ${type}`;

    notification.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: start; gap: 12px;">
        <div style="flex: 1; line-height: 1.4; color: white;">${message}</div>
        <button style="background: transparent; border: none; color: var(--text-muted); font-size: 16px; cursor: pointer; padding: 0; line-height: 1;">&times;</button>
      </div>
    `;

    shadow.appendChild(notification);

    // Trigger animations
    requestAnimationFrame(() => {
      notification.style.opacity = '1';
      notification.style.transform = 'translateY(0)';
    });

    const dismiss = () => {
      notification.style.opacity = '0';
      notification.style.transform = 'translateY(20px)';
      setTimeout(() => {
        notification.remove();
      }, 200);
    };

    notification.querySelector('button').addEventListener('click', dismiss);

    // Auto dismiss after 4 seconds
    setTimeout(() => {
      if (shadow.getElementById(notificationId) === notification) {
        dismiss();
      }
    }, 4000);
  }

  let activeTab = 'manual';
  let selectionMode = false;
  let parameterSelectionMode = false;
  let parameterRowTarget = null;
  let hoveredEl = null;
  const liveLogs = [];

  // Generate selector and fallback healing selectors
  function getSelectorDetails(el) {
    const primary = getUniqueSelector(el);
    const fallbacks = [];
    if (el.className) {
      el.classList.forEach(c => {
        if (!c.startsWith('pixelonwp-') && c !== 'pp-highlighted-el') {
          fallbacks.push('.' + c);
        }
      });
    }
    return { primary, fallbacks };
  }

  function getUniqueSelector(el) {
    const target = el;
    if (el.id) return '#' + el.id;
    if (el === document.body) return 'body';
    
    let path = [];
    while (el && el.nodeType === Node.ELEMENT_NODE) {
      if (el.id) {
        path.unshift('#' + el.id);
        break;
      }
      if (el.nodeName.toLowerCase() === 'body') {
        path.unshift('body');
        break;
      }
      let selector = el.nodeName.toLowerCase();
      // Only keep classes for the target element itself to keep the selector short
      if (el === target && el.className) {
        const classes = Array.from(el.classList)
          .filter(c => !c.startsWith('pixelonwp-') && c !== 'pp-highlighted-el')
          .join('.');
        if (classes) {
          selector += '.' + classes;
        }
      }
      path.unshift(selector);
      el = el.parentNode;
    }
    return path.join(' > ');
  }

  const renderUI = () => {
    // Preserve minimized rotation state if re-rendered
    const isMin = wrapper.classList.contains('is-minimized');
    
    wrapper.innerHTML = `
      <div class="panel-header" style="position: relative; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
        <div class="mobile-toggle-handle" style="display: none; width: 40px; height: 4px; background: var(--border-color); border-radius: 2px; position: absolute; top: 6px; left: 50%; transform: translateX(-50%);"></div>
        <strong style="color: var(--primary); display: flex; align-items: center; gap: 6px; padding-top: 4px;">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>
          Tracker Pro Panel
        </strong>
        <div style="display: flex; align-items: center; gap: 8px;">
          <button id="btn-collapse-toggle" class="btn-collapse-toggle" style="background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 4px; display: none; transform: ${isMin ? 'rotate(180deg)' : 'rotate(0deg)'};">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <button id="btn-exit-visual" style="background: transparent; border: none; color: var(--danger); font-size: 18px; cursor: pointer; padding: 4px; line-height: 1;">&times;</button>
        </div>
      </div>
      <div class="panel-tabs">
        <button class="panel-tab ${activeTab === 'manual' ? 'active' : ''}" data-tab="manual">Manual Setup</button>
        <button class="panel-tab ${activeTab === 'presets' ? 'active' : ''}" data-tab="presets">Playbooks</button>
        <button class="panel-tab ${activeTab === 'livefire' ? 'active' : ''}" data-tab="livefire">Test Console</button>
      </div>
      <div class="panel-body" id="panel-body-content"></div>
    `;

    // Click handler for header toggle (collapse/expand on mobile)
    const headerEl = wrapper.querySelector('.panel-header');
    headerEl.addEventListener('click', (e) => {
      if (e.target.closest('#btn-exit-visual')) return;
      if (window.innerWidth <= 767) {
        wrapper.classList.toggle('is-minimized');
        const collapseBtn = wrapper.querySelector('#btn-collapse-toggle');
        if (collapseBtn) {
          const isCurrentlyMin = wrapper.classList.contains('is-minimized');
          collapseBtn.style.transform = isCurrentlyMin ? 'rotate(180deg)' : 'rotate(0deg)';
        }
      }
    });

    wrapper.querySelector('#btn-exit-visual').addEventListener('click', (e) => {
      e.stopPropagation();
      urlParams.delete('pixelonwp_visual_builder');
      window.location.search = urlParams.toString();
    });

    wrapper.querySelectorAll('.panel-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        activeTab = tab.dataset.tab;
        renderUI();
      });
    });

    const bodyContent = wrapper.querySelector('#panel-body-content');
    if (activeTab === 'manual') renderManualTab(bodyContent);
    else if (activeTab === 'presets') renderPresetsTab(bodyContent);
    else if (activeTab === 'livefire') renderLiveFireTab(bodyContent);
  };

  // --- MANUAL SETUP TAB ---
  const renderManualTab = (container) => {
    const isFbConnected = !!config.platforms.fb_pixel_id;
    const isTtConnected = !!config.platforms.tt_pixel_id;
    const isGoogleConnected = !!config.platforms.google_ads_id;
    const isGa4Connected = !!config.platforms.ga4_measurement_id;

    container.innerHTML = `
      <button id="btn-visual-select" class="btn" style="width: 100%;">
        ${selectionMode ? '[Hover and Click Element]' : '+ Visual Target Selector'}
      </button>
      
      <div class="form-group">
        <label>Rule Name</label>
        <input type="text" id="vs-name" class="input-field" placeholder="e.g. Purchase Button Click">
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Trigger</label>
          <select id="vs-trigger" class="input-field" style="background: #1e293b;">
            <option value="click">Click</option>
            <option value="submit">Submit</option>
            <option value="visibility">Visibility</option>
          </select>
        </div>
        <div class="form-group">
          <label>Event Name</label>
          <input type="text" id="vs-event-name" class="input-field" placeholder="AddToCart">
        </div>
      </div>
      
      <div class="form-group">
        <label>Element CSS Selector</label>
        <input type="text" id="vs-selector" class="input-field">
      </div>
      
      <div class="form-group">
        <label>Destinations</label>
        <div style="display: flex; flex-direction: column; gap: 8px; padding: 10px; background: var(--bg-card); border-radius: 6px; border: 1px solid var(--border-color);">
          <label style="display: flex; align-items: center; justify-content: space-between; font-weight: normal; cursor: pointer;">
            <span><input type="checkbox" class="vs-plat" value="facebook" checked> Meta Facebook</span>
            <span class="badge ${isFbConnected ? 'badge-success' : 'badge-neutral'}">${isFbConnected ? 'Connected' : 'Not Configured'}</span>
          </label>
          <label style="display: flex; align-items: center; justify-content: space-between; font-weight: normal; cursor: pointer;">
            <span><input type="checkbox" class="vs-plat" value="tiktok" checked> TikTok</span>
            <span class="badge ${isTtConnected ? 'badge-success' : 'badge-neutral'}">${isTtConnected ? 'Connected' : 'Not Configured'}</span>
          </label>
          <label style="display: flex; align-items: center; justify-content: space-between; font-weight: normal; cursor: pointer;">
            <span><input type="checkbox" id="plat-vs-google-ads" class="vs-plat" value="google_ads"> Google Ads</span>
            <span class="badge ${isGoogleConnected ? 'badge-success' : 'badge-neutral'}">${isGoogleConnected ? 'Connected' : 'Not Configured'}</span>
          </label>
          <label style="display: flex; align-items: center; justify-content: space-between; font-weight: normal; cursor: pointer;">
            <span><input type="checkbox" class="vs-plat" value="ga4" checked> GA4</span>
            <span class="badge ${isGa4Connected ? 'badge-success' : 'badge-neutral'}">${isGa4Connected ? 'Connected' : 'Not Configured'}</span>
          </label>
        </div>
      </div>

      <div id="vs-google-ads-label-panel" style="display: none;" class="form-group">
        <label>Google Ads Conversion Label</label>
        <input type="text" id="vs-google-ads-label" class="input-field" placeholder="e.g. AbC1DeFgHiJ...">
      </div>

      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <label>Parameters</label>
          <button id="vs-btn-add-param" style="background: transparent; border: 1px solid var(--border-color); color: var(--primary); border-radius: 4px; padding: 2px 8px; font-size: 11px; cursor: pointer;">+ Add</button>
        </div>
        <div id="vs-params-container" style="display: flex; flex-direction: column; gap: 8px;"></div>
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
        <button id="vs-test-btn" class="btn btn-secondary" style="flex: 1; border-color: var(--primary); color: var(--primary);">Live Test</button>
        <button id="vs-save-btn" class="btn" style="background: var(--success); flex: 1;">Save Rule</button>
      </div>
    `;

    const selectBtn = container.querySelector('#btn-visual-select');
    selectBtn.addEventListener('click', () => {
      if (selectionMode) {
        selectionMode = false;
        selectBtn.innerHTML = '+ Visual Target Selector';
        selectBtn.style.background = 'var(--primary)';
        wrapper.classList.remove('is-minimized');
      } else {
        selectionMode = true;
        parameterSelectionMode = false;
        selectBtn.innerHTML = '[Hover and Click Element]';
        selectBtn.style.background = 'var(--warning)';
        if (window.innerWidth <= 767) {
          wrapper.classList.add('is-minimized');
        }
      }
    });

    const paramsContainer = container.querySelector('#vs-params-container');
    const addParamRow = (key = '', valType = 'innerText', valSource = '') => {
      const row = document.createElement('div');
      row.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr auto; gap: 6px; align-items: center; margin-bottom: 4px;';
      row.className = 'vs-param-row';
      
      row.innerHTML = `
        <input type="text" class="param-key input-field" style="padding: 6px;" value="${key}" placeholder="Key">
        <select class="param-val-type input-field" style="padding: 6px; background: var(--bg-card);">
          <option value="innerText" ${valType === 'innerText' ? 'selected' : ''}>Inner Text</option>
          <option value="attribute" ${valType === 'attribute' ? 'selected' : ''}>Attribute</option>
          <option value="static" ${valType === 'static' ? 'selected' : ''}>Static</option>
          <option value="input" ${valType === 'input' ? 'selected' : ''}>Input Field</option>
        </select>
        <button class="btn-remove-vs-param" style="border: none; background: transparent; color: var(--danger); font-size: 16px; cursor: pointer;">&times;</button>
        <div style="grid-column: span 3; display: flex; gap: 6px; margin-top: 2px;">
          <input type="text" class="param-val-src input-field" style="flex: 1; padding: 6px;" value="${valSource}" placeholder="Selector/Value">
          <button class="btn-select-dom-param" style="background: #334155; border: none; border-radius: 4px; color: white; padding: 4px 8px; font-size: 11px; cursor: pointer; display: ${valType === 'static' ? 'none' : 'block'};">🎯 Point</button>
        </div>
      `;

      const select = row.querySelector('.param-val-type');
      const pointBtn = row.querySelector('.btn-select-dom-param');
      const srcInput = row.querySelector('.param-val-src');

      select.addEventListener('change', () => {
        if (select.value === 'static') {
          pointBtn.style.display = 'none';
          srcInput.placeholder = 'e.g. USD';
        } else {
          pointBtn.style.display = 'block';
          srcInput.placeholder = select.value === 'attribute' ? 'e.g. data-price' : 'Selector of target element';
        }
      });

      pointBtn.addEventListener('click', (e) => {
        e.preventDefault();
        parameterSelectionMode = true;
        parameterRowTarget = row;
        document.body.style.cursor = 'crosshair';
      });

      row.querySelector('.btn-remove-vs-param').addEventListener('click', () => row.remove());
      paramsContainer.appendChild(row);
    };

    addParamRow();

    container.querySelector('#vs-btn-add-param').addEventListener('click', () => addParamRow());

    container.querySelector('#plat-vs-google-ads').addEventListener('change', (e) => {
      container.querySelector('#vs-google-ads-label-panel').style.display = e.target.checked ? 'block' : 'none';
    });

    container.querySelector('#vs-test-btn').addEventListener('click', () => {
      const eventName = container.querySelector('#vs-event-name').value;
      const selector = container.querySelector('#vs-selector').value;
      if (!eventName || !selector) {
        showNotification('Event Name and Target Selector are required for live testing.', 'error');
        return;
      }
      const paramsData = {};
      container.querySelectorAll('.vs-param-row').forEach(row => {
        const key = row.querySelector('.param-key').value;
        const valType = row.querySelector('.param-val-type').value;
        const valSource = row.querySelector('.param-val-src').value;
        if (key) {
           let resolvedVal = valSource;
           if (valType === 'innerText' || valType === 'attribute' || valType === 'input_val') {
              try {
                const el = document.querySelector(selector);
                if (el) {
                   if (valType === 'innerText') resolvedVal = el.innerText;
                   else if (valType === 'input_val') resolvedVal = el.value;
                   else if (valType === 'attribute') resolvedVal = el.getAttribute(valSource) || '';
                }
              } catch(e) {}
           }
           paramsData[key] = resolvedVal;
        }
      });
      if (window.PixelOnWP && window.PixelOnWP.track) {
         window.PixelOnWP.track(eventName, paramsData);
         showNotification(`Live Test: Fired '${eventName}' successfully! Check the Test Console tab to view the payload.`, 'success');
      } else {
         showNotification('Tracking engine not fully loaded.', 'warning');
      }
    });

    container.querySelector('#vs-save-btn').addEventListener('click', async () => {
      const name = container.querySelector('#vs-name').value;
      const trigger = container.querySelector('#vs-trigger').value;
      const eventName = container.querySelector('#vs-event-name').value;
      const selector = container.querySelector('#vs-selector').value;
      const googleAdsLabel = container.querySelector('#vs-google-ads-label').value;

      const targetPlats = [];
      container.querySelectorAll('.vs-plat:checked').forEach(chk => targetPlats.push(chk.value));

      const paramsData = [];
      container.querySelectorAll('.vs-param-row').forEach(row => {
        const key = row.querySelector('.param-key').value;
        const valType = row.querySelector('.param-val-type').value;
        const valSource = row.querySelector('.param-val-src').value;
        if (key) {
          paramsData.push({ key, value_type: valType, value_source: valSource });
        }
      });

      if (!name || !eventName || !selector) {
        showNotification('Please fill out all required fields.', 'error');
        return;
      }

      const saveBtn = container.querySelector('#vs-save-btn');
      saveBtn.innerHTML = 'Saving...';
      saveBtn.disabled = true;

      const formData = new FormData();
      formData.append('action', 'PixelOnWP_save_tracker_rule');
      formData.append('nonce', config.nonce || '');
      formData.append('name', name);
      formData.append('trigger_type', trigger);
      formData.append('selector', selector);
      formData.append('event_name', eventName);
      formData.append('url_match_type', 'all');
      targetPlats.forEach(p => formData.append('platforms[]', p));
      formData.append('google_ads_label', googleAdsLabel);
      formData.append('parameters', JSON.stringify(paramsData));

      try {
        const res = await fetch(window.location.origin + '/wp-admin/admin-ajax.php', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
          rules = json.data.rules;
          showNotification('Manual rule saved successfully!', 'success');
          renderUI();
        } else {
          showNotification('Error: ' + json.data.message, 'error');
        }
      } catch (e) {
        showNotification('Network error.', 'error');
      }
      saveBtn.innerHTML = 'Save Rule';
      saveBtn.disabled = false;
    });
  };

  // --- PLAYBOOK PRESETS TAB ---
  const renderPresetsTab = (container) => {
    container.innerHTML = `
      <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 10px;">
        Detected Context: <strong style="color: var(--primary);">${activeContext.business_model}</strong> (${activeContext.theme_builder})
      </div>
      <div style="display: flex; flex-direction: column; gap: 8px;" id="presets-accordion-list">
        <!-- Presets listed here -->
      </div>
    `;

    const accordion = container.querySelector('#presets-accordion-list');
    
    // Group categories
    const categories = Object.keys(playbooks);
    
    categories.forEach(cat => {
      // Prioritize detected environment presets
      const isRelevant = cat === activeContext.business_model || cat === 'Theme-Specific';
      
      const catDiv = document.createElement('div');
      catDiv.style.cssText = 'border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;';
      catDiv.innerHTML = `
        <div style="background: ${isRelevant ? 'rgba(56, 189, 248, 0.1)' : '#1e293b'}; padding: 10px; font-weight: bold; cursor: pointer; display: flex; justify-content: space-between;" class="cat-header">
          <span>${cat} Presets</span>
          <span>${isRelevant ? '★' : '▾'}</span>
        </div>
        <div class="cat-body" style="display: ${isRelevant ? 'block' : 'none'}; padding: 10px; background: #0f172a; border-top: 1px solid var(--border-color);">
          <!-- presets -->
        </div>
      `;

      catDiv.querySelector('.cat-header').addEventListener('click', () => {
        const body = catDiv.querySelector('.cat-body');
        body.style.display = body.style.display === 'none' ? 'block' : 'none';
      });

      const catBody = catDiv.querySelector('.cat-body');
      const presets = playbooks[cat] || [];
      if (presets.length === 0) {
        catBody.innerHTML = '<div style="color: var(--text-muted); font-style: italic;">No presets in this category.</div>';
      } else {
        presets.forEach(p => {
          const pEl = document.createElement('div');
          pEl.style.cssText = 'padding: 8px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; cursor: pointer;';
          pEl.innerHTML = `
            <div>
              <div style="font-weight: 600;">${p.name}</div>
              <div style="font-size: 11px; color: var(--text-muted);">Selector: ${p.selector}</div>
            </div>
            <button class="btn btn-secondary apply-preset-btn" style="padding: 4px 8px; font-size: 11px;">Apply</button>
          `;

          pEl.querySelector('.apply-preset-btn').addEventListener('click', async (e) => {
            e.stopPropagation();
            if (!confirm(`Apply the "${p.name}" preset rules?`)) return;

            const formData = new FormData();
            formData.append('action', 'PixelOnWP_save_tracker_rule');
            formData.append('nonce', config.nonce || '');
            formData.append('name', p.name);
            formData.append('trigger_type', p.trigger_type);
            formData.append('selector', p.selector);
            formData.append('event_name', p.event_name);
            formData.append('url_match_type', 'all');
            p.platforms.forEach(plat => formData.append('platforms[]', plat));
            formData.append('parameters', JSON.stringify(p.parameters));

            try {
              const res = await fetch(window.location.origin + '/wp-admin/admin-ajax.php', { method: 'POST', body: formData });
              const json = await res.json();
              if (json.success) {
                rules = json.data.rules;
                showNotification('Playbook preset rule applied successfully!', 'success');
                activeTab = 'manual';
                renderUI();
              }
            } catch(err) { console.error(err); }
          });

          catBody.appendChild(pEl);
        });
      }

      accordion.appendChild(catDiv);
    });
  };

  // --- TEST LIVE FIRE TAB ---
  const renderLiveFireTab = (container) => {
    container.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <strong>Console Output</strong>
        <button id="btn-clear-console" style="background: transparent; border: none; color: var(--danger); cursor: pointer; font-size: 11px;">Clear</button>
      </div>
      <div id="visual-live-fire-box" style="background: #0b0f19; border: 1px solid var(--border-color); border-radius: 6px; padding: 12px; font-family: monospace; font-size: 11px; color: #38bdf8; min-height: 220px; max-height: 380px; overflow-y: auto; box-sizing: border-box;">
        <div style="color: var(--text-muted);">[Testing Mode: Trigger custom events to watch execution outputs here...]</div>
      </div>
    `;

    const consoleBox = container.querySelector('#visual-live-fire-box');

    const appendConsoleLog = (log) => {
      const logDiv = document.createElement('div');
      logDiv.style.borderBottom = '1px solid #1e293b';
      logDiv.style.padding = '8px 0';
      
      const plats = log.platforms && log.platforms.length > 0
        ? log.platforms.map(p => `<span style="background: #1e293b; color: #a5f3fc; padding: 1px 4px; border-radius: 3px; font-size: 9px; margin-right: 3px;">${p}</span>`).join('')
        : '<span style="color: var(--danger);">Dropped</span>';

      logDiv.innerHTML = `
        <div style="font-weight: bold; color: #34d399;">⚡ ${log.event_name} (${log.trigger_type.toUpperCase()})</div>
        <div style="color: var(--text-muted);">Selector: ${log.selector || 'N/A'}</div>
        <div style="color: #cbd5e1;">Params: ${JSON.stringify(log.params)}</div>
        <div style="margin-top: 4px;">Fired: ${plats}</div>
      `;

      if (consoleBox.innerHTML.includes('Testing Mode')) {
        consoleBox.innerHTML = '';
      }

      consoleBox.appendChild(logDiv);
      consoleBox.scrollTop = consoleBox.scrollHeight;
    };

    if (liveLogs.length > 0) {
      consoleBox.innerHTML = '';
      liveLogs.forEach(appendConsoleLog);
    }

    container.querySelector('#btn-clear-console').addEventListener('click', () => {
      liveLogs.length = 0;
      consoleBox.innerHTML = '<div style="color: var(--text-muted);">[Console cleared. Trigger custom events to watch execution outputs here...]</div>';
    });

    const handleLog = (e) => {
      const log = e.detail;
      liveLogs.push(log);
      appendConsoleLog(log);
    };

    window.addEventListener('plugin_live_event_tracked', handleLog);

    const observer = new MutationObserver(() => {
      if (!document.body.contains(consoleBox)) {
        window.removeEventListener('plugin_live_event_tracked', handleLog);
        observer.disconnect();
      }
    });
    observer.observe(container, { childList: true });
  };

  // Prevent default navigations during visual selection mode and any link clicks
  ['mousedown', 'mouseup', 'pointerdown', 'pointerup', 'dblclick'].forEach(evt => {
    document.addEventListener(evt, (e) => {
      const target = e.composedPath ? e.composedPath()[0] : e.target;
      if (host.contains(target) || target === host) return;

      if (selectionMode || parameterSelectionMode) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);
  });

  // Click handler wrapper
  document.addEventListener('mouseover', (e) => {
    if (!selectionMode && !parameterSelectionMode) return;
    
    const target = e.composedPath ? e.composedPath()[0] : e.target;
    if (host.contains(target) || target === host) return;

    if (hoveredEl) {
      hoveredEl.style.outline = '';
      hoveredEl.classList.remove('pp-highlighted-el');
    }

    hoveredEl = target;
    hoveredEl.style.outline = '2px solid #2563eb';
    hoveredEl.classList.add('pp-highlighted-el');
  }, true);

  document.addEventListener('mouseout', (e) => {
    if (hoveredEl) {
      hoveredEl.style.outline = '';
      hoveredEl.classList.remove('pp-highlighted-el');
      hoveredEl = null;
    }
  }, true);

  document.addEventListener('click', (e) => {
    const target = e.composedPath ? e.composedPath()[0] : e.target;
    if (host.contains(target) || target === host) return;

    if (!selectionMode && !parameterSelectionMode) {
      const link = target.closest ? target.closest('a') : (target.tagName === 'A' ? target : null);
      if (link && link.href) {
        e.preventDefault();
        try {
          const navUrl = new URL(link.href);
          if (navUrl.origin === window.location.origin) {
            navUrl.searchParams.set('pixelonwp_visual_builder', '1');
            window.location.href = navUrl.toString();
          } else {
            window.location.href = link.href;
          }
        } catch(err) {
          window.location.href = link.href;
        }
      }
      return;
    }
    
    e.preventDefault();
    e.stopPropagation();

    const clickedEl = target;
    if (hoveredEl) {
      hoveredEl.style.outline = '';
      hoveredEl.classList.remove('pp-highlighted-el');
      hoveredEl = null;
    }

    if (parameterSelectionMode && parameterRowTarget) {
      const details = getSelectorDetails(clickedEl);
      parameterRowTarget.querySelector('.param-val-src').value = details.primary;
      
      parameterSelectionMode = false;
      parameterRowTarget = null;
      document.body.style.cursor = 'default';
      return;
    }

    if (selectionMode) {
      selectionMode = false;
      wrapper.classList.remove('is-minimized');
      const details = getSelectorDetails(clickedEl);
      
      activeTab = 'manual';
      renderUI();
      
      const form = shadow.querySelector('#vs-selector');
      if (form) {
        form.value = details.primary;
      }
      
      const selectBtn = shadow.querySelector('#btn-visual-select');
      if (selectBtn) {
        selectBtn.innerHTML = '+ Visual Target Selector';
        selectBtn.style.background = 'var(--primary)';
      }
    }
  }, true);

    renderUI();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startVisualBuilder);
  } else {
    startVisualBuilder();
  }
})();
