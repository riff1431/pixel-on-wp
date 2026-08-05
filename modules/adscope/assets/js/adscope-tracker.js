(function() {
    // AdScope AI Tracker Script
    // Captures basic device and UTM metrics silently and sends to backend.

    if (window.pixelonwp_adscope_initialized) return;
    window.pixelonwp_adscope_initialized = true;

    // Session memory to prevent duplicate event tracks in same page load
    const trackedEvents = new Set();

    function getUTMParameters() {
        const urlParams = new URLSearchParams(window.location.search);
        return {
            utm_source: urlParams.get('utm_source') || '',
            utm_medium: urlParams.get('utm_medium') || ''
        };
    }

    function getDeviceType() {
        const ua = navigator.userAgent;
        if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(ua)) return 'tablet';
        if (/Mobile|iP(hone|od)|Android|BlackBerry|IEMobile|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/.test(ua)) return 'mobile';
        return 'desktop';
    }

    function trackAdScopeEvent(eventName = 'page_view') {
        if (!window.wptAdscopeVars || !window.wptAdscopeVars.ajaxurl) return;
        
        // Normalize common event names
        let normalizedEvent = eventName.toLowerCase().replace(/ /g, '_');
        if (normalizedEvent === 'viewcontent' || normalizedEvent === 'view_item') normalizedEvent = 'view_item';
        if (normalizedEvent === 'addtocart' || normalizedEvent === 'add_to_cart') normalizedEvent = 'add_to_cart';
        if (normalizedEvent === 'initiatecheckout' || normalizedEvent === 'begin_checkout') normalizedEvent = 'begin_checkout';
        
        // Prevent dupes in same page load (e.g. Meta & TikTok both firing AddToCart)
        if (trackedEvents.has(normalizedEvent)) return;
        trackedEvents.add(normalizedEvent);
        
        const utm = getUTMParameters();
        const payload = {
            action: 'pixelonwp_adscope_track',
            event_type: normalizedEvent,
            utm_source: utm.utm_source,
            utm_medium: utm.utm_medium,
            device: getDeviceType(),
            nonce: window.wptAdscopeVars.nonce || ''
        };

        const formData = new FormData();
        for (const key in payload) {
            formData.append(key, payload[key]);
        }

        if (navigator.sendBeacon) {
            navigator.sendBeacon(window.wptAdscopeVars.ajaxurl, formData);
        } else {
            fetch(window.wptAdscopeVars.ajaxurl, {
                method: 'POST',
                body: formData,
                keepalive: true
            }).catch(() => {});
        }
    }

    // Run on page load
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        trackAdScopeEvent('page_view');
    } else {
        document.addEventListener('DOMContentLoaded', () => trackAdScopeEvent('page_view'));
    }

    // Intercept DataLayer (GTM / GA4 / Custom)
    const originalDataLayerPush = window.dataLayer ? window.dataLayer.push : null;
    if (window.dataLayer && typeof window.dataLayer.push === 'function') {
        window.dataLayer.push = function() {
            const args = Array.prototype.slice.call(arguments);
            args.forEach(arg => {
                if (arg && arg.event) {
                    trackAdScopeEvent(arg.event);
                } else if (arg && arg[1]) { // Handle gtag() which pushes arguments
                    trackAdScopeEvent(arg[1]);
                }
            });
            return originalDataLayerPush.apply(window.dataLayer, arguments);
        };
    } else {
        // If dataLayer doesn't exist yet, define it with interceptor
        window.dataLayer = window.dataLayer || [];
        const originalPush = window.dataLayer.push;
        window.dataLayer.push = function() {
            const args = Array.prototype.slice.call(arguments);
            args.forEach(arg => {
                if (arg && arg.event) trackAdScopeEvent(arg.event);
                else if (arg && arg[1]) trackAdScopeEvent(arg[1]);
            });
            return originalPush.apply(window.dataLayer, arguments);
        };
    }

    // Intercept Meta Pixel (fbq)
    if (window.fbq) {
        const originalFbq = window.fbq;
        window.fbq = function() {
            const args = Array.prototype.slice.call(arguments);
            if (args[0] === 'track' || args[0] === 'trackCustom') {
                trackAdScopeEvent(args[1]);
            }
            return originalFbq.apply(this, arguments);
        };
    }

    // Intercept TikTok Pixel (ttq)
    if (window.ttq && window.ttq.track) {
        const originalTtqTrack = window.ttq.track;
        window.ttq.track = function() {
            const args = Array.prototype.slice.call(arguments);
            if (args[0]) {
                trackAdScopeEvent(args[0]);
            }
            return originalTtqTrack.apply(this, arguments);
        };
    }

    // Intercept gtag
    if (window.gtag) {
        const originalGtag = window.gtag;
        window.gtag = function() {
            const args = Array.prototype.slice.call(arguments);
            if (args[0] === 'event' && args[1]) {
                trackAdScopeEvent(args[1]);
            }
            return originalGtag.apply(this, arguments);
        };
    }

    // Expose global method if WooCommerce or other scripts want to trigger it manually
    window.wptAdScopeTrack = trackAdScopeEvent;
})();
