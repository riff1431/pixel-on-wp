<?php
/**
 * Ad Attribution Tracker Class.
 *
 * @package PixelOnWP\Includes
 * @since 1.0.0
 */

namespace PixelOnWP\Includes;

if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Ad_Tracker Class.
 *
 * Tracks ad parameters (UTM, FBCLID) and attaches them to WooCommerce orders.
 */
class PixelOnWP_Ad_Tracker {

  /**
   * Register hooks.
   *
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void {
    $loader->add_action('template_redirect', $this, 'track_incoming_parameters', 5);
    $loader->add_action('wp_enqueue_scripts', $this, 'enqueue_tracker_script');
    $loader->add_action('woocommerce_checkout_update_order_meta', $this, 'attribute_order_meta', 10, 2);
  }

  /**
   * Detect and capture UTM parameters and fbclid from URL.
   * Sets HTTP-only secure first-party cookies.
   *
   * @return void
   */
  public function track_incoming_parameters(): void {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
      return;
    }

    $params = [
      'utm_id'       => FILTER_DEFAULT,
      'utm_campaign' => FILTER_DEFAULT,
      'utm_source'   => FILTER_DEFAULT,
      'utm_medium'   => FILTER_DEFAULT,
      'fbclid'       => FILTER_DEFAULT,
    ];

    $cookie_expiry = time() + (30 * DAY_IN_SECONDS);

    foreach ($params as $key => $filter) {
      if (isset($_GET[$key])) {
        $value = sanitize_text_field(wp_unslash($_GET[$key]));
        if (!empty($value)) {
          // Set cookie. Bypasses Safari ITP 7-day restriction by using server-side headers
          setcookie(
            'pixelonwp_' . $key,
            $value,
            [
              'expires'  => $cookie_expiry,
              'path'     => '/',
              'domain'   => COOKIE_DOMAIN ?: '',
              'secure'   => is_ssl(),
              'httponly' => true,
              'samesite' => 'Lax',
            ]
          );
        }
      }
    }
  }

  /**
   * Enqueue the fallback JavaScript tracking script.
   *
   * @return void
   */
  public function enqueue_tracker_script(): void {
    wp_enqueue_script(
      'pixelonwp-utm-tracker',
      plugins_url('assets/js/utm-tracker.js', __DIR__),
      [],
      PixelOnWP_VERSION,
      true
    );
  }

  /**
   * Attribute metadata to WooCommerce order on checkout.
   *
   * @param int $order_id WooCommerce Order ID.
   * @param array $data Posted checkout data.
   * @return void
   */
  public function attribute_order_meta(int $order_id, array $data = []): void {
    $order = wc_get_order($order_id);
    if (!$order) {
      return;
    }

    $keys = [
      'utm_id'       => '_tracked_ad_campaign_id',
      'utm_campaign' => '_tracked_ad_campaign_name',
      'fbclid'       => '_tracked_ad_fbclid',
    ];

    $attributed = false;

    foreach ($keys as $cookie_suffix => $meta_key) {
      $cookie_name = 'pixelonwp_' . $cookie_suffix;
      $value = '';

      if (isset($_COOKIE[$cookie_name])) {
        $value = sanitize_text_field(wp_unslash($_COOKIE[$cookie_name]));
      }

      if (!empty($value)) {
        $order->update_meta_data($meta_key, $value);
        $attributed = true;
      }
    }

    if ($attributed) {
      $order->update_meta_data('_tracked_attribution_timestamp', time());
      $order->save();
    }
  }
}
