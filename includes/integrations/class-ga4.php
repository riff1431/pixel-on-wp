<?php
/**
 * Google Analytics 4 (GA4) Integration Class.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
namespace PixelOnWP\Includes\Integrations;
// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_GA4 Class.
 *
 * Manages Google Analytics 4 tracking tags, measurement ID integration, and e-commerce event mapping.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_GA4
{

  /**
   * Register hooks with WordPress.
   *
   * @since 1.0.0
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
  {
    $loader->add_action('wp_head', $this, 'inject_ga4_tag', 1);
  }

  /**
   * Inject Google Analytics 4 (gtag.js) tracking tag.
   *
   * @since 1.0.0
   * @return void
   */
  public function inject_ga4_tag(): void
  {
    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();
    $ga4_id = isset($settings['ga4_measurement_id']) ? trim($settings['ga4_measurement_id']) : '';

    if (empty($ga4_id)) {
      return;
    }

    $sanitized_id = sanitize_text_field($ga4_id);
    ?>
    <!-- PixelOnWP - Google Analytics 4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($sanitized_id); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag() { dataLayer.push(arguments); }
      gtag('js', new Date());
      gtag('config', '<?php echo esc_js($sanitized_id); ?>');
    </script>
    <!-- End Google Analytics 4 -->
    <?php
  }

  /**
   * Generate GA4 event tracking snippet or push to dataLayer.
   *
   * @since 1.0.0
   * @param string $event_name GA4 event name (e.g., 'view_item', 'add_to_cart', 'purchase').
   * @param array  $params     Event parameters.
   * @return string            JavaScript tag string.
   */
  public static function generate_event_snippet(string $event_name, array $params = []): string
  {
    $sanitized_event = sanitize_text_field($event_name);
    $encoded_params = !empty($params) ? wp_json_encode($params) : '{}';

    return sprintf(
      "<script>if (typeof gtag === 'function') { gtag('event', '%s', %s); }</script>",
      esc_js($sanitized_event),
      $encoded_params
    );
  }
}