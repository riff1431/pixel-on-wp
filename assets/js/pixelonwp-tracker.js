(function() {
  const TRACKING_INTERVAL = 60000;
  let visitorHash = localStorage.getItem('pixelonwp_visitor_hash');
  
  if (!visitorHash) {
    visitorHash = 'v_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15) + Date.now().toString(36);
    localStorage.setItem('pixelonwp_visitor_hash', visitorHash);
  }

  let activityLog = {
    page_views: [],
    clicks: [],
    searches: [],
    cart_actions: [],
    max_scroll: 0
  };

  const getDeviceContext = () => {
    return {
      userAgent: navigator.userAgent,
      language: navigator.language,
      screen: `${window.screen.width}x${window.screen.height}`,
      viewport: `${window.innerWidth}x${window.innerHeight}`
    };
  };

  // Track initial page view
  activityLog.page_views.push({
    url: window.location.href,
    title: document.title,
    time: new Date().toISOString()
  });

  // Track scroll depth
  window.addEventListener('scroll', () => {
    const scrollPercent = Math.round((window.scrollY + window.innerHeight) / document.body.scrollHeight * 100);
    if (scrollPercent > activityLog.max_scroll) {
      activityLog.max_scroll = scrollPercent > 100 ? 100 : scrollPercent;
    }
  }, { passive: true });

  // Track clicks (generic links and buttons)
  document.addEventListener('click', (e) => {
    const target = e.target.closest('a, button');
    if (target) {
      activityLog.clicks.push({
        text: target.innerText?.substring(0, 50) || 'unknown',
        href: target.href || null,
        time: new Date().toISOString()
      });
      // Keep only last 10 clicks to avoid huge payload
      if (activityLog.clicks.length > 10) activityLog.clicks.shift();
    }
  }, { passive: true });

  // Track search inputs loosely
  document.addEventListener('change', (e) => {
    if (e.target.tagName === 'INPUT' && (e.target.type === 'search' || e.target.name === 's' || e.target.id.includes('search'))) {
      activityLog.searches.push({
        query: e.target.value,
        time: new Date().toISOString()
      });
    }
  });

  // Intercept Add to Cart (WooCommerce generic)
  document.addEventListener('click', (e) => {
    if (e.target.closest('.add_to_cart_button') || e.target.closest('.single_add_to_cart_button')) {
      activityLog.cart_actions.push({
        action: 'add_to_cart',
        time: new Date().toISOString()
      });
    }
  });

  const sendHeartbeat = () => {
    if (typeof pixelonwp_tracker_vars === 'undefined') return;

    // Skip if there is no meaningful new activity to send
    const hasActivity = activityLog.page_views.length > 0 || 
                        activityLog.clicks.length > 0 || 
                        activityLog.searches.length > 0 || 
                        activityLog.cart_actions.length > 0 || 
                        activityLog.max_scroll > 0;

    if (!hasActivity) {
      return;
    }

    const formData = new FormData();
    formData.append('action', 'pixelonwp_sync_visitor');
    formData.append('nonce', pixelonwp_tracker_vars.nonce);
    formData.append('visitor_hash', visitorHash);
    formData.append('device_context', JSON.stringify(getDeviceContext()));
    formData.append('activity_log', JSON.stringify(activityLog));

    fetch(pixelonwp_tracker_vars.ajaxurl, {
      method: 'POST',
      body: formData,
      keepalive: true
    }).then(() => {
      // Clear log after successful send to prevent bloated payloads and redundant requests
      activityLog = { page_views: [], clicks: [], searches: [], cart_actions: [], max_scroll: 0 };
    }).catch(() => {
      // Ignore background errors
    });
  };

  // Initial immediate sync
  setTimeout(sendHeartbeat, 2000);

  // 15 seconds heartbeat
  setInterval(sendHeartbeat, TRACKING_INTERVAL);

})();
