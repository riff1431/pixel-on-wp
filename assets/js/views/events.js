import { showToast } from '../components/toaster.js';

export function renderEvents(container, state) {

  // Search and Filter Bar
  const filterBar = document.createElement('div');
  filterBar.style.display = 'flex';
  filterBar.style.gap = '16px';
  filterBar.style.marginBottom = '24px';
  filterBar.style.alignItems = 'center';

  filterBar.innerHTML = `
    <div class="pp-search-bar" style="flex:1;">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
      <input type="text" placeholder="Search events instantly...">
    </div>
  `;

  const tabs = document.createElement('div');
  tabs.className = 'pp-tabs';
  tabs.style.marginBottom = '24px';
  tabs.style.borderBottom = '1px solid var(--pp-border)';
  tabs.innerHTML = `
    <button class="pp-tab active" data-platform="facebook" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-primary); border-bottom: 2px solid var(--pp-primary); font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
      Meta (Facebook)
    </button>
    <button class="pp-tab" data-platform="tiktok" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-text-muted); font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19.589 6.686a4.793 4.793 0 01-3.77-4.245V2h-3.445v13.672a2.896 2.896 0 01-5.201 1.743 2.895 2.895 0 013.313-4.508V9.423a6.33 6.33 0 00-1.109-.1 6.34 6.34 0 106.34 6.34V8.625A8.214 8.214 0 0019.589 10v-3.314z" fill="currentColor"/><path d="M16.25 2v4.686a4.793 4.793 0 003.339 3.314V7.5a4.793 4.793 0 01-3.339-3.314V2h-.001z" fill="#25F4EE"/><path d="M12.374 15.672V2h3.445v.441a4.793 4.793 0 003.77 4.245V10a8.214 8.214 0 01-4.77-1.375v7.047a6.34 6.34 0 01-6.34 6.34 6.33 6.33 0 01-3.666-1.161A2.895 2.895 0 0012.374 15.672z" fill="#FE2C55"/></svg>
      TikTok
    </button>
    <button class="pp-tab" data-platform="reddit" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-text-muted); font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#FF4500"><path d="M12 18c-2.28 0-4.14-.62-4.48-1.42-.08-.18.02-.38.21-.43.19-.05.39.04.47.22.25.6 1.83 1.13 3.8 1.13 1.97 0 3.55-.53 3.8-1.13.08-.18.28-.27.47-.22.19.05.29.25.21.43C16.14 17.38 14.28 18 12 18zm-4.32-5.46a1.28 1.28 0 100-2.56 1.28 1.28 0 000 2.56zm8.64 0a1.28 1.28 0 100-2.56 1.28 1.28 0 000 2.56zm1.9-2.31a1.2 1.2 0 00-1.17-.92c-.17 0-.34.04-.49.12-1.07-.76-2.54-1.25-4.17-1.31l.85-4.01 2.78.59a1.18 1.18 0 10.27-.72l-3.13-.67a.38.38 0 00-.45.29l-.97 4.57c-1.67.04-3.17.54-4.26 1.32-.15-.09-.32-.13-.49-.13A1.2 1.2 0 003.6 11.23c0 .46.26.86.64 1.06-.03.17-.04.34-.04.52 0 2.87 3.49 5.19 7.8 5.19s7.8-2.32 7.8-5.19c0-.18-.01-.35-.04-.52.38-.2.64-.6.64-1.06z" fill="#FF4500"/></svg>
      Reddit
    </button>
    <button class="pp-tab" data-platform="pinterest" style="padding: 12px 24px; background: transparent; border: none; color: var(--pp-text-muted); font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#E60023"><path d="M12.017 4C7.59 4 4 7.59 4 12.017c0 3.39 2.108 6.284 5.084 7.449-.07-.633-.133-1.604.028-2.294.145-.625.938-3.977.938-3.977s-.239-.48-.239-1.188c0-1.112.645-1.942 1.447-1.942.682 0 1.012.513 1.012 1.127 0 .686-.437 1.712-.663 2.663-.189.796.4 1.446 1.185 1.446 1.422 0 2.515-1.5 2.515-3.662 0-1.915-1.376-3.255-3.337-3.255-2.273 0-3.608 1.705-3.608 3.47 0 .687.265 1.425.595 1.825.065.079.075.149.055.23-.06.25-.195.799-.223.909-.037.153-.122.185-.282.112-1.053-.49-1.71-2.03-1.71-3.267 0-2.658 1.932-5.1 5.568-5.1 2.923 0 5.198 2.083 5.198 4.871 0 2.905-1.831 5.242-4.373 5.242-.854 0-1.657-.444-1.932-.968l-.526 2.004c-.19.728-.704 1.64-1.05 2.2 1.034.32 2.127.495 3.26.495 4.427 0 8.017-3.59 8.017-8.017C20.034 7.59 16.444 4 12.017 4z" fill="#E60023"/></svg>
      Pinterest
    </button>
  `;

  const tableContainer = document.createElement('div');
  tableContainer.className = 'pp-table-container pp-card';
  tableContainer.style.padding = '0';
  tableContainer.innerHTML = `
    <table class="pp-table pp-events-table-mobile" style="width: 100%; border-collapse: collapse;">
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
        <tr class="pp-category-row" style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--pp-border);">
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
          <tr class="pp-event-row" style="border-bottom: 1px solid var(--pp-border); transition: background 0.2s;">
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
      const btn = e.currentTarget;
      tabs.querySelectorAll('.pp-tab').forEach(t => {
        t.style.color = 'var(--pp-text-muted)';
        t.style.borderBottom = 'none';
        t.style.fontWeight = '500';
      });
      btn.style.color = 'var(--pp-primary)';
      btn.style.borderBottom = '2px solid var(--pp-primary)';
      btn.style.fontWeight = '600';

      currentPlatform = btn.dataset.platform;
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
