export function renderFraudPrevention(container, state) {
  const fSettings = Object.assign({
    enable_fraud_check: '0',
    risk_threshold: 70,
    warning_message: 'Your order cannot be processed due to a high rate of returned parcels on this phone number.',
    support_phone: '',
    pathao_token: '',
    steadfast_key: '',
    steadfast_secret: '',
    redx_token: '',
    enable_layer1: '1',
    phone_length: 11,
    block_dummy_phones: '1',
    block_gibberish_names: '1',
    enable_layer2: '1',
    enable_layer3: '1',
    velocity_limit: 3,
    velocity_window: 24,
    enable_layer4: '1',
    blocked_popup_title: 'Order Blocked',
    blocked_popup_message: 'Your order could not be processed. Please contact our support team for assistance.',
    show_wa_button: '1',
    show_call_button: '1'
  }, window.pixelonwp_admin_vars.fraud_settings || {});

  container.innerHTML = '';

  // Header
  const header = document.createElement('div');
  header.className = 'pp-view-header';
  header.innerHTML = `
    <h2>Multi-Layer Fraud Prevention Engine</h2>
    <p>Cascading fail-safe protection with 2 validation layers and courier API sync.</p>
  `;

  // Tabs Navigation
  const tabsNav = document.createElement('div');
  tabsNav.style.cssText = 'display: flex; gap: 6px; margin-bottom: 24px; flex-wrap: wrap;';
  
  const tabs = [
    { id: 'rules', label: '⚙️ Global Protection Rules', active: true },
    { id: 'courier', label: '🚚 Courier API Sync' },
    { id: 'notice', label: '💬 Blocked Notice' },
    { id: 'logs', label: '📊 Fraud Logs' }
  ];

  tabs.forEach(tab => {
    const btn = document.createElement('button');
    btn.className = 'pp-tab-btn';
    btn.dataset.tab = tab.id;
    btn.textContent = tab.label;
    btn.style.cssText = tab.active
      ? 'background: var(--pp-primary); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px;'
      : 'background: transparent; border: 1px solid var(--pp-border); padding: 10px 20px; border-radius: 8px; color: var(--pp-text-muted); cursor: pointer; font-size: 13px;';
    tabsNav.appendChild(btn);
  });

  const contentArea = document.createElement('div');
  contentArea.className = 'pp-card';
  contentArea.style.padding = '24px';

  container.appendChild(header);
  container.appendChild(tabsNav);
  container.appendChild(contentArea);

  renderTab('rules', contentArea, fSettings);

  tabsNav.querySelectorAll('.pp-tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      tabsNav.querySelectorAll('.pp-tab-btn').forEach(b => {
        b.style.background = 'transparent';
        b.style.color = 'var(--pp-text-muted)';
        b.style.border = '1px solid var(--pp-border)';
        b.style.fontWeight = 'normal';
      });
      btn.style.background = 'var(--pp-primary)';
      btn.style.color = '#fff';
      btn.style.border = 'none';
      btn.style.fontWeight = '600';
      renderTab(btn.dataset.tab, contentArea, fSettings);
    });
  });
}

// ─── Tab Renderer ───
function renderTab(tab, container, fSettings) {
  if (tab === 'rules') renderRulesTab(container, fSettings);
  else if (tab === 'courier') renderCourierTab(container, fSettings);
  else if (tab === 'notice') renderNoticeTab(container, fSettings);
  else if (tab === 'logs') renderLogsTab(container);
}

// ═══════════════════════════════════════════════════════════
//  TAB 1: Global Protection Rules
// ═══════════════════════════════════════════════════════════
function renderRulesTab(container, f) {
  container.innerHTML = `
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
      <div>
        <h3 style="color: var(--pp-text-main); font-size: 17px; margin: 0 0 4px 0;">Global Fraud Check</h3>
        <p style="color: var(--pp-text-muted); font-size: 13px; margin: 0;">Master toggle for the entire validation engine.</p>
      </div>
      <label class="pp-switch"><input type="checkbox" class="fraud-cfg-checkbox" data-key="enable_fraud_check" ${f.enable_fraud_check==='1'?'checked':''}><span class="pp-slider"></span></label>
    </div>
    <hr style="border: none; border-top: 1px solid var(--pp-border); margin-bottom: 28px;">

    <h4 style="color: var(--pp-text-main); margin: 0 0 16px 0; font-size: 15px;">Layer 1: Basic Input & Pattern Validation</h4>
    <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border-light); padding: 20px; border-radius: 8px; margin-bottom: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div><strong style="color: var(--pp-text-main);">Enable Layer 1</strong><br><span style="font-size: 12px; color: var(--pp-text-muted);">Phone length, dummy numbers, gibberish names.</span></div>
        <label class="pp-switch"><input type="checkbox" class="fraud-cfg-checkbox" data-key="enable_layer1" ${f.enable_layer1==='1'?'checked':''}><span class="pp-slider"></span></label>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div>
          <label class="pp-label" style="font-size: 13px;">Phone Number Length (Digits)</label>
          <input type="number" class="pp-input fraud-cfg" data-key="phone_length" value="${f.phone_length}" min="7" max="15" style="width: 100px;">
        </div>
        <div style="display: flex; flex-direction: column; gap: 10px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" class="fraud-cfg-checkbox" data-key="block_dummy_phones" ${f.block_dummy_phones==='1'?'checked':''}> 
            <span style="font-size: 13px; color: var(--pp-text-main);">Block Dummy Phone Numbers</span>
          </label>
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" class="fraud-cfg-checkbox" data-key="block_gibberish_names" ${f.block_gibberish_names==='1'?'checked':''}> 
            <span style="font-size: 13px; color: var(--pp-text-main);">Block Gibberish Names/Addresses</span>
          </label>
        </div>
      </div>
    </div>

    <button id="btn-save-fraud" class="pp-btn" style="padding: 10px 32px;">Save Protection Rules</button>
  `;
  bindSave(container, f);
}

// ═══════════════════════════════════════════════════════════
//  TAB 2: Courier API Sync & Fall-back
// ═══════════════════════════════════════════════════════════
function renderCourierTab(container, f) {
  container.innerHTML = `
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
      <div>
        <h3 style="color: var(--pp-text-main); font-size: 17px; margin: 0 0 4px 0;">Layer 2: Courier API Sync</h3>
        <p style="color: var(--pp-text-muted); font-size: 13px; margin: 0;">Connect your Steadfast / Pathao / RedX Merchant API credentials.</p>
      </div>
      <label class="pp-switch"><input type="checkbox" class="fraud-cfg-checkbox" data-key="enable_layer2" ${f.enable_layer2==='1'?'checked':''}><span class="pp-slider"></span></label>
    </div>

    <div style="display: grid; gap: 20px; margin-bottom: 24px;">
      <div>
        <label class="pp-label">High Risk Threshold (%)</label>
        <p style="color: var(--pp-text-muted); font-size: 12px; margin: 0 0 8px 0;">If return ratio ≥ this %, checkout is blocked.</p>
        <input type="number" class="pp-input fraud-cfg" data-key="risk_threshold" value="${f.risk_threshold}" min="1" max="100" style="width: 120px;">
      </div>
    </div>

    <hr style="border: none; border-top: 1px solid var(--pp-border); margin-bottom: 24px;">

    <div style="display: grid; gap: 20px;">
      <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border-light); padding: 20px; border-radius: 8px;">
        <h4 style="margin: 0 0 12px 0; color: var(--pp-text-main); font-size: 14px;">Steadfast API</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div><label class="pp-label" style="font-size: 13px;">API Key</label><input type="text" class="pp-input fraud-cfg" data-key="steadfast_key" value="${f.steadfast_key}" placeholder="Enter Steadfast API Key"></div>
          <div><label class="pp-label" style="font-size: 13px;">Secret Key</label><input type="text" class="pp-input fraud-cfg" data-key="steadfast_secret" value="${f.steadfast_secret}" placeholder="Enter Steadfast Secret Key"></div>
        </div>
      </div>

      <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border-light); padding: 20px; border-radius: 8px;">
        <h4 style="margin: 0 0 12px 0; color: var(--pp-text-main); font-size: 14px;">Pathao Courier API</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
          <div><label class="pp-label" style="font-size: 13px;">Client ID</label><input type="text" class="pp-input fraud-cfg" data-key="pathao_client_id" value="${f.pathao_client_id || ''}" placeholder="Enter Client ID"></div>
          <div><label class="pp-label" style="font-size: 13px;">Client Secret</label><input type="text" class="pp-input fraud-cfg" data-key="pathao_client_secret" value="${f.pathao_client_secret || ''}" placeholder="Enter Client Secret"></div>
          <div><label class="pp-label" style="font-size: 13px;">Username (Email)</label><input type="text" class="pp-input fraud-cfg" data-key="pathao_username" value="${f.pathao_username || ''}" placeholder="test@pathao.com"></div>
          <div><label class="pp-label" style="font-size: 13px;">Password</label><input type="password" class="pp-input fraud-cfg" data-key="pathao_password" value="${f.pathao_password || ''}" placeholder="Enter Password"></div>
        </div>
        <div style="padding: 12px; background: rgba(255,165,0,0.1); border-left: 3px solid orange; margin-bottom: 12px; border-radius: 4px;">
          <p style="margin: 0; font-size: 12px; color: #8a6d3b;"><strong>Notice:</strong> The plugin will automatically generate an Access Token using the credentials above. If auto-generation fails, you can manually generate a token and paste it below as a fallback.</p>
        </div>
        <div><label class="pp-label" style="font-size: 13px;">Manual Access Token (Fallback)</label><input type="text" class="pp-input fraud-cfg" data-key="pathao_token" value="${f.pathao_token || ''}" placeholder="Enter Pathao Access Token" style="width: 100%;"></div>
      </div>

      <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border-light); padding: 20px; border-radius: 8px;">
        <h4 style="margin: 0 0 12px 0; color: var(--pp-text-main); font-size: 14px;">RedX Courier API</h4>
        <div style="padding: 12px; background: rgba(23,162,184,0.1); border-left: 3px solid #17a2b8; margin-bottom: 12px; border-radius: 4px;">
          <p style="margin: 0; font-size: 12px; color: #0c5460;"><strong>Notice:</strong> RedX uses a static JWT token generated from their merchant panel. Paste it here.</p>
        </div>
        <div><label class="pp-label" style="font-size: 13px;">Bearer Token</label><input type="text" class="pp-input fraud-cfg" data-key="redx_token" value="${f.redx_token || ''}" placeholder="Enter RedX Bearer Token" style="width: 100%;"></div>
      </div>
    </div>

    <div style="margin-top: 28px;">
      <button id="btn-save-fraud" class="pp-btn" style="padding: 10px 32px;">Save API Settings</button>
    </div>

    <hr style="border: none; border-top: 1px solid var(--pp-border); margin: 28px 0;">
    <h3 style="color: var(--pp-text-main); font-size: 16px; margin-bottom: 16px;">Manual Customer Lookup</h3>
    <div style="display: flex; gap: 12px; margin-bottom: 16px;">
      <input type="text" id="pp-fraud-phone" class="pp-input" placeholder="Enter mobile number (e.g., 017XXXXXXXX)" style="flex: 1; max-width: 300px;">
      <button id="btn-fraud-lookup" class="pp-btn">Query Couriers</button>
    </div>
    <div id="pp-fraud-lookup-result" style="display: none; padding: 24px; background: rgba(0,0,0,0.02); border-radius: 8px; border: 1px solid var(--pp-border-light);"></div>
  `;
  bindSave(container, f);
  bindLookup(container, f);
}

// ═══════════════════════════════════════════════════════════
//  TAB 3: Blocked Notice & Popup Customizer
// ═══════════════════════════════════════════════════════════
function renderNoticeTab(container, f) {
  container.innerHTML = `
    <h3 style="color: var(--pp-text-main); font-size: 17px; margin: 0 0 4px 0;">Blocked Notice & Popup Customizer</h3>
    <p style="color: var(--pp-text-muted); font-size: 13px; margin: 0 0 28px 0;">Customize the message and buttons shown to blocked customers at checkout.</p>

    <div style="display: grid; gap: 20px;">
      <div>
        <label class="pp-label">Popup Title</label>
        <input type="text" class="pp-input fraud-cfg" data-key="blocked_popup_title" value="${f.blocked_popup_title || 'Order Blocked'}" style="width: 100%; max-width: 400px;">
      </div>
      <div>
        <label class="pp-label">Block Warning Message</label>
        <textarea class="pp-input fraud-cfg" data-key="warning_message" style="min-height: 80px; width: 100%;">${f.warning_message}</textarea>
      </div>
      <div>
        <label class="pp-label">Support Phone Number / WhatsApp</label>
        <p style="color: var(--pp-text-muted); font-size: 12px; margin-bottom: 8px;">This number is used for WhatsApp and Call redirect buttons in the popup.</p>
        <input type="text" class="pp-input fraud-cfg" data-key="support_phone" value="${f.support_phone}" placeholder="01XXXXXXXXX" style="width: 300px;">
      </div>
      <div style="display: flex; gap: 24px;">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
          <input type="checkbox" class="fraud-cfg-checkbox" data-key="show_wa_button" ${f.show_wa_button==='1'?'checked':''}> 
          <span style="font-size: 13px; color: var(--pp-text-main);">Show WhatsApp Button</span>
        </label>
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
          <input type="checkbox" class="fraud-cfg-checkbox" data-key="show_call_button" ${f.show_call_button==='1'?'checked':''}> 
          <span style="font-size: 13px; color: var(--pp-text-main);">Show Call Button</span>
        </label>
      </div>
    </div>

    <div style="margin-top: 28px;">
      <button id="btn-save-fraud" class="pp-btn" style="padding: 10px 32px;">Save Notice Settings</button>
    </div>
  `;
  bindSave(container, f);
}

// ═══════════════════════════════════════════════════════════
//  TAB 4: Fraud & Block Logs
// ═══════════════════════════════════════════════════════════
function renderLogsTab(container) {
  container.innerHTML = `
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <div>
        <h3 style="color: var(--pp-text-main); font-size: 17px; margin: 0 0 4px 0;">Fraud & Block Logs</h3>
        <p style="color: var(--pp-text-muted); font-size: 13px; margin: 0;">Displays blocked checkout attempts with the layer that triggered the block.</p>
      </div>
      <div style="display: flex; gap: 8px;">
        <button id="btn-refresh-logs" class="pp-btn-outline" style="padding: 6px 16px; font-size: 12px;">Refresh</button>
        <button id="btn-clear-logs" class="pp-btn" style="padding: 6px 16px; font-size: 12px; background: var(--pp-danger);">Clear All Logs</button>
      </div>
    </div>
    <div id="pp-fraud-logs-table" style="overflow-x: auto;">
      <p style="color: var(--pp-text-muted);">Loading logs...</p>
    </div>
  `;

  const loadLogs = async () => {
    const tableDiv = container.querySelector('#pp-fraud-logs-table');
    const formData = new FormData();
    formData.append('action', 'PixelOnWP_get_fraud_block_logs');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const result = await res.json();
      if (result.success) {
        const logs = result.data.logs;
        if (!logs || logs.length === 0) {
          tableDiv.innerHTML = '<p style="color: var(--pp-text-muted); font-size: 13px;">No blocked attempts recorded yet.</p>';
          return;
        }
        let html = `<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
          <thead><tr style="background: rgba(0,0,0,0.03); text-align: left;">
            <th style="padding: 10px 12px; border-bottom: 1px solid var(--pp-border); color: var(--pp-text-muted); font-weight: 600;">IP / Phone</th>
            <th style="padding: 10px 12px; border-bottom: 1px solid var(--pp-border); color: var(--pp-text-muted); font-weight: 600;">Reason & Triggered Layer</th>
            <th style="padding: 10px 12px; border-bottom: 1px solid var(--pp-border); color: var(--pp-text-muted); font-weight: 600;">Time</th>
          </tr></thead><tbody>`;
        logs.forEach(log => {
          html += `<tr style="border-bottom: 1px solid var(--pp-border-light);">
            <td style="padding: 10px 12px; color: var(--pp-text-main); font-family: monospace;">${log.ip}</td>
            <td style="padding: 10px 12px; color: var(--pp-text-main);">${log.reason}</td>
            <td style="padding: 10px 12px; color: var(--pp-text-muted); white-space: nowrap;">${log.time_ago}</td>
          </tr>`;
        });
        html += '</tbody></table>';
        tableDiv.innerHTML = html;
      }
    } catch (e) {
      tableDiv.innerHTML = '<p style="color: var(--pp-danger);">Error loading logs.</p>';
    }
  };

  loadLogs();
  container.querySelector('#btn-refresh-logs').addEventListener('click', loadLogs);
  container.querySelector('#btn-clear-logs').addEventListener('click', async () => {
    if (!confirm('Are you sure you want to clear all fraud block logs?')) return;
    const formData = new FormData();
    formData.append('action', 'PixelOnWP_clear_fraud_block_logs');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
    loadLogs();
  });
}

// ═══════════════════════════════════════════════════════════
//  SHARED: Save handler
// ═══════════════════════════════════════════════════════════
function bindSave(container, fSettings) {
  const saveBtn = container.querySelector('#btn-save-fraud');
  if (!saveBtn) return;

  saveBtn.addEventListener('click', async () => {
    saveBtn.disabled = true;
    saveBtn.innerHTML = 'Saving...';

    container.querySelectorAll('.fraud-cfg').forEach(input => {
      fSettings[input.dataset.key] = input.value;
    });
    container.querySelectorAll('.fraud-cfg-checkbox').forEach(input => {
      fSettings[input.dataset.key] = input.checked ? '1' : '0';
    });

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_save_fraud_settings');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('data', JSON.stringify(fSettings));

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const result = await res.json();
      if (result.success) {
        saveBtn.innerHTML = '✓ Saved!';
        saveBtn.style.background = 'var(--pp-success)';
        window.pixelonwp_admin_vars.fraud_settings = fSettings;
      }
    } catch (e) {
      saveBtn.innerHTML = 'Error';
    } finally {
      setTimeout(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Save Settings';
        saveBtn.style.background = 'var(--pp-primary)';
      }, 2000);
    }
  });
}

// ═══════════════════════════════════════════════════════════
//  SHARED: Lookup handler
// ═══════════════════════════════════════════════════════════
function bindLookup(container, fSettings) {
  const lookupBtn = container.querySelector('#btn-fraud-lookup');
  if (!lookupBtn) return;

  lookupBtn.addEventListener('click', async () => {
    const phone = container.querySelector('#pp-fraud-phone').value;
    if (!phone) return alert('Please enter a phone number');

    lookupBtn.disabled = true;
    lookupBtn.innerHTML = 'Searching...';
    const resultBox = container.querySelector('#pp-fraud-lookup-result');
    resultBox.style.display = 'block';
    resultBox.innerHTML = '<p style="color: var(--pp-text-muted);">Fetching data from couriers...</p>';

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_courier_lookup');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('phone', phone);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const result = await res.json();

      if (result.success) {
        const data = result.data;
        const riskColor = data.risk >= fSettings.risk_threshold ? 'var(--pp-danger)' : (data.risk >= 40 ? 'var(--pp-warning)' : 'var(--pp-success)');
        
        let breakdownHTML = '';
        if (data.breakdown) {
          Object.keys(data.breakdown).forEach(courier => {
            const cData = data.breakdown[courier];
            if (cData && cData.total > 0) {
              breakdownHTML += `
                <div style="background: rgba(0,0,0,0.03); padding: 12px; border-radius: 6px; margin-top: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--pp-border-light);">
                  <div style="text-transform: capitalize; font-weight: bold; color: var(--pp-text-main); font-size: 14px;">${courier}</div>
                  <div style="display: flex; gap: 16px; font-size: 13px;">
                    <div>Total: <span style="color: var(--pp-text-main);">${cData.total}</span></div>
                    <div>Success: <span style="color: var(--pp-success);">${cData.success}</span></div>
                    <div>Returned: <span style="color: var(--pp-danger);">${cData.returned}</span></div>
                  </div>
                </div>
              `;
            }
          });
        }

        resultBox.innerHTML = `
          <div style="text-align: center; margin-bottom: 20px;">
            <span style="color: var(--pp-text-muted); font-size: 13px;">Results for:</span>
            <strong style="color: var(--pp-text-main); font-size: 18px; display: block;">${data.phone}</strong>
          </div>
          <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px;">
            <div style="text-align: center;"><div style="font-size: 24px; font-weight: bold; color: var(--pp-text-main);">${data.total}</div><div style="font-size: 11px; color: var(--pp-text-muted); text-transform: uppercase;">Total</div></div>
            <div style="text-align: center;"><div style="font-size: 24px; font-weight: bold; color: var(--pp-success);">${data.success}</div><div style="font-size: 11px; color: var(--pp-text-muted); text-transform: uppercase;">Success</div></div>
            <div style="text-align: center;"><div style="font-size: 24px; font-weight: bold; color: var(--pp-danger);">${data.returned}</div><div style="font-size: 11px; color: var(--pp-text-muted); text-transform: uppercase;">Returned</div></div>
            <div style="text-align: center; border-left: 1px solid var(--pp-border);"><div style="font-size: 24px; font-weight: bold; color: ${riskColor};">${data.risk}%</div><div style="font-size: 11px; color: var(--pp-text-muted); text-transform: uppercase;">Risk</div></div>
          </div>
          ${breakdownHTML}
        `;
      } else {
        resultBox.innerHTML = `<p style="color: var(--pp-danger);">${result.data.message || 'Error fetching data.'}</p>`;
      }
    } catch (err) {
      resultBox.innerHTML = `<p style="color: var(--pp-danger);">Request failed.</p>`;
    } finally {
      lookupBtn.disabled = false;
      lookupBtn.innerHTML = 'Query Couriers';
    }
  });
}
