export function renderCookieConsent(container, state) {
  const defaultConsent = {
    enabled: false,
    mode: 'strict', // strict, optout, notice, custom
    cm_v2: true,
    fallback_behavior: 'strict',
    geo_engine: 'cloudflare', // cloudflare, maxmind, native
    geo_rules: [], // array of { region_name, countries[], banner_behavior, default_state, reject_btn }
    scripts: [], // array of { id, name, type, content, category, trigger }
    banner: {
      layout: 'floating_bottom',
      title: 'Privacy Preferences',
      description: 'We use cookies to improve your experience. Select your preferences below.',
      policy_url: '/privacy-policy',
      btn_accept: 'Accept All',
      btn_reject: 'Reject All',
      btn_prefs: 'Cookie Settings',
      btn_save: 'Save My Preferences',
      color_bg: '#1e293b',
      color_text: '#f8fafc',
      color_btn: '#3b82f6',
      custom_css: '',
      expiry_days: 365,
      cat_necessary_title: 'Strictly Necessary',
      cat_necessary_desc: 'These cookies are essential for the website to function properly and cannot be disabled.',
      cat_analytics_title: 'Analytics & Performance',
      cat_analytics_desc: 'These cookies allow us to count visits and traffic sources so we can measure and improve the performance of our site.',
      cat_marketing_title: 'Marketing & Targeting',
      cat_marketing_desc: 'These cookies may be set through our site by our advertising partners to build a profile of your interests and show you relevant adverts on other sites.',
      cat_functional_title: 'Functional & Preferences',
      cat_functional_desc: 'These cookies enable the website to provide enhanced functionality and personalization.'
    },
    ecommerce: {
      whitelist_cart: true
    },
    logs: {
      enabled: false
    }
  };

  const config = state.config?.cookie_consent || defaultConsent;
  if (!config.geo_rules) config.geo_rules = [];
  if (!config.scripts) config.scripts = [];

  const dashboardGrid = document.createElement('div');
  dashboardGrid.className = 'pp-grid-sidebar-left';
  dashboardGrid.style.marginBottom = '40px';

  // Responsive logic
  const style = document.createElement('style');
  style.innerHTML = `
    .cc-tab-menu { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; }
    .cc-tab-btn { padding: 12px 16px; background: transparent; border: 1px solid transparent; color: var(--pp-text-muted); text-align: left; border-radius: 8px; cursor: pointer; transition: all 0.2s; font-weight: 500; }
    .cc-tab-btn:hover { background: rgba(255,255,255,0.05); color: var(--pp-text-main); }
    .cc-tab-btn.active { background: rgba(59, 130, 246, 0.1); color: var(--pp-primary); border-color: rgba(59, 130, 246, 0.2); font-weight: 600; }
    .cc-tab-content { display: none; }
    .cc-tab-content.active { display: block; animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    
    .pp-toggle { display: inline-flex; align-items: center; cursor: pointer; }
    .pp-toggle-input { display: none !important; }
    .pp-toggle-slider { width: 44px; height: 24px; background-color: var(--pp-border); border-radius: 24px; position: relative; transition: 0.3s; }
    .pp-toggle-slider::before { content: ""; position: absolute; width: 18px; height: 18px; border-radius: 50%; background-color: white; top: 3px; left: 3px; transition: 0.3s; }
    .pp-toggle-input:checked + .pp-toggle-slider { background-color: var(--pp-primary); }
    .pp-toggle-input:checked + .pp-toggle-slider::before { transform: translateX(20px); }
    
    .cc-repeater-row { background: rgba(255,255,255,0.02); border: 1px solid var(--pp-border); border-radius: 8px; padding: 16px; margin-bottom: 16px; position: relative; }
    .cc-repeater-remove { position: absolute; top: 16px; right: 16px; color: var(--pp-danger); cursor: pointer; background: transparent; border: none; padding: 4px; }
  `;
  document.head.appendChild(style);

  // Left Sidebar
  const sidebar = document.createElement('div');
  sidebar.innerHTML = `
    <ul class="cc-tab-menu">
      <li><button class="cc-tab-btn active" data-target="tab-general">Global Settings</button></li>
      <li><button class="cc-tab-btn" data-target="tab-geo">Geo-Targeting Matrix</button></li>
      <li><button class="cc-tab-btn" data-target="tab-scripts">Script Manager</button></li>
      <li><button class="cc-tab-btn" data-target="tab-banner">Banner Studio</button></li>
      <li><button class="cc-tab-btn" data-target="tab-ecom">E-Commerce</button></li>
      <li><button class="cc-tab-btn" data-target="tab-logs">Consent Logs</button></li>
    </ul>
  `;

  const contentArea = document.createElement('div');
  contentArea.className = 'pp-card';
  contentArea.style.padding = '32px';

  contentArea.innerHTML = `
    <form id="frm-cookie-consent">
      <!-- TAB 1: General -->
      <div id="tab-general" class="cc-tab-content active">
        <div style="margin-bottom: 24px;">
          <h3 style="margin-top: 0; margin-bottom: 8px; color: var(--pp-text-main);">Master Module Switch</h3>
          <label class="pp-toggle">
            <input type="checkbox" id="cc_enabled" class="pp-toggle-input" ${config.enabled ? 'checked' : ''}>
            <span class="pp-toggle-slider"></span>
            <span style="margin-left: 12px; font-weight: 500;">Enable Cookie Consent Suite</span>
          </label>
        </div>

        <div class="pp-grid-2col" style="margin-bottom: 24px;">
          <div>
            <label style="display: block; margin-bottom: 6px; font-weight: 500;">Global Mode</label>
            <select id="cc_mode" class="pp-input">
              <option value="strict" ${config.mode === 'strict' ? 'selected' : ''}>Strict Opt-In (GDPR Style)</option>
              <option value="optout" ${config.mode === 'optout' ? 'selected' : ''}>Opt-Out (CCPA Style)</option>
              <option value="notice" ${config.mode === 'notice' ? 'selected' : ''}>Notice Only</option>
              <option value="custom" ${config.mode === 'custom' ? 'selected' : ''}>Custom Manual Mode</option>
            </select>
          </div>
          <div>
            <label style="display: block; margin-bottom: 6px; font-weight: 500;">Fallback Behavior (Unknown Region)</label>
            <select id="cc_fallback" class="pp-input">
              <option value="strict" ${config.fallback_behavior === 'strict' ? 'selected' : ''}>Assume Strict Opt-In</option>
              <option value="optout" ${config.fallback_behavior === 'optout' ? 'selected' : ''}>Assume Opt-Out</option>
            </select>
          </div>
        </div>

        <div style="margin-bottom: 24px;">
          <h3 style="margin-top: 0; margin-bottom: 8px; color: var(--pp-text-main);">Google Advanced Consent Mode V2</h3>
          <p style="color: var(--pp-text-muted); font-size: 13px; margin-bottom: 12px;">Sends cookieless pings when consent is denied.</p>
          <label class="pp-toggle">
            <input type="checkbox" id="cc_cm_v2" class="pp-toggle-input" ${config.cm_v2 ? 'checked' : ''}>
            <span class="pp-toggle-slider"></span>
            <span style="margin-left: 12px; font-weight: 500;">Enable Advanced Consent Mode</span>
          </label>
        </div>
      </div>

      <!-- TAB 2: Geo Rules -->
      <div id="tab-geo" class="cc-tab-content">
        <div style="margin-bottom: 24px;">
           <label style="display: block; margin-bottom: 6px; font-weight: 500;">Geo-Detection Engine</label>
           <select id="cc_geo_engine" class="pp-input" style="max-width: 300px;">
             <option value="cloudflare" ${config.geo_engine === 'cloudflare' ? 'selected' : ''}>Cloudflare Headers (Fastest)</option>
             <option value="native" ${config.geo_engine === 'native' ? 'selected' : ''}>WordPress Native (Timezone/Locale)</option>
           </select>
        </div>
        
        <div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
          <h3 style="margin: 0; color: var(--pp-text-main);">Custom Geo-Rules Matrix</h3>
          <button type="button" id="btn-add-geo" class="pp-btn pp-btn-secondary" style="padding: 6px 12px; font-size: 12px;">+ Add Region Rule</button>
        </div>
        
        <div id="geo-rules-container"></div>
      </div>

      <!-- TAB 3: Script Manager -->
      <div id="tab-scripts" class="cc-tab-content">
        <div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h3 style="margin: 0; color: var(--pp-text-main);">Script & Pixel Blocker</h3>
            <p style="color: var(--pp-text-muted); font-size: 12px; margin-top: 4px;">Scripts are automatically blocked until category consent is granted.</p>
          </div>
          <button type="button" id="btn-add-script" class="pp-btn pp-btn-secondary" style="padding: 6px 12px; font-size: 12px;">+ Add Script</button>
        </div>
        <div id="scripts-container"></div>
      </div>

      <!-- TAB 4: Banner Studio -->
      <div id="tab-banner" class="cc-tab-content">
        <div class="pp-grid-2col">
          <div>
            <label style="display: block; margin-bottom: 6px; font-weight: 500;">Layout Engine</label>
            <select id="cc_b_layout" class="pp-input">
              <option value="floating_bottom" ${config.banner?.layout === 'floating_bottom' ? 'selected' : ''}>Floating Bottom Bar</option>
              <option value="center_modal" ${config.banner?.layout === 'center_modal' ? 'selected' : ''}>Center Modal Popup</option>
              <option value="corner_badge" ${config.banner?.layout === 'corner_badge' ? 'selected' : ''}>Floating Corner Badge</option>
            </select>
          </div>
          <div>
            <label style="display: block; margin-bottom: 6px; font-weight: 500;">Privacy Policy URL</label>
            <input type="text" id="cc_b_policy" class="pp-input" value="${config.banner?.policy_url || ''}">
          </div>
          <div>
            <label style="display: block; margin-bottom: 6px; font-weight: 500;">Cookie Expiry (Days)</label>
            <input type="number" id="cc_b_expiry_days" class="pp-input" value="${config.banner?.expiry_days || 365}" min="1" max="400">
            <p style="font-size: 11px; color: var(--pp-text-muted); margin-top: 4px;">Browsers limit max cookie lifespan to 400 days.</p>
          </div>
          <div style="grid-column: span 2;">
            <label style="display: block; margin-bottom: 6px; font-weight: 500;">Banner Title</label>
            <input type="text" id="cc_b_title" class="pp-input" value="${config.banner?.title || ''}">
          </div>
          <div style="grid-column: span 2;">
            <label style="display: block; margin-bottom: 6px; font-weight: 500;">Banner Description</label>
            <textarea id="cc_b_desc" class="pp-input" style="min-height: 80px;">${config.banner?.description || ''}</textarea>
          </div>
          
          <div>
            <label style="display: block; margin-bottom: 6px; font-weight: 500;">[Accept All] Text</label>
            <input type="text" id="cc_b_btn_accept" class="pp-input" value="${config.banner?.btn_accept || ''}">
          </div>
          <div>
            <label style="display: block; margin-bottom: 6px; font-weight: 500;">[Reject All] Text</label>
            <input type="text" id="cc_b_btn_reject" class="pp-input" value="${config.banner?.btn_reject || ''}">
          </div>
          <div>
            <label style="display: block; margin-bottom: 6px; font-weight: 500;">[Cookie Settings] Text</label>
            <input type="text" id="cc_b_btn_prefs" class="pp-input" value="${config.banner?.btn_prefs || ''}">
          </div>
          
          <div class="pp-grid-2col" style="grid-column: span 2; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--pp-border);">
            <div style="grid-column: span 2;"><h4 style="margin: 0; color: var(--pp-text-main);">Preference Center Content</h4></div>
            
            <div style="grid-column: span 2;">
              <label style="display: block; margin-bottom: 6px; font-weight: 500;">[Save Preferences] Button Text</label>
              <input type="text" id="cc_b_btn_save" class="pp-input" value="${config.banner?.btn_save || 'Save My Preferences'}">
            </div>

            <div>
              <label style="display: block; margin-bottom: 6px; font-weight: 500;">Necessary Title</label>
              <input type="text" id="cc_b_c_n_t" class="pp-input" value="${config.banner?.cat_necessary_title || 'Strictly Necessary'}">
            </div>
            <div>
              <label style="display: block; margin-bottom: 6px; font-weight: 500;">Necessary Description</label>
              <textarea id="cc_b_c_n_d" class="pp-input" style="min-height: 50px;">${config.banner?.cat_necessary_desc || 'These cookies are essential for the website to function properly.'}</textarea>
            </div>
            
            <div>
              <label style="display: block; margin-bottom: 6px; font-weight: 500;">Analytics Title</label>
              <input type="text" id="cc_b_c_a_t" class="pp-input" value="${config.banner?.cat_analytics_title || 'Analytics & Performance'}">
            </div>
            <div>
              <label style="display: block; margin-bottom: 6px; font-weight: 500;">Analytics Description</label>
              <textarea id="cc_b_c_a_d" class="pp-input" style="min-height: 50px;">${config.banner?.cat_analytics_desc || 'Cookies to measure and improve performance.'}</textarea>
            </div>
            
            <div>
              <label style="display: block; margin-bottom: 6px; font-weight: 500;">Marketing Title</label>
              <input type="text" id="cc_b_c_m_t" class="pp-input" value="${config.banner?.cat_marketing_title || 'Marketing & Targeting'}">
            </div>
            <div>
              <label style="display: block; margin-bottom: 6px; font-weight: 500;">Marketing Description</label>
              <textarea id="cc_b_c_m_d" class="pp-input" style="min-height: 50px;">${config.banner?.cat_marketing_desc || 'Cookies set by advertising partners to show relevant adverts.'}</textarea>
            </div>
            
            <div>
              <label style="display: block; margin-bottom: 6px; font-weight: 500;">Functional Title</label>
              <input type="text" id="cc_b_c_f_t" class="pp-input" value="${config.banner?.cat_functional_title || 'Functional & Preferences'}">
            </div>
            <div>
              <label style="display: block; margin-bottom: 6px; font-weight: 500;">Functional Description</label>
              <textarea id="cc_b_c_f_d" class="pp-input" style="min-height: 50px;">${config.banner?.cat_functional_desc || 'Cookies for enhanced functionality and personalization.'}</textarea>
            </div>
          </div>
          
          <div style="grid-column: span 2; display: flex; gap: 24px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--pp-border);">
            <div>
              <label style="display: block; margin-bottom: 6px; font-size: 12px;">Background Color</label>
              <input type="color" id="cc_b_color_bg" class="pp-input" style="width: 60px; padding: 2px;" value="${config.banner?.color_bg || '#1e293b'}">
            </div>
            <div>
              <label style="display: block; margin-bottom: 6px; font-size: 12px;">Text Color</label>
              <input type="color" id="cc_b_color_text" class="pp-input" style="width: 60px; padding: 2px;" value="${config.banner?.color_text || '#f8fafc'}">
            </div>
            <div>
              <label style="display: block; margin-bottom: 6px; font-size: 12px;">Button Color</label>
              <input type="color" id="cc_b_color_btn" class="pp-input" style="width: 60px; padding: 2px;" value="${config.banner?.color_btn || '#3b82f6'}">
            </div>
          </div>
          
          <div style="grid-column: span 2;">
            <label style="display: block; margin-bottom: 6px; font-weight: 500;">Advanced CSS Injection</label>
            <textarea id="cc_b_custom_css" class="pp-input" style="min-height: 100px; font-family: monospace; font-size: 12px;" placeholder=".pp-cookie-banner { border: 1px solid red; }">${config.banner?.custom_css || ''}</textarea>
          </div>
        </div>
      </div>

      <!-- TAB 5: E-Commerce -->
      <div id="tab-ecom" class="cc-tab-content">
        <div style="margin-bottom: 24px;">
          <h3 style="margin-top: 0; margin-bottom: 8px; color: var(--pp-text-main);">WooCommerce Cart Protection</h3>
          <p style="color: var(--pp-text-muted); font-size: 13px; margin-bottom: 12px;">Permanently exempts essential session cookies (e.g. <code>wp_woocommerce_session_*</code>) from being blocked.</p>
          <label class="pp-toggle">
            <input type="checkbox" id="cc_ecom_whitelist" class="pp-toggle-input" ${config.ecommerce?.whitelist_cart !== false ? 'checked' : ''}>
            <span class="pp-toggle-slider"></span>
            <span style="margin-left: 12px; font-weight: 500;">Whitelist WooCommerce Session Cookies</span>
          </label>
        </div>
      </div>

      <!-- TAB 6: Logs -->
      <div id="tab-logs" class="cc-tab-content">
        <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h3 style="margin-top: 0; margin-bottom: 8px; color: var(--pp-text-main);">Consent Audit Trail</h3>
            <p style="color: var(--pp-text-muted); font-size: 13px; margin-bottom: 0;">Record anonymized consent proofs for legal compliance.</p>
          </div>
          <label class="pp-toggle">
            <input type="checkbox" id="cc_logs_enabled" class="pp-toggle-input" ${config.logs?.enabled ? 'checked' : ''}>
            <span class="pp-toggle-slider"></span>
            <span style="margin-left: 12px; font-weight: 500;">Enable Logging</span>
          </label>
        </div>
        
        <div style="border-top: 1px solid var(--pp-border); padding-top: 24px;">
          <button type="button" id="btn-export-logs" class="pp-btn pp-btn-secondary">Download CSV Export</button>
        </div>
      </div>

      <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--pp-border); display: flex; justify-content: flex-end;">
        <button type="button" id="btn-save-consent" class="pp-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
          Save Configuration
        </button>
      </div>
    </form>
  `;

  dashboardGrid.appendChild(sidebar);
  dashboardGrid.appendChild(contentArea);
  container.appendChild(dashboardGrid);

  // Tab switching logic
  const tabBtns = container.querySelectorAll('.cc-tab-btn');
  const tabContents = container.querySelectorAll('.cc-tab-content');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      tabBtns.forEach(b => b.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      container.querySelector('#' + btn.getAttribute('data-target')).classList.add('active');
    });
  });

  // Dynamic Repeaters: Geo Rules
  let geoRules = [...config.geo_rules];
  const geoContainer = document.getElementById('geo-rules-container');
  
  function renderGeoRules() {
    geoContainer.innerHTML = '';
    if (geoRules.length === 0) {
      geoContainer.innerHTML = '<p style="color:var(--pp-text-muted); font-size:13px;">No geo-rules added. Fallback behavior will apply everywhere.</p>';
      return;
    }
    geoRules.forEach((rule, index) => {
      const div = document.createElement('div');
      div.className = 'cc-repeater-row';
      div.innerHTML = `
        <button type="button" class="cc-repeater-remove" data-index="${index}">✖</button>
        <div class="pp-grid-2col" style="gap: 16px;">
          <div>
            <label style="display: block; margin-bottom: 4px; font-size: 12px;">Region Name</label>
            <input type="text" class="pp-input g-name" value="${rule.region_name || ''}" placeholder="e.g. European Union">
          </div>
          <div>
            <label style="display: block; margin-bottom: 4px; font-size: 12px;">Countries (Comma separated codes)</label>
            <input type="text" class="pp-input g-countries" value="${(rule.countries || []).join(', ')}" placeholder="AT, BE, BG, HR, CY...">
          </div>
          <div>
            <label style="display: block; margin-bottom: 4px; font-size: 12px;">Banner Behavior</label>
            <select class="pp-input g-behavior">
              <option value="opt_in" ${rule.banner_behavior === 'opt_in' ? 'selected' : ''}>Strict Opt-In</option>
              <option value="opt_out" ${rule.banner_behavior === 'opt_out' ? 'selected' : ''}>Opt-Out (Notice)</option>
              <option value="hidden" ${rule.banner_behavior === 'hidden' ? 'selected' : ''}>Hidden</option>
            </select>
          </div>
          <div>
            <label style="display: block; margin-bottom: 4px; font-size: 12px;">Show Reject Button</label>
            <select class="pp-input g-reject">
              <option value="yes" ${rule.reject_btn === 'yes' ? 'selected' : ''}>Yes</option>
              <option value="no" ${rule.reject_btn === 'no' ? 'selected' : ''}>No</option>
            </select>
          </div>
        </div>
      `;
      geoContainer.appendChild(div);
    });
    
    geoContainer.querySelectorAll('.cc-repeater-remove').forEach(btn => {
      btn.addEventListener('click', (e) => {
        geoRules.splice(parseInt(e.target.dataset.index, 10), 1);
        renderGeoRules();
      });
    });
  }
  
  document.getElementById('btn-add-geo').addEventListener('click', () => {
    geoRules.push({ region_name: '', countries: [], banner_behavior: 'opt_in', reject_btn: 'yes' });
    renderGeoRules();
  });
  renderGeoRules();

  // Dynamic Repeaters: Scripts
  let scriptsList = [...config.scripts];
  const scriptsContainer = document.getElementById('scripts-container');
  
  function renderScripts() {
    scriptsContainer.innerHTML = '';
    if (scriptsList.length === 0) {
      scriptsContainer.innerHTML = '<p style="color:var(--pp-text-muted); font-size:13px;">No custom scripts defined.</p>';
      return;
    }
    scriptsList.forEach((script, index) => {
      const div = document.createElement('div');
      div.className = 'cc-repeater-row';
      div.innerHTML = `
        <button type="button" class="cc-repeater-remove" data-index="${index}">✖</button>
        <div class="pp-grid-2col" style="gap: 16px;">
          <div>
            <label style="display: block; margin-bottom: 4px; font-size: 12px;">Script Name</label>
            <input type="text" class="pp-input s-name" value="${script.name || ''}" placeholder="e.g. Custom Tracking Pixel">
          </div>
          <div>
            <label style="display: block; margin-bottom: 4px; font-size: 12px;">Category Mapping</label>
            <select class="pp-input s-cat">
              <option value="necessary" ${script.category === 'necessary' ? 'selected' : ''}>Strictly Necessary</option>
              <option value="analytics" ${script.category === 'analytics' ? 'selected' : ''}>Analytics</option>
              <option value="marketing" ${script.category === 'marketing' ? 'selected' : ''}>Marketing</option>
              <option value="functional" ${script.category === 'functional' ? 'selected' : ''}>Functional</option>
            </select>
          </div>
          <div style="grid-column: span 2;">
            <label style="display: block; margin-bottom: 4px; font-size: 12px;">Execution Location</label>
            <select class="pp-input s-loc">
              <option value="head" ${script.location === 'head' ? 'selected' : ''}>&lt;head&gt;</option>
              <option value="body" ${script.location === 'body' ? 'selected' : ''}>&lt;body&gt; (Top)</option>
              <option value="footer" ${script.location === 'footer' ? 'selected' : ''}>Footer</option>
            </select>
          </div>
          <div style="grid-column: span 2;">
            <label style="display: block; margin-bottom: 4px; font-size: 12px;">Raw Script / HTML</label>
            <textarea class="pp-input s-content" style="min-height: 80px; font-family: monospace; font-size: 12px;" placeholder="<script>...</script>">${script.content || ''}</textarea>
          </div>
        </div>
      `;
      scriptsContainer.appendChild(div);
    });
    
    scriptsContainer.querySelectorAll('.cc-repeater-remove').forEach(btn => {
      btn.addEventListener('click', (e) => {
        scriptsList.splice(parseInt(e.target.dataset.index, 10), 1);
        renderScripts();
      });
    });
  }

  document.getElementById('btn-add-script').addEventListener('click', () => {
    scriptsList.push({ name: '', category: 'marketing', location: 'head', content: '' });
    renderScripts();
  });
  renderScripts();

  // Export Logs CSV
  document.getElementById('btn-export-logs').addEventListener('click', async () => {
    window.location.href = window.pixelonwp_admin_vars.ajaxurl + '?action=pixelonwp_export_consent_logs&nonce=' + window.pixelonwp_admin_vars.nonce;
  });

  // Save Config
  document.getElementById('btn-save-consent').addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="pp-spinner"></span> Saving...';
    btn.disabled = true;

    try {
      // Gather dynamic rows
      const finalGeo = [];
      geoContainer.querySelectorAll('.cc-repeater-row').forEach(row => {
        finalGeo.push({
          region_name: row.querySelector('.g-name').value,
          countries: row.querySelector('.g-countries').value.split(',').map(s=>s.trim()).filter(Boolean),
          banner_behavior: row.querySelector('.g-behavior').value,
          reject_btn: row.querySelector('.g-reject').value
        });
      });

      const finalScripts = [];
      scriptsContainer.querySelectorAll('.cc-repeater-row').forEach(row => {
        finalScripts.push({
          name: row.querySelector('.s-name').value,
          category: row.querySelector('.s-cat').value,
          location: row.querySelector('.s-loc').value,
          content: row.querySelector('.s-content').value
        });
      });

      const payload = {
        enabled: document.getElementById('cc_enabled').checked,
        mode: document.getElementById('cc_mode').value,
        cm_v2: document.getElementById('cc_cm_v2').checked,
        fallback_behavior: document.getElementById('cc_fallback').value,
        geo_engine: document.getElementById('cc_geo_engine').value,
        geo_rules: finalGeo,
        scripts: finalScripts,
        banner: {
          layout: document.getElementById('cc_b_layout').value,
          policy_url: document.getElementById('cc_b_policy').value,
          title: document.getElementById('cc_b_title').value,
          description: document.getElementById('cc_b_desc').value,
          btn_accept: document.getElementById('cc_b_btn_accept').value,
          btn_reject: document.getElementById('cc_b_btn_reject').value,
          btn_prefs: document.getElementById('cc_b_btn_prefs').value,
          btn_save: document.getElementById('cc_b_btn_save').value,
          cat_necessary_title: document.getElementById('cc_b_c_n_t').value,
          cat_necessary_desc: document.getElementById('cc_b_c_n_d').value,
          cat_analytics_title: document.getElementById('cc_b_c_a_t').value,
          cat_analytics_desc: document.getElementById('cc_b_c_a_d').value,
          cat_marketing_title: document.getElementById('cc_b_c_m_t').value,
          cat_marketing_desc: document.getElementById('cc_b_c_m_d').value,
          cat_functional_title: document.getElementById('cc_b_c_f_t').value,
          cat_functional_desc: document.getElementById('cc_b_c_f_d').value,
          color_bg: document.getElementById('cc_b_color_bg').value,
          color_text: document.getElementById('cc_b_color_text').value,
          color_btn: document.getElementById('cc_b_color_btn').value,
          custom_css: document.getElementById('cc_b_custom_css').value,
          expiry_days: document.getElementById('cc_b_expiry_days').value
        },
        ecommerce: {
          whitelist_cart: document.getElementById('cc_ecom_whitelist').checked
        },
        logs: {
          enabled: document.getElementById('cc_logs_enabled').checked
        }
      };

      const fd = new FormData();
      fd.append('action', 'pixelonwp_save_cookie_consent');
      fd.append('nonce', window.pixelonwp_admin_vars.nonce);
      fd.append('payload', JSON.stringify(payload));

      const response = await fetch(window.pixelonwp_admin_vars.ajaxurl, {
        method: 'POST',
        body: fd
      });

      const res = await response.json();
      if (res.success) {
        window.PixelOnWP.showToast('Compliance settings saved!', 'success');
        if (!state.config) state.config = {};
        state.config.cookie_consent = payload;
      } else {
        window.PixelOnWP.showToast(res.data.message || 'Error saving.', 'error');
      }
    } catch (err) {
      console.error(err);
      window.PixelOnWP.showToast('Network error while saving.', 'error');
    } finally {
      btn.innerHTML = originalText;
      btn.disabled = false;
    }
  });
}
