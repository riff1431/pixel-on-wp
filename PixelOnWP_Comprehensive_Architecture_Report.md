# PixelOnWP — Comprehensive Architecture, Codebase, Modules & UI/UX Technical Report

> **Plugin Name**: PixelOnWP  
> **Version**: 1.0.0  
> **Text Domain**: `pixel-on-wp`  
> **Minimum Requirements**: WordPress 6.0+, PHP 8.0+  
> **Primary Purpose**: Enterprise-grade multi-platform server-side & browser tracking engine (Meta CAPI, TikTok Events API, Reddit CAPI, Pinterest API, GA4 Measurement Protocol, Google Ads Enhanced Conversions, GTM), AI-powered ad management, ROAS attribution, fraud prevention, ecommerce automation, and visual element tracking.

---

## Table of Contents

1. [Executive Summary & Core Objectives](#1-executive-summary--core-objectives)
2. [Plugin Architecture & System Lifecycle](#2-plugin-architecture--system-lifecycle)
3. [Database Schema & Persistent Storage](#3-database-schema--persistent-storage)
4. [Multi-Platform Tracking & Conversions API (CAPI) Suite](#4-multi-platform-tracking--conversions-api-capi-suite)
5. [Advanced Privacy, Attribution & Server-Side Core](#5-advanced-privacy-attribution--server-side-core)
6. [Specialized Functional Modules](#6-specialized-functional-modules)
   - 6.1 [AI Ad Engine & AI Intelligence Suite](#61-ai-ad-engine--ai-intelligence-suite)
   - 6.2 [AdScope & Fraud Prevention Engine](#62-adscope--fraud-prevention-engine)
   - 6.3 [eCommerce Module (WhatsApp Messaging & Product Feed)](#63-ecommerce-module-whatsapp-messaging--product-feed)
   - 6.4 [Universal Tracker & Shadow DOM Visual Builder](#64-universal-tracker--shadow-dom-visual-builder)
   - 6.5 [ROAS Analytics & Meta Campaign Integration](#65-roas-analytics--meta-campaign-integration)
   - 6.6 [Auto-Rules & Campaign Automation Engine](#66-auto-rules--campaign-automation-engine)
   - 6.7 [Custom Audience Syncing Engine](#67-custom-audience-syncing-engine)
   - 6.8 [Marketing Utilities (UTM Builder, Code Injectors)](#68-marketing-utilities-utm-builder-code-injectors)
7. [UI/UX Design System & Single Page Application (SPA) Architecture](#7-uiux-design-system--single-page-application-spa-architecture)
8. [Directory & File Map](#8-directory--file-map)
9. [Verification & Operational Workflow](#9-verification--operational-workflow)

---

## 1. Executive Summary & Core Objectives

**PixelOnWP** is an all-in-one, enterprise-level conversion tracking and advertising intelligence plugin built specifically for WordPress and WooCommerce. It bridges browser-side event tracking with server-side Conversions API (CAPI) dispatches to overcome modern privacy challenges (Apple ITP, ad blockers, cookie deprecation).

### Key Value Propositions
* **100% Data Accuracy & Deduplication**: Employs dual-dispatch (Browser JS + PHP Server cURL) linked via matching `event_id` tokens for seamless deduplication across Meta, TikTok, Reddit, Pinterest, and GA4.
* **Bypass Ad-Blockers & Safari ITP**: Generates server-side first-party cookies (`_fbp`, `_fbc`, `_rdt_uuid`) in PHP, preserving attribution far beyond Safari's 7-day storage cap.
* **AI-Powered Ad Automation**: Integrated with Google Gemini 1.5 Flash (with ChatGPT & fallback engines) for real-time exit-intent coupon generation, search demand analysis, fraud scoring, and multi-platform ad copy creation.
* **ROAS & Multi-Touch Attribution**: Correlates live Meta Ad Account spend data with WooCommerce High-Performance Order Storage (HPOS) revenue to calculate true ROAS, CPA, and AOV.
* **Automated Campaign Rules**: Daily cron-driven rules engine to automatically scale winning campaigns (+20% daily budget), cut underperforming budgets (-50%), or pause ads for out-of-stock WooCommerce items.
* **Shadow DOM Visual Element Tracker**: Interactive visual event tagger running inside an isolated Shadow DOM on the front-end to tag custom click events without CSS interference.

---

## 2. Plugin Architecture & System Lifecycle

PixelOnWP follows modern PHP design patterns, strictly adhering to **PSR-4 Namespaces**, **Singleton Pattern**, **Dependency Injection**, and modular event loader patterns.

```
                   +---------------------------------------+
                   |          PixelOnWP_Main (PHP)         |
                   |      [Singleton Entry: init()]        |
                   +-------------------+-------------------+
                                       |
           +---------------------------+---------------------------+
           |                           |                           |
+----------v----------+      +---------v---------+       +---------v---------+
|  PixelOnWP_Container |      |  PixelOnWP_Loader |       |  Database Manager |
| (DI Container Core) |      | (Hook Registrar)  |       |  (dbDelta Tables) |
+---------------------+      +---------+---------+       +-------------------+
                                       |
    +-------------------------+--------+--------+-------------------------+
    |                         |                 |                         |
+---v------------------+  +---v--------------+  +v---------------------+  +v-------------------+
| PixelOnWP_Admin_Menu |  | Event Controller |  | Isolated Trackers    |  | Specialized Modules|
| (SPA App Shell)      |  | (WooCommerce/EDD)|  | (Meta/TikTok/Reddit) |  | (AI, AdScope, Feed)|
+----------------------+  +------------------+  +----------------------+  +--------------------+
```

### Core Design Components

1. **Main Plugin Singleton (`PixelOnWP_Main`)**
   * File: [pixel-on-wp.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/pixel-on-wp.php)
   * Prevents duplicate instantiations using `get_instance()`.
   * Controls autoloader initialization (`spl_autoload_register` for `PixelOnWP\` namespace), lifecycle hook registrations, text-domain loading, and module execution.

2. **Hook Registrar (`PixelOnWP_Loader`)**
   * File: [includes/class-loader.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/class-loader.php)
   * Maintained actions (`$actions`) and filters (`$filters`) in internal arrays, registering all WordPress hooks cleanly during `$loader->run()`.

3. **Lifecycle Manager (`PixelOnWP_Activator` & `PixelOnWP_Deactivator`)**
   * Files: [includes/class-activator.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/class-activator.php), [includes/class-deactivator.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/class-deactivator.php)
   * Executes database table creation via WordPress `dbDelta()`, sets default options, and schedules background cron tasks.

4. **Dependency Injection Container (`PixelOnWP_Container`)**
   * File: [includes/core/class-container.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/core/class-container.php)
   * Service registry providing lazy instantiation and dependency resolution across controllers and services.

5. **Background Queue Processor (`PixelOnWP_Queue_Processor`)**
   * File: [includes/queue/](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/queue/)
   * Processes asynchronous CAPI dispatches and audience syncs in batches via hourly WP-Cron jobs (`pixelonwp_background_queue_cron`).

---

## 3. Database Schema & Persistent Storage

PixelOnWP creates 7 dedicated custom database tables during activation to manage event logs, fraud tracking, visitor intelligence, consent audit trails, and background queues:

| Table Name | Primary Keys & Indexes | Purpose & Content |
| :--- | :--- | :--- |
| `wp_pixelonwp_event_logs` | `id` (PK), `KEY event_id`, `KEY status` | Stores all client and server event dispatches, platform targets, JSON payload dumps, execution status (`sent`, `failed`, `pending`), and retry counts. |
| `wp_pixelonwp_fraud_logs` | `id` (PK), `KEY ip_address`, `KEY created_at` | Logs flagged IP addresses, threat detection reasons, request data, and security risk scores. |
| `wp_PixelOnWP_fraud_cache` | `id` (PK), `UNIQUE KEY phone_number` | Caches courier fraud check results and historical COD delivery records for phone numbers. |
| `wp_pixelonwp_consent_logs` | `id` (PK) | Stores Cookie Consent Mode v2 audit records (timestamp, hashed IP, country code, policy version, consent status). |
| `wp_pixelonwp_queue` | `id` (PK), `KEY status`, `KEY scheduled_at` | Background task queue managing retry queues for failed API calls and heavy background jobs. |
| `wp_pixelonwp_visitor_intelligence` | `id` (PK), `UNIQUE KEY visitor_hash` | Stores visitor activity logs, device context JSON, IP geolocation data, and exit-intent tracking. |
| `wp_pixelonwp_adscope_logs` | `id` (PK), `KEY ip_address`, `KEY event_type` | Logs visitor traffic parameters (IP, city, region, ISP, device type, UTM parameters) for AdScope traffic analysis. |

---

## 4. Multi-Platform Tracking & Conversions API (CAPI) Suite

PixelOnWP includes native client-side and server-side connectors for 7 major advertising and analytics platforms:

```
                          +-------------------------------+
                          | WooCommerce / EDD Event Trigger|
                          +---------------+---------------+
                                          |
                         +----------------v----------------+
                         | PixelOnWP_Event_Controller      |
                         +----------------+----------------+
                                          |
        +---------------------------------+---------------------------------+
        |                                                                   |
+-------v-----------------------+                          +----------------v-----------------------+
|  Browser JS Trackers          |                          |  Server-Side CAPI Engine               |
|  (fbq, ttq, rdt, gtag, etc.)  |                          |  (cURL Async Dispatches)               |
+-------+-----------------------+                          +----------------+-----------------------+
        |                                                                   |
        | [event_id] Match                                                  | [event_id] Match
        v                                                                   v
+---------------------------------------------------------------------------------------------------+
|                               Ad Platform Event Ingestion Servers                                 |
|               (Meta, TikTok, Reddit, Pinterest, Google Analytics 4, Google Ads)                  |
+---------------------------------------------------------------------------------------------------+
```

### Supported Platforms & Implementation Details

1. **Meta (Facebook) Pixel & CAPI**
   * Files: [includes/capi/class-meta-capi.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/capi/class-meta-capi.php), [includes/tracking/class-native-tracker.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/tracking/class-native-tracker.php)
   * **Browser**: Injects standard `fbq('track', ...)` with `eventID` parameter.
   * **Server**: Dispatches POST requests to Meta Graph API (`https://graph.facebook.com/v19.0/{pixel_id}/events`) with Bearer token authentication.
   * **Advanced Matching**: Normalizes and SHA-256 hashes user details (`em`, `ph`, `fn`, `ln`, `ct`, `st`, `zp`, `country`, `external_id`, `client_ip_address`, `client_user_agent`).

2. **TikTok Pixel & Events API**
   * File: [includes/tracking/class-tiktok-tracker.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/tracking/class-tiktok-tracker.php)
   * **Browser**: Enqueues `ttq.track(...)` scripts.
   * **Server**: Dispatches requests to TikTok Business API (`https://business-api.tiktok.com/open_api/v1.3/event/track/`) with access tokens and product item array arrays.

3. **Reddit Pixel & Conversions API**
   * File: [includes/tracking/class-reddit-tracker.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/tracking/class-reddit-tracker.php)
   * **Browser**: Enqueues `rdt('track', ...)` script.
   * **Server**: Pushes JSON payloads to Reddit Ads API (`https://ads-api.reddit.com/api/v2.0/conversions/events/{pixel_id}`) with Bearer authorization headers.

4. **Pinterest Tag & Conversions API**
   * File: [includes/tracking/class-pinterest-tracker.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/tracking/class-pinterest-tracker.php)
   * **Browser**: Enqueues `pintrk('track', ...)` tag script.
   * **Server**: Sends POST requests to Pinterest API (`https://api.pinterest.com/v5/ad_accounts/{ad_account_id}/events`). Supports Enhanced Match and first-party cookies (`_epik`).

5. **Google Analytics 4 (GA4) & Measurement Protocol**
   * File: [includes/platforms/google-analytics/class-ga4-server-tracker.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/platforms/google-analytics/class-ga4-server-tracker.php)
   * **Browser**: Loads `gtag.js` for measurement ID stream.
   * **Server**: Sends HTTP POST requests directly to GA4 Measurement Protocol (`https://www.google-analytics.com/mp/collect?measurement_id={id}&api_secret={secret}`) preserving Client ID (`client_id`).

6. **Google Ads Conversion Tracking & Enhanced Conversions**
   * Tracks purchase conversion values, IDs, and currency. Encrypts customer details using SHA-256 for Google Enhanced Conversions matching.

7. **Google Tag Manager (GTM)**
   * File: [includes/integrations/class-gtm.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/integrations/class-gtm.php)
   * Injects GTM container script into `<head>` and noscript iframe directly after the opening `<body>` tag.

---

## 5. Advanced Privacy, Attribution & Server-Side Core

### Event Deduplication System
To prevent double-counting when both Browser JS and Server CAPI are enabled, PixelOnWP generates a deterministic unique string (`event_id`) per user interaction (e.g., `wc_order_{order_id}_{event_name}`). The exact same `$event_id` is passed in both the JS call (`{ eventID: event_id }`) and the CAPI JSON payload. Ad platforms use this key to deduplicate events.

### Safari ITP & First-Party Cookies
To bypass Apple ITP restrictions that wipe 3rd-party and JS-set cookies after 7 days, PixelOnWP writes persistent first-party cookies (`_fbp`, `_fbc`, `_rdt_uuid`) directly via PHP `setcookie()` during HTTP header responses.

### Cookie Consent Mode v2 Integration
* File: [assets/js/views/cookie-consent.js](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/assets/js/views/cookie-consent.js)
* Listens to consent status from major banners (Cookiebot, OneTrust, Complianz, Real Cookie Banner).
* Holds tracking events in an internal JavaScript client buffer queue (`pixelonwp_queued_events`) until explicit consent is granted, then flushes queued events to all platform SDKs.

### Dynamic Currency Conversion Engine
* File: [includes/class-currency-converter.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/class-currency-converter.php)
* Fetches live daily exchange rates from the Frankfurter API (`https://api.frankfurter.app/latest`).
* Caches exchange rates in WordPress transients (`set_transient`, 24-hour expiration) with fallback manual rates if the external API times out.

---

## 6. Specialized Functional Modules

### 6.1 AI Ad Engine & AI Intelligence Suite
* Files: [includes/ai/class-ai-engine.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/ai/class-ai-engine.php), [includes/ai/class-ad-generator.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/ai/class-ad-generator.php), [includes/ai/class-fraud-predictor.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/ai/class-fraud-predictor.php), [includes/ai/class-search-analyzer.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/ai/class-search-analyzer.php)
* **LLM Engine**: Connects to **Google Gemini 1.5 Flash** (via `OMNITRACK_GEMINI_KEY` or custom key), with fallback options for OpenAI ChatGPT and local intelligent heuristics.
* **Exit-Intent Recovery**: Monitors visitor cursor trajectory and cart abandonment on WooCommerce pages. Triggers an AI-generated pop-up coupon tailored to cart items.
* **Search Demand Analyzer**: Analyzes zero-result customer site searches and recommends catalog products to stock based on demand trends.
* **AI Copy Generator**: Automatically generates ad titles, descriptions, primary text, and hashtags tailored for Meta Ads, TikTok video scripts, and Google Search headlines.
* **Fraud Risk Radar**: Computes a behavioral risk score for incoming sessions to identify automated scrapers and click bots.

### 6.2 AdScope & Fraud Prevention Engine
* Files: [modules/adscope/class-adscope-tracker.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/modules/adscope/class-adscope-tracker.php), [modules/adscope/class-adscope-admin.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/modules/adscope/class-adscope-admin.php), [includes/security/class-fraud-prevention.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/security/class-fraud-prevention.php)
* **IP Intelligence**: Logs IP, city, region, ISP, device model, and campaign UTM parameters into `wp_pixelonwp_adscope_logs`.
* **Courier & Fake Order Radar**: Scans customer phone numbers against courier delivery data (`wp_PixelOnWP_fraud_cache`) to prevent Cash on Delivery (COD) fraud.

### 6.3 eCommerce Module (WhatsApp Messaging & Product Feed)
* Files: [modules/ecommerce/class-whatsapp-order-messaging.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/modules/ecommerce/class-whatsapp-order-messaging.php), [modules/ecommerce/class-product-feed-generator.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/modules/ecommerce/class-product-feed-generator.php)
* **WhatsApp Automation**: Triggers automated WhatsApp order alerts (order received, processing, shipped, completed) using customizable message templates and gateway APIs.
* **Product Feed Generator**: Generates formatted XML catalog feeds for Meta Dynamic Product Ads (DPA) and Google Shopping Catalogs with scheduled WP-Cron updates.

### 6.4 Universal Tracker & Shadow DOM Visual Builder
* Files: [modules/universal-tracker/class-universal-tracker-module.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/modules/universal-tracker/class-universal-tracker-module.php), [assets/js/frontend/visual-builder.js](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/assets/js/frontend/visual-builder.js)
* **Shadow DOM Isolation**: Renders an interactive point-and-click event creation drawer directly on the live front-end inside a closed Shadow DOM context, preventing theme CSS leakage.
* Allows site administrators to click any element (button, link, form) to bind custom event triggers without writing JavaScript code.

### 6.5 ROAS Analytics & Meta Campaign Integration
* Files: [admin/class-roas-admin-ui.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/admin/class-roas-admin-ui.php), [includes/class-analytics-engine.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/class-analytics-engine.php)
* Fetches Meta Ads Insights API performance data (spend, impressions, clicks) and correlates it with local WooCommerce revenue.
* Calculates:
  * $\text{True ROAS} = \frac{\text{Attributed WooCommerce Revenue}}{\text{Meta Ad Spend}}$
  * $\text{CPA} = \frac{\text{Meta Ad Spend}}{\text{Attributed Orders}}$
  * $\text{AOV} = \frac{\text{Attributed WooCommerce Revenue}}{\text{Attributed Orders}}$
  * $\text{CTR} = \left(\frac{\text{Clicks}}{\text{Impressions}}\right) \times 100$

### 6.6 Auto-Rules & Campaign Automation Engine
* File: [includes/class-auto-rules-engine.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/class-auto-rules-engine.php)
* Scheduled daily cron task evaluating 3-day ROAS averages against user thresholds:
  * **Auto-Scale Rule**: Increases Meta daily campaign budgets by 20% if ROAS exceeds target.
  * **Budget-Cut Rule**: Decreases daily budget by 50% or pauses campaigns if ROAS falls below lower threshold.
  * **Stock Pauser**: Listens for WooCommerce product stock updates; automatically pauses Meta campaigns when stock reaches `outofstock`.

### 6.7 Custom Audience Syncing Engine
* File: [includes/class-audience-sync.php](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/includes/class-audience-sync.php)
* Automatically hashes customer contact information (SHA-256) and updates Meta Custom Audiences:
  * **VIP Audience**: Syncs high-value customers exceeding configurable total spend thresholds.
  * **Purchaser Exclusion**: Immediately pushes recent order emails to exclusion audiences post-checkout.
  * **Cart Abandoner Recovery**: Syncs abandoned checkouts older than 2 hours to retargeting audiences.

### 6.8 Marketing Utilities (UTM Builder, Code Injectors)
* Files: [assets/js/views/utm-builder.js](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/assets/js/views/utm-builder.js), [assets/js/views/header-footer.js](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/assets/js/views/header-footer.js)
* **UTM Builder**: Formats campaign URLs with standardized tracking tokens (`utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`).
* **Header & Footer Injector**: Safely enqueues raw custom scripts into site headers and footers without editing theme templates.

---

## 7. UI/UX Design System & Single Page Application (SPA) Architecture

The entire WordPress Admin dashboard of PixelOnWP is built as a JavaScript Single Page Application (SPA) using native ES Modules (`type="module"`), delivering a desktop-grade experience without page reloads.

```
+--------------------------------------------------------------------------------+
|                        WordPress Admin Shell (#wpcontent)                       |
|                                                                                |
|  +--------------------------------------------------------------------------+  |
|  | PixelOnWP SPA Root (#wpt-admin-app)                                      |  |
|  |                                                                          |  |
|  |  +-----------------------+  +-----------------------------------------+  |  |
|  |  | Sidebar (.pp-sidebar) |  | Main Content Area (.pp-main-content)    |  |  |
|  |  |                       |  |                                         |  |  |
|  |  | - Navigation Items    |  |  [Active View Component]                |  |  |
|  |  | - Active State        |  |  - Dashboard View / AI Engine View /    |  |  |
|  |  | - App Branding        |  |    Setup Wizard / Diagnostics / etc.   |  |  |
|  |  |                       |  |  - Dynamic Cards & Stat Grids           |  |  |
|  |  |                       |  |  - Interactive Switches & Tables        |  |  |
|  |  +-----------------------+  +-----------------------------------------+  |  |
|  +--------------------------------------------------------------------------+  |
+--------------------------------------------------------------------------------+
```

### SPA Application Architecture (`assets/js/app.js`)
* Entry Point: [assets/js/app.js](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/assets/js/app.js)
* Enqueued via `PixelOnWP_Admin_Menu::enqueue_admin_assets()` with script tag filter replacing standard tags with `<script type="module">`.
* **State Management**: Reactive `state` object maintaining `currentView`, `queryParams`, and `config` variables synced from `pixelonwp_admin_vars`.
* **Hash Routing**: Supports URL hash routing (`#dashboard`, `#setup`, `#events`, `#ai-engine`, `#fraud`, `#roas`, etc.) and native WP submenu links.

### Design System Specifications (`assets/css/admin-global.css`)
* File: [assets/css/admin-global.css](file:///c:/Users/user/Local%20Sites/pixelonwp/app/public/wp-content/plugins/pixel-on-wp/assets/css/admin-global.css)
* **Typography**: Clean, modern typography powered by Inter font (`'Inter', sans-serif`).
* **Design Tokens & Color Palette**:
  * Primary Accent: `#2271b1` / Hover: `#135e96` / Light Tint: `#f6f7f7`
  * Background Surface: `#ffffff` / Page BG: `#f0f0f1`
  * Headings: `#1d2327` / Main Text: `#3c434a` / Muted Text: `#50575e`
  * Success Green: `#00a32a` (`#edfaef` tint) / Danger Red: `#d63638` (`#fcf0f1` tint) / Warning Yellow: `#dba617`
* **Components & Elements**:
  * **Sidebar**: Fixed 240px width (`--pp-sidebar-width`), active indicator tabs, hover states, responsive drawer collapse on mobile screen widths.
  * **Cards & Stat Grids**: Multi-column CSS Grid layouts (`.pp-grid`, `.pp-card`), subtle elevation shadows (`0 1px 1px rgba(0,0,0,0.04)`), rounded corners (`--pp-radius: 4px`).
  * **Form Controls & Toggles**: Custom iOS-style toggle switches, styled inputs, outline buttons, action menus.
  * **Data Tables**: Striped table rows, status pills (`Active`, `Inactive`, `Pending`), copyable snippet boxes.

### Modular SPA Views (`assets/js/views/`)
The UI is broken down into 20 distinct modular view scripts:

```
assets/js/views/
├── dashboard.js            # Main analytics overview, channel status cards, quick metrics
├── setup.js                # Multi-step setup wizard for platform credential entry
├── events.js               # Event Manager switchboard toggle matrix
├── server-side.js          # CAPI & server-side tracking configuration
├── ai-engine.js            # AI Ad Engine, Copy Generator & Prompt controls
├── ai-campaign-builder.js  # Interactive AI Campaign Builder wizard
├── ai-search-demand.js     # Missing product demand analyzer UI
├── ai-fraud-radar.js       # Visitor intelligence & bot threat radar
├── fraud-prevention.js     # Security rules, IP blacklist, COD fraud checker
├── ecommerce.js            # Product feed settings & WhatsApp order messaging
├── gtm-setup.js            # GTM container configuration & DataLayer toggles
├── utm-builder.js          # Campaign URL encoder & builder tool
├── header-footer.js        # Script snippet injector textareas
├── cookie-consent.js       # Cookie banner integration & consent mode settings
├── universal-tracker.js    # Visual point-and-click tracker manager
├── diagnostics.js          # Real-time event dispatches log viewer & payload inspector
├── settings.js             # General plugin configuration & currency settings
├── license.js              # License key activation & status management
├── reset.js                # Database reset & plugin cleanup utilities
└── reset.js / docs         # System architecture & user operational guides
```

---

## 8. Directory & File Map

```
pixel-on-wp/
├── admin/
│   ├── class-order-metabox.php          # Ad attribution metabox inside WooCommerce orders
│   └── class-roas-admin-ui.php          # ROAS analytics dashboard renderer & Meta API connector
├── assets/
│   ├── css/
│   │   ├── admin-global.css             # Main design system CSS tokens & UI styles
│   │   ├── admin.css                    # Admin helper styles
│   │   └── fraud-popup.css              # Exit-intent AI modal styles
│   └── js/
│       ├── app.js                       # SPA entry point & router
│       ├── components/
│       │   └── sidebar.js               # Responsive sidebar navigation component
│       ├── frontend/
│       │   ├── datalayer-listener.js    # Front-end DataLayer event listener
│       │   ├── universal-tracker.js     # Front-end tracking dispatcher
│       │   └── visual-builder.js        # Shadow DOM visual click tagger
│       └── views/                       # 20 Admin SPA view scripts
├── includes/
│   ├── ai/                              # Google Gemini LLM, Ad Copy Generator & Risk Radar
│   ├── api/                             # Meta API services & external connectors
│   ├── capi/                            # Meta CAPI dispatcher & event formatter
│   ├── controllers/
│   │   └── class-event-controller.php   # Main WooCommerce/EDD event listener
│   ├── core/                            # DI Container, Logger, Plugin core
│   ├── database/                        # Database manager & table upgrade handlers
│   ├── datalayer/                       # DataLayer push generators
│   ├── diagnostics/                     # System diagnostics & status checkers
│   ├── integrations/                    # GA4 and GTM integration handlers
│   ├── licensing/                       # License activation & validation manager
│   ├── platforms/                       # Individual platform server trackers
│   ├── queue/                           # Asynchronous task queue processor
│   ├── security/                        # Fraud prevention & IP risk calculator
│   ├── tracking/                        # Native Meta, TikTok, Reddit, Pinterest trackers
│   ├── class-activator.php              # Plugin activation & dbDelta script
│   ├── class-analytics-engine.php       # ROAS math engine
│   ├── class-audience-sync.php          # Meta Custom Audience SHA-256 sync
│   ├── class-auto-rules-engine.php      # Daily WP-Cron campaign scaling rules
│   ├── class-cron-sync.php              # Cron schedule manager
│   ├── class-currency-converter.php     # Frankfurter API currency converter
│   ├── class-deactivator.php            # Plugin cleanup on deactivation
│   └── class-loader.php                 # WordPress action/filter hook loader
├── modules/
│   ├── adscope/                         # AdScope IP tracking & bot detection module
│   ├── ecommerce/                       # WhatsApp order messaging & product feed generator
│   └── universal-tracker/               # Universal tracker module entry point
├── templates/
│   └── admin/                           # PHP templates & developer documentation
├── pixel-on-wp.php                      # Main plugin bootstrap file
└── uninstall.php                        # Database table drop & option cleanup script
```

---

## 9. Verification & Operational Workflow

### Activation & System Initialization
1. Upon activating PixelOnWP, `PixelOnWP_Activator::activate()` fires `dbDelta()` to create the 7 custom database tables.
2. Default options are stored in `pixelonwp_settings`, and the hourly background queue cron (`pixelonwp_background_queue_cron`) is registered.
3. The main singleton `PixelOnWP_Main::get_instance()` bootstraps the PSR-4 autoloader, initializes the DI container, loads all platform tracking classes, and runs the hook loader.

### Admin Dashboard Operation
1. Navigating to **PixelOnWP** in WordPress admin loads the `#wpt-admin-app` SPA container.
2. `app.js` initializes the responsive navigation sidebar and renders the target view script without page reloads.
3. Administrators can configure credentials in the Setup Wizard, monitor true ROAS on the Attribution dashboard, view live event dispatches in Diagnostics, configure AI prompts, and build custom visual click events using the front-end Visual Builder.

---

*Report generated automatically for PixelOnWP Plugin Architecture Codebase Review.*
