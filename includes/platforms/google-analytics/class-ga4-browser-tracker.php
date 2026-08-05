<?php
/**
 * GA4 Browser Tracker Class.
 *
 * @package PixelOnWP\Includes\Platforms\GoogleAnalytics
 */

namespace PixelOnWP\Includes\Platforms\GoogleAnalytics;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_GA4_Browser_Tracker {

    /**
     * Enqueue GA4 tracking scripts if GA4 setup is active.
     */
    public static function enqueue_scripts(): void {
        $options = PixelOnWP_GA4_Admin_Options::get_options();
        $measurement_id = trim($options['measurement_id'] ?? '');

        if (empty($measurement_id)) {
            return;
        }

        // Output global site tag (gtag.js)
        add_action('wp_head', function() use ($measurement_id, $options) {
            $debug_mode = (!empty($options['test_code']) || is_user_logged_in()) ? 'true' : 'false';
            ?>
            <!-- Global site tag (gtag.js) - Google Analytics -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($measurement_id); ?>"></script>
            <script>
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());
              gtag('config', '<?php echo esc_js($measurement_id); ?>', {
                'debug_mode': <?php echo $debug_mode; ?>
              });
            </script>
            <?php
        }, 1);
    }
}
