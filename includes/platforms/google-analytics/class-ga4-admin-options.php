<?php
/**
 * GA4 Admin Options and settings Manager.
 *
 * @package PixelOnWP\Includes\Platforms\GoogleAnalytics
 */

namespace PixelOnWP\Includes\Platforms\GoogleAnalytics;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_GA4_Admin_Options {

    /**
     * Get GA4 Options.
     *
     * @return array
     */
    public static function get_options(): array {
        $options = get_option('PixelOnWP_ga4_config', [
            'setup_type'     => 'basic',
            'measurement_id' => '',
            'api_secret'     => '',
            'test_code'      => '',
            'events'         => [],
            'custom_events'  => []
        ]);
        
        if (empty($options['measurement_id'])) {
            $options['measurement_id'] = get_option('PixelOnWP_ga4_id', '');
        }
        
        return $options;
    }

    /**
     * Update GA4 Options.
     *
     * @param array $options
     * @return bool
     */
    public static function update_options(array $options): bool {
        $old_options = self::get_options();
        $new_options = array_merge($old_options, $options);
        return update_option('PixelOnWP_ga4_config', $new_options);
    }
}
