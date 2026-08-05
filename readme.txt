=== PixelOnWP ===
Contributors: pixel-on-wp-team
Tags: meta pixel, conversions api, server-side tracking, datalayer, woocommerce tracking, gtm, ga4
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise-grade server-side tracking, Meta Pixel, CAPI, GTM, GA4, DataLayer, and WooCommerce tracking suite for WordPress.

== Description ==

**PixelOnWP** is an enterprise-grade, high-performance tracking suite engineered for WordPress and WooCommerce. It combines client-side tracking with robust server-side integrations, ensuring maximum data accuracy, event match quality, and resilience against ad blockers.

### Key Features
* **Meta Pixel & Conversions API (CAPI):** Full support for official Meta Standard Events with automatic parameter mapping, SHA256 customer data hashing, event deduplication, and automatic background queue retry handling.
* **Complete DataLayer Generator:** One-click automated dataLayer population for WooCommerce, Elementor, Bricks, Breakdance, Oxygen, GeneratePress, Kadence, Astra, Block Themes, and Classic Themes.
* **WooCommerce Tracking:** Comprehensive event tracking for Simple, Variable, Grouped products, Bundles, Subscriptions, Bookings, Coupons, Refunds, and Order Status changes.
* **GTM & GA4 Integration:** Seamless container injection and automated event schema forwarding for Google Tag Manager and Google Analytics 4.
* **Advanced Diagnostics & Logs:** Real-time diagnostics for missing parameters, duplicate events, blocked cookies, failed requests, and low match quality.
* **Modern SaaS Admin UI:** Built from scratch with a clean, responsive, dark-mode ready interface comparable to modern SaaS products.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/pixel-on-wp` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to the **PixelOnWP** menu in your WordPress admin dashboard to configure your Pixel ID, Access Token, and tracking preferences.

== Frequently Asked Questions ==

= Does this plugin support server-side tracking? =
Yes! It includes a dedicated server-side endpoint, queue processing, and background retry mechanisms to deliver events directly via the Meta Conversions API and other endpoints.

= Is WooCommerce required? =
No. While it features deep native WooCommerce tracking capabilities, it works seamlessly on business websites, lead generation sites, membership platforms, LMS websites, and blogs.

== Changelog ==

= 1.0.0 =
* Initial release of PixelOnWP.