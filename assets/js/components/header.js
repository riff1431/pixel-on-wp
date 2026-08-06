/**
 * Header Component for PixelOnWP Admin
 * Displays live tracking status badge, environment status, quick actions, and search trigger.
 */

import { openAddPixelModal, openVisualBuilderModal } from './config-modals.js';

export function renderHeader(container, state, navigateCallback) {
  if (!container) return;

  const headerWrapper = document.createElement('div');
  headerWrapper.className = 'pp-top-header';

  // Resolve active platforms and generate mini badges
  const platformsSelected = state.config?.platforms || [];
  const platformIconsMap = {
    facebook: `<span class="pp-mini-platform-icon" title="Meta Pixel & CAPI Active" style="color: #1877F2;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></span>`,
    tiktok: `<span class="pp-mini-platform-icon" title="TikTok Events API Active" style="color: #000000;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.17-2.81-.74-3.99-1.63V15.5c-.09 1.56-.74 3.12-1.97 4.11-1.39 1.14-3.32 1.6-5.08 1.25-2.22-.44-4.08-2.31-4.49-4.53-.55-2.92 1.32-5.96 4.29-6.31.02-.01.03-.01.05-.02v4.09c-1.28.16-2.35 1.15-2.5 2.43-.2 1.67 1.05 3.23 2.72 3.42 1.6.18 3.17-.89 3.49-2.47.07-.35.08-.71.07-1.07V0z"/></svg></span>`,
    reddit: `<span class="pp-mini-platform-icon" title="Reddit Pixel & CAPI Active" style="color: #FF4500;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 18c-2.28 0-4.14-.62-4.48-1.42-.08-.18.02-.38.21-.43.19-.05.39.04.47.22.25.6 1.83 1.13 3.8 1.13 1.97 0 3.55-.53 3.8-1.13.08-.18.28-.27.47-.22.19.05.29.25.21.43C16.14 17.38 14.28 18 12 18zm-4.32-5.46a1.28 1.28 0 100-2.56 1.28 1.28 0 000 2.56zm8.64 0a1.28 1.28 0 100-2.56 1.28 1.28 0 000 2.56zm1.9-2.31a1.2 1.2 0 00-1.17-.92c-.17 0-.34.04-.49.12-1.07-.76-2.54-1.25-4.17-1.31l.85-4.01 2.78.59a1.18 1.18 0 10.27-.72l-3.13-.67a.38.38 0 00-.45.29l-.97 4.57c-1.67.04-3.17.54-4.26 1.32-.15-.09-.32-.13-.49-.13A1.2 1.2 0 003.6 11.23c0 .46.26.86.64 1.06-.03.17-.04.34-.04.52 0 2.87 3.49 5.19 7.8 5.19s7.8-2.32 7.8-5.19c0-.18-.01-.35-.04-.52.38-.2.64-.6.64-1.06z"/></svg></span>`,
    pinterest: `<span class="pp-mini-platform-icon" title="Pinterest Tag & CAPI Active" style="color: #E60023;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12.017 4C7.59 4 4 7.59 4 12.017c0 3.39 2.108 6.284 5.084 7.449-.07-.633-.133-1.604.028-2.294.145-.625.938-3.977.938-3.977s-.239-.48-.239-1.188c0-1.112.645-1.942 1.447-1.942.682 0 1.012.513 1.012 1.127 0 .686-.437 1.712-.663 2.663-.189.796.4 1.446 1.185 1.446 1.422 0 2.515-1.5 2.515-3.662 0-1.915-1.376-3.255-3.337-3.255-2.273 0-3.608 1.705-3.608 3.47 0 .687.265 1.425.595 1.825.065.079.075.149.055.23-.06.25-.195.799-.223.909-.037.153-.122.185-.282.112-1.053-.49-1.71-2.03-1.71-3.267 0-2.658 1.932-5.1 5.568-5.1 2.923 0 5.198 2.083 5.198 4.871 0 2.905-1.831 5.242-4.373 5.242-.854 0-1.657-.444-1.932-.968l-.526 2.004c-.19.728-.704 1.64-1.05 2.2 1.034.32 2.127.495 3.26.495 4.427 0 8.017-3.59 8.017-8.017C20.034 7.59 16.444 4 12.017 4z"/></svg></span>`,
    ga4: `<span class="pp-mini-platform-icon" title="GA4 Tag Active" style="color: #EA4335;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M22 20a2 2 0 01-2 2H4a2 2 0 01-2-2V4a2 2 0 012-2h16a2 2 0 012 2v16z" fill="#F8FAFC"/><path d="M6 18a2 2 0 100-4 2 2 0 000 4z" fill="#EA4335"/><path d="M12 18a2 2 0 002-2V9a2 2 0 10-4 0v7a2 2 0 002 2z" fill="#F9AB00"/><path d="M18 18a2 2 0 002-2V5a2 2 0 10-4 0v11a2 2 0 002 2z" fill="#4285F4"/></svg></span>`,
    google: `<span class="pp-mini-platform-icon" title="Google Ads Conversion Engine Active" style="color: #4285F4;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M3.75 16.5A4.5 4.5 0 008.25 21h7.5a4.5 4.5 0 004.5-4.5v-9A4.5 4.5 0 0015.75 3h-7.5A4.5 4.5 0 003.75 7.5v9z" fill="#FFFFFF"/><path d="M16.5 6L8.25 18H5.25L13.5 6h3z" fill="#4285F4"/><path d="M18.75 18l-5.25-7.5 1.8-2.6 6.45 10.1h-3z" fill="#FBBC04"/><path fill-rule="evenodd" clip-rule="evenodd" d="M14.55 11.95L11.7 16.1 9.4 12.8l2.85-4.15 2.3 3.3z" fill="#34A853"/></svg></span>`
  };

  const activePlatformsHtml = platformsSelected
    .map(p => platformIconsMap[p] || '')
    .filter(Boolean)
    .join('');

  // Initial local notifications data
  let notifications = [
    { id: 1, text: 'Universal Stream Engine is fully active and monitoring WooCommerce events.', type: 'success', time: 'Just now', read: false },
    { id: 2, text: 'Google Tag consolidated integration successfully enqueued on front end.', type: 'info', time: '5 mins ago', read: false },
    { id: 3, text: 'Diagnostics status healthy. No dispatch failures reported in last 24h.', type: 'success', time: '1 hour ago', read: false }
  ];

  headerWrapper.innerHTML = `
    <div class="pp-header-left">
      <button class="pp-header-menu-toggle" id="pp-header-menu-toggle-btn" aria-label="Toggle Menu">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width: 20px; height: 20px;">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
      <div class="pp-status-pill pp-status-pill-active" title="Universal Pixel & CAPI Stream Engine is active">
        <span class="pp-status-pulse"></span>
        <span class="pp-status-text">Tracking Engine Active</span>
      </div>
      ${activePlatformsHtml ? `
        <div class="pp-header-active-platforms" style="display: flex; align-items: center; gap: 8px; margin-left: 16px; padding-left: 16px; border-left: 1px solid var(--pp-border);">
          <span style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-right: 4px;">Active:</span>
          ${activePlatformsHtml}
        </div>
      ` : ''}
    </div>

    <div class="pp-header-center">
      <div class="pp-header-search" id="pp-header-search-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span>Search features, pixels, diagnostics...</span>
        <kbd>⌘K</kbd>
      </div>
    </div>

    <div class="pp-header-right">
      <div class="pp-header-notification-wrapper" style="position: relative;">
        <button class="pp-header-btn-icon" id="pp-btn-notifications" title="System Notifications">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <span class="pp-notification-badge" id="pp-notification-badge-el"></span>
        </button>

        <div class="pp-notifications-dropdown" id="pp-notifications-dropdown-el" style="display: none; position: absolute; right: 0; top: 48px; width: 340px; background: var(--pp-bg); border: 1px solid var(--pp-border); border-radius: var(--pp-radius-md); box-shadow: 0 10px 25px rgba(0,0,0,0.15); z-index: 99; overflow: hidden; animation: fadeInUp 0.2s ease-out;">
          <div style="padding: 16px 20px; border-bottom: 1px solid var(--pp-border); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.02);">
            <span style="font-weight: 600; color: var(--pp-text-main); font-size: 14px;">Notifications</span>
            <button id="pp-notifications-clear-btn" style="background: none; border: none; color: var(--pp-primary); font-size: 12px; font-weight: 600; cursor: pointer; padding: 4px;">Clear All</button>
          </div>
          <div id="pp-notifications-list-container" style="max-height: 280px; overflow-y: auto;">
            <!-- Rendered list items -->
          </div>
        </div>
      </div>

      <button class="pp-header-btn pp-header-btn-primary" id="pp-btn-quick-setup">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        <span>Add Pixel</span>
      </button>
    </div>
  `;

  container.appendChild(headerWrapper);

  // Notification UI Renderer Helper
  const renderNotifications = () => {
    const listContainer = headerWrapper.querySelector('#pp-notifications-list-container');
    const badge = headerWrapper.querySelector('#pp-notification-badge-el');
    if (!listContainer || !badge) return;

    const unreadCount = notifications.filter(n => !n.read).length;
    if (unreadCount > 0) {
      badge.textContent = unreadCount;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }

    if (notifications.length === 0) {
      listContainer.innerHTML = `
        <div style="padding: 32px 20px; text-align: center; color: var(--pp-text-muted); font-size: 13px;">
          No new system notifications.
        </div>
      `;
      return;
    }

    listContainer.innerHTML = notifications.map(n => {
      const iconColor = n.type === 'success' ? 'var(--pp-success)' : 'var(--pp-primary)';
      return `
        <div class="pp-notification-item" data-id="${n.id}" style="padding: 14px 20px; border-bottom: 1px solid var(--pp-border); display: flex; gap: 12px; cursor: pointer; transition: background 0.2s; background: ${n.read ? 'transparent' : 'rgba(225, 29, 72, 0.02)'};">
          <div style="width: 8px; height: 8px; border-radius: 50%; background: ${iconColor}; margin-top: 5px; flex-shrink: 0;"></div>
          <div style="flex: 1;">
            <div style="font-size: 13px; color: var(--pp-text-main); line-height: 1.4; font-weight: ${n.read ? '500' : '600'};">${n.text}</div>
            <div style="font-size: 11px; color: var(--pp-text-muted); margin-top: 4px;">${n.time}</div>
          </div>
        </div>
      `;
    }).join('');

    // Attach click listener to individual notifications
    listContainer.querySelectorAll('.pp-notification-item').forEach(item => {
      item.addEventListener('click', (e) => {
        e.stopPropagation();
        const id = parseInt(item.dataset.id, 10);
        const notif = notifications.find(n => n.id === id);
        if (notif) {
          notif.read = true;
          renderNotifications();
        }
      });
    });
  };

  // Wire Notifications events
  const notifBtn = headerWrapper.querySelector('#pp-btn-notifications');
  const notifDropdown = headerWrapper.querySelector('#pp-notifications-dropdown-el');
  if (notifBtn && notifDropdown) {
    notifBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isVisible = notifDropdown.style.display === 'block';
      notifDropdown.style.display = isVisible ? 'none' : 'block';
    });

    // Close notifications dropdown on click outside
    document.addEventListener('click', (e) => {
      if (!notifDropdown.contains(e.target) && e.target !== notifBtn && !notifBtn.contains(e.target)) {
        notifDropdown.style.display = 'none';
      }
    });
  }

  const clearBtn = headerWrapper.querySelector('#pp-notifications-clear-btn');
  if (clearBtn) {
    clearBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      notifications = [];
      renderNotifications();
    });
  }

  // Initial notifications render
  renderNotifications();

  // Wire events
  const searchBtn = headerWrapper.querySelector('#pp-header-search-btn');
  if (searchBtn) {
    searchBtn.addEventListener('click', () => {
      openSearchModal(state, navigateCallback);
    });
  }

  const setupBtn = headerWrapper.querySelector('#pp-btn-quick-setup');
  if (setupBtn) {
    setupBtn.addEventListener('click', () => {
      openAddPixelModal();
    });
  }

  // Wire header menu toggle
  const headerToggle = headerWrapper.querySelector('#pp-header-menu-toggle-btn');
  if (headerToggle) {
    headerToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const sidebar = document.getElementById('pp-sidebar');
      const overlay = document.getElementById('pp-sidebar-overlay');
      if (sidebar && overlay) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
      }
    });
  }

  // Keyboard shortcut ⌘K or Ctrl+K
  window.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      openSearchModal(state, navigateCallback);
    }
  });
}

function openSearchModal(state, navigateCallback) {
  let modal = document.getElementById('pp-search-modal');
  if (modal) {
    modal.remove();
  }

  modal = document.createElement('div');
  modal.id = 'pp-search-modal';
  modal.className = 'pp-modal-overlay open';

  modal.innerHTML = `
    <div class="pp-modal-content pp-search-modal-content">
      <div class="pp-search-input-wrapper">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="text" id="pp-modal-search-input" placeholder="Search settings, pixels, triggers..." autofocus />
        <span class="pp-search-esc">ESC</span>
        <button class="pp-modal-close-btn" id="pp-modal-search-close" aria-label="Close modal">&times;</button>
      </div>
      <div class="pp-search-results" id="pp-search-results">
        <div class="pp-search-group-title">Quick Actions</div>
        <div class="pp-search-item" data-view="setup">
          <div class="pp-search-item-title">Setup Wizard</div>
          <div class="pp-search-item-desc">Configure Meta Pixel, GA4, TikTok, LinkedIn, Pinterest & CAPI</div>
        </div>
        <div class="pp-search-item" data-view="server-side">
          <div class="pp-search-item-title">Server-Side Tracking & CAPI</div>
          <div class="pp-search-item-desc">Configure access tokens, test events, and payload logs</div>
        </div>
        <div class="pp-search-item" data-view="events">
          <div class="pp-search-item-title">Event Manager</div>
          <div class="pp-search-item-desc">Manage standard WooCommerce & custom point-and-click events</div>
        </div>
        <div class="pp-search-item" data-view="ai-engine">
          <div class="pp-search-item-title">AI Ad Engine</div>
          <div class="pp-search-item-desc">Optimize ad audience tracking and automated campaign builder</div>
        </div>
        <div class="pp-search-item" data-view="diagnostics">
          <div class="pp-search-item-title">Diagnostics & System Logs</div>
          <div class="pp-search-item-desc">Run self-test diagnostic checks and check system health</div>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  const input = modal.querySelector('#pp-modal-search-input');
  if (input) input.focus();

  modal.addEventListener('click', (e) => {
    if (e.target === modal || e.target.closest('#pp-modal-search-close')) modal.remove();
  });

  const searchItems = modal.querySelectorAll('.pp-search-item');
  searchItems.forEach(item => {
    item.addEventListener('click', () => {
      const view = item.dataset.view;
      modal.remove();
      if (view) navigateCallback(view);
    });
  });

  input.addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase();
    searchItems.forEach(item => {
      const title = item.querySelector('.pp-search-item-title').textContent.toLowerCase();
      const desc = item.querySelector('.pp-search-item-desc').textContent.toLowerCase();
      if (title.includes(term) || desc.includes(term)) {
        item.style.display = 'block';
      } else {
        item.style.display = 'none';
      }
    });
  });

  window.addEventListener('keydown', function escHandler(e) {
    if (e.key === 'Escape' && document.getElementById('pp-search-modal')) {
      document.getElementById('pp-search-modal').remove();
      window.removeEventListener('keydown', escHandler);
    }
  });
}
