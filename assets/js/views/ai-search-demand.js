export function renderSearchDemand(container, state) {
  const content = document.createElement('div');
  content.innerHTML = `
    <div style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: var(--pp-radius-lg); padding: 24px; margin-bottom: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0;">Unmet Customer Demand</h3>
        <button id="ai-refresh-demand-btn" class="pp-btn pp-btn-secondary">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
          Refresh Analysis
        </button>
      </div>
      <p style="color: var(--pp-text-muted);">AI analyzes recent visitor search queries and cross-references them against your active WooCommerce catalog to find missed opportunities.</p>
      
      <div id="ai-demand-results" style="margin-top: 24px;">
        <div style="text-align: center; padding: 40px; color: var(--pp-text-muted);">
           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite; margin-bottom: 12px;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
           <div>Analyzing search data...</div>
        </div>
      </div>
    </div>
  `;

  container.appendChild(content);

  const fetchDemand = async () => {
    const resultsContainer = document.getElementById('ai-demand-results');
    const btn = document.getElementById('ai-refresh-demand-btn');
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'pixelonwp_analyze_search_demand');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const json = await res.json();
      
      if (json.success && json.data) {
        renderResults(json.data);
      } else {
        resultsContainer.innerHTML = `<div style="color: var(--pp-text-muted); padding: 24px; background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border); border-radius: 8px; text-align: center;">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px; opacity: 0.5;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <div style="font-weight: 600; margin-bottom: 4px;">No search demand data available yet</div>
          <div style="font-size: 13px;">Search insights will populate as visitors interact with your site.</div>
        </div>`;
      }
    } catch (e) {
      resultsContainer.innerHTML = `<div style="color: var(--pp-text-muted); padding: 24px; background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border); border-radius: 8px; text-align: center;">
        <div style="font-weight: 600; margin-bottom: 4px;">Could not load search demand data</div>
        <div style="font-size: 13px;">Please try refreshing. If the issue persists, check your AI API configuration.</div>
      </div>`;
    } finally {
      btn.disabled = false;
    }
  };

  const renderResults = (data) => {
    const resultsContainer = document.getElementById('ai-demand-results');
    
    let tableHtml = `
      <div style="background: rgba(0,0,0,0.02); padding: 20px; border-radius: 8px; border: 1px solid var(--pp-border); margin-bottom: 24px;">
        <div style="font-size: 12px; color: var(--pp-text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">AI Recommendation</div>
        <div style="font-size: 15px; font-weight: 500; color: var(--pp-text-main); line-height: 1.5;">${data.recommendation}</div>
      </div>
      
      <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
          <tr style="border-bottom: 2px solid var(--pp-border);">
            <th style="padding: 12px; font-weight: 600; color: var(--pp-text-muted);">Search Keyword</th>
            <th style="padding: 12px; font-weight: 600; color: var(--pp-text-muted);">Frequency (Last 200 Logs)</th>
            <th style="padding: 12px; font-weight: 600; color: var(--pp-text-muted);">Catalog Status</th>
          </tr>
        </thead>
        <tbody>
    `;

    if (data.unmet_demand && data.unmet_demand.length > 0) {
      data.unmet_demand.forEach(item => {
        tableHtml += `
          <tr style="border-bottom: 1px solid var(--pp-border);">
            <td style="padding: 16px 12px; font-weight: 500; color: var(--pp-text-main);">${item.keyword}</td>
            <td style="padding: 16px 12px;">
              <div style="display: inline-block; background: var(--pp-primary); color: white; border-radius: 12px; padding: 2px 10px; font-size: 12px; font-weight: 600;">${item.frequency}</div>
            </td>
            <td style="padding: 16px 12px;">
              <span style="color: ${item.status.toLowerCase().includes('out of stock') || item.status.toLowerCase().includes('unmet') ? 'var(--pp-danger)' : 'var(--pp-success)'}; font-weight: 500;">
                ${item.status}
              </span>
            </td>
          </tr>
        `;
      });
    } else {
      tableHtml += `<tr><td colspan="3" style="padding: 24px; text-align: center; color: var(--pp-text-muted);">No unmet demand found yet.</td></tr>`;
    }

    tableHtml += `</tbody></table>`;
    resultsContainer.innerHTML = tableHtml;
  };

  document.getElementById('ai-refresh-demand-btn').addEventListener('click', () => {
    document.getElementById('ai-demand-results').innerHTML = `
      <div style="text-align: center; padding: 40px; color: var(--pp-text-muted);">
         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite; margin-bottom: 12px;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
         <div>Re-analyzing search data...</div>
      </div>
    `;
    fetchDemand();
  });

  fetchDemand();
}
