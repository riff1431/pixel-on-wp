<?php
/**
 * WhatsApp Order Messaging Class
 *
 * @package PixelOnWP\Ecommerce
 */

namespace PixelOnWP\Ecommerce;

use Automattic\WooCommerce\Utilities\OrderUtil;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_WhatsApp_Order_Messaging
{
    private $settings;

    public function __construct()
    {
        $this->settings = get_option('pixelonwp_ecommerce_settings', []);
        
        // Default to enabled if not explicitly disabled
        $is_enabled = isset($this->settings['wa_enabled']) ? $this->settings['wa_enabled'] === '1' : true;
        
        if (!$is_enabled) {
            return;
        }

        $this->register_hooks();
    }

    private function register_hooks(): void
    {
        // Enqueue JS for bulk action handling
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Check if HPOS is active
        $hpos_enabled = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && OrderUtil::custom_orders_table_usage_is_enabled();

        if ($hpos_enabled) {
            add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_column'], 20);
            add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'render_column'], 20, 2);
            add_filter('bulk_actions-woocommerce_page_wc-orders', [$this, 'add_bulk_action'], 20);
        } else {
            add_filter('manage_edit-shop_order_columns', [$this, 'add_column'], 20);
            add_action('manage_shop_order_posts_custom_column', [$this, 'render_column_legacy'], 20, 2);
            add_filter('bulk_actions-edit-shop_order', [$this, 'add_bulk_action'], 20);
        }
    }

    public function enqueue_scripts($hook)
    {
        $hpos_enabled = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && OrderUtil::custom_orders_table_usage_is_enabled();
        
        $is_orders_page = false;
        if ($hpos_enabled) {
            if ($hook === 'woocommerce_page_wc-orders') {
                $is_orders_page = true;
            }
        } else {
            if ($hook === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') {
                $is_orders_page = true;
            }
        }

        if ($is_orders_page) {
            wp_enqueue_script(
                'pixelonwp-whatsapp-bulk',
                plugins_url('../../assets/js/whatsapp-bulk.js', __FILE__),
                ['jquery'],
                filemtime(plugin_dir_path(__FILE__) . '../../assets/js/whatsapp-bulk.js'),
                true
            );
        }
    }

    public function add_column($columns)
    {
        $new_columns = [];
        foreach ($columns as $key => $name) {
            $new_columns[$key] = $name;
            if ($key === 'order_total') {
                $new_columns['pixelonwp_whatsapp'] = __('WhatsApp', 'pixel-on-wp');
            }
        }
        return $new_columns;
    }

    public function render_column_legacy($column, $post_id)
    {
        if ($column === 'pixelonwp_whatsapp') {
            $order = wc_get_order($post_id);
            if ($order) {
                $this->render_button($order);
            }
        }
    }

    public function render_column($column, $order)
    {
        if ($column === 'pixelonwp_whatsapp') {
            if ($order) {
                $this->render_button($order);
            }
        }
    }

    private function render_button($order)
    {
        $link = $this->generate_whatsapp_link($order);
        if ($link) {
            echo '<a href="' . esc_url($link) . '" target="_blank" class="button button-primary pixelonwp-wa-btn" style="background-color: #25D366; border-color: #25D366; display: flex; align-items: center; gap: 4px;" data-wa-link="' . esc_url($link) . '">';
            echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>';
            echo 'Chat</a>';
        } else {
            echo '<span style="color: #999;">' . __('No Phone', 'pixel-on-wp') . '</span>';
        }
    }

    public function add_bulk_action($actions)
    {
        $actions['pixelonwp_send_whatsapp'] = __('Send WhatsApp Message', 'pixel-on-wp');
        return $actions;
    }

    private function generate_whatsapp_link($order)
    {
        $phone = $order->get_billing_phone();
        if (empty($phone)) {
            return false;
        }

        $country = $order->get_billing_country();
        $formatted_phone = $this->sanitize_and_format_phone($phone, $country);
        if (!$formatted_phone) {
            return false;
        }

        $template = $this->settings['wa_template'] ?? 'Hello {customer_name}, your order #{order_id} for {order_total} is confirmed! We will ship it to: {shipping_address}';
        
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $order_id = $order->get_order_number();
        $order_total = wp_strip_all_tags(wc_price($order->get_total(), ['currency' => $order->get_currency()]));
        
        $items = $order->get_items();
        $product_names = [];
        foreach ($items as $item) {
            $product_names[] = $item->get_name() . ' x' . $item->get_quantity();
        }
        $product_names_str = implode(', ', $product_names);
        
        $currency = $order->get_currency();
        $shipping_address = $order->get_formatted_shipping_address();
        if (empty(strip_tags($shipping_address))) {
            $shipping_address = $order->get_formatted_billing_address();
        }
        $shipping_address = wp_strip_all_tags(str_replace('<br/>', ', ', $shipping_address));

        $message = str_replace(
            ['{customer_name}', '{order_id}', '{product_names}', '{order_total}', '{currency}', '{shipping_address}'],
            [$customer_name, $order_id, $product_names_str, $order_total, $currency, $shipping_address],
            $template
        );

        return 'https://wa.me/' . $formatted_phone . '?text=' . urlencode($message);
    }

    private function sanitize_and_format_phone($phone, $country)
    {
        // Remove everything except numbers and '+'
        $clean = preg_replace('/[^0-9+]/', '', $phone);
        
        // If empty after cleaning
        if (empty($clean)) {
            return false;
        }

        // If it already has a '+' sign, assume it has a country code. Just strip the '+' and return.
        if (strpos($clean, '+') === 0) {
            return ltrim($clean, '+');
        }

        // It doesn't have a '+'. Let's check if it looks like it lacks an international code.
        // We will use WooCommerce countries to get the calling code.
        $calling_code = $this->get_calling_code($country);
        
        // If the user specified a fallback code, we might use it if the country code from WC fails.
        $fallback = !empty($this->settings['wa_country_code']) ? preg_replace('/[^0-9]/', '', $this->settings['wa_country_code']) : '';
        
        $code_to_use = $calling_code ?: $fallback;

        if ($code_to_use) {
            // Check if the number already starts with the calling code (sometimes people type it without '+')
            // Special handling for local leading zero: e.g., in BD, local is 017, code is 880.
            // If clean is '017...', we strip the '0' and prepend '880'.
            if (strpos($clean, '0') === 0) {
                // Strip the leading zero
                $clean = ltrim($clean, '0');
                // Prepend code
                return $code_to_use . $clean;
            }

            if (strpos($clean, $code_to_use) === 0) {
                // It already has the code
                return $clean;
            }

            // Otherwise, prepend the code
            return $code_to_use . $clean;
        }

        // If no calling code could be determined, just return the clean number (might fail wa.me but nothing else we can do)
        return $clean;
    }

    private function get_calling_code($country_code)
    {
        if (empty($country_code)) {
            return false;
        }

        if (class_exists('WC_Countries')) {
            $wc_countries = new \WC_Countries();
            $calling_codes = $wc_countries->get_country_calling_code($country_code);
            if (!empty($calling_codes)) {
                $code = is_array($calling_codes) ? $calling_codes[0] : $calling_codes;
                return preg_replace('/[^0-9]/', '', $code);
            }
        }
        
        // Manual fallback for common ones if WC_Countries fails
        $common = [
            'BD' => '880', 'US' => '1', 'GB' => '44', 'AE' => '971', 'IN' => '91', 
            'AU' => '61', 'CA' => '1', 'MY' => '60', 'SG' => '65', 'PK' => '92',
            'SA' => '966', 'ZA' => '27'
        ];

        return $common[$country_code] ?? false;
    }
}
