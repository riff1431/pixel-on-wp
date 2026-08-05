/**
 * PixelOnWP - Frontend Tracking JavaScript
 *
 * Handles client-side event dispatching, cookie harvesting (_fbp, _fbc), and user interactions.
 *
 * @package PixelOnWP
 * @since 1.0.0
 */

(function () {
  'use strict';

  /**
   * Helper to retrieve cookie values by name.
   *
   * @param {string} name Cookie name.
   * @return {string|null} Cookie value or null.
   */
  function getCookie(name) {
    var value = '; ' + document.cookie;
    var parts = value.split('; ' + name + '=');
    if (parts.length === 2) {
      return parts.pop().split(';').shift();
    }
    return null;
  }

  /**
   * Initialize frontend tracking handlers.
   */
  function initFrontendTracking() {
    // Automatically inject or capture standard tracking parameters if needed
    window.wptDataLayer = window.wptDataLayer || [];

    // Example: Track phone link clicks
    document.addEventListener('click', function (e) {
      var target = e.target.closest('a');
      if (!target) {
        return;
      }

      var href = target.getAttribute('href');
      if (!href) {
        return;
      }

      if (href.indexOf('tel:') === 0) {
        window.wptDataLayer.push({
          event: 'PhoneClick',
          phone_number: href.replace('tel:', ''),
          timestamp: Date.now()
        });
      } else if (href.indexOf('mailto:') === 0) {
        window.wptDataLayer.push({
          event: 'EmailClick',
          email_address: href.replace('mailto:', ''),
          timestamp: Date.now()
        });
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFrontendTracking);
  } else {
    initFrontendTracking();
  }

})();