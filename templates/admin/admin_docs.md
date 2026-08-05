# Developer & System Architecture Guide

This guide details the internal system architecture, database configurations, API schemas, and hook events of the **PixelOnWP** tracking engine.

---

## 1. Meta (Facebook) Pixel & CAPI
* **File Reference**: `includes/class-meta-api-service.php`
* **Trigger Mechanics**: Client-side triggers use standard fbq JavaScript enqueues. Backend dispatches execute via `wp_remote_post()` targeting `https://graph.facebook.com/v19.0/{pixel_id}/events` using HTTP headers: `Authorization: Bearer {token}`.
* **Payload Structure**: Packages standard user data objects (hashed parameters) and custom data arrays (currency, purchase value, order contents).

## 2. TikTok Pixel & Events API
* **File Reference**: `includes/tracking/class-tiktok-tracker.php`
* **Trigger Mechanics**: Fires frontend pixels using the `ttq` tracking engine. Pushes server events to `https://business-api.tiktok.com/open_api/v1.3/event/track/` containing the API token.
* **Payload Structure**: Maps custom attributes like `value`, `currency`, and product metadata arrays to match TikTok's event requirements.

## 3. Reddit Pixel & Conversions API
* **File Reference**: `includes/tracking/class-reddit-tracker.php`
* **Trigger Mechanics**: Enqueues Reddit browser tracking pixels. Pushes server dispatches to `https://ads-api.reddit.com/api/v2.0/conversions/events/{pixel_id}` using Bearer token headers.
* **Payload Structure**: Sends transaction IDs, value, currency, and hashed customer parameters.

## 4. Google Analytics 4 (GA4)
* **File Reference**: `includes/platforms/google-analytics/class-ga4-server-tracker.php`
* **Trigger Mechanics**: Loads `gtag.js` client-side. Dispatches server-side events using the GA4 Measurement Protocol targeting `https://www.google-analytics.com/mp/collect?measurement_id={id}&api_secret={secret}`.
* **Payload Structure**: Pushes standard Google Analytics events matching persistent client ID parameters.

## 5. Google Ads Conversion Tracking
* **File Reference**: `includes/platforms/google-analytics/class-ga4-server-tracker.php`
* **Trigger Mechanics**: Fires conversion scripts on order completion pages. Hashes customer contact details (email, phone) using SHA-256 to support Google Enhanced Conversions.

## 6. Google Tag Manager (GTM)
* **Trigger Mechanics**: Injects GTM container tracking scripts into the page header and `<noscript>` blocks directly below the opening body tag.

## 7. Event Manager
* **File Reference**: `includes/admin/class-admin-ajax.php`
* **Trigger Mechanics**: Checks active option flags inside the `pixelonwp_settings` array to verify if event tracking is enabled for a platform before enqueuing scripts or CAPI payloads.

## 8. eCommerce Tracking & DataLayer
* **File Reference**: `includes/datalayer/`
* **Trigger Mechanics**: Maps WooCommerce hook variables to `window.dataLayer` pushes. Intercepts AJAX cart updates and maps variables for product variations.

## 9. ROAS Dashboard & Analytics Engine
* **File Reference**: `includes/class-analytics-engine.php`
* **Trigger Mechanics**: Evaluates Meta campaign insights and calculates metrics (CPA, AOV, CTR, CPC, ROAS) using WooCommerce HPOS order history.

## 10. Auto-Rules & Automation
* **File Reference**: `includes/class-auto-rules-engine.php`
* **Trigger Mechanics**: Evaluates active rules daily via `roas_evaluate_automation_rules` WP-Cron. Sends POST requests to Meta API to adjust campaign budgets. Listens for product inventory updates to pause active campaigns when stock status changes to `outofstock`.

## 11. Custom Audience Syncing
* **File Reference**: `includes/class-audience-sync.php`
* **Trigger Mechanics**: Queries WooCommerce customer databases. Hashes user details (emails, phones) using SHA-256 and uploads them to Meta Custom Audiences in batches. Excludes purchasers on checkout completion and logs checkout abandoners after 2 hours.

## 12. Dynamic Currency Converter
* **File Reference**: `includes/class-currency-converter.php`
* **Trigger Mechanics**: Calls the Frankfurter API (`https://api.frankfurter.app/latest`) to fetch currency exchange rates. Caches conversion rates in transients for 24 hours. Falls back to manual settings on API timeouts.

## 13. Diagnostics & Logging Engine
* **File Reference**: `includes/logger/`
* **Trigger Mechanics**: Logs API dispatches, status codes, and raw JSON payloads inside the custom `wp_pixelonwp_event_logs` database table.

## 14. ITP & First-Party Cookies
* **Trigger Mechanics**: Writes persistent first-party cookies (`_fbp`, `_fbc`, `_rdt_uuid`) server-side via PHP `setcookie()` during page redirects to maintain session attribution beyond Safari's 7-day storage limit.

## 15. Fraud Prevention
* **File Reference**: `includes/security/class-fraud-prevention.php`
* **Trigger Mechanics**: Scans visitor IPs, user agents, and geolocation proxy states. Assigns behavioral threat scores and logs suspicious activity in custom database tables.

## 16. Cookie Consent v2
* **Trigger Mechanics**: Intercepts tracking events, holding them in a browser queue array until the visitor grants consent on the cookie banner.

## 17. UTM Builder
* **Trigger Mechanics**: Formats and encodes query parameters (`utm_source`, `utm_medium`, `utm_campaign`, `utm_id`, `fbclid`) to construct campaign URLs.

## 18. Header & Footer Injector
* **Trigger Mechanics**: Enqueues custom header/footer script snippets safely onto the site's frontend without requiring manual theme code modifications.

## 19. Universal Tracker
* **File Reference**: `modules/universal-tracker/class-universal-tracker-module.php`
* **Trigger Mechanics**: Dynamically enqueues scripts on the site's frontend. Renders a Visual Builder Panel inside an isolated shadow DOM container to prevent page CSS conflicts.

## 20. WhatsApp Order Messaging
* **File Reference**: `modules/ecommerce/class-whatsapp-order-messaging.php`
* **Trigger Mechanics**: Listens for WooCommerce order status changes and calls WhatsApp gateways to send status updates.

## 21. Product Feed Generator
* **File Reference**: `modules/ecommerce/class-product-feed-generator.php`
* **Trigger Mechanics**: Generates WooCommerce product feed XML catalogs and schedules updates to keep ad channels synced.

## 22. License Activation Manager
* **File Reference**: `includes/licensing/class-license-manager.php`
* **Trigger Mechanics**: Validates license keys against license servers and stores activation status transients.

## 23. AI Ad Engine
* **File Reference**: `includes/ai/class-ai-engine.php`
* **Trigger Mechanics**: Interfaces with Google Gemini 1.5 Flash using the key defined in `OMNITRACK_GEMINI_KEY`:
  * **Exit intent**: Monitors mouse activity on WooCommerce pages to display dynamic coupon popups.
  * **Search analyzer**: Recommends missing catalog items based on user search queries.
  * **Risk radar**: Computes behavioral scores to detect bot traffic and proxy activity.
  * **Copy generator**: Instantly generates Meta copy, TikTok scripts, and Google Ads headlines.
