# 🚀 PixelOnWP

**Enterprise-Grade Multi-Channel Server-Side Tracking, Conversions API (CAPI), AI Marketing Suite & WooCommerce Analytics for WordPress.**

---

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg?logo=wordpress)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tracking](https://img.shields.io/badge/Tracking-Client%20%2B%20Server--Side%20(CAPI)-orange.svg)]()

---

## 🌟 Overview

**PixelOnWP** is an all-in-one tracking, analytics, and marketing intelligence solution built natively for WordPress and WooCommerce. It bridges browser-side pixel tracking with server-side Conversions API (CAPI) dispatches to ensure **100% accurate attribution**, bypassing ad-blockers, iOS 14.5+ ATT restrictions, and browser privacy filters.

Equipped with an integrated **AI Marketing Engine** (Gemini & ChatGPT), **AdScope Fraud Protection**, and a **Visual Universal Tracker**, PixelOnWP gives store owners, marketers, and developers total control over their ad spend and conversion funnels.

---

## ✨ Key Features

### 🎯 1. Multi-Platform Pixel & CAPI Suite
Simultaneously send events via browser client-side and server-side CAPI with **automatic event deduplication (`event_id`)**:
- **Meta (Facebook & Instagram)**: Pixel + Conversions API (CAPI)
- **Google Analytics 4 (GA4)**: Measurement Protocol + Browser tracking
- **Google Ads**: Conversion Tracking & Dynamic Remarketing
- **TikTok**: Pixel + Events API (CAPI)
- **Pinterest**: Tag + Conversions API
- **LinkedIn**: Insight Tag + Conversions API
- **Reddit**: Pixel + Conversion API
- **Snapchat**: Pixel + Conversions API

---

### 🛒 2. WooCommerce Full-Funnel E-Commerce Tracking
Automatically tracks every step of the buyer journey with accurate currency conversion & schema validation:
- `PageView` & `ViewContent` (Product view)
- `ViewItemList` & `SelectItem` (Category & archive browsing)
- `AddToCart` & `RemoveFromCart`
- `BeginCheckout` & `CheckoutOption`
- `Purchase` (Order complete with server-side fallback)
- `Search` & `Wishlist` activity

---

### 🤖 3. Built-in AI Marketing Engine
Integrates Google Gemini & OpenAI ChatGPT to assist marketing workflows:
- **AI Ad Copy Generator**: Generate multi-platform ad text, headlines, and calls-to-action tailored to your products.
- **AI Fraud Predictor**: Analyze order anomalies, risky IPs, and suspicious checkout behaviors.
- **Search Demand Analyzer**: Extract high-converting keywords and intent insights.
- **AI Campaign Builder**: Receive step-by-step strategy recommendations for scale.

---

### 🛡️ 4. AdScope Anti-Fraud & Invalid Traffic Protection
Prevent wasted ad spend on bots, click farms, and proxy traffic:
- **Real-Time Fraud Radar**: Detect invalid clicks and suspicious user agents.
- **IP & Country Blocking**: Prevent junk traffic from hitting checkout.
- **Checkout Bot Shield**: Interactive verification popups for flagged high-risk sessions.

---

### 🎨 5. Universal Tracker & Visual Builder
Track custom user interactions without writing a single line of code:
- Point-and-click visual builder interface to select DOM elements (buttons, forms, links).
- Custom event triggers (Click, Scroll Depth, Form Submit, Time on Page).
- Flexible payload mapping to custom pixels or GA4 event parameters.

---

### 🍪 6. Cookie Consent & Privacy Compliance
- Fully compliant with GDPR, CCPA, and ePrivacy Directive requirements.
- Customizable Cookie Consent banner with granular category toggles (Analytics, Marketing, Necessary).
- Consent state automatically controls browser pixel script execution.

---

### 📊 7. Live Debugger & Real-Time Diagnostics
- Inspect live events being dispatched from browser and server.
- Verify status codes, payloads, `event_id` matches, and API response latency.
- Comprehensive log manager with automatic log rotation.

---

## 🛠️ System Requirements

| Requirement | Minimum Version | Recommended |
| :--- | :--- | :--- |
| **WordPress** | 6.0+ | 6.4+ |
| **PHP** | 8.0+ | 8.2+ |
| **WooCommerce** | 7.0+ | Latest |
| **MySQL / MariaDB** | 5.7+ / 10.3+ | 8.0+ / 10.6+ |

---

## 🚀 Installation & Setup

1. **Upload Plugin**:
   - Download or clone the repository into `/wp-content/plugins/pixel-on-wp/`.
   - Ensure folder name is `pixel-on-wp`.

2. **Activate Plugin**:
   - Navigate to **WordPress Admin Dashboard -> Plugins -> Installed Plugins**.
   - Click **Activate** under **PixelOnWP**.

3. **Configure Pixels & API Keys**:
   - Go to **PixelOnWP** in the WordPress admin menu.
   - Enter your Meta Pixel ID, Access Token, GA4 Measurement ID, TikTok Pixel ID, etc.
   - Enable Server-Side (CAPI) tracking for maximum accuracy.

---

## 📂 Project Architecture

```
pixel-on-wp/
├── admin/                  # Admin UI controllers & metaboxes
├── assets/                 # CSS styles, JS modules, & views
│   ├── css/                # Admin & frontend stylesheets
│   └── js/                 # Modular JS views & trackers
├── includes/               # Core PHP business logic
│   ├── ai/                 # AI Engine (Gemini & ChatGPT clients)
│   ├── capi/               # Conversions API dispatchers
│   ├── core/               # Container, Plugin bootstrap, Logger
│   ├── datalayer/          # GTM & GA4 DataLayer generator
│   ├── frontend/           # Cookie consent & frontend scripts
│   ├── platforms/          # Platform mappers & transformers
│   └── tracking/           # Pixel & CAPI tracking handlers
├── languages/              # i18n translation POT files
├── modules/                # Decoupled feature modules
│   ├── adscope/            # Anti-fraud radar module
│   ├── ecommerce/          # Product feed & WooCommerce tools
│   └── universal-tracker/  # Visual builder & custom events
├── templates/              # Admin dashboard & documentation templates
├── pixel-on-wp.php         # Plugin main bootstrap entry file
├── README.md               # Repository documentation
└── uninstall.php           # Cleanup handler on deletion
```

---

## 🔒 Security Standards

PixelOnWP follows official **WordPress Plugin Developer Security Guidelines**:
- **Direct Access Guard**: Every PHP file contains `if (!defined('ABSPATH')) exit;`.
- **Input Sanitization & Output Escaping**: All inputs sanitized early (`sanitize_text_field`, `esc_url_raw`) and output escaped late (`esc_html`, `esc_attr`, `esc_url`).
- **CSRF Protection**: All AJAX and REST endpoints use `wp_verify_nonce()` validation.
- **Strict Capabilities**: Administrative actions check `current_user_can('manage_options')`.
- **Prepared Database Queries**: Database interactions strictly use `$wpdb->prepare()`.

---

## 📄 License

Distributed under the **GPL v2 or later** License. See `LICENSE` for more information.

---

Made with ❤️ by [riff1431](https://github.com/riff1431)
