<?php
/**
 * PixelOnWP Developer & System Architecture Guide
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pixelonwp-docs-wrap" style="max-width: 1200px; margin: 20px 20px 20px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    
    <!-- Hero Header -->
    <div class="pixelonwp-docs-header" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 12px; padding: 40px 30px; color: #fff; margin-bottom: 30px; box-shadow: 0 10px 20px rgba(0,0,0,0.05);">
        <h1 style="color: #fff; font-size: 32px; margin: 0 0 10px 0; font-weight: 700;"><?php esc_html_e('Developer & System Architecture Guide', 'pixel-on-wp'); ?></h1>
        <p style="font-size: 16px; margin: 0; color: #94a3b8;"><?php esc_html_e('Internal systems guide outlining autoloader configurations, database schemas, CAPI integrations, and AI models.', 'pixel-on-wp'); ?></p>
    </div>

    <!-- Grid Layout -->
    <div class="pixelonwp-docs-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px;">
        
        <!-- Card 1: Autoloader & Bootstrap -->
        <div class="pixelonwp-docs-card" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
            <h2 style="background: #f8fafc; margin: 0; padding: 15px 20px; font-size: 18px; border-bottom: 1px solid #e2e8f0; color: #0f172a; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <span class="dashicons dashicons-editor-code" style="color: #3b82f6;"></span> <?php esc_html_e('1. Autoloader & Bootstrap Lifecycle', 'pixel-on-wp'); ?>
            </h2>
            <div class="pixelonwp-card-content" style="padding: 20px;">
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 12px;">
                    The plugin begins execution from the root file <code>pixel-on-wp.php</code>, registering custom activator/deactivator classes and mapping namespace autoloaders directly to file paths matching classes.
                </p>
                <ul style="padding-left: 20px; color: #475569; font-size: 14px; list-style-type: disc;">
                    <li style="margin-bottom: 8px;"><strong>Loader Registration:</strong> Instantiates <code>class-loader.php</code> to manage admin/frontend actions and filters with WordPress.</li>
                    <li style="margin-bottom: 8px;"><strong>Bootstrap Lifecycles:</strong> Initializes active platforms, background cron handlers, REST endpoints, and log processors.</li>
                </ul>
            </div>
        </div>

        <!-- Card 2: Database Option Schemas -->
        <div class="pixelonwp-docs-card" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
            <h2 style="background: #f8fafc; margin: 0; padding: 15px 20px; font-size: 18px; border-bottom: 1px solid #e2e8f0; color: #0f172a; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <span class="dashicons dashicons-database" style="color: #3b82f6;"></span> <?php esc_html_e('2. Core Database Schemas', 'pixel-on-wp'); ?>
            </h2>
            <div class="pixelonwp-card-content" style="padding: 20px;">
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 12px;">
                    During activator installation, the plugin sets up custom tables to manage visit profiles and dispatch audit trails:
                </p>
                <ul style="padding-left: 20px; color: #475569; font-size: 14px; list-style-type: disc;">
                    <li style="margin-bottom: 8px;"><code>wp_pixelonwp_visitor_intelligence</code>: Stores session tracking logs, proxied details, query variables, and behavioral scores.</li>
                    <li style="margin-bottom: 8px;"><code>wp_pixelonwp_event_logs</code>: Main audit trail recording request payloads, target endpoints, response status codes, and errors.</li>
                </ul>
            </div>
        </div>

        <!-- Card 3: Multi-Platform CAPI -->
        <div class="pixelonwp-docs-card" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
            <h2 style="background: #f8fafc; margin: 0; padding: 15px 20px; font-size: 18px; border-bottom: 1px solid #e2e8f0; color: #0f172a; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <span class="dashicons dashicons-networking" style="color: #3b82f6;"></span> <?php esc_html_e('3. Conversions API Payloads', 'pixel-on-wp'); ?>
            </h2>
            <div class="pixelonwp-card-content" style="padding: 20px;">
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 12px;">
                    Dispatches backend events via cURL request helpers to platform server endpoints:
                </p>
                <ul style="padding-left: 20px; color: #475569; font-size: 14px; list-style-type: disc;">
                    <li style="margin-bottom: 8px;"><strong>Meta CAPI:</strong> Calls Graph API v19.0 using HTTP Bearer authorization tokens.</li>
                    <li style="margin-bottom: 8px;"><strong>TikTok & Reddit:</strong> Calls corresponding events API endpoints using token headers.</li>
                    <li style="margin-bottom: 8px;"><strong>GA4 Server-Side:</strong> Dispatches to Measurement Protocol routes containing Measurement IDs and API secrets.</li>
                </ul>
            </div>
        </div>

        <!-- Card 4: Deduplication & Encryption -->
        <div class="pixelonwp-docs-card" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
            <h2 style="background: #f8fafc; margin: 0; padding: 15px 20px; font-size: 18px; border-bottom: 1px solid #e2e8f0; color: #0f172a; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <span class="dashicons dashicons-shield-alt" style="color: #3b82f6;"></span> <?php esc_html_e('4. Deduplication & Hashing', 'pixel-on-wp'); ?>
            </h2>
            <div class="pixelonwp-card-content" style="padding: 20px;">
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 12px;">
                    Integrates advanced data normalizations and deduplication triggers:
                </p>
                <ul style="padding-left: 20px; color: #475569; font-size: 14px; list-style-type: disc;">
                    <li style="margin-bottom: 8px;"><strong>Deduplication matching:</strong> Generates unique <code>event_id</code> keys containing hashes and action timestamps. The matching key is passed to both browser pixel queues and CAPI parameters to prevent duplicate tracking counts.</li>
                    <li style="margin-bottom: 8px;"><strong>SHA-256 matching:</strong> Formats customer identifiers (email, phone, first/last name, address) to trimmed lowercases and encrypts them using SHA-256 before API dispatching.</li>
                </ul>
            </div>
        </div>

        <!-- Card 5: Security & Privacy Compliance -->
        <div class="pixelonwp-docs-card" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
            <h2 style="background: #f8fafc; margin: 0; padding: 15px 20px; font-size: 18px; border-bottom: 1px solid #e2e8f0; color: #0f172a; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <span class="dashicons dashicons-lock" style="color: #3b82f6;"></span> <?php esc_html_e('5. ITP Safeguards & Bot Protection', 'pixel-on-wp'); ?>
            </h2>
            <div class="pixelonwp-card-content" style="padding: 20px;">
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 12px;">
                    Bypasses browser limits and blocks bot traffic:
                </p>
                <ul style="padding-left: 20px; color: #475569; font-size: 14px; list-style-type: disc;">
                    <li style="margin-bottom: 8px;"><strong>ITP Cookie Persistence:</strong> Creates first-party HttpOnly cookies server-side on redirects to bypass Safari's 7-day storage expiration.</li>
                    <li style="margin-bottom: 8px;"><strong>Bot Obfuscation:</strong> Drops tracking dispatches for crawlers and masks IP address octets to enforce privacy compliance.</li>
                    <li style="margin-bottom: 8px;"><strong>Consent Queues:</strong> Temporarily holds tracking triggers in browser queues until Cookie Consent v2 flags are set.</li>
                </ul>
            </div>
        </div>

        <!-- Card 6: AI Flash Engine Models -->
        <div class="pixelonwp-docs-card" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
            <h2 style="background: #f8fafc; margin: 0; padding: 15px 20px; font-size: 18px; border-bottom: 1px solid #e2e8f0; color: #0f172a; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <span class="dashicons dashicons-visibility" style="color: #3b82f6;"></span> <?php esc_html_e('6. AI Gemini Engine Triggers', 'pixel-on-wp'); ?>
            </h2>
            <div class="pixelonwp-card-content" style="padding: 20px;">
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 12px;">
                    Uses Google Gemini 1.5 Flash via <code>OMNITRACK_GEMINI_KEY</code> to analyze user behavior:
                </p>
                <ul style="padding-left: 20px; color: #475569; font-size: 14px; list-style-type: disc;">
                    <li style="margin-bottom: 8px;"><strong>Exit intent:</strong> Generates dynamic, custom discount code copy based on visitor cart lists.</li>
                    <li style="margin-bottom: 8px;"><strong>Search analyzer:</strong> Flags search queries for items not currently in stock to highlight catalog gaps.</li>
                    <li style="margin-bottom: 8px;"><strong>Threat radar:</strong> Scores visitor requests for VPN or proxy anomalies.</li>
                </ul>
            </div>
        </div>

    </div>
</div>
