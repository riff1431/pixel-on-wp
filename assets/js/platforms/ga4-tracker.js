/**
 * Google Analytics 4 (GA4) Frontend Tracker
 */
(function() {
    if (typeof window.PixelOnWPGA4Data === 'undefined') {
        return;
    }

    const measurementId = window.PixelOnWPGA4Data.measurement_id;
    if (!measurementId) {
        return;
    }

    // 1. Initialize Google Consent Mode v2 default states
    window.dataLayer = window.dataLayer || [];
    function gtag() {
        window.dataLayer.push(arguments);
    }
    
    if (window.PixelOnWPGA4Data.consent_mode_enabled) {
        gtag('consent', 'default', {
            'ad_storage': 'granted',
            'analytics_storage': 'granted',
            'ad_user_data': 'granted',
            'ad_personalization': 'granted',
            'wait_for_update': 500
        });
    }

    // 2. Initialize gtag and configure config script dynamically
    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId}`;
    document.head.appendChild(script);

    gtag('js', new Date());

    const configPayload = {
        send_page_view: true
    };

    if (window.PixelOnWPGA4Data.debug_mode) {
        configPayload.debug_mode = true;
    }

    gtag('config', measurementId, configPayload);

    // 3. Attach Enhanced Conversions (User-Provided Data) if configured
    if (window.PixelOnWPGA4Data.enhanced_conversions && window.PixelOnWPGA4Data.user_data) {
        const ud = window.PixelOnWPGA4Data.user_data;
        const hashedData = {};
        if (ud.email) {
            hashedData.sha256_email_address = ud.email;
        }
        if (ud.phone) {
            hashedData.sha256_phone_number = ud.phone;
        }
        if (Object.keys(hashedData).length > 0) {
            gtag('set', 'user_data', hashedData);
        }
    }

    // Expose dynamic event trigger
    window.PixelOnWPGA4 = {
        track: function(eventName, payload = {}) {
            // Check events control toggles
            let browserEnabled = true;
            if (window.PixelOnWPGA4Data.events_control && window.PixelOnWPGA4Data.events_control[eventName] !== undefined) {
                const cfg = window.PixelOnWPGA4Data.events_control[eventName];
                if (typeof cfg === 'object') {
                    browserEnabled = cfg.browser !== false && cfg.browser !== 'false';
                } else {
                    browserEnabled = cfg !== false && cfg !== 'false';
                }
            }

            if (!browserEnabled) {
                return; // Browser execution disabled
            }

            if (window.PixelOnWPGA4Data.debug_mode) {
                payload.debug_mode = true;
            }

            gtag('event', eventName, payload);
        }
    };
})();
