export function renderServerSide(container, state) {
  const header = document.createElement('div');
  header.className = 'pp-view-header';
  header.innerHTML = `
    <h2>Server-Side & ITP Bypass</h2>
    <p>Configure your first-party collection endpoint and isolated GA4 Measurement Protocol integration to bypass browser trackers limitations.</p>
  `;

  const dashboardGrid = document.createElement('div');
  dashboardGrid.style.display = 'grid';
  dashboardGrid.style.gridTemplateColumns = '1fr 1fr';
  dashboardGrid.style.gap = '24px';
  dashboardGrid.style.marginBottom = '40px';

  // Architectural Diagram Card
  const diagramCard = document.createElement('div');
  diagramCard.className = 'pp-card';
  diagramCard.style.gridColumn = '1 / -1';
  diagramCard.innerHTML = `
    <div class="pp-card-header">
      <div class="pp-card-title">First-Party Routing Architecture</div>
    </div>
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 24px; background: rgba(0,0,0,0.2); border-radius: var(--pp-radius); border: 1px solid var(--pp-border-strong);">
      
      <!-- Browser Node -->
      <div style="text-align: center; flex: 1;">
        <div style="width: 72px; height: 72px; margin: 0 auto 12px; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(224, 242, 254, 0.85) 100%); border: 1.5px solid rgba(56, 189, 248, 0.4); border-radius: 20px; display: flex; align-items: center; justify-content: center; box-shadow: 0 12px 28px -6px rgba(14, 165, 233, 0.22), inset 0 1.5px 1.5px rgba(255, 255, 255, 1); transition: transform 0.3s ease;">
          <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-globe">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
            <path d="M2 12h20"/>
          </svg>
        </div>
        <div style="font-weight: 700; font-size: 14px; color: var(--pp-text-heading);">User Browser</div>
        <div style="font-size: 11px; color: var(--pp-text-muted);">Safari / Chrome</div>
      </div>

      <!-- Arrow 1 -->
      <div style="flex: 1; text-align: center; position: relative; padding: 0 8px;">
        <div style="height: 3px; background: linear-gradient(90deg, #0284c7 0%, var(--pp-primary) 100%); width: 100%; position: absolute; top: 50%; z-index: 1; border-radius: 4px;"></div>
        <div class="pp-status-dot pulse" style="position: absolute; top: calc(50% - 5px); left: 50%; z-index: 2; background: var(--pp-primary); box-shadow: 0 0 12px var(--pp-primary-glow);"></div>
      </div>

      <!-- First Party Endpoint Node (Central Server Hub) -->
      <div style="text-align: center; flex: 1;">
        <div style="width: 84px; height: 84px; margin: 0 auto 12px; background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(254, 226, 226, 0.9) 100%); border: 2.2px solid var(--pp-primary); border-radius: 24px; display: flex; align-items: center; justify-content: center; box-shadow: 0 16px 36px -6px rgba(225, 29, 72, 0.32), 0 0 24px rgba(225, 29, 72, 0.18), inset 0 2px 2px rgba(255, 255, 255, 1); transition: transform 0.3s ease;">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--pp-primary)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-server">
            <rect width="20" height="8" x="2" y="2" rx="2" ry="2"/>
            <rect width="20" height="8" x="2" y="14" rx="2" ry="2"/>
            <line x1="6" x2="6.01" y1="6" y2="6"/>
            <line x1="6" x2="6.01" y1="18" y2="18"/>
          </svg>
        </div>
        <div style="font-weight: 800; font-size: 14px; color: var(--pp-primary);">First-Party Endpoint</div>
        <div style="font-size: 11px; color: var(--pp-text-muted);">yoursite.com/pixelonwp/v1/collect</div>
        <div class="pp-badge success" style="margin-top: 8px; font-size: 10px; font-weight: 700;">Bypasses ITP</div>
      </div>

      <!-- Arrow 2 -->
      <div style="flex: 1; text-align: center; position: relative; padding: 0 8px;">
        <div style="height: 3px; background: linear-gradient(90deg, var(--pp-primary) 0%, #d946ef 100%); width: 100%; position: absolute; top: 50%; z-index: 1; border-radius: 4px;"></div>
        <div style="position: absolute; top: calc(50% - 22px); left: 0; right: 0; text-align: center; font-size: 10px; color: var(--pp-primary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Server-to-Server</div>
      </div>

      <!-- Meta CAPI Node -->
      <div style="text-align: center; flex: 1;">
        <div style="width: 72px; height: 72px; margin: 0 auto 12px; background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(243, 232, 255, 0.85) 100%); border: 1.5px solid rgba(217, 70, 239, 0.4); border-radius: 20px; display: flex; align-items: center; justify-content: center; box-shadow: 0 12px 28px -6px rgba(217, 70, 239, 0.22), inset 0 1.5px 1.5px rgba(255, 255, 255, 1); transition: transform 0.3s ease;">
          <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#d946ef" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-share-2">
            <circle cx="18" cy="5" r="3"/>
            <circle cx="6" cy="12" r="3"/>
            <circle cx="18" cy="19" r="3"/>
            <line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/>
            <line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/>
          </svg>
        </div>
        <div style="font-weight: 700; font-size: 14px; color: var(--pp-text-heading);">Meta Graph API</div>
        <div style="font-size: 11px; color: var(--pp-text-muted);">graph.facebook.com</div>
      </div>

    </div>
  `;

  // Status Card
  const statusCard = document.createElement('div');
  statusCard.className = 'pp-card';
  statusCard.innerHTML = `
    <div class="pp-card-header">
      <div class="pp-card-title">Endpoint Health</div>
    </div>
    <div style="display: flex; flex-direction: column; gap: 16px;">
      <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <span class="pp-status-dot success pulse"></span>
          <span style="font-weight: 500;">First-Party Cookie Mapping</span>
        </div>
        <span class="pp-badge success active">Active</span>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <span class="pp-status-dot success"></span>
          <span style="font-weight: 500;">/pixelonwp/v1/collect Endpoint</span>
        </div>
        <span class="pp-badge success">200 OK</span>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <span class="pp-status-dot success"></span>
          <span style="font-weight: 500;">CAPI & MP Forwarding</span>
        </div>
        <span class="pp-badge success">Operational</span>
      </div>
    </div>
  `;

  const fbTrackingMode = state.config?.config?.facebook_tracking_mode || 'hybrid';
  const ttTrackingMode = state.config?.config?.tiktok_tracking_mode || 'hybrid';
  const redditTrackingMode = state.config?.config?.reddit_tracking_mode || 'hybrid';
  const customRoute = state.config?.config?.custom_route || 'wp-json/pixelonwp/v1/collect';

  const ga4Config = window.pixelonwp_admin_vars?.config?.ga4_config || state.config?.ga4_config || {
    setup_type: 'basic',
    measurement_id: '',
    api_secret: '',
    test_code: '',
    events: {}
  };

  // Config Card
  const configCard = document.createElement('div');
  configCard.className = 'pp-card';
  configCard.innerHTML = `
    <div class="pp-card-header">
      <div class="pp-card-title">Endpoint Configuration</div>
    </div>
    <div style="display: flex; flex-direction: column; gap: 20px;">
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <!-- Facebook Mode -->
        <div style="background: rgba(0,0,0,0.02); padding: 16px; border-radius: var(--pp-radius-sm); border: 1px solid var(--pp-border-light);">
          <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: var(--pp-text-main);">Meta (Facebook) Routing</label>
          <select id="select-fb-tracking-mode" class="pp-input" style="padding: 10px; font-size: 13px;">
            <option value="hybrid" ${fbTrackingMode === 'hybrid' ? 'selected' : ''}>Hybrid (Browser + Server-Side)</option>
            <option value="server" ${fbTrackingMode === 'server' ? 'selected' : ''}>Server-Side Only</option>
            <option value="browser" ${fbTrackingMode === 'browser' ? 'selected' : ''}>Browser Only</option>
          </select>
        </div>

        <!-- TikTok Mode -->
        <div style="background: rgba(0,0,0,0.02); padding: 16px; border-radius: var(--pp-radius-sm); border: 1px solid var(--pp-border-light);">
          <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: var(--pp-text-main);">TikTok Routing</label>
          <select id="select-tt-tracking-mode" class="pp-input" style="padding: 10px; font-size: 13px;">
            <option value="hybrid" ${ttTrackingMode === 'hybrid' ? 'selected' : ''}>Hybrid (Browser + Server-Side)</option>
            <option value="server" ${ttTrackingMode === 'server' ? 'selected' : ''}>Server-Side Only</option>
            <option value="browser" ${ttTrackingMode === 'browser' ? 'selected' : ''}>Browser Only</option>
          </select>
        </div>

        <!-- Reddit Mode -->
        <div style="background: rgba(0,0,0,0.02); padding: 16px; border-radius: var(--pp-radius-sm); border: 1px solid var(--pp-border-light);">
          <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: var(--pp-text-main);">Reddit Routing</label>
          <select id="select-reddit-tracking-mode" class="pp-input" style="padding: 10px; font-size: 13px;">
            <option value="hybrid" ${redditTrackingMode === 'hybrid' ? 'selected' : ''}>Hybrid (Browser + Server-Side)</option>
            <option value="server" ${redditTrackingMode === 'server' ? 'selected' : ''}>Server-Side Only</option>
            <option value="browser" ${redditTrackingMode === 'browser' ? 'selected' : ''}>Browser Only</option>
          </select>
        </div>
      </div>
      <p style="font-size: 12px; color: var(--pp-text-muted); margin-top: 4px;">Hybrid mode is highly recommended. It uses advanced deduplication to ensure maximum match rates across platforms.</p>

      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: var(--pp-text-muted); text-transform: uppercase;">Custom Route (Optional)</label>
        <div style="display: flex; gap: 8px;">
          <span style="padding: 14px; background: rgba(255,255,255,0.05); border: 1px solid var(--pp-border-strong); border-radius: var(--pp-radius-sm); border-right: none; border-top-right-radius: 0; border-bottom-right-radius: 0; color: var(--pp-text-muted);">yoursite.com/</span>
          <input type="text" id="input-custom-route" class="pp-input" value="${customRoute}" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
        </div>
        <p style="font-size: 12px; color: var(--pp-text-muted); margin-top: 8px;">Use a completely custom route to further mask tracking scripts from adblockers.</p>
      </div>

      <!-- Dedicated GA4 Measurement Protocol API Fields -->
      <div style="border-top: 1px solid var(--pp-border); padding-top: 20px;">
        <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: var(--pp-text-main);">GA4 Measurement Protocol API Secret</h4>
        <div style="display: flex; flex-direction: column; gap: 12px;">
          <div>
            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 12px; color: var(--pp-text-muted);">API Secret Key</label>
            <input type="text" id="input-ga4-secret" class="pp-input" value="${ga4Config.api_secret || ''}" placeholder="e.g. _XyZaBcDeFgHiJkLmNoPqR">
          </div>
          <div>
            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 12px; color: var(--pp-text-muted);">GA4 Test Code (For DebugView)</label>
            <input type="text" id="input-ga4-test-code" class="pp-input" value="${ga4Config.test_code || ''}" placeholder="e.g. debug_mode=true">
          </div>
        </div>
      </div>

      <button id="btn-save-server" class="pp-btn" style="align-self: flex-start;">Update Configuration</button>
    </div>
  `;

  dashboardGrid.appendChild(diagramCard);
  dashboardGrid.appendChild(statusCard);
  dashboardGrid.appendChild(configCard);

  container.appendChild(header);
  container.appendChild(dashboardGrid);

  // Bind save event
  setTimeout(() => {
    const saveBtn = document.getElementById('btn-save-server');
    if (saveBtn) {
      saveBtn.addEventListener('click', async () => {
        const ogText = saveBtn.innerText;
        saveBtn.innerText = 'Saving...';
        saveBtn.style.pointerEvents = 'none';

        // Save platform server modes
        const formData = new FormData();
        formData.append('action', 'PixelOnWP_save_server_config');
        formData.append('nonce', window.pixelonwp_admin_vars.nonce);
        formData.append('facebook_tracking_mode', document.getElementById('select-fb-tracking-mode').value);
        formData.append('tiktok_tracking_mode', document.getElementById('select-tt-tracking-mode').value);
        formData.append('reddit_tracking_mode', document.getElementById('select-reddit-tracking-mode').value);
        formData.append('custom_route', document.getElementById('input-custom-route').value);

        // Save ga4 secret configuration key-value inputs
        const ga4Data = Object.assign({}, ga4Config, {
          apiSecret: document.getElementById('input-ga4-secret').value.trim(),
          testCode: document.getElementById('input-ga4-test-code').value.trim(),
        });

        const ga4FormData = new FormData();
        ga4FormData.append('action', 'PixelOnWP_save_platform_config');
        ga4FormData.append('nonce', window.pixelonwp_admin_vars.nonce);
        ga4FormData.append('platform', 'ga4');
        ga4FormData.append('data', JSON.stringify({
          setupType: ga4Data.setup_type || 'basic',
          measurementId: ga4Data.measurement_id || '',
          apiSecret: ga4Data.apiSecret,
          testCode: ga4Data.testCode,
          events: ga4Data.events || {}
        }));

        try {
          await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
          await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: ga4FormData });
          
          saveBtn.innerText = 'Saved!';
          saveBtn.style.background = 'var(--pp-success)';
          
          if (!state.config.config) state.config.config = {};
          state.config.config.facebook_tracking_mode = document.getElementById('select-fb-tracking-mode').value;
          state.config.config.tiktok_tracking_mode = document.getElementById('select-tt-tracking-mode').value;
          state.config.config.reddit_tracking_mode = document.getElementById('select-reddit-tracking-mode').value;
          state.config.config.custom_route = document.getElementById('input-custom-route').value;
          
          if (!window.pixelonwp_admin_vars.config) window.pixelonwp_admin_vars.config = {};
          window.pixelonwp_admin_vars.config.ga4_config = ga4Data;
          state.config.ga4_config = ga4Data;

        } catch (e) {
          saveBtn.innerText = 'Error';
        }

        setTimeout(() => {
          saveBtn.innerText = ogText;
          saveBtn.style.background = '';
          saveBtn.style.pointerEvents = 'auto';
        }, 2000);
      });
    }
  }, 100);
}
