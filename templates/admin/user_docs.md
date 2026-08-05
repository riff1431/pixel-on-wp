# Complete Setup & Operational User Manual

This manual provides comprehensive, step-by-step instructions on how to configure and operate all features of the **PixelOnWP** tracking engine.

---

## 1. Meta (Facebook) Pixel & Conversions API (CAPI)

### How to Get Credentials
1. Log into your **Meta Business Suite** and open **Events Manager**.
2. Under the **Data Sources** tab, select your Web Pixel and copy the **Pixel ID** (e.g., `123456789012345`).
3. To generate a Conversions API token, go to the **Settings** tab, scroll down to the **Conversions API** section, and click **Generate Access Token** under "Set up manually". Copy the generated token immediately.
4. To test dispatches in real-time, navigate to the **Test Events** tab and copy the **Test Event Code** (e.g., `TEST12345`).

### How to Setup
* Paste your **Pixel ID** and **Access Token** into the Meta tab inside the Setup Wizard.
* Add your **Test Event Code** in the debugging field. *Remove this code before pushing your site live to prevent test events from polluting your production analytics.*
* Select the user parameters (Email, Phone, Name) you want to include in **Advanced Matching**.

### How It Works
The plugin injects the standard Meta Pixel javascript code into the page header to capture browser-side events. Simultaneously, when key e-commerce events (like purchases or add-to-carts) occur, the server generates a matching payload and dispatches it directly to Meta's servers via secure cURL POST requests, bypassing ad-blockers.

---

## 2. TikTok Pixel & Events API

### How to Get Credentials
1. Log into your **TikTok Ads Manager** and navigate to **Assets** -> **Events**.
2. Select **Web Events**, open your configured pixel, and copy the **Pixel ID**.
3. In the **Settings** tab of the pixel, scroll down to the Conversions API settings, click **Generate Access Token**, and copy the token.

### How to Setup
* Paste your **Pixel ID** and **Access Token** into the TikTok settings card in the Setup Wizard.
* Toggle the **TikTok Conversions API** setting on.

### How It Works
The browser script enqueues client events, while the backend server listens for actions and dispatches server-side calls with TikTok-specific formatted parameters (like value, currency, and product catalog metadata) directly to TikTok's API.

---

## 3. Reddit Pixel & Conversions API

### How to Get Credentials
1. Open your **Reddit Ads Manager** -> **Events Manager**.
2. Select your event source and copy the **Pixel ID**.
3. Under the Conversions API settings, click **Create API Token** and copy the Bearer Token.

### How to Setup
* Paste your **Pixel ID** and **Bearer Token** into the Reddit settings card.
* Toggle the **Reddit Conversions API** integration on.

### How It Works
Injects the Reddit pixel library browser-side and sends backend server events using Authorization Bearer headers for Conversion API parity.

---

## 4. Google Analytics 4 (GA4)

### How to Get Credentials
1. Log into **Google Analytics** -> **Admin** -> **Data Streams**.
2. Select your Web stream and copy the **Measurement ID** (e.g. `G-XXXXXXXXXX`).
3. Scroll down to **Measurement Protocol API secrets**, create a new secret, and copy the secret key.

### How to Setup
* Paste your **Measurement ID** and **API Secret** into the GA4 card.
* Save settings to initialize GA4 tracking.

### How It Works
Runs `gtag.js` client-side to track user actions, and uses the Measurement Protocol API secret to dispatch server-side events directly to Google Analytics.

---

## 5. Google Ads Conversion Tracking

### How to Get Credentials
1. Open **Google Ads** -> **Tools and Settings** -> **Conversions**.
2. Create or edit your Conversion action.
3. Select **Use Google Tag Manager** to view instructions, and copy both the **Conversion ID** and **Conversion Label**.

### How to Setup
* Paste the **Conversion ID** and **Conversion Label** into the Google Ads settings card.
* Enable **Enhanced Conversions** to securely hash and send first-party customer data.

### How It Works
Fires conversion scripts on success pages. If Enhanced Conversions is enabled, customer contact details are normalized, hashed, and sent securely to Google to improve matching accuracy.

---

## 6. Google Tag Manager (GTM)

### How to Get Credentials
* Log into **Google Tag Manager** and copy your **Container ID** (e.g., `GTM-XXXXXX`) from the top dashboard menu.

### How to Setup
* Paste the **Container ID** into the GTM configuration settings.

### How It Works
Automatically injects the official Google Tag Manager container script into your site's header, and the required `<noscript>` fallback frame directly below the body tag.

---

## 7. Event Manager

### How to Setup
* Navigate to **Event Manager** inside the plugin settings.
* Toggle individual checkboxes to enable or disable tracking for specific events (e.g., `AddToCart`, `InitiateCheckout`, `Purchase`) across different ad platforms.

### How It Works
Serves as the central switchboard for event tracking. If an event is disabled for a platform, all client-side scripts and server-side API requests for that event and platform are blocked.

---

## 8. eCommerce Tracking & DataLayer

### How to Setup
* Tracking initializes automatically if WooCommerce or Easy Digital Downloads (EDD) is active. No manual code setup is required.

### How It Works
* **WooCommerce DataLayer**: Outputs standard e-commerce variables (`window.dataLayer.push`) on product views, cart additions, checkout steps, and order confirmations.
* **AJAX Cart Intercept**: Captures dynamic cart updates (e.g., variation ID, category, name, price) without requiring page reloads.
* **Variations Mapping**: Dynamically maps attributes of variable products to pass accurate SKUs to ad platform catalogs.

---

## 9. ROAS Dashboard & Analytics Engine

### How to Setup
* Configure your Meta API credentials and select your base **Ad Account Currency** (e.g., USD) in the settings.

### How It Works
Automatically imports Meta campaign statistics (spend, clicks, impressions) in the background. It combines this data with local WooCommerce order history to calculate key performance metrics:
* **True ROAS**: Attributed Revenue / Meta Ad Spend.
* **CPA (Cost per Acquisition)**: Meta Ad Spend / Attributed Orders.
* **AOV (Average Order Value)**: Attributed Revenue / Attributed Orders.
* **CTR (Click-Through Rate)**: (Clicks / Impressions) * 100.
* **CPC (Cost per Click)**: Spend / Clicks.

---

## 10. Auto-Rules & Automation

### How to Setup
* Toggle **Auto-Scale** and **Budget Cut** rules on the Automation tab.
* Set your desired Scaling ROAS Threshold and Budget Cut Threshold.
* Enable the **Out-of-Stock Ad Pauser**.

### How It Works
* **Auto-Scale**: Runs a daily background task to evaluate campaign ROAS over the last 3 days. If ROAS exceeds the threshold, it sends a POST request to the Meta API to increase the daily budget by 20%.
* **Budget Cut**: If campaign ROAS drops below your threshold over 3 days, it reduces the budget by 50% or pauses the campaign.
* **Stock Pauser**: Listens for product inventory updates. If an item is marked out-of-stock, it immediately pauses the corresponding ad campaign on Meta.

---

## 11. Custom Audience Syncing

### How to Setup
* Go to the **Audience Syncing** tab.
* Paste your **VIP Custom Audience ID**, **Cart Abandoners Audience ID**, and **Recent Purchasers Audience ID**.
* Set your **VIP Spend Threshold** (e.g., 10000 BDT).

### How It Works
* **VIP Sync**: Clicking "Sync VIP List Now" queries customer history, filters for accounts that exceed the spend threshold, and hashes their details (email, phone) using SHA-256 before uploading them to your Meta Custom Audience.
* **Purchasers Exclusion**: When a customer completes checkout, their details are immediately hashed and pushed to the Recent Purchasers Audience to exclude them from cold traffic campaigns.
* **Abandoner Recovery**: Automatically syncs pending checkouts older than 2 hours to your Cart Abandoners Custom Audience.

---

## 12. Dynamic Currency Converter

### How to Setup
* In the Meta settings tab, select your **Ad Account Currency** (e.g., USD) and your base store currency (e.g., BDT).
* If the currencies differ, enter a manual fallback exchange rate.

### How It Works
Sends a request to the Frankfurter currency API to fetch the current exchange rate, caching it for 24 hours using transients. If the API request fails, it falls back to your manual exchange rate to ensure uninterrupted tracking calculations.

---

## 13. Diagnostics & Logging Engine

### How to Setup
* No setup required. Access logs via the **Diagnostics & Logs** tab.

### How It Works
Maintains a detailed log of API requests, raw payloads, timestamps, and HTTP response codes. If a server dispatch fails, you can review the exact API error message (e.g., "Invalid Access Token") to troubleshoot the integration.

---

## 14. ITP & First-Party Cookies

### How It Works
To bypass browser restrictions like Safari's 7-day cookie storage limit (ITP), the plugin sets persistent first-party cookies (such as `_fbp`, `_fbc`, and `_rdt_uuid`) server-side via PHP on page redirect, maintaining session attribution.

---

## 15. Fraud Prevention

### How to Setup
* Enable the Fraud Prevention toggle in the settings.
* Set your fraud risk threshold (e.g., 70%).

### How It Works
Evaluates visitors in real-time. It checks for VPN/Proxy usage using geolocation APIs, scans user agents, and logs threat logs in the database. Suspicious visitors are assigned a risk score; visits exceeding the threshold are blocked from firing tracking scripts.

---

## 16. Cookie Consent v2

### How to Setup
* Enable Consent Integration and select your banner plugin (e.g. Cookiebot).

### How It Works
Intercepts tracking calls, holding them in a temporary queue array inside the client browser. It releases and fires the queued events only after the visitor clicks "Accept" on the cookie banner.

---

## 17. UTM Builder

### How to Setup
* Open the **UTM Builder** tab.
* Enter your website URL, Campaign ID, Source, Medium, and Name. Copy the generated URL.

### How It Works
Formats and encodes your parameters into a clean URL, ensuring consistent tracking structures across all your marketing channels.

---

## 18. Header & Footer Injector

### How to Setup
* Paste your custom header/footer script snippets into the provided textareas.

### How It Works
Enqueues custom scripts safely onto the site's frontend without requiring manual theme code modifications.

---

## 19. Universal Tracker

### How It Works
Loads script enqueues dynamically on the site's frontend. Features a Visual Builder Panel rendered inside an isolated Shadow DOM container on the frontend, allowing admins to map custom click events without styling page element conflicts.

---

## 20. WhatsApp Order Messaging

### How to Setup
* Paste your WhatsApp Gateway API credentials and message template.

### How It Works
Listens for WooCommerce order status changes. When an order status updates (e.g., to "Completed" or "Processing"), the plugin automatically sends a WhatsApp alert to the customer's phone number.

---

## 21. Product Feed Generator

### How to Setup
* Enable the feed generator, set the execution schedule, and copy the generated XML feed URL.

### How It Works
Generates an XML catalog feed of your WooCommerce products, updating it automatically on your selected schedule. Submit this URL to Meta or Google to keep your ad catalog synced.

---

## 22. License Activation Manager

### How to Setup
* Enter your **License Key** and click **Activate**.

### How It Works
Validates your license key against the license server. When active, it stores a local activation status transient to unlock all premium features of the plugin.

---

## 23. AI Ad Engine

### How to Setup
* Configure your Google Gemini API key.

### How It Works
* **Exit-Intent Recovery**: Detects when a user is leaving the site with items in their cart, displaying a dynamic, AI-generated coupon popup to recover the sale.
* **Search Analyzer**: Recommends missing products by matching visitor search terms against your current store inventory.
* **Risk Radar**: Analyzes sessions to detect click-fraud or bot activity.
* **Copy Generator**: Instantly generates Meta ad copy, TikTok video scripts, and Google Search Ads for any selected product.
