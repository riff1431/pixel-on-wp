<?php
/**
 * eCommerce Tools Admin logic.
 *
 * @package PixelOnWP\Ecommerce
 */

namespace PixelOnWP\Ecommerce;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_Ecommerce_Tools
{
    public function register_hooks($loader): void
    {
        $loader->add_action('wp_ajax_pixelonwp_save_ecommerce_settings', $this, 'ajax_save_ecommerce_settings');
    }

    /**
     * AJAX handler to save eCommerce settings.
     */
    public function ajax_save_ecommerce_settings(): void
    {
        check_ajax_referer('PixelOnWP_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
        }

        $settings = get_option('pixelonwp_ecommerce_settings', []);
        $settings['push_enabled'] = isset($_POST['push_enabled']) && $_POST['push_enabled'] === '1' ? '1' : '0';
        $settings['wa_enabled'] = isset($_POST['wa_enabled']) && $_POST['wa_enabled'] === '1' ? '1' : '0';
        
        if (isset($_POST['wa_template'])) {
            $settings['wa_template'] = sanitize_textarea_field(wp_unslash($_POST['wa_template']));
        }
        
        if (isset($_POST['wa_country_code'])) {
            $settings['wa_country_code'] = sanitize_text_field(wp_unslash($_POST['wa_country_code']));
        }

        update_option('pixelonwp_ecommerce_settings', $settings);

        wp_send_json_success(['message' => __('Settings updated successfully.', 'pixel-on-wp')]);
    }
}
