export function renderUniversalTracker(container, state) {
  let activeTab = 'rules';
  let rules = window.pixelonwp_admin_vars?.config?.tracker_rules || [];
  
  // Inherit global integration configs from main plugin configuration
  const globalConfig = window.pixelonwp_admin_vars?.config || {};
  const isFbConnected = !!globalConfig.meta?.pixel_id;
  const isTtConnected = !!globalConfig.tiktok?.pixel_id;
  const isGoogleConnected = !!globalConfig.google?.conversion_id;
  const isGa4Connected = !!globalConfig.ga4_id;

  const header = document.createElement('div');
  header.className = 'pp-view-header';
  header.style.display = 'flex';
  header.style.justifyContent = 'space-between';
  header.style.alignItems = 'center';
  header.innerHTML = `
    <div>
      <h2>Universal Event & Parameter Tracker</h2>
      <p>Configure advanced point-and-click or manual tracking rules mapped globally across active channels.</p>
    </div>
    <button id="btn-launch-visual-tool" class="pp-btn" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; border: none; font-weight: 600; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: middle;"><polygon points="5 3 19 12 5 21 5 3"/></svg>
      Launch Visual Setup Tool
    </button>
  `;
  container.appendChild(header);

  // Tab Navigation Bar
  const navBar = document.createElement('div');
  navBar.className = 'pp-tabs-container';
  navBar.style.marginBottom = '24px';
  navBar.innerHTML = `
    <button class="pp-tab active" data-tab="rules" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-primary); border-bottom: 2px solid var(--pp-primary); font-weight: 600; cursor: pointer;">Event Rules</button>
    <button class="pp-tab" data-tab="debugger" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-text-muted); font-weight: 500; cursor: pointer;">Live Event Debugger</button>
  `;
  container.appendChild(navBar);

  // Main Content Area
  const contentArea = document.createElement('div');
  contentArea.className = 'pp-tracker-content';
  container.appendChild(contentArea);

  // Event list array for Live Debugger
  const debugLogs = [];

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
    if (activeTab === 'rules') {
      renderRulesTab();
    } else if (activeTab === 'debugger') {
      renderDebuggerTab();
    }
  };

  // Launch Visual setup handler
  header.querySelector('#btn-launch-visual-tool').addEventListener('click', () => {
    // Create the modal overlay container
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'pixelonwp-modal-overlay';
    
    // Style the modal overlay
    modalOverlay.style.position = 'fixed';
    modalOverlay.style.top = '0';
    modalOverlay.style.left = '0';
    modalOverlay.style.width = '100%';
    modalOverlay.style.height = '100%';
    modalOverlay.style.backgroundColor = 'rgba(15, 23, 42, 0.6)';
    modalOverlay.style.zIndex = '999999';
    modalOverlay.style.display = 'flex';
    modalOverlay.style.alignItems = 'center';
    modalOverlay.style.justifyContent = 'center';
    modalOverlay.style.backdropFilter = 'blur(4px)';
    modalOverlay.style.opacity = '0';
    modalOverlay.style.transition = 'opacity 0.2s ease-out';
    
    // Modal card
    modalOverlay.innerHTML = `
      <div class="pixelonwp-modal-card" style="background: white; border-radius: 12px; max-width: 500px; width: 90%; padding: 28px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); transform: translateY(20px); transition: transform 0.2s ease-out;">
        <h3 style="margin: 0 0 8px 0; font-size: 18px; color: #111827; font-weight: 600;">Launch Visual Event Setup Tool</h3>
        <p style="margin: 0 0 20px 0; font-size: 13px; color: #6b7280; line-height: 1.5;">Enter the page URL where you want to visually setup events:</p>
        <div style="margin-bottom: 24px;">
          <input type="text" id="visual-tool-url-input" class="pp-input" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none; transition: border-color 0.2s;" value="${window.location.origin}">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
          <button id="btn-visual-tool-cancel" class="pp-btn pp-btn-outline" style="min-height: 36px; padding: 6px 16px; font-size: 13px;">Cancel</button>
          <button id="btn-visual-tool-launch" class="pp-btn" style="min-height: 36px; padding: 6px 16px; font-size: 13px; background: #2563eb; color: white; border: none;">Launch</button>
        </div>
      </div>
    `;

    document.body.appendChild(modalOverlay);

    // Trigger animations
    requestAnimationFrame(() => {
      modalOverlay.style.opacity = '1';
      modalOverlay.querySelector('.pixelonwp-modal-card').style.transform = 'translateY(0)';
    });

    const closeVisualModal = () => {
      modalOverlay.style.opacity = '0';
      modalOverlay.querySelector('.pixelonwp-modal-card').style.transform = 'translateY(20px)';
      setTimeout(() => {
        modalOverlay.remove();
      }, 200);
    };

    // Input focus styling
    const inputField = modalOverlay.querySelector('#visual-tool-url-input');
    inputField.focus();
    inputField.addEventListener('focus', () => {
      inputField.style.borderColor = '#2563eb';
      inputField.style.boxShadow = '0 0 0 3px rgba(37, 99, 235, 0.1)';
    });
    inputField.addEventListener('blur', () => {
      inputField.style.borderColor = '#d1d5db';
      inputField.style.boxShadow = 'none';
    });

    // Close actions
    modalOverlay.querySelector('#btn-visual-tool-cancel').addEventListener('click', closeVisualModal);
    
    // Close on click outside
    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) {
        closeVisualModal();
      }
    });

    // Close on ESC key
    const escHandler = (e) => {
      if (e.key === 'Escape') {
        closeVisualModal();
        window.removeEventListener('keydown', escHandler);
      }
    };
    window.addEventListener('keydown', escHandler);

    // Launch action
    modalOverlay.querySelector('#btn-visual-tool-launch').addEventListener('click', () => {
      let targetUrl = inputField.value.trim();
      if (!targetUrl.startsWith('http://') && !targetUrl.startsWith('https://')) {
        alert('Invalid URL entered. The URL must start with http:// or https://');
        return;
      }

      try {
        const sanitizedUrl = encodeURI(targetUrl);
        const urlObj = new URL(sanitizedUrl);
        
        // Preserve any existing parameters and set visual builder parameter
        urlObj.searchParams.set('pixelonwp_visual_builder', '1');
        
        window.open(urlObj.toString(), '_blank');
        closeVisualModal();
        window.removeEventListener('keydown', escHandler);
      } catch (err) {
        alert('Invalid URL format. Please try again.');
      }
    });
  });

  // --- TAB 1: EVENT RULES ---
  const renderRulesTab = () => {
    const card = document.createElement('div');
    card.className = 'pp-card';
    card.style.padding = '24px';
    
    let tableRows = '';
    if (rules.length === 0) {
      tableRows = `<tr><td colspan="6" style="padding: 32px; text-align: center; color: var(--pp-text-muted);">No rules defined yet. Click "Add New Rule" or use the Visual Setup Tool above.</td></tr>`;
    } else {
      rules.forEach(rule => {
        const triggers = { click: 'Click', submit: 'Submit', visibility: 'Visibility', page_view: 'Page View' };
        const platformsStr = rule.platforms && rule.platforms.length > 0
          ? rule.platforms.map(p => `<span class="pp-badge pp-badge-neutral" style="margin-right: 4px; font-size: 11px;">${p}</span>`).join('')
          : '<span style="color: var(--pp-text-muted);">None</span>';
          
        tableRows += `
          <tr style="border-bottom: 1px solid var(--pp-border-light);">
            <td style="padding: 14px 16px; font-weight: 600; color: var(--pp-text-main);">${rule.name}</td>
            <td style="padding: 14px 16px;"><span class="pp-badge pp-badge-success">${triggers[rule.trigger_type] || rule.trigger_type}</span></td>
            <td style="padding: 14px 16px; font-family: monospace; font-size: 12px; color: var(--pp-primary); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${rule.selector || ''}">${rule.selector || '<span style="color: var(--pp-text-muted); font-style: italic;">N/A (Page View)</span>'}</td>
            <td style="padding: 14px 16px; font-weight: 500;">${rule.event_name}</td>
            <td style="padding: 14px 16px;">${platformsStr}</td>
            <td style="padding: 14px 16px; text-align: right;">
              <div style="display: flex; gap: 10px; justify-content: flex-end; align-items: center;">
                <label class="pp-switch" style="transform: scale(0.85);">
                  <input type="checkbox" class="rule-toggle" data-id="${rule.id}" ${rule.active ? 'checked' : ''}>
                  <span class="pp-slider"></span>
                </label>
                <button class="pp-btn-outline edit-rule-btn" data-id="${rule.id}" style="padding: 4px 10px; min-height: 28px; font-size: 12px;">Edit</button>
                <button class="pp-btn delete-rule-btn" data-id="${rule.id}" style="padding: 4px 10px; min-height: 28px; font-size: 12px; background: var(--pp-danger); border-color: var(--pp-danger);">Delete</button>
              </div>
            </td>
          </tr>
        `;
      });
    }

    card.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; font-size: 16px; color: var(--pp-text-heading);">Rules Catalog</h3>
        <button id="btn-add-rule" class="pp-btn pp-btn-primary">Add New Rule</button>
      </div>
      <div class="pp-table-container">
        <table class="pp-table" style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="border-bottom: 1px solid var(--pp-border); text-align: left; background: rgba(0,0,0,0.02);">
              <th style="padding: 12px 16px; color: var(--pp-text-muted); font-size: 12px;">Rule Name</th>
              <th style="padding: 12px 16px; color: var(--pp-text-muted); font-size: 12px;">Trigger</th>
              <th style="padding: 12px 16px; color: var(--pp-text-muted); font-size: 12px;">Selector</th>
              <th style="padding: 12px 16px; color: var(--pp-text-muted); font-size: 12px;">Event Name</th>
              <th style="padding: 12px 16px; color: var(--pp-text-muted); font-size: 12px;">Destinations</th>
              <th style="padding: 12px 16px; color: var(--pp-text-muted); font-size: 12px; text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            ${tableRows}
          </tbody>
        </table>
      </div>
    `;

    contentArea.appendChild(card);

    // Event listeners
    card.querySelector('#btn-add-rule').addEventListener('click', () => openRuleModal());
    card.querySelectorAll('.edit-rule-btn').forEach(btn => {
      btn.addEventListener('click', () => openRuleModal(btn.dataset.id));
    });
    card.querySelectorAll('.delete-rule-btn').forEach(btn => {
      btn.addEventListener('click', () => deleteRule(btn.dataset.id));
    });
    card.querySelectorAll('.rule-toggle').forEach(chk => {
      chk.addEventListener('change', (e) => toggleRule(chk.dataset.id, e.target.checked));
    });
  };

  // --- TAB 2: LIVE EVENT DEBUGGER ---
  const renderDebuggerTab = () => {
    const card = document.createElement('div');
    card.className = 'pp-card';
    card.style.padding = '24px';
    card.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; font-size: 16px; color: var(--pp-text-heading);">Live Event Log</h3>
        <button id="btn-clear-logs" class="pp-btn pp-btn-outline" style="min-height: 32px; padding: 4px 12px; font-size: 13px;">Clear Logs</button>
      </div>
      <p style="color: var(--pp-text-muted); font-size: 13px; margin-bottom: 20px;">This panel shows custom tracker events fired in this window in real-time. Test your custom selector actions on the frontend page to watch events appear live.</p>
      
      <div id="tracker-logs-container" style="background: #0f172a; color: #38bdf8; font-family: monospace; border-radius: 8px; padding: 16px; min-height: 240px; max-height: 480px; overflow-y: auto; font-size: 12px; border: 1px solid var(--pp-border);">
        <div style="color: #64748b;">[Listening for real-time trigger events...]</div>
      </div>
    `;

    contentArea.appendChild(card);

    const logsBox = card.querySelector('#tracker-logs-container');
    
    const appendLog = (log) => {
      const logDiv = document.createElement('div');
      logDiv.style.borderBottom = '1px solid #1e293b';
      logDiv.style.padding = '8px 0';
      
      const platformsBadges = log.platforms && log.platforms.length > 0
        ? log.platforms.map(p => `<span style="background: #1e293b; color: #a5f3fc; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-right: 4px;">${p}</span>`).join('')
        : '<span style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-right: 4px;">Dropped</span>';
      
      logDiv.innerHTML = `
        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
          <span style="color: #34d399; font-weight: bold;">⚡ ${log.event_name} (${log.trigger_type.toUpperCase()})</span>
          <span style="color: #64748b;">${new Date(log.timestamp).toLocaleTimeString()}</span>
        </div>
        <div style="margin-bottom: 4px; color: #e2e8f0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${log.selector || ''}"><strong>Selector:</strong> ${log.selector || 'N/A'}</div>
        <div style="margin-bottom: 4px; color: #cbd5e1;"><strong>Parameters:</strong> ${JSON.stringify(log.params)}</div>
        <div><strong>Sent To:</strong> ${platformsBadges}</div>
      `;
      
      if (logsBox.innerHTML.includes('Listening for real-time')) {
        logsBox.innerHTML = '';
      }
      
      logsBox.appendChild(logDiv);
      logsBox.scrollTop = logsBox.scrollHeight;
    };

    if (debugLogs.length > 0) {
      logsBox.innerHTML = '';
      debugLogs.forEach(appendLog);
    }

    const handleLiveEvent = (e) => {
      const log = e.detail;
      debugLogs.push(log);
      appendLog(log);
    };

    window.addEventListener('plugin_live_event_tracked', handleLiveEvent);

    const observer = new MutationObserver(() => {
      if (!document.body.contains(logsBox)) {
        window.removeEventListener('plugin_live_event_tracked', handleLiveEvent);
        observer.disconnect();
      }
    });
    observer.observe(contentArea, { childList: true });

    card.querySelector('#btn-clear-logs').addEventListener('click', () => {
      debugLogs.length = 0;
      logsBox.innerHTML = '<div style="color: #64748b;">[Logs cleared. Listening for real-time trigger events...]</div>';
    });
  };

  // --- RULE MODAL (ADD / EDIT) ---
  const openRuleModal = (ruleId = '') => {
    const existingRule = ruleId ? rules.find(r => r.id === ruleId) : null;
    const modalId = 'pp-tracker-rule-modal';
    
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

    const isChecked = (platform) => {
      if (!existingRule) return false;
      return existingRule.platforms && existingRule.platforms.includes(platform);
    };

    modal.innerHTML = `
      <div style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 style="margin: 0 0 16px 0; font-size: 18px; color: var(--pp-text-heading);">${existingRule ? 'Edit Manual Tracker Rule' : 'Create Manual Tracker Rule'}</h3>
        
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <div>
            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">Rule Name</label>
            <input type="text" id="mr-name" class="pp-input" value="${existingRule ? existingRule.name : ''}" placeholder="e.g. Add To Cart Button Click">
          </div>
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
              <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">Trigger Event</label>
              <select id="mr-trigger" class="pp-select">
                <option value="click" ${existingRule && existingRule.trigger_type === 'click' ? 'selected' : ''}>Click Element</option>
                <option value="submit" ${existingRule && existingRule.trigger_type === 'submit' ? 'selected' : ''}>Form Submit</option>
                <option value="visibility" ${existingRule && existingRule.trigger_type === 'visibility' ? 'selected' : ''}>Element Visibility (IntersectionObserver)</option>
                <option value="page_view" ${existingRule && existingRule.trigger_type === 'page_view' ? 'selected' : ''}>Page View</option>
              </select>
            </div>
            <div>
              <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">CSS Selector</label>
              <input type="text" id="mr-selector" class="pp-input" value="${existingRule ? existingRule.selector : ''}" placeholder="e.g. .buy-now, #submit-btn">
            </div>
          </div>
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
              <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">URL Condition</label>
              <select id="mr-url-type" class="pp-select">
                <option value="all" ${existingRule && existingRule.url_match_type === 'all' ? 'selected' : ''}>All Pages</option>
                <option value="specific" ${existingRule && existingRule.url_match_type === 'specific' ? 'selected' : ''}>Specific URLs (Contains/Equals/Regex)</option>
                <option value="exclude" ${existingRule && existingRule.url_match_type === 'exclude' ? 'selected' : ''}>Exclude URLs</option>
              </select>
            </div>
            <div>
              <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">URL Matching Value</label>
              <input type="text" id="mr-url-value" class="pp-input" value="${existingRule ? existingRule.url_match_value : ''}" placeholder="e.g. /cart, ^/checkout/.*">
            </div>
          </div>

          <div>
            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">Event Name</label>
            <input type="text" id="mr-event-name" class="pp-input" value="${existingRule ? existingRule.event_name : ''}" placeholder="e.g. Purchase, Lead, Custom_Event">
          </div>
          
          <div>
            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">Target Destinations</label>
            <div style="display: flex; flex-direction: column; gap: 10px; padding: 12px; background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border-light); border-radius: 6px;">
              <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                  <input type="checkbox" class="mr-plat" value="facebook" ${isChecked('facebook') ? 'checked' : ''}> Meta Facebook
                </label>
                <span class="pp-badge ${isFbConnected ? 'pp-badge-success' : 'pp-badge-neutral'}" style="font-size: 11px;">
                  ${isFbConnected ? 'Connected' : 'Not Configured in Settings'}
                </span>
              </div>
              <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                  <input type="checkbox" class="mr-plat" value="tiktok" ${isChecked('tiktok') ? 'checked' : ''}> TikTok
                </label>
                <span class="pp-badge ${isTtConnected ? 'pp-badge-success' : 'pp-badge-neutral'}" style="font-size: 11px;">
                  ${isTtConnected ? 'Connected' : 'Not Configured in Settings'}
                </span>
              </div>
              <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                  <input type="checkbox" id="plat-google-ads" class="mr-plat" value="google_ads" ${isChecked('google_ads') ? 'checked' : ''}> Google Ads
                </label>
                <span class="pp-badge ${isGoogleConnected ? 'pp-badge-success' : 'pp-badge-neutral'}" style="font-size: 11px;">
                  ${isGoogleConnected ? 'Connected' : 'Not Configured in Settings'}
                </span>
              </div>
              <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                  <input type="checkbox" class="mr-plat" value="ga4" ${isChecked('ga4') ? 'checked' : ''}> Google Analytics 4 (GA4)
                </label>
                <span class="pp-badge ${isGa4Connected ? 'pp-badge-success' : 'pp-badge-neutral'}" style="font-size: 11px;">
                  ${isGa4Connected ? 'Connected' : 'Not Configured in Settings'}
                </span>
              </div>
            </div>
          </div>
          
          <div id="google-ads-label-panel" style="display: ${isChecked('google_ads') ? 'block' : 'none'};">
            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">Google Ads Conversion Label</label>
            <input type="text" id="mr-google-ads-label" class="pp-input" value="${existingRule ? existingRule.google_ads_label : ''}" placeholder="e.g. AbC1DeFgHiJkLmNoPqR">
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
          <button id="btn-save-rule" class="pp-btn pp-btn-primary">Save Rule</button>
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
        <input type="text" class="param-key pp-input" value="${key}" placeholder="Key (e.g. value)">
        <select class="param-val-type pp-select">
          <option value="static" ${valType === 'static' ? 'selected' : ''}>Static Value</option>
          <option value="innerText" ${valType === 'innerText' ? 'selected' : ''}>Element innerText</option>
          <option value="attribute" ${valType === 'attribute' ? 'selected' : ''}>Element Attribute</option>
          <option value="input" ${valType === 'input' ? 'selected' : ''}>Input Field Value</option>
          <option value="query_param" ${valType === 'query_param' ? 'selected' : ''}>URL Query Parameter</option>
          <option value="js_var" ${valType === 'js_var' ? 'selected' : ''}>JS Variable Value</option>
        </select>
        <input type="text" class="param-val-src pp-input" style="display: ${valType === 'innerText' ? 'none' : 'block'};" value="${valSource}" placeholder="${valType === 'static' ? 'e.g. USD' : 'e.g. data-price'}">
        <button class="btn-remove-param" style="border: none; background: transparent; color: var(--pp-danger); font-size: 18px; cursor: pointer; padding: 4px;">&times;</button>
      `;
      
      const select = row.querySelector('.param-val-type');
      const input = row.querySelector('.param-val-src');
      
      select.addEventListener('change', () => {
        if (select.value === 'innerText') {
          input.style.display = 'none';
        } else {
          input.style.display = 'block';
          if (select.value === 'static') input.placeholder = 'e.g. USD';
          else if (select.value === 'attribute') input.placeholder = 'e.g. data-price';
          else if (select.value === 'input') input.placeholder = 'e.g. #input-selector';
          else if (select.value === 'query_param') input.placeholder = 'e.g. utm_campaign';
          else if (select.value === 'js_var') input.placeholder = 'e.g. window.cartTotal';
        }
      });
      
      row.querySelector('.btn-remove-param').addEventListener('click', () => row.remove());
      repeater.appendChild(row);
    };

    if (existingRule && existingRule.parameters && existingRule.parameters.length > 0) {
      existingRule.parameters.forEach(p => addParamRow(p.key, p.value_type, p.value_source));
    } else {
      addParamRow();
    }

    modal.querySelector('#plat-google-ads').addEventListener('change', (e) => {
      modal.querySelector('#google-ads-label-panel').style.display = e.target.checked ? 'block' : 'none';
    });

    modal.querySelector('#btn-add-param').addEventListener('click', () => addParamRow());
    modal.querySelector('#btn-close-modal').addEventListener('click', () => modal.remove());

    modal.querySelector('#btn-save-rule').addEventListener('click', async () => {
      const name = modal.querySelector('#mr-name').value;
      const selector = modal.querySelector('#mr-selector').value;
      const trigger = modal.querySelector('#mr-trigger').value;
      const eventName = modal.querySelector('#mr-event-name').value;
      const urlMatchType = modal.querySelector('#mr-url-type').value;
      const urlMatchValue = modal.querySelector('#mr-url-value').value;
      
      const targetPlats = [];
      modal.querySelectorAll('.mr-plat:checked').forEach(chk => targetPlats.push(chk.value));
      const googleAdsLabel = modal.querySelector('#mr-google-ads-label').value;

      const paramsData = [];
      modal.querySelectorAll('.param-row').forEach(row => {
        const key = row.querySelector('.param-key').value;
        const valType = row.querySelector('.param-val-type').value;
        const valSource = row.querySelector('.param-val-src').value;
        if (key) {
          paramsData.push({ key, value_type: valType, value_source: valSource });
        }
      });

      if (!name || !eventName || (trigger !== 'page_view' && !selector)) {
        alert('Please fill out all required fields.');
        return;
      }

      const saveBtn = modal.querySelector('#btn-save-rule');
      saveBtn.innerHTML = 'Saving...';
      saveBtn.disabled = true;

      const formData = new FormData();
      formData.append('action', 'PixelOnWP_save_tracker_rule');
      formData.append('nonce', window.pixelonwp_admin_vars.nonce);
      if (ruleId) {
        formData.append('rule_id', ruleId);
      }
      formData.append('name', name);
      formData.append('trigger_type', trigger);
      formData.append('selector', selector);
      formData.append('event_name', eventName);
      formData.append('url_match_type', urlMatchType);
      formData.append('url_match_value', urlMatchValue);
      targetPlats.forEach(p => formData.append('platforms[]', p));
      formData.append('google_ads_label', googleAdsLabel);
      formData.append('parameters', JSON.stringify(paramsData));
      if (existingRule) {
        formData.append('active', existingRule.active);
      }

      try {
        const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
          rules = json.data.rules;
          window.pixelonwp_admin_vars.config.tracker_rules = rules;
          modal.remove();
          renderTabContent();
        } else {
          alert('Error: ' + json.data.message);
        }
      } catch (e) {
        alert('Network error while saving rule.');
      }
      saveBtn.innerHTML = 'Save Rule';
      saveBtn.disabled = false;
    });
  };

  // --- ACTIONS: TOGGLE & DELETE ---
  const toggleRule = async (ruleId, activeState) => {
    const formData = new FormData();
    formData.append('action', 'PixelOnWP_toggle_tracker_rule');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('rule_id', ruleId);
    formData.append('active', activeState ? 1 : 0);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const json = await res.json();
      if (json.success) {
        rules = json.data.rules;
        window.pixelonwp_admin_vars.config.tracker_rules = rules;
      }
    } catch (e) {
      console.error('Failed to toggle rule state', e);
    }
  };

  const deleteRule = async (ruleId) => {
    if (!confirm('Are you sure you want to delete this rule?')) return;

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_delete_tracker_rule');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('rule_id', ruleId);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const json = await res.json();
      if (json.success) {
        rules = json.data.rules;
        window.pixelonwp_admin_vars.config.tracker_rules = rules;
        renderTabContent();
      }
    } catch (e) {
      console.error('Failed to delete rule', e);
    }
  };

  renderTabContent();
}
