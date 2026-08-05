import { showToast } from '../components/toaster.js';

export function renderEvents(container, state) {
  const header = document.createElement('div');
  header.className = 'pp-view-header';
  header.innerHTML = `
    <h2>Event Manager</h2>
    <p>Monitor your active tracking events across all platforms in real-time.</p>
  `;

  // Search and Filter Bar
  const filterBar = document.createElement('div');
  filterBar.style.display = 'flex';
  filterBar.style.gap = '16px';
  filterBar.style.marginBottom = '24px';
  filterBar.style.alignItems = 'center';

  filterBar.innerHTML = `
    <div class="pp-search-bar" style="flex:1; margin-bottom: 0; background: rgba(0,0,0,0.2);">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
      <input type="text" placeholder="Search events instantly...">
    </div>
  `;

  const tabs = document.createElement('div');
  tabs.className = 'pp-tabs';
  tabs.style.marginBottom = '24px';
  tabs.style.borderBottom = '1px solid var(--pp-border)';
  tabs.innerHTML = `
    <button class="pp-tab active" data-platform="facebook" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-primary); border-bottom: 2px solid var(--pp-primary); font-weight: 600; cursor: pointer;">Meta (Facebook)</button>
    <button class="pp-tab" data-platform="tiktok" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-text-muted); font-weight: 500; cursor: pointer;">TikTok</button>
    <button class="pp-tab" data-platform="reddit" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-text-muted); font-weight: 500; cursor: pointer;">Reddit</button>
    <button class="pp-tab" data-platform="pinterest" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-text-muted); font-weight: 500; cursor: pointer;">Pinterest</button>
  `;

  const tableContainer = document.createElement('div');
  tableContainer.className = 'pp-table-container pp-card';
  tableContainer.style.padding = '0';
  tableContainer.innerHTML = `
    <table class="pp-table" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr style="border-bottom: 1px solid var(--pp-border); text-align: left; background: rgba(0,0,0,0.2);">
          <th style="padding: 16px 24px; color: var(--pp-text-muted); font-size: 12px; text-transform: uppercase;">Event Name</th>
          <th style="padding: 16px 24px; color: var(--pp-text-muted); font-size: 12px; text-transform: uppercase;">Status</th>
          <th style="padding: 16px 24px; color: var(--pp-text-muted); font-size: 12px; text-transform: uppercase;">Match Rate</th>
          <th style="padding: 16px 24px; color: var(--pp-text-muted); font-size: 12px; text-transform: uppercase;">Last Trigger</th>
        </tr>
      </thead>
      <tbody id="pp-events-tbody">
        <tr>
          <td colspan="4" style="padding: 32px; text-align: center; color: var(--pp-text-muted);">Fetching live event data...</td>
        </tr>
      </tbody>
    </table>
  `;

  container.appendChild(header);
  container.appendChild(filterBar);
  container.appendChild(tabs);
  container.appendChild(tableContainer);

  let categoriesData = [];
  let currentPlatform = 'facebook';

  const toggleEventStatus = async (eventName, state) => {
    const formData = new FormData();
    formData.append('action', 'PixelOnWP_toggle_event_state');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('event_name', eventName);
    formData.append('state', state ? '1' : '0');
    try {
      await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      showToast({ message: `${eventName} is now ${state ? 'Enabled' : 'Disabled'}`, type: state ? 'success' : 'info', title: 'Event Updated' });
      fetchEventsData(); // Refresh UI
    } catch (e) {
      showToast({ message: `Failed to update ${eventName}`, type: 'error' });
      console.warn('Failed to toggle event state', e);
    }
  };

  const toggleEventParamStatus = async (eventName, paramName, state) => {
    const formData = new FormData();
    formData.append('action', 'PixelOnWP_toggle_event_param_state');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);
    formData.append('event_name', eventName);
    formData.append('param_name', paramName);
    formData.append('state', state ? '1' : '0');
    try {
      await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
    } catch (e) {
      console.warn('Failed to toggle event param state', e);
    }
  };

  const renderTable = () => {
    const tbody = tableContainer.querySelector('#pp-events-tbody');
    tbody.innerHTML = '';

    if (categoriesData.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4" style="padding: 32px; text-align: center; color: var(--pp-text-muted);">No events found.</td></tr>`;
      return;
    }

    categoriesData.forEach(category => {
      // Category Header
      tbody.innerHTML += `
        <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--pp-border);">
          <td colspan="4" style="padding: 12px 24px;">
            <div style="font-weight: 700; color: var(--pp-text-main); font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">
               ${category.title}
            </div>
          </td>
        </tr>
      `;

      category.events.forEach(ev => {
        let successRate = '-';
        let lastTrigger = '-';
        let hasRecent = false;
        let isConfigured = false;

        if (currentPlatform === 'facebook') {
          successRate = ev.fb_success;
          lastTrigger = ev.fb_time;
          hasRecent = ev.fb_has_recent;
          isConfigured = ev.fb_active && ev.enabled;
        } else if (currentPlatform === 'tiktok') {
          successRate = ev.tt_success;
          lastTrigger = ev.tt_time;
          hasRecent = ev.tt_has_recent;
          isConfigured = ev.tt_active && ev.enabled;
        } else if (currentPlatform === 'reddit') {
          successRate = ev.reddit_success;
          lastTrigger = ev.reddit_time;
          hasRecent = ev.reddit_has_recent;
          isConfigured = ev.reddit_active && ev.enabled;
        } else if (currentPlatform === 'pinterest') {
          successRate = ev.pinterest_success;
          lastTrigger = ev.pinterest_time;
          hasRecent = ev.pinterest_has_recent;
          isConfigured = ev.pinterest_active && ev.enabled;
        }

        let isActive = isConfigured && hasRecent;

        const successColor = successRate === '-' ? 'var(--pp-text-muted)' : (parseInt(successRate) > 90 ? 'var(--pp-success)' : 'var(--pp-warning)');

        let warningHtml = '';
        if (isConfigured && !hasRecent) {
          warningHtml = `<div style="color: var(--pp-warning); font-size: 11px; margin-top: 6px; display: flex; align-items: center; gap: 4px;">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
              No activity in the last 48 hours.
           </div>`;
        }

        let paramsHtml = '';
        let expandBtn = '';
        if (ev.params && ev.params.length > 0) {
          let paramRows = ev.params.map(p => `
             <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
               <span style="font-size: 12px; color: var(--pp-text-main);">${p.name}</span>
               <label class="pp-switch" style="margin:0;">
                 <input type="checkbox" class="param-toggle" data-event="${ev.name}" data-param="${p.name}" ${p.enabled ? 'checked' : ''}>
                 <span class="pp-slider"></span>
               </label>
             </div>
           `).join('');

          let paramsContainerId = `params-${ev.name.replace(/[^a-zA-Z0-9]/g, '')}`;
          paramsHtml = `
             <div class="event-params-container" id="${paramsContainerId}" style="display: none; padding: 12px 16px; background: rgba(0,0,0,0.2); margin-top: 12px; border-radius: 4px;">
               <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--pp-text-muted); margin-bottom: 8px;">Event Parameters</div>
               ${paramRows}
             </div>
           `;

          expandBtn = `
             <div style="margin-top: 8px; margin-left: 54px;">
               <button class="expand-params-btn" data-target="${paramsContainerId}" style="background:none; border:none; color:var(--pp-primary); font-size: 12px; cursor: pointer; padding:0; text-decoration: underline;">Configure Parameters</button>
             </div>
           `;
        }

        const isChecked = ev.enabled ? 'checked' : '';

        tbody.innerHTML += `
          <tr style="border-bottom: 1px solid var(--pp-border); transition: background 0.2s;">
            <td style="padding: 16px 24px;">
              <div style="font-weight: 600; color: var(--pp-text-main); font-size: 14px; display: flex; align-items: center; gap: 12px;">
                 <label class="pp-switch" style="margin:0;">
                   <input type="checkbox" class="event-toggle" data-event="${ev.name}" ${isChecked}>
                   <span class="pp-slider"></span>
                 </label>
                 ${ev.name}
              </div>
              <div style="color: var(--pp-text-muted); font-size: 12px; margin-top: 4px; margin-left: 54px;">${ev.desc}</div>
              <div style="margin-left: 54px;">${warningHtml}</div>
              ${expandBtn}
              <div style="margin-left: 54px;">${paramsHtml}</div>
            </td>
            <td style="padding: 16px 24px;">
              <span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; background: ${isActive ? 'var(--pp-success-bg)' : 'rgba(255,255,255,0.05)'}; color: ${isActive ? 'var(--pp-success)' : 'var(--pp-text-muted)'};">
                ${isActive ? 'Active' : (ev.enabled ? 'Inactive' : 'Disabled')}
              </span>
            </td>
            <td style="padding: 16px 24px;">
              <span style="color: ${successColor}; font-weight: 600; font-size: 14px;">${successRate}</span>
            </td>
            <td style="padding: 16px 24px; color: var(--pp-text-muted); font-size: 13px;">
              ${lastTrigger}
            </td>
          </tr>
        `;
      });
    });

    tbody.querySelectorAll('.event-toggle').forEach(toggle => {
      toggle.addEventListener('change', (e) => {
        toggleEventStatus(e.target.dataset.event, e.target.checked);
      });
    });

    tbody.querySelectorAll('.expand-params-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = e.target.dataset.target;
        const targetEl = document.getElementById(targetId);
        if (targetEl) {
          if (targetEl.style.display === 'none') {
            targetEl.style.display = 'block';
            e.target.textContent = 'Hide Parameters';
          } else {
            targetEl.style.display = 'none';
            e.target.textContent = 'Configure Parameters';
          }
        }
      });
    });

    tbody.querySelectorAll('.param-toggle').forEach(toggle => {
      toggle.addEventListener('change', (e) => {
        toggleEventParamStatus(e.target.dataset.event, e.target.dataset.param, e.target.checked);
      });
    });
  };

  tabs.querySelectorAll('.pp-tab').forEach(tab => {
    tab.addEventListener('click', (e) => {
      tabs.querySelectorAll('.pp-tab').forEach(t => {
        t.style.color = 'var(--pp-text-muted)';
        t.style.borderBottom = 'none';
        t.style.fontWeight = '500';
      });
      e.target.style.color = 'var(--pp-primary)';
      e.target.style.borderBottom = '2px solid var(--pp-primary)';
      e.target.style.fontWeight = '600';

      currentPlatform = e.target.dataset.platform;
      renderTable();
    });
  });

  const fetchEventsData = async () => {
    if (!document.body.contains(container)) return;

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_get_events_manager_data');
    formData.append('nonce', window.pixelonwp_admin_vars.nonce);

    try {
      const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
      const result = await res.json();

      if (result.success && result.data && result.data.categories) {
        categoriesData = result.data.categories;
        renderTable();
      }
    } catch (e) {
      console.warn('Failed to fetch events data', e);
    }
  };

  fetchEventsData();
  setInterval(fetchEventsData, 10000);
}
