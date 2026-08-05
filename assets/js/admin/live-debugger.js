(function() {
  if (document.getElementById('pixelonwp-live-debugger')) return;

  const container = document.createElement('div');
  container.id = 'pixelonwp-live-debugger';
  container.style.cssText = `
    position: fixed;
    bottom: 20px;
    left: 20px;
    width: 380px;
    max-height: 420px;
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(8px);
    color: #e2e8f0;
    border: 1px solid #334155;
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 12px;
    z-index: 9999999;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  `;

  container.innerHTML = `
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #0f172a; border-bottom: 1px solid #1e293b; cursor: pointer;" id="debug-header">
      <span style="font-weight: bold; color: #38bdf8; display: flex; align-items: center; gap: 6px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        PixelOnWP Live Inspector
      </span>
      <div style="display: flex; gap: 8px;">
        <button id="btn-minimize-debug" style="background: transparent; border: none; color: #94a3b8; cursor: pointer; font-size: 14px; padding: 0 4px;">–</button>
      </div>
    </div>
    <!-- Content Body -->
    <div id="debug-body" style="padding: 12px 16px; overflow-y: auto; max-height: 360px; display: flex; flex-direction: column; gap: 10px;">
      <div style="color: #64748b; text-align: center; padding: 24px 0;">
        [No events detected yet.<br>Interact with selector actions on page.]
      </div>
    </div>
  `;

  document.body.appendChild(container);

  const header = container.querySelector('#debug-header');
  const body = container.querySelector('#debug-body');
  const minBtn = container.querySelector('#btn-minimize-debug');
  let minimized = false;

  const toggleMinimize = () => {
    if (minimized) {
      body.style.display = 'flex';
      container.style.height = 'auto';
      container.style.width = '380px';
      minBtn.innerHTML = '–';
    } else {
      body.style.display = 'none';
      container.style.height = 'auto';
      container.style.width = '180px';
      minBtn.innerHTML = '+';
    }
    minimized = !minimized;
  };

  header.addEventListener('click', (e) => {
    if (e.target !== minBtn) toggleMinimize();
  });
  minBtn.addEventListener('click', toggleMinimize);

  window.addEventListener('plugin_live_event_tracked', (e) => {
    const log = e.detail;

    if (body.innerHTML.includes('No events detected')) {
      body.innerHTML = '';
    }

    // Calculate match confidence based on parameter completeness
    let confidence = 100;
    const paramKeys = Object.keys(log.params || {});
    if (paramKeys.length > 0) {
      const filled = paramKeys.filter(k => log.params[k] !== '' && log.params[k] !== undefined).length;
      confidence = Math.round((filled / paramKeys.length) * 100);
    }

    const logEl = document.createElement('div');
    logEl.style.cssText = 'background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; gap: 6px; animation: slideIn 0.2s ease-out;';

    // Renders real-time delivery statuses
    let statusesHTML = '';
    const details = log.execution_details || {};
    Object.keys(details).forEach(plat => {
      const statusInfo = details[plat];
      const isSent = statusInfo.status === 'Sent';
      statusesHTML += `
        <div style="display: flex; justify-content: space-between; font-size: 10px; margin-top: 2px;">
          <span style="color: #cbd5e1;">${plat}</span>
          <span style="color: ${isSent ? '#34d399' : '#f87171'}; font-weight: 500;">
            ${isSent ? 'Sent ✓' : statusInfo.status}
          </span>
        </div>
      `;
    });

    if (!statusesHTML) {
      statusesHTML = '<div style="color: #f87171; font-size: 10px;">No platform dispatches configured.</div>';
    }

    logEl.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: bold; color: #34d399; font-size: 13px;">⚡ ${log.event_name}</span>
        <span style="color: #64748b; font-size: 10px;">${new Date(log.timestamp).toLocaleTimeString()}</span>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 4px; margin-bottom: 2px;">
        <span style="font-size: 10px; color: #94a3b8;">Trigger: <strong>${log.trigger_type.toUpperCase()}</strong></span>
        <span style="font-size: 10px; padding: 2px 6px; border-radius: 4px; background: ${confidence > 70 ? 'rgba(16,185,129,0.15)' : 'rgba(234,179,8,0.15)'}; color: ${confidence > 70 ? '#10b981' : '#eab308'}; font-weight: bold;">
          Confidence: ${confidence}%
        </span>
      </div>
      <div style="font-size: 10px; color: #94a3b8; font-family: monospace; word-break: break-all;"><strong>Selector:</strong> ${log.selector || 'N/A'}</div>
      <div style="font-size: 10px; color: #e2e8f0; background: #0f172a; padding: 6px; border-radius: 4px;">
        <strong>Parameters:</strong>
        <pre style="margin: 4px 0 0 0; white-space: pre-wrap; font-family: monospace; font-size: 10px; color: #38bdf8;">${JSON.stringify(log.params, null, 2)}</pre>
      </div>
      <div style="margin-top: 4px; padding-top: 4px; border-top: 1px solid #334155;">
        <div style="font-weight: bold; color: #94a3b8; font-size: 9px; text-transform: uppercase; margin-bottom: 2px;">Execution Status</div>
        ${statusesHTML}
      </div>
    `;

    body.insertBefore(logEl, body.firstChild);
    if (minimized) toggleMinimize();
  });

  const styleId = 'pp-debug-animations';
  if (!document.getElementById(styleId)) {
    const style = document.createElement('style');
    style.id = styleId;
    style.innerHTML = `@keyframes slideIn { from { transform: translateY(10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }`;
    document.head.appendChild(style);
  }
})();
