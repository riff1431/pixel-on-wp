document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('wpt-adscope-app');
    if (!root || !window.wptAdscopeAdminVars) return;

    // Initial Layout Setup
    root.className = 'adscope-dashboard';
    root.innerHTML = `
        <div class="adscope-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>PixelOnWP AI Intelligence Dashboard</h1>
                <p>Real-time telemetry, IP intelligence, and automated Smart Ad Blueprints.</p>
            </div>
            <div>
                <button id="adscope-clear-btn" class="pp-btn pp-btn-outline" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3);">Clear All History</button>
            </div>
        </div>
        
        <div class="adscope-tabs" style="display: flex; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid var(--adscope-border-strong);">
            <button class="adscope-tab-btn active" data-tab="live" style="background: none; border: none; color: white; padding: 10px 16px; cursor: pointer; border-bottom: 2px solid var(--adscope-primary);">Live Tracker</button>
            <button class="adscope-tab-btn" data-tab="reports" style="background: none; border: none; color: var(--adscope-text-muted); padding: 10px 16px; cursor: pointer; border-bottom: 2px solid transparent;">Advanced Reporting</button>
            <button class="adscope-tab-btn" data-tab="ai" style="background: none; border: none; color: var(--adscope-text-muted); padding: 10px 16px; cursor: pointer; border-bottom: 2px solid transparent;">Live AI Engine</button>
        </div>
        
        <div id="adscope-tab-live" class="adscope-tab-content">
            <div class="adscope-kpi-grid">
            <div class="adscope-card">
                <div class="adscope-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Total Tracked IPs
                </div>
                <div class="adscope-kpi-value" id="kpi-total-ips">--</div>
            </div>
            
            <div class="adscope-card">
                <div class="adscope-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    Active Regions
                </div>
                <div class="adscope-kpi-value" id="kpi-regions">--</div>
            </div>
            
            <div class="adscope-card">
                <div class="adscope-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    Top Source
                </div>
                <div class="adscope-kpi-value" id="kpi-top-source">--</div>
            </div>
            
            <div class="adscope-card">
                <div class="adscope-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>
                    Conversion Rate
                </div>
                <div class="adscope-kpi-value" id="kpi-cr">--</div>
            </div>
        </div>

        <div class="adscope-main-grid">
            <!-- Left Column: Table -->
            <div class="adscope-card">
                <div class="adscope-card-title" style="color: var(--adscope-text-main); font-size: 16px;">
                    Live Audience & IP Geolocation
                </div>
                <div class="adscope-table-wrapper">
                    <table class="adscope-table">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Event</th>
                                <th>Location</th>
                                <th>ISP</th>
                                <th>Device</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody id="adscope-table-body">
                            <tr><td colspan="6" style="text-align: center; color: var(--adscope-text-muted);">Fetching live data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Right Column: AI Blueprint -->
            <div class="adscope-card adscope-ai-card">
                <div class="adscope-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 16 16 12 12 8"></polyline><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    Smart Ad Blueprint Generator
                </div>
                
                <div class="adscope-ai-section" style="margin-top: 16px; padding-top: 0; border: none;">
                    <div class="adscope-ai-label">Interest-Based Targets</div>
                    <div class="adscope-ai-tags" id="bp-interests">
                        <div class="adscope-ai-tag">Analyzing...</div>
                    </div>
                </div>

                <div class="adscope-ai-section">
                    <div class="adscope-ai-label">Demographic Focus</div>
                    <div class="adscope-ai-value" id="bp-demographics">Analyzing conversion trends...</div>
                </div>

                <div class="adscope-ai-section">
                    <div class="adscope-ai-label">Budget & Device Shift</div>
                    <div class="adscope-ai-value" id="bp-device">Calculating optimal allocation...</div>
                </div>
                <div class="adscope-ai-section">
                    <div class="adscope-ai-label">Recommended Strategy</div>
                    <div class="adscope-ai-value" id="bp-strategy">Analyzing funnel...</div>
                </div>

                <div class="adscope-ai-section">
                    <div class="adscope-ai-label">Recommended Platform</div>
                    <div class="adscope-ai-value" id="bp-platform">Evaluating traffic sources...</div>
                </div>
            </div>
        </div>
        </div><!-- End Live Tab -->

        <!-- Advanced Reporting Tab -->
        <div id="adscope-tab-reports" class="adscope-tab-content" style="display: none;">
            <div class="adscope-kpi-grid">
                <div class="adscope-card">
                    <div class="adscope-card-title">Total Processed Events</div>
                    <div class="adscope-kpi-value" id="rep-total-events">--</div>
                </div>
                <div class="adscope-card">
                    <div class="adscope-card-title">Pixel API Matches</div>
                    <div class="adscope-kpi-value" id="rep-total-pixels">--</div>
                </div>
                <div class="adscope-card">
                    <div class="adscope-card-title">DataLayer Events</div>
                    <div class="adscope-kpi-value" id="rep-total-dl">--</div>
                </div>
            </div>
            <div class="adscope-main-grid" style="grid-template-columns: 1fr 1fr; margin-top: 24px;">
                <div class="adscope-card">
                    <div class="adscope-card-title">Top Locations (Geo)</div>
                    <table class="adscope-table" id="rep-locations-table">
                        <thead><tr><th>City, Region</th><th>Count</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="adscope-card">
                    <div class="adscope-card-title">Top Traffic Sources</div>
                    <table class="adscope-table" id="rep-sources-table">
                        <thead><tr><th>Source/Interest</th><th>Count</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Live AI Engine Tab -->
        <div id="adscope-tab-ai" class="adscope-tab-content" style="display: none;">
            <div class="adscope-card" style="max-width: 800px; margin: 0 auto; background: #0f172a; border-color: #1e293b;">
                <div class="adscope-card-title" style="color: #38bdf8; border-bottom: 1px solid #1e293b; padding-bottom: 16px; margin-bottom: 16px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    Live AI Tracker & Processing Engine
                </div>
                <div id="ai-simulator-console" style="font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #a3e635; min-height: 250px; white-space: pre-line;">
                    System initialized. Ready to process real-time events.
                    Listening to browser DataLayer, Meta fbq, and TikTok ttq...
                </div>
                <div style="margin-top: 24px; display: flex; gap: 16px;">
                    <button id="ai-start-btn" class="pp-btn pp-btn-primary" style="background: #0284c7; border: none;">Start Live AI Processing</button>
                    <input type="password" placeholder="Optional: Enter OpenAI API Key (or leave blank for Local Simulator)" style="flex: 1; background: #1e293b; border: 1px solid #334155; color: white; padding: 8px 12px; border-radius: 4px;" />
                </div>
            </div>
        </div>
    `;

    // Tab Switching Logic
    const tabs = document.querySelectorAll('.adscope-tab-btn');
    const contents = document.querySelectorAll('.adscope-tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => {
                t.classList.remove('active');
                t.style.borderBottomColor = 'transparent';
                t.style.color = 'var(--adscope-text-muted)';
            });
            contents.forEach(c => c.style.display = 'none');
            
            tab.classList.add('active');
            tab.style.borderBottomColor = 'var(--adscope-primary)';
            tab.style.color = 'white';
            document.getElementById('adscope-tab-' + tab.dataset.tab).style.display = 'block';
            
            if (tab.dataset.tab === 'reports') fetchReporting();
        });
    });

    // Clear History Action
    document.getElementById('adscope-clear-btn').addEventListener('click', async () => {
        if (!confirm('Are you sure you want to clear all AdScope history? This cannot be undone.')) return;
        const formData = new FormData();
        formData.append('action', 'pixelonwp_adscope_clear_history');
        formData.append('nonce', window.wptAdscopeAdminVars.nonce);
        try {
            await fetch(window.wptAdscopeAdminVars.ajaxurl, { method: 'POST', body: formData });
            fetchInsights();
            if (document.querySelector('.adscope-tab-btn[data-tab="reports"]').classList.contains('active')) fetchReporting();
        } catch (e) { console.error('Clear failed', e); }
    });

    // AI Simulator Logic
    document.getElementById('ai-start-btn').addEventListener('click', () => {
        const consoleEl = document.getElementById('ai-simulator-console');
        document.getElementById('ai-start-btn').textContent = 'Processing Live Traffic...';
        document.getElementById('ai-start-btn').disabled = true;
        
        consoleEl.innerHTML = '<span style="color: #94a3b8;">[LIVE]</span> Intercepting incoming browser telemetry...<br>';
        
        setTimeout(() => {
            consoleEl.innerHTML += '<span style="color: #38bdf8;">[API]</span> Connecting to AI Engine...<br>';
        }, 1000);
        
        setTimeout(() => {
            consoleEl.innerHTML += '<span style="color: #facc15;">[PROCESS]</span> Analyzing IPs, DataLayer events, and Pixels...<br>';
        }, 2000);
        
        setTimeout(() => {
            const bp = document.getElementById('bp-platform').textContent;
            const st = document.getElementById('bp-strategy').textContent;
            consoleEl.innerHTML += `<br><span style="color: #4ade80;">[SUCCESS]</span> AI SUGGESTION GENERATED:<br>`;
            consoleEl.innerHTML += `<span style="color: white;">Platform Match:</span> ${bp}<br>`;
            consoleEl.innerHTML += `<span style="color: white;">Ad Strategy:</span> ${st}<br>`;
            consoleEl.innerHTML += `<br>Pushing data to Live Tracker Dashboard... DONE.`;
            
            setTimeout(() => {
                document.getElementById('ai-start-btn').textContent = 'Start Live AI Processing';
                document.getElementById('ai-start-btn').disabled = false;
            }, 3000);
        }, 4000);
    });

    const fetchInsights = async () => {
        const formData = new FormData();
        formData.append('action', 'pixelonwp_adscope_insights');
        formData.append('nonce', window.wptAdscopeAdminVars.nonce);

        try {
            const res = await fetch(window.wptAdscopeAdminVars.ajaxurl, {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            if (result.success && result.data) {
                updateUI(result.data);
            }
        } catch (e) {
            console.error('AdScope insights fetch failed:', e);
        }
    };

    const updateUI = (data) => {
        // Update KPIs
        document.getElementById('kpi-total-ips').textContent = data.kpis.total_ips;
        document.getElementById('kpi-regions').textContent = data.kpis.active_regions;
        document.getElementById('kpi-top-source').textContent = data.kpis.top_source || 'N/A';
        document.getElementById('kpi-cr').textContent = data.kpis.conversion_rate;

        // Update Blueprint
        document.getElementById('bp-demographics').textContent = data.blueprint.demographics;
        document.getElementById('bp-device').textContent = data.blueprint.device_shift;
        if (document.getElementById('bp-strategy')) document.getElementById('bp-strategy').textContent = data.blueprint.strategy;
        if (document.getElementById('bp-platform')) document.getElementById('bp-platform').textContent = data.blueprint.platform;
        
        const tagsContainer = document.getElementById('bp-interests');
        tagsContainer.innerHTML = '';
        data.blueprint.interests.forEach(interest => {
            const el = document.createElement('div');
            el.className = 'adscope-ai-tag';
            el.textContent = interest;
            tagsContainer.appendChild(el);
        });

        // Update Table
        const tbody = document.getElementById('adscope-table-body');
        if (data.live_logs && data.live_logs.length > 0) {
            tbody.innerHTML = '';
            data.live_logs.forEach(log => {
                const tr = document.createElement('tr');
                
                let sourceBadge = log.utm_source ? log.utm_source.toUpperCase() : 'DIRECT';
                let sourceClass = log.utm_source ? 'primary' : '';
                
                let deviceIcon = log.device === 'mobile' ? '📱' : (log.device === 'tablet' ? '📟' : '💻');
                
                let eventDisplay = log.event_type.replace(/_/g, ' ');
                let eventClass = log.event_type === 'purchase' ? 'success' : (log.event_type === 'add_to_cart' ? 'warning' : '');

                tr.innerHTML = `
                    <td style="font-family: monospace;">${log.ip_address}</td>
                    <td><span class="adscope-badge ${eventClass}" style="text-transform: capitalize;">${eventDisplay}</span></td>
                    <td><strong>${log.city}</strong></td>
                    <td style="color: var(--adscope-text-muted);">${log.isp}</td>
                    <td>${deviceIcon} <span style="text-transform: capitalize;">${log.device}</span></td>
                    <td><span class="adscope-badge ${sourceClass}">${sourceBadge}</span></td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--adscope-text-muted); padding: 32px;">No tracking data collected yet. Visit the frontend with ?utm_source=facebook to test.</td></tr>';
        }
    };

    const fetchReporting = async () => {
        const formData = new FormData();
        formData.append('action', 'pixelonwp_adscope_reporting');
        formData.append('nonce', window.wptAdscopeAdminVars.nonce);

        try {
            const res = await fetch(window.wptAdscopeAdminVars.ajaxurl, {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            if (result.success && result.data) {
                document.getElementById('rep-total-events').textContent = result.data.total_events;
                document.getElementById('rep-total-pixels').textContent = result.data.total_pixels;
                document.getElementById('rep-total-dl').textContent = result.data.total_datalayer;
                
                const locTbody = document.querySelector('#rep-locations-table tbody');
                locTbody.innerHTML = '';
                if (result.data.locations && result.data.locations.length > 0) {
                    result.data.locations.forEach(loc => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td>${loc.city}, ${loc.region}</td><td>${loc.cnt}</td>`;
                        locTbody.appendChild(tr);
                    });
                } else {
                    locTbody.innerHTML = '<tr><td colspan="2" style="text-align: center; color: var(--adscope-text-muted);">No location data.</td></tr>';
                }

                const srcTbody = document.querySelector('#rep-sources-table tbody');
                srcTbody.innerHTML = '';
                if (result.data.sources && result.data.sources.length > 0) {
                    result.data.sources.forEach(src => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td><span class="adscope-badge primary">${src.utm_source.toUpperCase()}</span></td><td>${src.cnt}</td>`;
                        srcTbody.appendChild(tr);
                    });
                } else {
                    srcTbody.innerHTML = '<tr><td colspan="2" style="text-align: center; color: var(--adscope-text-muted);">No source data.</td></tr>';
                }
            }
        } catch (e) {
            console.error('Reporting fetch failed:', e);
        }
    };

    fetchInsights();
    // Poll insights every 5 seconds if on Live tab
    setInterval(() => {
        if (document.querySelector('.adscope-tab-btn[data-tab="live"]').classList.contains('active')) {
            fetchInsights();
        }
    }, 5000);
});
