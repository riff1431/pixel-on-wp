<?php
/**
 * GA4 Queue Processor Class.
 *
 * @package PixelOnWP\Includes\Queue
 */

namespace PixelOnWP\Includes\Queue;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_GA4_Queue_Processor {

    /**
     * Register hooks.
     *
     * @param \PixelOnWP\PixelOnWP_Loader $loader
     */
    public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void {
        $loader->add_action('init', $this, 'schedule_ga4_queue_event');
        $loader->add_action('pixelonwp_process_ga4_queue', $this, 'process_ga4_batch');
    }

    /**
     * Schedule recurring GA4 queue processing event.
     */
    public function schedule_ga4_queue_event(): void {
        if (!wp_next_scheduled('pixelonwp_process_ga4_queue')) {
            wp_schedule_event(time(), 'every_five_minutes', 'pixelonwp_process_ga4_queue');
        }
    }

    /**
     * Process a batch of pending or failed GA4 events from database logs.
     */
    public function process_ga4_batch(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'pixelonwp_event_logs';

        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
            return;
        }

        // Fetch up to 20 pending/failed ga4 events
        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE platform = 'ga4' AND status IN ('pending', 'failed') AND retry_count < 3 ORDER BY id ASC LIMIT 20",
                ARRAY_A
            ),
            ARRAY_A
        );

        if (empty($events)) {
            return;
        }

        $options = get_option('PixelOnWP_ga4_config', []);
        $measurement_id = trim($options['measurement_id'] ?? get_option('PixelOnWP_ga4_id', ''));
        $api_secret = trim($options['api_secret'] ?? '');
        $test_code = trim($options['test_code'] ?? '');

        if (empty($measurement_id) || empty($api_secret)) {
            return;
        }

        // Base URL
        $base_url = 'https://www.google-analytics.com/mp/collect';
        $is_debug_mode = !empty($test_code) || strpos($test_code, 'debug_mode=true') !== false;
        if ($is_debug_mode) {
            $base_url = 'https://www.google-analytics.com/debug/mp/collect';
        }

        $url = $base_url . '?measurement_id=' . urlencode($measurement_id) . '&api_secret=' . urlencode($api_secret);

        foreach ($events as $event) {
            $payload = json_decode($event['payload'], true);
            if (!is_array($payload)) {
                continue;
            }

            if (isset($payload['custom_data'])) {
                $enhanced_conversions = !empty($options['enhanced_conversions']);
                if (class_exists('\\PixelOnWP\\Includes\\Platforms\\Ga4\\PixelOnWP_GA4_Event_Mapper')) {
                    $payload = \PixelOnWP\Includes\Platforms\Ga4\PixelOnWP_GA4_Event_Mapper::map_event(
                        $event['event_name'],
                        $payload['custom_data'] ?? [],
                        $payload['user_data'] ?? [],
                        $event['event_id'],
                        $enhanced_conversions
                    );
                }
            }

            // Verify Server execution in events control settings before dispatch (with fallback to true)
            $events_control = get_option('pixelonwp_ga4_events_control', $options['events'] ?? []);
            $event_name = $event['event_name'];
            
            $server_enabled = true;
            if (isset($events_control[$event_name])) {
                $val = $events_control[$event_name];
                if (is_array($val)) {
                    $server_enabled = !isset($val['server']) || filter_var($val['server'], FILTER_VALIDATE_BOOLEAN);
                } else {
                    $server_enabled = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                }
            }

            if (!$server_enabled) {
                // Update status as disabled
                $wpdb->update($table, ['status' => 'disabled'], ['id' => $event['id']], ['%s'], ['%d']);
                continue;
            }

            $response = wp_remote_post($url, [
                'method'      => 'POST',
                'timeout'     => 15,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking'    => true,
                'headers'     => ['Content-Type' => 'application/json'],
                'body'        => wp_json_encode($payload),
            ]);

            $success = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
            $new_status = $success ? 'success' : 'failed';
            $retry_count = (int)$event['retry_count'] + 1;

            // Log JSON response if debug mode is active
            if ($is_debug_mode) {
                $response_body = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
                if (class_exists('\\PixelOnWP\\Services\\Logger')) {
                    \PixelOnWP\Services\Logger::log("GA4 Debug Response: " . $response_body);
                } elseif (class_exists('\\PixelOnWP\\Includes\\Core\\PixelOnWP_Logger')) {
                    $logger = new \PixelOnWP\Includes\Core\PixelOnWP_Logger();
                    $logger->log_event($event_name, $event['event_id'], ['debug_response' => $response_body], $new_status);
                }
            }

            if (class_exists('\\PixelOnWP\\Includes\\Diagnostics\\PixelOnWP_Diagnostics_Logger')) {
                $status_code = is_wp_error($response) ? 'Error' : wp_remote_retrieve_response_code($response) . ' ' . wp_remote_retrieve_response_message($response);
                $msg = is_wp_error($response) ? $response->get_error_message() : 'GA4 MP queue dispatch completed.';
                \PixelOnWP\Includes\Diagnostics\PixelOnWP_Diagnostics_Logger::log_server_event('ga4', $event_name, $status_code, $msg, $payload);
            }

            $wpdb->update(
                $table,
                [
                    'status' => $new_status,
                    'retry_count' => $retry_count,
                ],
                ['id' => $event['id']],
                ['%s', '%d'],
                ['%d']
            );
        }
    }
}
