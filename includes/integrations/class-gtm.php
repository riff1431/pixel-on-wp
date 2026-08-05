<?php
/**
 * Google Tag Manager Integration Class.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Tracking;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_GTM Class.
 *
 * Handles Google Tag Manager container snippet injection and dataLayer synchronization.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_GTM
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
    $loader->add_action('wp_head', $this, 'inject_gtm_head', 0);
    $loader->add_action('wp_body_open', $this, 'inject_gtm_body', 0);
  }

  /**
   * Inject Google Tag Manager head script snippet.
   *
   * @since 1.0.0
   * @return void
   */
  public function inject_gtm_head(): void
  {
    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();
    $gtm_id = isset($settings['gtm_container_id']) ? trim($settings['gtm_container_id']) : '';

    if (empty($gtm_id)) {
      return;
    }

    $sanitized_id = sanitize_text_field($gtm_id);
    ?>
    <!-- PixelOnWP - Google Tag Manager Head -->
    <script>
      (function (w, d, s, l, i) {
        w[l] = w[l] || []; w[l].push({
          'gtm.start':
            new Date().getTime(), event: 'gtm.js'
        }); var f = d.getElementsByTagName(s)[0],
          j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : ''; j.async = true; j.src =
            'https://www.googletagmanager.com/gtm.js?id=' + i + dl; f.parentNode.insertBefore(j, f);
      })(window, document, 'script', 'wptDataLayer', '<?php echo esc_js($sanitized_id); ?>');
    </script>
    <!-- End Google Tag Manager -->
    <?php
  }

  /**
   * Inject Google Tag Manager body noscript snippet.
   *
   * @since 1.0.0
   * @return void
   */
  public function inject_gtm_body(): void
  {
    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();
    $gtm_id = isset($settings['gtm_container_id']) ? trim($settings['gtm_container_id']) : '';

    if (empty($gtm_id)) {
      return;
    }

    $sanitized_id = sanitize_text_field($gtm_id);
    ?>
    <!-- PixelOnWP - Google Tag Manager Body -->
    <noscript><iframe
        src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($sanitized_id); ?>&l=wptDataLayer" height="0"
        width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php
  }
}