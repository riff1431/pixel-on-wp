<?php
/**
 * Helper and Utility Functions Class.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Helpers;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Helper Class.
 *
 * Provides cryptographic hashing, client IP detection, user agent extraction, and event ID generation.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_Helper
{

  /**
   * Get Catalog ID for a product to match Facebook / TikTok catalogs.
   *
   * @param \WC_Product $product
   * @return string
   */
  public static function get_product_id($product): string
  {
      if (!$product || !is_a($product, 'WC_Product')) {
          return '';
      }
      
      $setting = get_option('pixelonwp_settings', []);
      $identifier_type = isset($setting['product_identifier']) ? $setting['product_identifier'] : 'id';
      
      if ($identifier_type === 'sku') {
          $sku = $product->get_sku();
          $id = !empty($sku) ? (string)$sku : (string)$product->get_id();
      } else {
          $id = (string)$product->get_id();
      }
      
      return apply_filters('PixelOnWP_product_id', $id, $product);
  }

  /**
   * Generate a unique event ID for deduplication between browser and server events.
   *
   * @since 1.0.0
   * @param string $prefix Optional prefix for the event ID.
   * @return string Unique event ID.
   */
  public static function generate_event_id(string $prefix = 'pixelonwp_'): string
  {
    return $prefix . wp_generate_password(16, false) . '_' . time();
  }

  /**
   * Hash user data according to Meta Conversions API specifications (SHA-256, lowercase, trimmed).
   *
   * @since 1.0.0
   * @param string $data  Raw data string (email, phone, name, etc.).
   * @param string $type  Data type ('email', 'phone', 'generic').
   * @return string       Hashed string or empty string.
   */
  public static function hash_user_data(string $data, string $type = 'generic'): string
  {
    $data = trim($data);
    if (empty($data)) {
      return '';
    }

    switch ($type) {
      case 'email':
        $data = strtolower($data);
        break;
      case 'phone':
        // Strip all non-digits to follow Meta CAPI requirement (digits only, no + symbol)
        $data = preg_replace('/\D/', '', $data);
        break;
      case 'name':
      case 'city':
      case 'state':
      case 'country':
        $data = strtolower(preg_replace('/\s+/', '', $data));
        break;
      default:
        $data = strtolower($data);
        break;
    }

    return hash('sha256', $data);
  }

  /**
   * Format phone number to E.164 format.
   *
   * @param string $phone
   * @param string $country_code
   * @return string
   */
  public static function format_phone_e164(string $phone, string $country_code = ''): string
  {
      $clean = preg_replace('/\D/', '', $phone);
      if (empty($clean)) {
          return '';
      }

      if (empty($country_code)) {
          if (function_exists('WC') && WC()->customer) {
              $country_code = WC()->customer->get_billing_country();
          }
          if (empty($country_code)) {
              $default_country = get_option('woocommerce_default_country');
              if (!empty($default_country)) {
                  $parts = explode(':', $default_country);
                  $country_code = $parts[0];
              }
          }
      }

      $calling_code = '';
      if (!empty($country_code)) {
          if (class_exists('WC_Countries')) {
              $wc_countries = new \WC_Countries();
              $calling_codes = $wc_countries->get_country_calling_code($country_code);
              if (!empty($calling_codes)) {
                  $calling_code = is_array($calling_codes) ? $calling_codes[0] : $calling_codes;
                  $calling_code = preg_replace('/\D/', '', $calling_code);
              }
          }
          if (empty($calling_code)) {
              $common = [
                  'BD' => '880', 'US' => '1', 'GB' => '44', 'AE' => '971', 'IN' => '91', 
                  'AU' => '61', 'CA' => '1', 'MY' => '60', 'SG' => '65', 'PK' => '92',
                  'SA' => '966', 'ZA' => '27'
              ];
              $calling_code = $common[strtoupper($country_code)] ?? '';
          }
      }

      if (!empty($calling_code)) {
          if (strpos($clean, '0') === 0) {
              $clean = substr($clean, 1);
          }
          if (strpos($clean, $calling_code) !== 0) {
              $clean = $calling_code . $clean;
          }
      }

      return $clean;
  }

  /**
   * Retrieve client IP address securely, accounting for proxy headers.
   *
   * @since 1.0.0
   * @return string Client IP address.
   */
  public static function get_client_ip(): string
  {
    $ip_address = '';

    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
      // Cloudflare proxy support
      $ip_address = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      // Forwarded header support
      $header = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
      $parts = explode(',', $header);
      $ip_address = trim(reset($parts));
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
      $ip_address = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }

    return filter_var($ip_address, FILTER_VALIDATE_IP) ? $ip_address : '127.0.0.1';
  }

  /**
   * Retrieve client User Agent securely.
   *
   * @since 1.0.0
   * @return string Client User Agent string.
   */
  public static function get_client_user_agent(): string
  {
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      return sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
    }
    return '';
  }

  /**
   * Get the required capability for managing the plugin settings.
   *
   * Allows administrators and shop managers to access the dashboard.
   *
   * @return string Required capability.
   */
  public static function get_admin_capability(): string
  {
    $cap = 'manage_options';
    if (class_exists('WooCommerce')) {
      if (current_user_can('manage_woocommerce') || current_user_can('manage_options')) {
        if (current_user_can('manage_woocommerce')) {
          $cap = 'manage_woocommerce';
        }
      }
    }
    return apply_filters('pixelonwp_admin_capability', $cap);
  }
}