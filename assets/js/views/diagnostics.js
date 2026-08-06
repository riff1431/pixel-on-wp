import { showConfirmModal } from '../components/modal.js';
import { createCustomSelect } from '../components/select.js';

export function renderDiagnostics(container, state) {
  let pollingInterval = null;
  let activeLogId = null;
  let liveLogs = [];

  const header = document.createElement('div');
  header.className = 'pp-view-header';
  header.innerHTML = `
    <div style="display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 16px; width: 100%;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <span id="pp-live-indicator" class="pp-badge success" style="animation: pulse 2s infinite;">Live Polling</span>
        <button id="btn-clear-diagnostics" class="pp-btn pp-btn-outline" style="color: var(--pp-danger); border-color: rgba(239, 68, 68, 0.3);">Clear Logs</button>
      </div>
    </div>
  `;

  const debuggerLayout = document.createElement('div');
  debuggerLayout.className = 'diagnostics-layout';
  debuggerLayout.style.display = 'flex';
  debuggerLayout.style.gap = '24px';
  debuggerLayout.style.height = 'calc(100vh - 250px)';
  debuggerLayout.style.minHeight = '650px';

  // Events Stream Column
  const eventsList = document.createElement('div');
  eventsList.className = 'pp-card diagnostics-list-container';
  eventsList.style.flex = '0 0 320px';
  eventsList.style.padding = '0';
  eventsList.style.overflowY = 'auto';
  eventsList.style.display = 'flex';
  eventsList.style.flexDirection = 'column';
  
  const streamHeader = document.createElement('div');
  streamHeader.style.padding = '16px';
  streamHeader.style.borderBottom = '1px solid var(--pp-border-light)';
  streamHeader.style.display = 'flex';
  streamHeader.style.justifyContent = 'space-between';
  streamHeader.style.alignItems = 'center';
  
  const streamTitle = document.createElement('span');
  streamTitle.style.fontWeight = '700';
  streamTitle.style.color = 'var(--pp-text-heading)';
  streamTitle.style.fontSize = '14px';
  streamTitle.textContent = 'Live Stream';
  streamHeader.appendChild(streamTitle);

  // Custom Select Dropdown
  const platformFilter = createCustomSelect({
    options: [
      { label: 'All Logs', value: 'all' },
      { label: 'Errors Only', value: 'error' },
      { label: 'Meta Events', value: 'facebook' },
      { label: 'TikTok Events', value: 'tiktok' },
      { label: 'Reddit Events', value: 'reddit' },
      { label: 'Pinterest Events', value: 'pinterest' }
    ],
    value: 'all',
    onChange: () => {
      activeLogId = null;
      fetchDiagnostics();
    }
  });
  streamHeader.appendChild(platformFilter);

  eventsList.appendChild(streamHeader);

  const listContent = document.createElement('div');
  listContent.id = 'pp-diagnostics-list';
  listContent.innerHTML = `<div style="padding:24px; text-align:center; color:var(--pp-text-muted);">Fetching...</div>`;
  eventsList.appendChild(listContent);

  // Terminal Payload Column
  const detailPanel = document.createElement('div');
  detailPanel.className = 'pp-card diagnostics-terminal-container';
  detailPanel.style.flex = '1';
  detailPanel.style.display = 'flex';
  detailPanel.style.flexDirection = 'column';
  detailPanel.style.padding = '0';
  detailPanel.style.minWidth = '0';
  
  const terminalHeader = document.createElement('div');
  terminalHeader.style.padding = '16px 24px';
  terminalHeader.style.borderBottom = '1px solid var(--pp-border)';
  terminalHeader.style.background = 'rgba(0,0,0,0.2)';
  terminalHeader.style.display = 'flex';
  terminalHeader.style.justifyContent = 'space-between';
  terminalHeader.style.alignItems = 'center';
  terminalHeader.style.flexWrap = 'wrap';
  terminalHeader.style.gap = '8px';
  terminalHeader.innerHTML = `
    <span id="pp-diagnostics-terminal-title" style="font-family: monospace; font-size: 13px; color: var(--pp-text-muted); word-break: break-all;">/var/log/PixelOnWP/waiting...</span>
    <span id="pp-diagnostics-platform-badge" style="display: none;" class="pp-badge"></span>
  `;
  
  const terminalBody = document.createElement('div');
  terminalBody.style.padding = '24px';
  terminalBody.style.flex = '1';
  terminalBody.style.overflowY = 'auto';
  terminalBody.style.display = 'flex';
  terminalBody.style.flexDirection = 'column';
  terminalBody.innerHTML = `
    <div style="font-family: monospace; font-size: 11px; color: var(--pp-success); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em;">Dispatched Payload Context</div>
    <div id="pp-diagnostics-terminal-content" style="background: #020617; border: 1px solid var(--pp-border-strong); color: #4ade80; padding: 16px; border-radius: var(--pp-radius-sm); font-size: 12px; font-family: 'JetBrains Mono', monospace; overflow-x: auto; flex: 1; margin: 0; box-shadow: inset 0 0 10px rgba(0,0,0,0.5); white-space: pre;">Select an event from the stream to view its payload.</div>
  `;

  detailPanel.appendChild(terminalHeader);
  detailPanel.appendChild(terminalBody);

  debuggerLayout.appendChild(eventsList);
  debuggerLayout.appendChild(detailPanel);

  container.appendChild(header);
  container.appendChild(debuggerLayout);

  // Responsive Styles
  if (!document.getElementById('pp-diagnostics-styles')) {
    const style = document.createElement('style');
    style.id = 'pp-diagnostics-styles';
    style.innerHTML = `
      @media (max-width: 768px) {
        .diagnostics-layout {
          flex-direction: column;
          height: auto !important;
        }
        .diagnostics-list-container {
          flex: none !important;
          max-height: 400px;
        }
        .diagnostics-terminal-container {
          min-height: 400px;
        }
      }
    `;
    document.head.appendChild(style);
  }

  const ajaxurl = (window.pixelonwp_admin_vars && window.pixelonwp_admin_vars.ajaxurl) || '';
  const nonce = (window.pixelonwp_admin_vars && window.pixelonwp_admin_vars.nonce) || '';

  const getInstructionsForError = (errObj, platform) => {
      let errStr = typeof errObj === 'object' ? JSON.stringify(errObj) : errObj;
      errStr = errStr.toLowerCase();
      
      let msg = '';
      if (errStr.includes('token') || errStr.includes('auth')) {
          msg = `Your Access Token for ${platform} is invalid, expired, or missing. <strong>How to Fix:</strong> Go to the Setup Wizard, click on the ${platform} configuration step, and ensure you have copy-pasted the correct Access Token exactly as provided by the platform.`;
      } else if (errStr.includes('pixel') || errStr.includes('id')) {
          msg = `The Pixel ID or Event Source ID you provided for ${platform} is invalid. <strong>How to Fix:</strong> Go to the Setup Wizard and verify your Pixel ID is correct (numbers only).`;
      } else if (errStr.includes('format') || errStr.includes('parameter') || errStr.includes('schema')) {
          msg = `The payload format was rejected by ${platform}. <strong>How to Fix:</strong> Ensure your website's tracking parameters (like currency, value) are properly configured. Clear your website cache and try a new test order.`;
      } else {
          msg = `An unexpected API error occurred when connecting to ${platform}. <strong>How to Fix:</strong> Please check your internet connection, verify your Setup Wizard credentials, and review the exact error JSON below for clues.`;
      }
      
      return msg;
  };

  const renderList = () => {
    let html = '';
    const filter = platformFilter.value;
    
     liveLogs.forEach(log => {
      if (filter !== 'all' && filter !== 'error' && filter !== '') {
        if (filter === 'facebook') {
          if (log.platform && log.platform !== 'facebook') return;
        } else {
          if (log.platform !== filter) return;
        }
      }
      if (filter === 'error' && log.status !== 'failed' && !String(log.status).startsWith('failed')) return;

      let color = 'var(--pp-text-muted)';
      if (log.status === 'success') color = 'var(--pp-success)';
      if (log.status === 'pending') color = 'var(--pp-warning)';
      if (log.status === 'failed') color = 'var(--pp-danger)';
      
      const isActive = activeLogId === log.uid;
      const platformDisplay = log.platform ? log.platform : (log.log_type === 'diagnostic' ? 'system' : 'facebook');
      const typeLabel = log.log_type === 'diagnostic' ? 'DIAGNOSTIC' : 'LOG';
      const typeColor = log.log_type === 'diagnostic' ? 'var(--pp-warning)' : 'var(--pp-primary)';
      const typeBg = log.log_type === 'diagnostic' ? 'rgba(234, 179, 8, 0.1)' : 'rgba(59, 130, 246, 0.1)';

      html += `
        <div class="pp-diagnostic-item" data-id="${log.uid}" style="padding: 16px; border-bottom: 1px solid var(--pp-border); cursor: pointer; transition: background 0.2s; ${isActive ? 'background: rgba(255,255,255,0.03); border-left: 3px solid var(--pp-primary);' : 'border-left: 3px solid transparent;'}">
          <div style="display: flex; justify-content: space-between; margin-bottom: 6px; pointer-events: none;">
            <strong style="color: var(--pp-text-main); font-family: monospace; font-size: 13px; word-break: break-all;">> ${log.event_name}</strong>
            <span style="font-size: 11px; color: var(--pp-text-muted); font-family: monospace; white-space: nowrap; margin-left: 8px;">${log.created_at.split(' ')[1]}</span>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-family: monospace; pointer-events: none;">
            <span style="color: var(--pp-text-muted);">${log.event_id}</span>
            <div style="display: flex; gap: 8px; align-items: center;">
              <span style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; font-size: 10px; text-transform: uppercase;">${platformDisplay}</span>
              <span style="background: ${typeBg}; color: ${typeColor}; border: 1px solid ${typeColor}; opacity: 0.8; padding: 2px 6px; border-radius: 4px; font-size: 10px; text-transform: uppercase; font-weight: 700;">${typeLabel}</span>
              <span style="color: ${color}; font-weight: 600;">[${log.status.toUpperCase()}]</span>
            </div>
          </div>
        </div>
      `;
    });

    if (!html) html = '<div style="padding:24px; text-align:center; color:var(--pp-text-muted);">No events logged yet.</div>';
    listContent.innerHTML = html;

    listContent.querySelectorAll('.pp-diagnostic-item').forEach(el => {
      el.addEventListener('click', (e) => {
        const id = e.currentTarget.getAttribute('data-id');
        activeLogId = id;
        renderList(); 
        showPayload(activeLogId);
      });
    });
    
    // Auto-scroll to active item if present
    if (activeLogId) {
        const activeEl = listContent.querySelector(`.pp-diagnostic-item[data-id="${activeLogId}"]`);
        if (activeEl) activeEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  };

  const showPayload = (id) => {
    const log = liveLogs.find(l => l.uid === id);
    if (!log) return;
    
    document.getElementById('pp-diagnostics-terminal-title').textContent = log.log_type === 'diagnostic' 
        ? `/var/log/PixelOnWP/sys_${log.event_name.toLowerCase().replace(/[^a-z0-9]/g, '_')}_${log.id}.log`
        : `/var/log/PixelOnWP/${log.event_name.toLowerCase()}_${log.event_id}.log`;
    
    const badge = document.getElementById('pp-diagnostics-platform-badge');
    badge.style.display = 'inline-block';
    const platformDisplay = log.log_type === 'diagnostic' ? 'SYSTEM DIAGNOSTIC' : (log.platform ? log.platform : 'facebook');
    badge.textContent = platformDisplay.toUpperCase();
    badge.className = `pp-badge ${log.log_type === 'diagnostic' ? 'warning' : (platformDisplay === 'tiktok' ? 'primary' : 'success')}`;
    
    let payloadStr = log.payload;
    let errorStr = null;
    let rawErrorObj = null;
    
    try {
      const parsed = JSON.parse(log.payload);
      if (parsed._PixelOnWP_error) {
         rawErrorObj = parsed._PixelOnWP_error;
         errorStr = typeof parsed._PixelOnWP_error === 'object' ? JSON.stringify(parsed._PixelOnWP_error, null, 2) : parsed._PixelOnWP_error;
         delete parsed._PixelOnWP_error;
      }
      payloadStr = JSON.stringify(parsed, null, 2);
    } catch(e) {}
    
    const termContent = document.getElementById('pp-diagnostics-terminal-content');
    termContent.innerHTML = ''; // Clear previous
    
    if (errorStr) {
      const errDiv = document.createElement('div');
      errDiv.style.marginBottom = '16px';
      errDiv.style.paddingBottom = '16px';
      errDiv.style.borderBottom = '1px solid rgba(239, 68, 68, 0.3)';
      
      const errTitle = document.createElement('div');
      errTitle.style.color = '#ef4444'; // Red
      errTitle.style.fontWeight = 'bold';
      errTitle.style.marginBottom = '8px';
      errTitle.innerText = 'API Error:\\n' + errorStr;
      
      const instructionDiv = document.createElement('div');
      instructionDiv.style.background = 'rgba(239, 68, 68, 0.1)';
      instructionDiv.style.border = '1px solid rgba(239, 68, 68, 0.2)';
      instructionDiv.style.color = '#fca5a5';
      instructionDiv.style.padding = '12px';
      instructionDiv.style.borderRadius = '6px';
      instructionDiv.style.fontFamily = 'Inter, sans-serif';
      instructionDiv.style.fontSize = '13px';
      instructionDiv.style.lineHeight = '1.5';
      instructionDiv.innerHTML = getInstructionsForError(rawErrorObj, platformDisplay.toUpperCase());
      
      errDiv.appendChild(errTitle);
      errDiv.appendChild(instructionDiv);
      termContent.appendChild(errDiv);
    }
    
    const textNode = document.createTextNode(payloadStr);
    termContent.appendChild(textNode);
  };

  const fetchDiagnostics = async () => {
    if (!document.body.contains(container)) {
      clearInterval(pollingInterval);
      return;
    }
    
    // Check for auto-select error ID from URL params on first load
    if (!pollingInterval && state.queryParams && state.queryParams.err_id) {
        activeLogId = state.queryParams.err_id;
        platformFilter.value = 'error'; // Auto-switch to error filter
    }

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_get_logs');
    formData.append('nonce', nonce);
    formData.append('level', platformFilter.value === 'error' ? 'error' : 'all');
    formData.append('platform', platformFilter.value);
    if (activeLogId) {
        formData.append('err_id', activeLogId);
    }

    try {
      const res = await fetch(ajaxurl, { method: 'POST', body: formData });
      const result = await res.json();
      if (result.success && result.data && result.data.logs) {
        liveLogs = result.data.logs;
        renderList();
        
        if (activeLogId) {
            const foundLog = liveLogs.find(l => l.uid === activeLogId);
            if (foundLog) {
                showPayload(activeLogId);
            } else {
                activeLogId = null; // Unselect if it truly doesn't exist
            }
        }
        
        if (!activeLogId) {
          document.getElementById('pp-diagnostics-terminal-title').textContent = `/var/log/PixelOnWP/waiting...`;
          document.getElementById('pp-diagnostics-platform-badge').style.display = 'none';
          document.getElementById('pp-diagnostics-terminal-content').textContent = 'Select an event from the stream to view its payload.';
        }
      }
    } catch (e) {
      console.warn('Diagnostics polling failed:', e);
    }
  };

  container.querySelector('#btn-clear-diagnostics').addEventListener('click', async () => {
    const confirmed = await showConfirmModal({
      title: 'Clear Diagnostic Logs?',
      message: 'This will erase all recorded tracking events and payload logs from memory. This action cannot be undone.',
      confirmText: 'Clear Logs',
      cancelText: 'Cancel',
      type: 'danger'
    });
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_clear_logs');
    formData.append('nonce', nonce);
    await fetch(ajaxurl, { method: 'POST', body: formData });
    fetchDiagnostics();
  });

  fetchDiagnostics();
  pollingInterval = setInterval(fetchDiagnostics, 3000);
}
