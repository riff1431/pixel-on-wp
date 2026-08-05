<?php
/**
 * Queue Processor Class.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Queue;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Queue_Processor Class.
 *
 * Handles asynchronous background batch processing of queued CAPI tracking events.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_Queue_Processor
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
    $loader->add_action('init', $this, 'schedule_queue_event');
    $loader->add_action('pixelonwp_process_event_queue', $this, 'process_batch');
  }

  /**
   * Schedule recurring background queue processing event if not already scheduled.
   *
   * @since 1.0.0
   * @return void
   */
  public function schedule_queue_event(): void
  {
    if (!wp_next_scheduled('pixelonwp_process_event_queue')) {
      wp_schedule_event(time(), 'every_five_minutes', 'pixelonwp_process_event_queue');
    }
  }

  /**
   * Process a batch of pending or failed events from the event logs queue.
   *
   * @since 1.0.0
   * @return void
   */
  public function process_batch(): void
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';

    // Check if table exists
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return;
    }

    // Fetch up to 20 pending or failed events with retry_count < 3
    $events = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM {$table} WHERE status IN (%s, %s) AND retry_count < %d ORDER BY id ASC LIMIT 20",
        'pending',
        'failed',
        3
      ),
      ARRAY_A
    );

    if (empty($events)) {
      return;
    }

    $meta_config = get_option('PixelOnWP_meta_config', []);
    $meta_pixel_id = isset($meta_config['pixel_id']) ? trim($meta_config['pixel_id']) : '';
    $meta_capi_token = isset($meta_config['capi_token']) ? trim($meta_config['capi_token']) : '';

    $tt_config = get_option('PixelOnWP_tiktok_config', []);
    $tt_access_token = isset($tt_config['access_token']) ? trim($tt_config['access_token']) : '';

    foreach ($events as $event) {
      $payload = json_decode($event['payload'], true);
      if (!is_array($payload)) {
        continue;
      }
      
      $platform = $event['platform'];
      $url = '';
      $headers = ['Content-Type' => 'application/json'];

      if ($platform === 'tiktok') {
          if (empty($tt_access_token)) continue;
          $url = 'https://business-api.tiktok.com/open_api/v1.3/pixel/track/';
          $headers['Access-Token'] = $tt_access_token;
      } elseif ($platform === 'ga4') {
          $ga_options = \PixelOnWP\Includes\Platforms\GoogleAnalytics\PixelOnWP_GA4_Admin_Options::get_options();
          $measurement_id = trim($ga_options['measurement_id'] ?? '');
          $api_secret = trim($ga_options['api_secret'] ?? '');
          if (empty($measurement_id)) continue;
          $url = 'https://www.google-analytics.com/mp/collect?measurement_id=' . urlencode($measurement_id);
          if ($ga_options['setup_type'] === 'advanced' && !empty($api_secret)) {
              $url .= '&api_secret=' . urlencode($api_secret);
          }
      } else {
          if (empty($meta_pixel_id) || empty($meta_capi_token)) continue;
          $url = "https://graph.facebook.com/v19.0/{$meta_pixel_id}/events?access_token={$meta_capi_token}";
      }

      if ($platform === 'ga4' && isset($payload['custom_data'])) {
          $enhanced_conversions = !empty($ga_options['enhanced_conversions']);
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

      $response = wp_remote_post($url, [
        'method'      => 'POST',
        'timeout'     => 15,
        'redirection' => 5,
        'httpversion' => '1.1',
        'blocking'    => true,
        'headers'     => $headers,
        'body'        => wp_json_encode($payload),
      ]);

      $success = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
      $new_status = $success ? 'success' : 'failed';
      $retry_count = (int) $event['retry_count'] + 1;

      if ($platform === 'ga4' && class_exists('\\PixelOnWP\\Includes\\Diagnostics\\PixelOnWP_Diagnostics_Logger')) {
          $status_code = is_wp_error($response) ? 'Error' : wp_remote_retrieve_response_code($response) . ' ' . wp_remote_retrieve_response_message($response);
          $msg = is_wp_error($response) ? $response->get_error_message() : 'GA4 MP queue dispatch completed.';
          \PixelOnWP\Includes\Diagnostics\PixelOnWP_Diagnostics_Logger::log_server_event('ga4', $event['event_name'], $status_code, $msg, $payload);
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

  /**
   * Process all failed and pending events from the queue manually, ignoring retry limit.
   *
   * @since 1.0.0
   * @return void
   */
  public function process_failed_queue(): array
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';
    
    $results = [
      'success_count' => 0,
      'failed_events' => []
    ];

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return $results;
    }

    $events = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM {$table} WHERE status IN (%s, %s) ORDER BY id ASC LIMIT 50",
        'pending',
        'failed'
      ),
      ARRAY_A
    );

    if (empty($events)) {
      return $results;
    }

    $meta_config = get_option('PixelOnWP_meta_config', []);
    $meta_pixel_id = isset($meta_config['pixel_id']) ? trim($meta_config['pixel_id']) : '';
    $meta_capi_token = isset($meta_config['capi_token']) ? trim($meta_config['capi_token']) : '';

    $tt_config = get_option('PixelOnWP_tiktok_config', []);
    $tt_access_token = isset($tt_config['access_token']) ? trim($tt_config['access_token']) : '';

    foreach ($events as $event) {
      $payload = json_decode($event['payload'], true);
      if (!is_array($payload)) {
        continue;
      }
      
      if (isset($payload['_PixelOnWP_error'])) {
        unset($payload['_PixelOnWP_error']);
      }

      $platform = $event['platform'];
      $url = '';
      $headers = ['Content-Type' => 'application/json'];

      if ($platform === 'tiktok') {
          if (empty($tt_access_token)) {
              $results['failed_events'][] = [
                  'uid' => 'evt_' . $event['id'],
                  'platform' => 'tiktok',
                  'error' => 'Missing TikTok Access Token. Please configure your TikTok settings.'
              ];
              continue;
          }
          $url = 'https://business-api.tiktok.com/open_api/v1.3/pixel/track/';
          $headers['Access-Token'] = $tt_access_token;
      } elseif ($platform === 'ga4') {
          $ga_options = \PixelOnWP\Includes\Platforms\GoogleAnalytics\PixelOnWP_GA4_Admin_Options::get_options();
          $measurement_id = trim($ga_options['measurement_id'] ?? '');
          $api_secret = trim($ga_options['api_secret'] ?? '');
          if (empty($measurement_id)) {
              $results['failed_events'][] = [
                  'uid' => 'evt_' . $event['id'],
                  'platform' => 'ga4',
                  'error' => 'Missing GA4 Measurement ID.'
              ];
              continue;
          }
          $url = 'https://www.google-analytics.com/mp/collect?measurement_id=' . urlencode($measurement_id);
          if ($ga_options['setup_type'] === 'advanced' && !empty($api_secret)) {
              $url .= '&api_secret=' . urlencode($api_secret);
          }
      } else {
          if (empty($meta_pixel_id) || empty($meta_capi_token)) {
              $results['failed_events'][] = [
                  'uid' => 'evt_' . $event['id'],
                  'platform' => 'facebook',
                  'error' => 'Missing Meta Pixel ID or CAPI Token. Please configure your Meta settings.'
              ];
              continue;
          }
          $url = "https://graph.facebook.com/v19.0/{$meta_pixel_id}/events?access_token={$meta_capi_token}";
      }

      if ($platform === 'ga4' && isset($payload['custom_data'])) {
          $enhanced_conversions = !empty($ga_options['enhanced_conversions']);
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

      $response = wp_remote_post($url, [
        'method'      => 'POST',
        'timeout'     => 15,
        'redirection' => 5,
        'httpversion' => '1.1',
        'blocking'    => true,
        'headers'     => $headers,
        'body'        => wp_json_encode($payload),
      ]);

      $success = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
      $new_status = $success ? 'success' : 'failed';
      $retry_count = (int) $event['retry_count'] + 1;

      if ($platform === 'ga4' && class_exists('\\PixelOnWP\\Includes\\Diagnostics\\PixelOnWP_Diagnostics_Logger')) {
          $status_code = is_wp_error($response) ? 'Error' : wp_remote_retrieve_response_code($response) . ' ' . wp_remote_retrieve_response_message($response);
          $msg = is_wp_error($response) ? $response->get_error_message() : 'GA4 MP manual retry completed.';
          \PixelOnWP\Includes\Diagnostics\PixelOnWP_Diagnostics_Logger::log_server_event('ga4', $event['event_name'], $status_code, $msg, $payload);
      }
      
      if ($success) {
          $results['success_count']++;
      } else {
        $error_msg = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
        $parsed_error = json_decode($error_msg, true) ?: $error_msg;
        $payload['_PixelOnWP_error'] = $parsed_error;
        
        $results['failed_events'][] = [
            'uid' => 'evt_' . $event['id'],
            'platform' => $platform ?: 'facebook',
            'error' => is_string($parsed_error) ? $parsed_error : wp_json_encode($parsed_error)
        ];
      }

      $wpdb->update(
        $table,
        [
          'status' => $new_status,
          'retry_count' => $retry_count,
          'payload' => wp_json_encode($payload)
        ],
        ['id' => $event['id']],
        ['%s', '%d', '%s'],
        ['%d']
      );
    }
    
    return $results;
  }
}