import { showToast } from '../components/toaster.js';

export function renderDashboard(container, state) {
  let pollingInterval = null;

  const header = document.createElement('div');
  header.className = 'pp-view-header';
  header.style.display = 'flex';
  header.style.justifyContent = 'space-between';
  header.style.alignItems = 'center';
  header.innerHTML = `
    <div>
      <h2>PixelOnWP Intelligence</h2>
      <p>Live metrics and synchronization status for Meta and TikTok Server-Side pipelines.</p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
      <span id="pp-dash-live-indicator" class="pp-badge pp-badge-success">Live</span>
      <button id="btn-clear-cache" class="pp-btn-outline" style="display: flex; align-items: center; gap: 6px;">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
        Clear Cache
      </button>
    </div>
  `;

  // Status Cards
  const grid = document.createElement('div');
  grid.className = 'pp-grid';
  grid.style.gridTemplateColumns = 'repeat(4, 1fr)';

  const renderCards = (data = null) => {
    grid.innerHTML = '';
    const cards = [
      { id: 'server_events', title: 'Server Events (24h)', value: data ? data.server_events : '...', variant: 'pp-card-liquid-blue', icon: '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 15h18v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4z"/><path d="M12 12v3"/>' },
      { id: 'match_rate', title: 'CAPI Match Rate', value: data ? data.match_rate : '...', variant: 'pp-card-liquid-primary', icon: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>' },
      { id: 'dedup', title: 'Deduplication', value: data ? data.deduplication : '100%', variant: 'pp-card-liquid-purple', icon: '<path d="m2 9 3-3 3 3"/><path d="M13 18H7a2 2 0 0 1-2-2V6"/><path d="m22 15-3 3-3-3"/><path d="M11 6h6a2 2 0 0 1 2 2v10"/>' },
      { id: 'queue_fail', title: 'Queue Failures', value: data ? data.queue_failures : '...', variant: 'pp-card-liquid-amber', icon: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>' },
    ];

    cards.forEach(card => {
      const cardEl = document.createElement('div');
      cardEl.className = `pp-card ${card.variant}`;
      cardEl.innerHTML = `
        <div class="pp-card-header">
          <div class="pp-card-title">
            <span class="pp-card-icon-pod">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${card.icon}</svg>
            </span>
            ${card.title}
          </div>
        </div>
        <div class="pp-card-value">${card.value}</div>
      `;
      grid.appendChild(cardEl);
    });
  };

  renderCards();

  // Integrations Table & Queue Retry Widget
  const lowerGrid = document.createElement('div');
  lowerGrid.style.display = 'grid';
  lowerGrid.style.gridTemplateColumns = '2fr 1fr';
  lowerGrid.style.gap = '20px';

  // Metrics Table
  const tableCard = document.createElement('div');
  tableCard.className = 'pp-card';
  tableCard.style.padding = '0';
  
    const renderIntegrations = (data = null) => {
    const metaStatus = data ? data.integrations.meta : 'Checking...';
    const tiktokStatus = data ? data.integrations.tiktok : 'Checking...';
    const gtmStatus = data ? data.integrations.gtm : 'Checking...';
    const ga4Status = data ? data.integrations.ga4 : 'Checking...';
    
    const platforms = [
      { id: 'meta', name: 'Meta Pixel / CAPI', status: metaStatus, active: metaStatus === 'Healthy' },
      { id: 'tiktok', name: 'TikTok Events API', status: tiktokStatus, active: tiktokStatus === 'Healthy' },
      { id: 'ga4', name: 'Google Analytics 4', status: ga4Status, active: ga4Status === 'Healthy' },
      { id: 'gtm', name: 'GTM Server Container', status: gtmStatus, active: gtmStatus === 'Healthy' },
    ];

    // Create global handler for disabled config clicks if it doesn't exist
    if (!window.showDisabledConfigModal) {
      window.showDisabledConfigModal = function(platformName) {
        const modalId = 'pp-disabled-config-modal';
        let modal = document.getElementById(modalId);
        if (modal) modal.remove();
        
        modal = document.createElement('div');
        modal.id = modalId;
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        modal.style.zIndex = '999999';
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.backdropFilter = 'blur(4px)';
        
        modal.innerHTML = `
          <div style="background: white; border-radius: 12px; max-width: 400px; width: 90%; padding: 32px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); animation: slideUp 0.3s ease-out;">
            <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                <div style="background: #fffbeb; color: #f59e0b; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                </div>
            </div>
            <h3 style="text-align: center; margin: 0 0 12px 0; font-size: 20px; color: #111827;">Feature Not Enabled</h3>
            <p style="text-align: center; color: #4b5563; font-size: 14px; line-height: 1.5; margin-bottom: 24px;">
                This is not enabled. Please enable <strong>${platformName}</strong> first before you can configure its settings.
            </p>
            <div style="display: flex; justify-content: center;">
                <button onclick="document.getElementById('${modalId}').remove()" class="pp-btn" style="background: #f59e0b; color: white; border: none; width: 100%; justify-content: center;">Got it</button>
            </div>
          </div>
        `;
        document.body.appendChild(modal);
      };
    }

    let tbody = '';
    platforms.forEach(p => {
      const displayStatus = p.status === 'Inactive' ? 'Not Enabled' : p.status;
      const btnAction = p.active 
        ? `onclick="window.location.hash = 'setup'"` 
        : `onclick="window.showDisabledConfigModal('${p.name.replace(/'/g, "\\'")}')"`;

      tbody += `
        <tr>
          <td style="padding: 12px 20px; font-weight: 500; border-bottom: 1px solid var(--pp-border-light);">${p.name}</td>
          <td style="padding: 12px 20px; border-bottom: 1px solid var(--pp-border-light);">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span class="pp-status-dot ${p.active ? 'success' : (p.status === 'Checking...' ? 'warning' : 'danger')}"></span>
              <span style="color: var(--pp-text-muted);">${displayStatus}</span>
            </div>
          </td>
          <td style="padding: 12px 20px; text-align: right; border-bottom: 1px solid var(--pp-border-light);">
            <button class="pp-btn-outline" ${btnAction}>Configure</button>
          </td>
        </tr>
      `;
    });

    tableCard.innerHTML = `
      <div style="padding: 18px 24px; border-bottom: 1px solid var(--pp-border-light); background: rgba(255, 255, 255, 0.45); backdrop-filter: blur(12px);">
        <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--pp-text-heading);">Integration Metrics</h3>
      </div>
      <table style="width: 100%; border-collapse: collapse;">
        <tbody>${tbody}</tbody>
      </table>
    `;
  };
  renderIntegrations();

  // Platform Performance Metrics Grid
  const platformGrid = document.createElement('div');
  platformGrid.className = 'pp-grid';
  platformGrid.style.gridTemplateColumns = 'repeat(4, 1fr)';
  platformGrid.style.marginTop = '20px';
  platformGrid.style.marginBottom = '20px';

  const renderPlatformMetrics = (data = null) => {
    platformGrid.innerHTML = '';
    
    const meta24h = data && data.metrics && data.metrics.meta ? data.metrics.meta['24h'] : '0';
    const metaLt = data && data.metrics && data.metrics.meta ? data.metrics.meta.lifetime : '0';
    const tiktok24h = data && data.metrics && data.metrics.tiktok ? data.metrics.tiktok['24h'] : '0';
    const tiktokLt = data && data.metrics && data.metrics.tiktok ? data.metrics.tiktok.lifetime : '0';
    const googleAds24h = data && data.metrics && data.metrics.google_ads ? data.metrics.google_ads['24h'] : '0';
    const googleAdsLt = data && data.metrics && data.metrics.google_ads ? data.metrics.google_ads.lifetime : '0';
    const ga424h = data && data.metrics && data.metrics.ga4 ? data.metrics.ga4['24h'] : '0';
    const ga4Lt = data && data.metrics && data.metrics.ga4 ? data.metrics.ga4.lifetime : '0';

    const metaStatus = data ? data.integrations.meta : 'Checking...';
    const tiktokStatus = data ? data.integrations.tiktok : 'Checking...';
    const ga4Status = data ? data.integrations.ga4 : 'Checking...';
    const googleAdsStatus = data ? data.integrations.google_ads : 'Checking...';

    const platformCards = [
      {
        name: 'Meta Pixel / CAPI',
        variant: 'pp-card-liquid-primary',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        '24h': meta24h,
        lifetime: metaLt,
        status: metaStatus,
        active: metaStatus === 'Healthy'
      },
      {
        name: 'TikTok Events API',
        variant: 'pp-card-liquid-purple',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#000000"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.02 1.59 4.23.95 1.15 2.27 1.95 3.72 2.23.01 1.29.01 2.58 0 3.87-1.37-.06-2.71-.56-3.81-1.39-.63-.48-1.17-1.09-1.57-1.8v8.34c0 1.25-.26 2.49-.78 3.62-.77 1.63-2.18 2.87-3.87 3.39-1.53.47-3.19.41-4.67-.18-1.74-.71-3.13-2.19-3.75-3.95-.61-1.74-.53-3.69.23-5.36.9-1.93 2.76-3.37 4.9-3.76.01 1.27-.02 2.54-.01 3.81-.88.16-1.69.67-2.19 1.43-.53.84-.6 1.88-.2 2.75.33.72.93 1.27 1.65 1.55.77.29 1.62.24 2.34-.14.73-.42 1.24-1.12 1.41-1.93.07-.46.06-.93.06-1.39V0h2.79z"/></svg>',
        '24h': tiktok24h,
        lifetime: tiktokLt,
        status: tiktokStatus,
        active: tiktokStatus === 'Healthy'
      },
      {
        name: 'Google Ads',
        variant: 'pp-card-liquid-blue',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 28 28" fill="none"><path d="M17.17 1.54L1.54 17.17a2.53 2.53 0 000 3.58l5.71 5.71c.99.99 2.6.99 3.58 0L26.46 10.83a2.53 2.53 0 000-3.58l-5.71-5.71a2.53 2.53 0 00-3.58 0z" fill="#FBBC05"/><path d="M9.13 22.87L24.76 7.24a2.53 2.53 0 000-3.58l-5.71-5.71a2.53 2.53 0 00-3.58 0L.24 13.58a2.53 2.53 0 000 3.58l5.71 5.71c.99.99 2.6.99 3.18 0z" fill="#4285F4"/><circle cx="9.13" cy="9.13" r="4.25" fill="#34A853"/></svg>',
        '24h': googleAds24h,
        lifetime: googleAdsLt,
        status: googleAdsStatus,
        active: googleAdsStatus === 'Healthy'
      },
      {
        name: 'Google Analytics 4',
        variant: 'pp-card-liquid-amber',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#F9AB00"><path d="M17 19h2c.6 0 1-.4 1-1v-4c0-.6-.4-1-1-1h-2c-.6 0-1 .4-1 1v4c0 .6.4 1 1 1zM11 19h2c.6 0 1-.4 1-1V8c0-.6-.4-1-1-1h-2c-.6 0-1 .4-1 1v10c0 .6.4 1 1 1zM5 19h2c.6 0 1-.4 1-1v-6c0-.6-.4-1-1-1H5c-.6 0-1 .4-1 1v6c0 .6.4 1 1 1z"/></svg>',
        '24h': ga424h,
        lifetime: ga4Lt,
        status: ga4Status,
        active: ga4Status === 'Healthy'
      }
    ];

    platformCards.forEach(c => {
      const cardEl = document.createElement('div');
      cardEl.className = `pp-card ${c.variant}`;
      cardEl.style.cursor = 'pointer';
      
      const btnAction = c.active 
        ? `window.location.hash = 'setup'` 
        : `window.showDisabledConfigModal('${c.name.replace(/'/g, "\\'")}')`;
      
      cardEl.setAttribute('onclick', btnAction);

      cardEl.innerHTML = `
        <div class="pp-card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
          <div class="pp-card-title" style="display: flex; align-items: center; gap: 8px;">
            <span class="pp-card-icon-pod">${c.icon}</span>
            <span style="font-weight: 600; font-size: 13px;">${c.name}</span>
          </div>
          <div style="display: flex; align-items: center; gap: 4px;">
            <span class="pp-status-dot ${c.active ? 'success' : 'danger'}" style="width: 6px; height: 6px;"></span>
            <span style="font-size: 11px; color: var(--pp-text-muted); font-weight: 500;">${c.status === 'Inactive' ? 'Disabled' : c.status}</span>
          </div>
        </div>
        <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--pp-border-light); padding-top: 10px; margin-top: 5px;">
          <div>
            <div style="font-size: 10px; color: var(--pp-text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Past 24h</div>
            <div class="pp-card-value" style="font-size: 20px; font-weight: 500; margin-top: 2px; line-height: 1.2;">${c['24h']}</div>
          </div>
          <div style="text-align: right;">
            <div style="font-size: 10px; color: var(--pp-text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Lifetime</div>
            <div class="pp-card-value" style="font-size: 20px; font-weight: 500; margin-top: 2px; line-height: 1.2;">${c.lifetime}</div>
          </div>
        </div>
      `;
      platformGrid.appendChild(cardEl);
    });
  };
  renderPlatformMetrics();


  // Queue Failure Widget
  const queueCard = document.createElement('div');
  queueCard.className = 'pp-card pp-card-liquid-primary';
  
  let isRetrying = false;
  
  const renderQueueWidget = (data = null) => {
    const queueCount = data && data.queue_failures ? parseInt(data.queue_failures.toString().replace(/,/g, '')) : 0;
    const hasFailures = queueCount > 0;
    
    queueCard.innerHTML = `
      <div class="pp-card-header">
        <div class="pp-card-title">
          <span class="pp-card-icon-pod">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
          </span>
          Background Queue
        </div>
      </div>
      <div style="text-align: center; padding: 10px 0;">
        <div style="font-size: 36px; font-weight: 800; color: ${hasFailures ? 'var(--pp-danger)' : 'var(--pp-text-heading)'}; line-height: 1; margin-bottom: 6px; font-family: var(--pp-font-heading);">${data ? data.queue_failures : '...'}</div>
        <div style="font-size: 13px; color: var(--pp-text-muted); margin-bottom: 20px; font-weight: 500;">Failed Payloads Waiting</div>
        <button id="btn-retry-queue" class="pp-btn" style="width: 100%; justify-content: center; ${isRetrying ? 'opacity: 0.7; pointer-events: none;' : ''}" ${!hasFailures && !isRetrying ? 'disabled' : ''}>${isRetrying ? 'Retrying...' : 'Retry Failed Payloads'}</button>
      </div>
    `;

    // Attach Retry Event
    setTimeout(() => {
      const retryBtn = queueCard.querySelector('#btn-retry-queue');
      if (retryBtn && hasFailures && !isRetrying) {
        retryBtn.addEventListener('click', async () => {
          isRetrying = true;
          retryBtn.innerHTML = 'Retrying...';
          retryBtn.style.pointerEvents = 'none';
          retryBtn.style.opacity = '0.7';

          const formData = new FormData();
          formData.append('action', 'PixelOnWP_retry_queue');
          formData.append('nonce', window.pixelonwp_admin_vars.nonce);
          
          try {
            const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
            const result = await res.json();
            
            if (result.success && result.data && result.data.has_errors && result.data.details.failed_events.length > 0) {
                // Show Warning Popup
                const firstErr = result.data.details.failed_events[0];
                const modalId = 'pp-queue-warning-modal';
                let modal = document.getElementById(modalId);
                if (modal) modal.remove();
                
                modal = document.createElement('div');
                modal.id = modalId;
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.width = '100%';
                modal.style.height = '100%';
                modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
                modal.style.zIndex = '999999';
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                modal.style.backdropFilter = 'blur(4px)';
                
                modal.innerHTML = `
                  <div style="background: white; border-radius: 12px; max-width: 450px; width: 90%; padding: 32px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); animation: slideUp 0.3s ease-out;">
                    <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                        <div style="background: #fef2f2; color: #ef4444; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                        </div>
                    </div>
                    <h3 style="text-align: center; margin: 0 0 12px 0; font-size: 20px; color: #111827;">Payload Delivery Failed</h3>
                    <p style="text-align: center; color: #4b5563; font-size: 14px; line-height: 1.5; margin-bottom: 24px;">
                        We attempted to resend your payloads, but the API rejected them. This is usually caused by an invalid Access Token or Pixel ID.<br><br>
                        <strong>Platform:</strong> <span style="text-transform: capitalize;">${firstErr.platform}</span>
                    </p>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <button onclick="document.getElementById('${modalId}').remove()" class="pp-btn pp-btn-outline" style="border-color: #d1d5db; color: #374151;">Close</button>
                        <button onclick="document.getElementById('${modalId}').remove(); window.location.hash = 'diagnostics?err_id=${firstErr.uid}';" class="pp-btn" style="background: #ef4444; color: white; border: none;">View Error Details</button>
                    </div>
                  </div>
                `;
                document.body.appendChild(modal);
                
                // Add slideUp keyframe if it doesn't exist
                if (!document.getElementById('pp-modal-style')) {
                    const style = document.createElement('style');
                    style.id = 'pp-modal-style';
                    style.innerHTML = '@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }';
                    document.head.appendChild(style);
                }
            }
          } catch(e) { console.error(e); }
          
          setTimeout(() => {
            isRetrying = false;
            if (typeof fetchDashboardStats === 'function') {
               fetchDashboardStats();
            }
          }, 1000);
        });
      }
    }, 50);
  };
  renderQueueWidget();

  lowerGrid.appendChild(tableCard);
  lowerGrid.appendChild(queueCard);

  container.appendChild(header);
  container.appendChild(grid);
  container.appendChild(lowerGrid);
  container.appendChild(platformGrid);
  
  // Data Fetching Logic
  const fetchDashboardStats = async () => {
    if (!document.body.contains(container)) {
      clearInterval(pollingInterval);
      return;
    }
    
    const formData = new FormData();
    formData.append('action', 'PixelOnWP_get_dashboard_stats');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const result = await res.json();
      if (result.success) {
        renderCards(result.data);
        renderIntegrations(result.data);
        renderPlatformMetrics(result.data);
        renderQueueWidget(result.data);
      }
    } catch (e) {
      console.warn('Dashboard stats fetch failed:', e);
    }
  };

  fetchDashboardStats();

  pollingInterval = setInterval(fetchDashboardStats, 5000); // Update every 5 seconds
  
  // Attach Event Listener for Clear Cache
  setTimeout(() => {
    const clearBtn = document.getElementById('btn-clear-cache');
    if (clearBtn) {
      clearBtn.addEventListener('click', async () => {
        clearBtn.innerHTML = `Clearing...`;
        clearBtn.style.pointerEvents = 'none';

        const formData = new FormData();
        formData.append('action', 'PixelOnWP_clear_website_cache');
        formData.append('nonce', window.pixelonwp_admin_vars.nonce);

        try {
          const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
          const data = await res.json();
          if (data.success) {
            clearBtn.innerHTML = `Cleared!`;
            showToast({ message: 'Website cache & transient data cleared successfully!', type: 'success', title: 'Cache Cleared' });
          } else {
            throw new Error('Failed');
          }
        } catch (e) {
          clearBtn.innerHTML = `Error`;
          showToast({ message: 'Failed to clear cache. Please try again.', type: 'error', title: 'Cache Error' });
        }
        
        setTimeout(() => {
          clearBtn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg> Clear Cache`;
          clearBtn.style.pointerEvents = 'auto';
        }, 3000);
      });
    }
  }, 100);
}
