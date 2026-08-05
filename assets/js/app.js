import { renderSidebar } from './components/sidebar.js?v=12';
import { renderHeader } from './components/header.js?v=12';
import { showToast } from './components/toaster.js?v=12';
import { renderDashboard } from './views/dashboard.js?v=12';
import { renderEvents } from './views/events.js?v=12';
import { renderSetup } from './views/setup.js?v=12';
import { renderSettings } from './views/settings.js?v=12';
import { renderDiagnostics } from './views/diagnostics.js?v=12';
import { renderLicense } from './views/license.js?v=12';
import { renderServerSide } from './views/server-side.js?v=12';
import { renderGTMSetup } from './views/gtm-setup.js?v=12';
import { renderReset } from './views/reset.js?v=12';
import { renderFraudPrevention } from './views/fraud-prevention.js?v=12';
import { renderEcommerce } from './views/ecommerce.js?v=12';
import { renderUTMBuilder } from './views/utm-builder.js?v=12';
import { renderHeaderFooter } from './views/header-footer.js?v=12';
import { renderCookieConsent } from './views/cookie-consent.js?v=12';
import { renderAiEngine } from './views/ai-engine.js?v=12';
import { renderUniversalTracker } from './views/universal-tracker.js?v=12';
import { renderDocumentation } from './views/documentation.js?v=12';
import { enhanceAllSelects } from './components/select.js?v=12';

// Make toast available globally
window.PixelOnWP_Toast = showToast;
window.PixelOnWP_EnhanceSelects = enhanceAllSelects;

// Global App State
const state = {
  currentView: 'dashboard',
  config: window.pixelonwp_admin_vars?.config || {},
};

const root = document.getElementById('wpt-admin-app');
if (root) {
  try {
    initApp(root);
  } catch (err) {
    root.innerHTML = '<div style="color:red; padding:20px;"><h3>JS Error in App.js</h3><pre>' + err.stack + '</pre></div>';
  }
}

function initApp(root) {
  root.innerHTML = '';

  // App Layout Scaffold
  const sidebar = document.createElement('div');
  sidebar.className = 'pp-sidebar';
  sidebar.id = 'pp-sidebar';

  const overlay = document.createElement('div');
  overlay.className = 'pp-sidebar-overlay';
  overlay.id = 'pp-sidebar-overlay';

  const menuToggle = document.createElement('button');
  menuToggle.className = 'pp-menu-toggle';
  menuToggle.setAttribute('aria-label', 'Toggle menu');
  menuToggle.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>';

  const mainWrapper = document.createElement('div');
  mainWrapper.className = 'pp-main-wrapper';
  mainWrapper.id = 'pp-main-wrapper';

  const headerContainer = document.createElement('div');
  headerContainer.className = 'pp-top-header-container';
  headerContainer.id = 'pp-top-header-container';

  const main = document.createElement('div');
  main.className = 'pp-main-content';
  main.id = 'pp-main-content';

  mainWrapper.appendChild(headerContainer);
  mainWrapper.appendChild(main);

  root.appendChild(overlay);
  root.appendChild(sidebar);
  root.appendChild(mainWrapper);
  root.appendChild(menuToggle);

  // Render top header bar
  renderHeader(headerContainer, state, navigate);


  // Toggle events
  menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
  });

  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
  });

  // Render sidebar
  renderSidebar(sidebar, state, navigate);

  // Parse URL query parameter for native WP submenus
  const searchParams = new URLSearchParams(window.location.search);
  const pageParam = searchParams.get('page');
  
  if (pageParam && pageParam.startsWith('wpt-')) {
    state.currentView = pageParam.replace('wpt-', '');
  } else if (pageParam && pageParam.startsWith('pixelonwp-')) {
    const viewName = pageParam.replace('pixelonwp-', '');
    state.currentView = viewName === 'dashboard' ? 'dashboard' : viewName;
  } else if (pageParam === 'pixel-on-wp') {
    state.currentView = 'dashboard';
  }

  // Parse URL hash
  const hash = window.location.hash.replace('#', '');
  if (hash) {
    state.currentView = hash;
  }

  // Global MutationObserver to enhance dynamic select elements created in modals or sub-components
  const selectObserver = new MutationObserver(() => {
    enhanceAllSelects(document.body);
  });
  selectObserver.observe(document.body, { childList: true, subtree: true });

  // Initial render
  navigate(state.currentView);
  
  // Listen for hash changes
  window.addEventListener('hashchange', () => {
    const newHash = window.location.hash.replace('#', '');
    if (newHash && newHash !== state.currentView) {
      navigate(newHash);
    }
  });
}

export function navigate(fullViewId) {
  let viewId = fullViewId;
  let queryParams = {};
  
  if (fullViewId.includes('?')) {
      const parts = fullViewId.split('?');
      viewId = parts[0];
      const searchParams = new URLSearchParams(parts[1]);
      for (const [key, value] of searchParams.entries()) {
          queryParams[key] = value;
      }
  }
  
  state.currentView = viewId;
  state.queryParams = queryParams;
  window.location.hash = fullViewId;
  
  // Update active sidebar item
  document.querySelectorAll('.pp-nav-item').forEach(el => {
    el.classList.remove('active');
    if (el.dataset.view === viewId) el.classList.add('active');
  });

  // Close mobile sidebar on navigation
  const sidebar = document.getElementById('pp-sidebar');
  const overlay = document.getElementById('pp-sidebar-overlay');
  if (sidebar) sidebar.classList.remove('open');
  if (overlay) overlay.classList.remove('open');

  const main = document.getElementById('pp-main-content');
  if (!main) return;
  
  // Smooth transition effect
  main.style.opacity = '0';
  main.style.transform = 'translateY(6px)';

  setTimeout(() => {
    main.innerHTML = '';
    switch (viewId) {
      case 'dashboard':
        renderDashboard(main, state);
        break;
      case 'license':
        renderLicense(main, state);
        break;
      case 'setup':
        renderSetup(main, state);
        break;
      case 'server-side':
        renderServerSide(main, state);
        break;
      case 'events':
        renderEvents(main, state);
        break;
      case 'settings':
        renderSettings(main, state);
        break;
      case 'gtmsetup':
        renderGTMSetup(main, state);
        break;
      case 'diagnostics':
        renderDiagnostics(main, state);
        break;
      case 'reset':
        renderReset(main, state);
        break;
      case 'fraud':
        renderFraudPrevention(main, state);
        break;
      case 'ecommerce':
        renderEcommerce(main, state);
        break;
      case 'ai-engine':
        renderAiEngine(main, state);
        break;
      case 'utm-builder':
        renderUTMBuilder(main, state);
        break;
      case 'header-footer':
        renderHeaderFooter(main, state);
        break;
      case 'cookie-consent':
        renderCookieConsent(main, state);
        break;
      case 'universal-tracker':
        renderUniversalTracker(main, state);
        break;
      case 'documentation':
        renderDocumentation(main, state);
        break;
      case 'roas':
        window.location.href = 'admin.php?page=pixelonwp-roas';
        break;
      default:
        main.innerHTML = `<div class="pp-view-header"><h2>View not found</h2><p>Please select an item from the menu.</p></div>`;
    }
    
    // Auto-enhance all native selects in the view to liquid glass custom selects
    enhanceAllSelects(main);

    main.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
    main.style.opacity = '1';
    main.style.transform = 'translateY(0)';
  }, 50);
}
