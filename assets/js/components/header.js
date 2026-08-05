/**
 * Header Component for PixelOnWP Admin
 * Displays live tracking status badge, environment status, quick actions, and search trigger.
 */

export function renderHeader(container, state, navigateCallback) {
  if (!container) return;

  const headerWrapper = document.createElement('div');
  headerWrapper.className = 'pp-top-header';

  headerWrapper.innerHTML = `
    <div class="pp-header-left">
      <div class="pp-status-pill pp-status-pill-active" title="Universal Pixel & CAPI Stream Engine is active">
        <span class="pp-status-pulse"></span>
        <span class="pp-status-text">Tracking Engine Active</span>
      </div>
      <div class="pp-header-badge">CAPI v2.4</div>
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
      <button class="pp-header-btn pp-header-btn-secondary" id="pp-btn-visual-builder" title="Launch Point & Click Visual Setup Tool">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        <span>Visual Builder</span>
      </button>

      <button class="pp-header-btn pp-header-btn-primary" id="pp-btn-quick-setup">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        <span>Add Pixel</span>
      </button>
    </div>
  `;

  container.appendChild(headerWrapper);

  // Wire events
  const searchBtn = headerWrapper.querySelector('#pp-header-search-btn');
  if (searchBtn) {
    searchBtn.addEventListener('click', () => {
      openSearchModal(state, navigateCallback);
    });
  }

  const visualBtn = headerWrapper.querySelector('#pp-btn-visual-builder');
  if (visualBtn) {
    visualBtn.addEventListener('click', () => {
      window.open(window.location.origin + '?pixelonwp_visual_builder=1', '_blank');
    });
  }

  const setupBtn = headerWrapper.querySelector('#pp-btn-quick-setup');
  if (setupBtn) {
    setupBtn.addEventListener('click', () => {
      navigateCallback('setup');
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
