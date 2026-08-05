<?php
/**
 * PixelOnWP Setup & Operational User Manual
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pixelonwp-docs-wrap" style="max-width: 1200px; margin: 20px 20px 20px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    
    <!-- Hero Header -->
    <div class="pixelonwp-docs-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; padding: 40px 30px; color: #fff; margin-bottom: 30px; box-shadow: 0 10px 20px rgba(0,0,0,0.05);">
        <h1 style="color: #fff; font-size: 32px; margin: 0 0 10px 0; font-weight: 700;"><?php esc_html_e('Setup & Operational User Manual', 'pixel-on-wp'); ?></h1>
        <p style="font-size: 16px; margin: 0; color: #d1fae5;"><?php esc_html_e('Everything you need to master your WordPress tracking, from basic setup to advanced integrations.', 'pixel-on-wp'); ?></p>
    </div>

    <!-- Search/Jump Bar -->
    <div class="pixelonwp-docs-search-bar" style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
        <input type="text" placeholder="<?php esc_attr_e('Search documentation... (Coming soon)', 'pixel-on-wp'); ?>" disabled class="pixelonwp-search-input" style="width: 60%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px;" />
        <a href="<?php echo esc_url(home_url('/pixelonwp/docs/user-documents')); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary button-hero pixelonwp-explore-btn" style="display: flex; align-items: center; gap: 8px; padding: 10px 20px; font-size: 14px; border-radius: 6px; font-weight: 600;">
            <?php esc_html_e('Explore Full Online Documentation', 'pixel-on-wp'); ?>
            <span class="dashicons dashicons-external"></span>
        </a>
    </div>

    <!-- Grid Layout -->
    <div class="pixelonwp-docs-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px;">
        
        <!-- Card 1: Setup Wizard & Credentials -->
        <div class="pixelonwp-docs-card" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
            <h2 style="background: #f8fafc; margin: 0; padding: 15px 20px; font-size: 18px; border-bottom: 1px solid #e2e8f0; color: #0f172a; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <span class="dashicons dashicons-admin-settings" style="color: #10b981;"></span> <?php esc_html_e('1. Credentials Sourcing Guide', 'pixel-on-wp'); ?>
            </h2>
            <div class="pixelonwp-card-content" style="padding: 20px;">
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 12px;">
                    Retrieve and configure integration keys for all platforms:
                </p>
                <ul style="padding-left: 20px; color: #475569; font-size: 14px; list-style-type: disc;">
                    <li style="margin-bottom: 8px;"><strong>Meta:</strong> Copy the Pixel ID from Meta Events Manager, generate a CAPI Access Token under Settings, and add a Test Event Code to verify dispatches.</li>
                    <li style="margin-bottom: 8px;"><strong>TikTok & Reddit:</strong> Retrieve Pixel IDs and Conversions API tokens from their respective settings panels.</li>
                    <li style="margin-bottom: 8px;"><strong>GA4 & Google Ads:</strong> Copy Measurement IDs, MP secrets, Conversion IDs, and conversion labels.</li>
                </ul>
            </div>
        </div>

        <!-- Card 2: Event Manager & Ecommerce -->
        <div class="pixelonwp-docs-card" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
            <h2 style="background: #f8fafc; margin: 0; padding: 15px 20px; font-size: 18px; border-bottom: 1px solid #e2e8f0; color: #0f172a; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <span class="dashicons dashicons-cart" style="color: #10b981;"></span> <?php esc_html_e('2. Events & eCommerce Mappings', 'pixel-on-wp'); ?>
            </h2>
            <div class="pixelonwp-card-content" style="padding: 20px;">
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 12px;">
                    Control how interactions map to standard advertising conversions:
                </p>
                <ul style="padding-left: 20px; color: #475569; font-size: 14px; list-style-type: disc;">
                    <li style="margin-bottom: 8px;"><strong>Event Toggles:</strong> Toggle tracking variables individually for `ViewContent`, `AddToCart`, `InitiateCheckout`, and `Purchase` events.</li>
                    <li style="margin-bottom: 8px;"><strong>Triggers mapping:</strong> Customize which order states (e.g. `Completed` or `Processing`) send a purchase conversion.</li>
                    <li style="margin-bottom: 8px;"><strong>GTM Integration:</strong> Paste your GTM container key to automatically inject required header and body scripts.</li>
                </ul>
            </div>
        </div>

        <!-- Card 3: Utilities & Security -->
        <div class="pixelonwp-docs-card" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
            <h2 style="background: #f8fafc; margin: 0; padding: 15px 20px; font-size: 18px; border-bottom: 1px solid #e2e8f0; color: #0f172a; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <span class="dashicons dashicons-shield" style="color: #10b981;"></span> <?php esc_html_e('3. Security & Utility Manuals', 'pixel-on-wp'); ?>
            </h2>
            <div class="pixelonwp-card-content" style="padding: 20px;">
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 12px;">
                    Activate advanced fraud shields, custom redirects, and campaign taggers:
                </p>
                <ul style="padding-left: 20px; color: #475569; font-size: 14px; list-style-type: disc;">
                    <li style="margin-bottom: 8px;"><strong>Bot Protection:</strong> Enable the fraud blocker to dynamically drop tracking enqueues for search crawlers and scrapers.</li>
                    <li style="margin-bottom: 8px;"><strong>Cookie Consent:</strong> Integrates with your active consent banners to block pixels until approval is granted.</li>
                    <li style="margin-bottom: 8px;"><strong>UTM Link Builder:</strong> Generate campaign-tracked links directly from your dashboard utilities panel.</li>
                    <li style="margin-bottom: 8px;"><strong>Header & Footer:</strong> Inject custom tracking scripts without modifying theme files.</li>
                </ul>
            </div>
        </div>

        <!-- Card 4: Troubleshooting & Logs -->
        <div class="pixelonwp-docs-card" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
            <h2 style="background: #f8fafc; margin: 0; padding: 15px 20px; font-size: 18px; border-bottom: 1px solid #e2e8f0; color: #0f172a; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <span class="dashicons dashicons-welcome-view-site" style="color: #10b981;"></span> <?php esc_html_e('4. Troubleshooting & Event Logs', 'pixel-on-wp'); ?>
            </h2>
            <div class="pixelonwp-card-content" style="padding: 20px;">
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 12px;">
                    Verify event dispatches and inspect CAPI response codes:
                </p>
                <ul style="padding-left: 20px; color: #475569; font-size: 14px; list-style-type: disc;">
                    <li style="margin-bottom: 8px;"><strong>Browser extensions:</strong> Install the Meta, TikTok, and Reddit Pixel Helper browser extensions to audit pixel triggers on your site.</li>
                    <li style="margin-bottom: 8px;"><strong>CAPI Audit Logs:</strong> Check the Diagnostics & Logs table to review server payloads, timestamps, and API response codes.</li>
                </ul>
            </div>
        </div>

    </div>
</div>
