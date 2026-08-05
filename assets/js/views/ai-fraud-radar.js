export function renderFraudRadar(container, state) {
  const content = document.createElement('div');
  content.innerHTML = `
    <div style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: var(--pp-radius-lg); padding: 24px; margin-bottom: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0;">Traffic Integrity & Risk Radar</h3>
        <button id="ai-refresh-fraud-btn" class="pp-btn pp-btn-secondary">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
          Run Full Scan
        </button>
      </div>
      
      <div id="ai-fraud-overview" style="display: flex; gap: 24px; margin-bottom: 32px; display: none;">
        <div style="flex: 1; background: rgba(34, 197, 94, 0.05); border: 1px solid rgba(34, 197, 94, 0.2); padding: 20px; border-radius: 8px; text-align: center;">
          <div style="font-size: 12px; color: var(--pp-success); font-weight: 700; text-transform: uppercase;">Safe Traffic</div>
          <div id="ai-safe-percent" style="font-size: 36px; font-weight: 700; color: var(--pp-success);">--%</div>
        </div>
        <div style="flex: 1; background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); padding: 20px; border-radius: 8px; text-align: center;">
          <div style="font-size: 12px; color: var(--pp-danger); font-weight: 700; text-transform: uppercase;">Suspicious Activity</div>
          <div id="ai-suspicious-percent" style="font-size: 36px; font-weight: 700; color: var(--pp-danger);">--%</div>
        </div>
      </div>
      
      <div id="ai-fraud-results">
        <div style="text-align: center; padding: 40px; color: var(--pp-text-muted);">
           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite; margin-bottom: 12px;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
           <div>Scanning IP logs for anomalous behavior...</div>
        </div>
      </div>
    </div>
  `;

  container.appendChild(content);

  const fetchFraudData = async () => {
    const resultsContainer = document.getElementById('ai-fraud-results');
    const overview = document.getElementById('ai-fraud-overview');
    const btn = document.getElementById('ai-refresh-fraud-btn');
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'pixelonwp_get_fraud_radar');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const json = await res.json();
      
      if (json.success && json.data) {
        overview.style.display = 'flex';
        document.getElementById('ai-safe-percent').innerText = `${json.data.safe_percentage}%`;
        document.getElementById('ai-suspicious-percent').innerText = `${json.data.suspicious_percentage}%`;
        renderResults(json.data.flagged_ips, json.data.is_demo);
      } else {
        resultsContainer.innerHTML = `<div style="color: var(--pp-text-muted); padding: 24px; background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border); border-radius: 8px; text-align: center;">
          <div style="font-weight: 600; margin-bottom: 4px;">No traffic data available yet</div>
          <div style="font-size: 13px;">Risk analysis will populate as visitors interact with your site.</div>
        </div>`;
      }
    } catch (e) {
      resultsContainer.innerHTML = `<div style="color: var(--pp-text-muted); padding: 24px; background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border); border-radius: 8px; text-align: center;">
        <div style="font-weight: 600; margin-bottom: 4px;">Could not load traffic risk data</div>
        <div style="font-size: 13px;">Please try refreshing. If the issue persists, check your AI API configuration.</div>
      </div>`;
    } finally {
      btn.disabled = false;
    }
  };

  const renderResults = (flaggedIps, isDemo = false) => {
    const resultsContainer = document.getElementById('ai-fraud-results');
    
    let demoIndicator = '';
    if (isDemo) {
      demoIndicator = `<div style="display: inline-block; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; margin-bottom: 16px;">🧪 DEMO DATA</div>`;
    }
    
    if (!flaggedIps || flaggedIps.length === 0) {
      resultsContainer.innerHTML = `
        <div style="background: rgba(34, 197, 94, 0.05); padding: 24px; text-align: center; border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 8px;">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--pp-success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
          <div style="color: var(--pp-success); font-weight: 600;">No suspicious IPs flagged recently.</div>
        </div>
      `;
      return;
    }

    let tableHtml = `
      ${demoIndicator}
      <h4 style="margin-top: 0;">Flagged IP Addresses</h4>
      <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
          <tr style="border-bottom: 2px solid var(--pp-border);">
            <th style="padding: 12px; font-weight: 600; color: var(--pp-text-muted);">IP Address</th>
            <th style="padding: 12px; font-weight: 600; color: var(--pp-text-muted);">Risk Score</th>
            <th style="padding: 12px; font-weight: 600; color: var(--pp-text-muted);">Reasoning (AI Gen)</th>
          </tr>
        </thead>
        <tbody>
    `;

    flaggedIps.forEach(item => {
      tableHtml += `
        <tr style="border-bottom: 1px solid var(--pp-border);">
          <td style="padding: 16px 12px; font-family: monospace; color: var(--pp-text-main); font-size: 14px;">${item.ip}</td>
          <td style="padding: 16px 12px;">
            <div style="display: inline-block; background: var(--pp-danger); color: white; border-radius: 12px; padding: 2px 10px; font-size: 12px; font-weight: 600;">${item.risk_score} / 100</div>
          </td>
          <td style="padding: 16px 12px; color: var(--pp-text-main); font-size: 13px;">${item.reason}</td>
        </tr>
      `;
    });

    tableHtml += `</tbody></table>`;
    resultsContainer.innerHTML = tableHtml;
  };

  document.getElementById('ai-refresh-fraud-btn').addEventListener('click', () => {
    document.getElementById('ai-fraud-overview').style.display = 'none';
    document.getElementById('ai-fraud-results').innerHTML = `
      <div style="text-align: center; padding: 40px; color: var(--pp-text-muted);">
         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite; margin-bottom: 12px;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
         <div>Running Deep Scan on IPs...</div>
      </div>
    `;
    fetchFraudData();
  });

  fetchFraudData();
}
