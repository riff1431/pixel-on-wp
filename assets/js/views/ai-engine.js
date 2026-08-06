import { renderCampaignBuilder } from './ai-campaign-builder.js?v=3';
import { renderSearchDemand } from './ai-search-demand.js?v=2';
import { renderFraudRadar } from './ai-fraud-radar.js?v=2';

export function renderAiEngine(container, state) {
  // Inject Switch Styles
  if (!document.getElementById('ai-switch-styles')) {
    const style = document.createElement('style');
    style.id = 'ai-switch-styles';
    style.innerHTML = `
      .pp-switch input:checked + .pp-slider {
        background-color: var(--pp-success, #22c55e) !important;
      }
      .pp-switch input:disabled + .pp-slider {
        opacity: 0.5;
        cursor: not-allowed;
      }
      .pp-switch .pp-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
      }
      .pp-switch input:checked + .pp-slider:before {
        transform: translateX(20px);
      }
      #ai-api-config-panel input:disabled {
        background-color: rgba(255, 255, 255, 0.02) !important;
        color: var(--pp-text-muted, #94a3b8) !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
        opacity: 0.4;
        cursor: not-allowed;
      }
    `;
    document.head.appendChild(style);
  }

  const header = document.createElement('div');
  header.className = 'pp-view-header pp-ai-engine-header';
  header.style.marginBottom = '24px';
  header.innerHTML = `
    <div class="pp-ai-header-actions" style="display: flex; justify-content: flex-end; align-items: center; gap: 16px; width: 100%;">
      <button id="btn-toggle-api-config" class="pp-btn pp-btn-api-config" style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); color: var(--pp-primary); padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: middle;"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
        API Configuration
      </button>
    </div>
  `;
  container.appendChild(header);

  // API Configuration Panel (collapsible)
  const apiPanel = document.createElement('div');
  apiPanel.id = 'ai-api-config-panel';
  apiPanel.style.cssText = 'display: none; background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: var(--pp-radius-lg); padding: 24px; margin-bottom: 24px; animation: fadeIn 0.3s ease;';
  apiPanel.innerHTML = `
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h3 style="margin: 0; font-size: 16px; color: var(--pp-text-main);">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: middle;"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
        AI Provider Configuration
      </h3>
      <span id="ai-active-provider-badge" style="background: rgba(34, 197, 94, 0.1); color: var(--pp-success); padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">Using: Inbuilt</span>
    </div>
    <p style="color: var(--pp-text-muted); font-size: 13px; margin-bottom: 24px; line-height: 1.5;">
      Configure your own API keys for better performance and no rate limits. Toggle a provider to activate it.
    </p>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
      <!-- Gemini API Key -->
      <div style="background: rgba(0,0,0,0.05); padding: 20px; border-radius: 12px; border: 1px solid var(--pp-border);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
          <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #4285f4, #34a853); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
          </div>
          <div>
            <div style="font-weight: 700; font-size: 14px; color: var(--pp-text-main);">Google Gemini API</div>
            <div style="font-size: 11px; color: var(--pp-text-muted);">Recommended — Fast & Free tier available</div>
          </div>
          <label class="pp-switch" style="margin-left: auto; position: relative; display: inline-block; width: 42px; height: 22px;">
            <input type="checkbox" id="gemini-active-toggle" style="opacity: 0; width: 0; height: 0;">
            <span class="pp-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 22px;"></span>
          </label>
        </div>
        <input type="text" id="ai-gemini-key" placeholder="Enter your Gemini API Key" style="width: 100%; padding: 10px 14px; border: 1px solid var(--pp-border); border-radius: 8px; background: var(--pp-surface); color: var(--pp-text-main); font-size: 13px; box-sizing: border-box; margin-bottom: 12px;">
        <div style="font-size: 11px; color: var(--pp-text-muted); margin-bottom: 4px;">Get your key from <a href="https://aistudio.google.com/apikey" target="_blank" style="color: var(--pp-primary);">Google AI Studio</a></div>
      </div>
      <!-- ChatGPT API Key -->
      <div style="background: rgba(0,0,0,0.05); padding: 20px; border-radius: 12px; border: 1px solid var(--pp-border);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
          <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #10a37f, #1a7f64); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
          </div>
          <div>
            <div style="font-weight: 700; font-size: 14px; color: var(--pp-text-main);">OpenAI ChatGPT API</div>
            <div style="font-size: 11px; color: var(--pp-text-muted);">GPT-4o-mini — Highly capable</div>
          </div>
          <label class="pp-switch" style="margin-left: auto; position: relative; display: inline-block; width: 42px; height: 22px;">
            <input type="checkbox" id="chatgpt-active-toggle" style="opacity: 0; width: 0; height: 0;">
            <span class="pp-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 22px;"></span>
          </label>
        </div>
        <input type="text" id="ai-chatgpt-key" placeholder="Enter your OpenAI API Key (sk-...)" style="width: 100%; padding: 10px 14px; border: 1px solid var(--pp-border); border-radius: 8px; background: var(--pp-surface); color: var(--pp-text-main); font-size: 13px; box-sizing: border-box; margin-bottom: 12px;">
        <div style="font-size: 11px; color: var(--pp-text-muted); margin-bottom: 4px;">Get your key from <a href="https://platform.openai.com/api-keys" target="_blank" style="color: var(--pp-primary);">OpenAI Dashboard</a></div>
      </div>
    </div>
    <div style="margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end;">
      <button id="btn-save-api-keys" class="pp-btn pp-btn-primary" style="padding: 10px 24px; font-size: 14px; font-weight: 600;">
        Save API Keys
      </button>
    </div>
    <div id="api-save-message" style="display: none; margin-top: 12px; padding: 10px 16px; border-radius: 8px; font-size: 13px; font-weight: 500;"></div>
  `;
  container.appendChild(apiPanel);

  // Tabs
  const tabsContainer = document.createElement('div');
  tabsContainer.className = 'pp-tabs pp-tabs-container';
  tabsContainer.style.marginBottom = '24px';

  const tabs = [
    { id: 'overview', label: 'Live AI Strategy' },
    { id: 'campaign', label: 'Instant Campaign Builder' },
    { id: 'search', label: 'Unmet Search Demand' },
    { id: 'fraud', label: 'Traffic Risk Radar' }
  ];

  let currentTab = 'overview';
  const tabButtons = {};

  tabs.forEach((tab, index) => {
    const btn = document.createElement('button');
    btn.className = `pp-tab ${index === 0 ? 'active' : ''}`;
    btn.innerText = tab.label;
    btn.addEventListener('click', () => switchTab(tab.id));
    tabsContainer.appendChild(btn);
    tabButtons[tab.id] = btn;
  });

  container.appendChild(tabsContainer);

  const contentArea = document.createElement('div');
  container.appendChild(contentArea);

  let fetchInterval;

  const switchTab = (tabId) => {
    currentTab = tabId;
    
    Object.values(tabButtons).forEach(btn => {
      btn.classList.remove('active');
    });
    if (tabButtons[tabId]) {
      tabButtons[tabId].classList.add('active');
    }

    contentArea.innerHTML = '';
    
    if (fetchInterval) clearInterval(fetchInterval);

    if (tabId === 'overview') {
      renderOverview(contentArea);
    } else if (tabId === 'campaign') {
      renderCampaignBuilder(contentArea, state);
    } else if (tabId === 'search') {
      renderSearchDemand(contentArea, state);
    } else if (tabId === 'fraud') {
      renderFraudRadar(contentArea, state);
    }
  };

  const renderOverview = (area) => {
    // Radar
    const radarContainer = document.createElement('div');
    radarContainer.className = 'pp-ai-radar-container';
    radarContainer.style.background = 'var(--pp-surface)';
    radarContainer.style.border = '1px solid var(--pp-border)';
    radarContainer.style.borderRadius = 'var(--pp-radius-lg)';
    radarContainer.style.padding = '24px';
    radarContainer.style.marginBottom = '32px';
    
    radarContainer.innerHTML = `
      <div class="pp-ai-radar-header" style="display: flex; align-items: center; gap: 16px;">
         <div class="radar-pulse" style="width: 16px; height: 16px; background: var(--pp-success); border-radius: 50%; box-shadow: 0 0 12px var(--pp-success); animation: pulse 2s infinite; flex-shrink: 0;"></div>
         <h3 style="margin: 0; font-size: 18px; color: var(--pp-text-main);">Live Activity Radar</h3>
      </div>
      <div class="pp-ai-radar-stats">
         <div class="pp-ai-stat-item">
           <div class="pp-ai-stat-label">Active Visitors</div>
           <div id="ai-stat-visitors" class="pp-ai-stat-value">--</div>
         </div>
         <div class="pp-ai-stat-item">
           <div class="pp-ai-stat-label">Top Search</div>
           <div id="ai-stat-search" class="pp-ai-stat-value">--</div>
         </div>
         <div class="pp-ai-stat-item">
           <div class="pp-ai-stat-label">Bounce Rate</div>
           <div id="ai-stat-bounce" class="pp-ai-stat-value">--</div>
         </div>
      </div>
    `;

    // Cards
    const cardsContainer = document.createElement('div');
    cardsContainer.style.display = 'grid';
    cardsContainer.style.gridTemplateColumns = 'repeat(auto-fit, minmax(320px, 1fr))';
    cardsContainer.style.gap = '24px';

    const createCard = (platform, title, color) => {
      return `
        <div class="ai-card" id="ai-card-${platform}" style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-top: 4px solid ${color}; border-radius: var(--pp-radius-md); overflow: hidden; display: flex; flex-direction: column;">
           <div style="padding: 16px 20px; background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--pp-border); display: flex; justify-content: space-between; align-items: center;">
              <h4 style="margin: 0; font-size: 16px; color: var(--pp-text-main);">${title}</h4>
              <div class="card-loader" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite; color: var(--pp-text-muted);"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
              </div>
           </div>
           <div class="card-body" style="padding: 20px; display: flex; flex-direction: column; gap: 16px; flex: 1;">
              <div style="color: var(--pp-text-muted); font-size: 14px; text-align: center; margin-top: 20px;">Fetching AI insights...</div>
           </div>
        </div>
      `;
    };

    cardsContainer.innerHTML = `
      ${createCard('meta', 'Meta Ads Strategy', '#1877F2')}
      ${createCard('tiktok', 'TikTok Ads Strategy', '#000000')}
      ${createCard('google', 'Google Ads Strategy', '#EA4335')}
    `;

    area.appendChild(radarContainer);
    area.appendChild(cardsContainer);

    // Pulse CSS
    if (!document.getElementById('ai-pulse-css')) {
      const style = document.createElement('style');
      style.id = 'ai-pulse-css';
      style.innerHTML = `
        @keyframes pulse {
          0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
          70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
          100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(-8px); }
          to { opacity: 1; transform: translateY(0); }
        }
        .ai-card-prop {
          margin-bottom: 16px;
        }
        .ai-card-label {
          font-size: 11px;
          text-transform: uppercase;
          font-weight: 600;
          color: var(--pp-text-muted);
          margin-bottom: 4px;
        }
        .ai-card-value {
          font-size: 14px;
          color: var(--pp-text-main);
          line-height: 1.5;
        }
      `;
      document.head.appendChild(style);
    }

    const fetchInsights = async () => {
      const formData = new FormData();
      formData.append('action', 'pixelonwp_get_ai_insights');
      formData.append('nonce', window.pixelonwp_admin_vars.nonce);

      document.querySelectorAll('.card-loader').forEach(el => el.style.display = 'block');

      try {
        const response = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success && result.data) {
          updateUI(result.data);
        } else {
          console.warn('AI Engine Error:', result);
          showEmptyState();
        }
      } catch (e) {
        console.warn('AI Engine Request Failed:', e);
      } finally {
        document.querySelectorAll('.card-loader').forEach(el => el.style.display = 'none');
      }
    };

    const updateUI = (data) => {
      if (data.live_stats && document.getElementById('ai-stat-visitors')) {
        document.getElementById('ai-stat-visitors').innerText = data.live_stats.active_visitors || '0';
        document.getElementById('ai-stat-search').innerText = data.live_stats.top_search || 'None';
        document.getElementById('ai-stat-bounce').innerText = data.live_stats.bounce_rate || '0%';
      }

      if (data.meta && document.querySelector('#ai-card-meta .card-body')) {
        document.querySelector('#ai-card-meta .card-body').innerHTML = `
          <div class="ai-card-prop"><div class="ai-card-label">High Intent Audience</div><div class="ai-card-value">${data.meta.audience || 'N/A'}</div></div>
          <div class="ai-card-prop"><div class="ai-card-label">Recommended Ad Type</div><div class="ai-card-value">${data.meta.ad_type || 'N/A'}</div></div>
          <div class="ai-card-prop"><div class="ai-card-label">Angle / Hook Copy</div><div class="ai-card-value">${data.meta.hook || 'N/A'}</div></div>
          <div class="ai-card-prop"><div class="ai-card-label">Call-To-Action (CTA)</div><div class="ai-card-value">${data.meta.cta || 'N/A'}</div></div>
        `;
      }

      if (data.tiktok && document.querySelector('#ai-card-tiktok .card-body')) {
        document.querySelector('#ai-card-tiktok .card-body').innerHTML = `
          <div class="ai-card-prop"><div class="ai-card-label">Video Script Concept (0-15s)</div><div class="ai-card-value">${data.tiktok.concept || 'N/A'}</div></div>
          <div class="ai-card-prop"><div class="ai-card-label">Trending Audio Style</div><div class="ai-card-value">${data.tiktok.audio || 'N/A'}</div></div>
          <div class="ai-card-prop"><div class="ai-card-label">Visual Hook</div><div class="ai-card-value">${data.tiktok.visual || 'N/A'}</div></div>
          <div class="ai-card-prop"><div class="ai-card-label">Target Demographics</div><div class="ai-card-value">${data.tiktok.demographics || 'N/A'}</div></div>
        `;
      }

      if (data.google && document.querySelector('#ai-card-google .card-body')) {
        document.querySelector('#ai-card-google .card-body').innerHTML = `
          <div class="ai-card-prop"><div class="ai-card-label">High-Intent Keywords</div><div class="ai-card-value">${data.google.keywords || 'N/A'}</div></div>
          <div class="ai-card-prop"><div class="ai-card-label">Performance Max (PMax) Plan</div><div class="ai-card-value">${data.google.pmax || 'N/A'}</div></div>
          <div class="ai-card-prop"><div class="ai-card-label">Recommended Bidding</div><div class="ai-card-value">${data.google.bidding || 'N/A'}</div></div>
        `;
      }
    };

    const showEmptyState = () => {
      if (document.getElementById('ai-stat-visitors')) {
        document.getElementById('ai-stat-visitors').innerText = '0';
        document.getElementById('ai-stat-search').innerText = 'None';
        document.getElementById('ai-stat-bounce').innerText = '0%';
      }
      const cards = ['meta', 'tiktok', 'google'];
      cards.forEach(platform => {
        const body = document.querySelector(`#ai-card-${platform} .card-body`);
        if (body) {
          body.innerHTML = `
            <div style="color: var(--pp-text-muted); font-size: 13px; text-align: center; margin-top: 20px; line-height: 1.6;">
              No visitor data available yet. Ad strategy insights will populate as visitors interact with your site.
            </div>
          `;
        }
      });
    };

    fetchInsights();
    fetchInterval = setInterval(fetchInsights, 30000);
  };

  // ---- EVENT LISTENERS ----

  // Toggle API Config Panel
  document.getElementById('btn-toggle-api-config').addEventListener('click', () => {
    const panel = document.getElementById('ai-api-config-panel');
    if (panel.style.display === 'none') {
      panel.style.display = 'block';
      loadApiKeys();
    } else {
      panel.style.display = 'none';
    }
  });

  // Save API Keys
  document.getElementById('btn-save-api-keys').addEventListener('click', async () => {
    const btn = document.getElementById('btn-save-api-keys');
    const geminiKey = document.getElementById('ai-gemini-key').value.trim();
    const chatgptKey = document.getElementById('ai-chatgpt-key').value.trim();
    const msgEl = document.getElementById('api-save-message');

    btn.innerText = 'Saving...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'pixelonwp_save_ai_api_keys');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('gemini_key', geminiKey);
    formData.append('chatgpt_key', chatgptKey);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const json = await res.json();

      if (json.success) {
        msgEl.style.display = 'block';
        msgEl.style.background = 'rgba(34, 197, 94, 0.1)';
        msgEl.style.border = '1px solid rgba(34, 197, 94, 0.3)';
        msgEl.style.color = 'var(--pp-success)';
        msgEl.innerHTML = '✅ API keys saved! AI insights will refresh with your provider.';

        // Update status dots
        updateProviderStatus(json.data.provider_status);

        setTimeout(() => { msgEl.style.display = 'none'; }, 4000);
      } else {
        throw new Error('Save failed');
      }
    } catch (e) {
      msgEl.style.display = 'block';
      msgEl.style.background = 'rgba(239, 68, 68, 0.1)';
      msgEl.style.border = '1px solid rgba(239, 68, 68, 0.3)';
      msgEl.style.color = 'var(--pp-danger)';
      msgEl.innerHTML = '❌ Failed to save API keys.';
    } finally {
      btn.innerText = 'Save API Keys';
      btn.disabled = false;
    }
  });



  // Load API keys from backend
  const loadApiKeys = async () => {
    const formData = new FormData();
    formData.append('action', 'pixelonwp_get_ai_api_keys');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const json = await res.json();

      if (json.success && json.data) {
        document.getElementById('ai-gemini-key').value = json.data.gemini_key || '';
        document.getElementById('ai-chatgpt-key').value = json.data.chatgpt_key || '';
        updateProviderStatus(json.data.provider_status);
      }
    } catch (e) {
      console.warn('Failed to load API keys:', e);
    }
  };

  const updateProviderStatus = (status) => {
    if (!status) return;

    const geminiToggle = document.getElementById('gemini-active-toggle');
    const chatgptToggle = document.getElementById('chatgpt-active-toggle');
    const geminiInput = document.getElementById('ai-gemini-key');
    const chatgptInput = document.getElementById('ai-chatgpt-key');
    const badge = document.getElementById('ai-active-provider-badge');

    if (geminiToggle) {
      geminiToggle.checked = status.gemini_configured && status.active_provider === 'gemini';
    }
    if (chatgptToggle) {
      chatgptToggle.checked = status.chatgpt_configured && status.active_provider === 'chatgpt';
    }

    if (geminiInput) {
      geminiInput.disabled = status.active_provider === 'chatgpt';
    }
    if (chatgptInput) {
      chatgptInput.disabled = status.active_provider === 'gemini';
    }

    if (badge) {
      const providerLabels = {
        'gemini': '🟢 Using: Your Gemini API',
        'chatgpt': '🟢 Using: Your ChatGPT API',
        'inbuilt': '🔵 Using: Inbuilt System'
      };
      badge.innerHTML = providerLabels[status.active_provider] || '🔵 Using: Inbuilt';
    }
  };

  const setActiveProvider = async (provider) => {
    const msgEl = document.getElementById('api-save-message');
    const formData = new FormData();
    formData.append('action', 'pixelonwp_set_active_provider');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('active_provider', provider);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const json = await res.json();

      if (json.success && json.data) {
        updateProviderStatus(json.data.provider_status);
        msgEl.style.display = 'block';
        msgEl.style.background = 'rgba(34, 197, 94, 0.1)';
        msgEl.style.border = '1px solid rgba(34, 197, 94, 0.3)';
        msgEl.style.color = 'var(--pp-success)';
        msgEl.innerHTML = `✅ Active provider set to ${provider === 'inbuilt' ? 'Inbuilt System' : (provider === 'gemini' ? 'Google Gemini' : 'OpenAI ChatGPT')}.`;
        setTimeout(() => { msgEl.style.display = 'none'; }, 3000);
      }
    } catch (e) {
      console.warn('Failed to set active provider:', e);
    }
  };

  document.getElementById('gemini-active-toggle').addEventListener('change', (e) => {
    const geminiKey = document.getElementById('ai-gemini-key').value.trim();
    if (e.target.checked && !geminiKey) {
      e.target.checked = false;
      const msgEl = document.getElementById('api-save-message');
      msgEl.style.display = 'block';
      msgEl.style.background = 'rgba(239, 68, 68, 0.1)';
      msgEl.style.border = '1px solid rgba(239, 68, 68, 0.3)';
      msgEl.style.color = 'var(--pp-danger)';
      msgEl.innerHTML = '❌ Please enter and save Google Gemini API key first.';
      setTimeout(() => { msgEl.style.display = 'none'; }, 4000);
      return;
    }

    const chatgptToggle = document.getElementById('chatgpt-active-toggle');
    const geminiInput = document.getElementById('ai-gemini-key');
    const chatgptInput = document.getElementById('ai-chatgpt-key');

    if (e.target.checked) {
      if (chatgptToggle) chatgptToggle.checked = false;
      if (chatgptInput) chatgptInput.disabled = true;
      if (geminiInput) geminiInput.disabled = false;
      setActiveProvider('gemini');
    } else {
      if (chatgptInput) chatgptInput.disabled = false;
      if (geminiInput) geminiInput.disabled = false;
      setActiveProvider('inbuilt');
    }
  });

  document.getElementById('chatgpt-active-toggle').addEventListener('change', (e) => {
    const chatgptKey = document.getElementById('ai-chatgpt-key').value.trim();
    if (e.target.checked && !chatgptKey) {
      e.target.checked = false;
      const msgEl = document.getElementById('api-save-message');
      msgEl.style.display = 'block';
      msgEl.style.background = 'rgba(239, 68, 68, 0.1)';
      msgEl.style.border = '1px solid rgba(239, 68, 68, 0.3)';
      msgEl.style.color = 'var(--pp-danger)';
      msgEl.innerHTML = '❌ Please enter and save OpenAI ChatGPT API key first.';
      setTimeout(() => { msgEl.style.display = 'none'; }, 4000);
      return;
    }

    const geminiToggle = document.getElementById('gemini-active-toggle');
    const geminiInput = document.getElementById('ai-gemini-key');
    const chatgptInput = document.getElementById('ai-chatgpt-key');

    if (e.target.checked) {
      if (geminiToggle) geminiToggle.checked = false;
      if (geminiInput) geminiInput.disabled = true;
      if (chatgptInput) chatgptInput.disabled = false;
      setActiveProvider('chatgpt');
    } else {
      if (geminiInput) geminiInput.disabled = false;
      if (chatgptInput) chatgptInput.disabled = false;
      setActiveProvider('inbuilt');
    }
  });

  switchTab('overview');
  loadApiKeys();

  container.addEventListener('DOMNodeRemoved', (e) => {
    if (e.target === container && fetchInterval) {
      clearInterval(fetchInterval);
    }
  });
}
