<?php
/**
 * Support Ticket System Class.
 *
 * Handles automated system diagnostics, local ticket storage, REST API endpoints,
 * file attachment handling, and outbound synchronization with Central Support Hub.
 *
 * @package PixelOnWP\Includes\Support
 * @since 1.0.1
 */

namespace PixelOnWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Tracker_Support Class.
 */
class PixelOnWP_Tracker_Support
{
  /**
   * Option name for storing local tickets history.
   */
  const OPTION_KEY = 'pixelonwp_support_tickets';

  /**
   * Register hooks with WordPress.
   *
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
  {
    $loader->add_action('rest_api_init', $this, 'register_rest_routes');
    $loader->add_action('wp_ajax_pixelonwp_create_ticket', $this, 'ajax_create_ticket');
    $loader->add_action('wp_ajax_pixelonwp_send_reply', $this, 'ajax_send_reply');
    $loader->add_action('wp_ajax_pixelonwp_close_ticket', $this, 'ajax_close_ticket');
    $loader->add_action('wp_ajax_pixelonwp_poll_support', $this, 'ajax_poll_support');

    // Ensure default demo seed data exists on init.
    $this->ensure_seed_data();
  }

  /**
   * Register REST API endpoints.
   *
   * @return void
   */
  public function register_rest_routes(): void
  {
    // List & Create Tickets
    register_rest_route('pixelonwp/v1', '/support/tickets', [
      [
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => [$this, 'rest_get_tickets'],
        'permission_callback' => [$this, 'check_permissions'],
      ],
      [
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => [$this, 'rest_create_ticket'],
        'permission_callback' => [$this, 'check_permissions'],
      ],
    ]);

    // Single Ticket Details, Reply & Close
    register_rest_route('pixelonwp/v1', '/support/tickets/(?P<id>[\w-]+)', [
      [
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => [$this, 'rest_get_ticket_details'],
        'permission_callback' => [$this, 'check_permissions'],
      ],
    ]);

    register_rest_route('pixelonwp/v1', '/support/tickets/(?P<id>[\w-]+)/reply', [
      'methods'             => \WP_REST_Server::CREATABLE,
      'callback'            => [$this, 'rest_send_reply'],
      'permission_callback' => [$this, 'check_permissions'],
    ]);

    register_rest_route('pixelonwp/v1', '/support/tickets/(?P<id>[\w-]+)/close', [
      'methods'             => \WP_REST_Server::CREATABLE,
      'callback'            => [$this, 'rest_close_ticket'],
      'permission_callback' => [$this, 'check_permissions'],
    ]);

    // Live Polling & Developer Status
    register_rest_route('pixelonwp/v1', '/support/status', [
      'methods'             => \WP_REST_Server::READABLE,
      'callback'            => [$this, 'rest_get_support_status'],
      'permission_callback' => [$this, 'check_permissions'],
    ]);

    // Secondary Polling Receiver Endpoint as per specification (/wp-json/tracker-pro/v1/update-ticket)
    register_rest_route('tracker-pro/v1', '/update-ticket', [
      'methods'             => \WP_REST_Server::READABLE,
      'callback'            => [$this, 'rest_get_support_status'],
      'permission_callback' => [$this, 'check_permissions'],
    ]);
  }

  /**
   * Permission callback for REST routes.
   *
   * @return bool|\WP_Error
   */
  public function check_permissions()
  {
    if (!current_user_can('manage_options')) {
      return new \WP_Error('rest_forbidden', __('You do not have permission to access support features.', 'pixel-on-wp'), ['status' => 403]);
    }
    return true;
  }

  /**
   * Generates automated system diagnostics payload.
   *
   * @return array
   */
  public function get_system_diagnostics(): array
  {
    if (!function_exists('get_plugins')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $all_plugins = get_plugins();
    $active_plugin_slugs = get_option('active_plugins', []);
    $active_plugins_list = [];

    foreach ($active_plugin_slugs as $plugin_slug) {
      if (isset($all_plugins[$plugin_slug])) {
        $active_plugins_list[] = [
          'name'    => $all_plugins[$plugin_slug]['Name'],
          'version' => $all_plugins[$plugin_slug]['Version'],
        ];
      }
    }

    $current_theme = wp_get_theme();

    // Gather active pixelonwp settings
    $pixelonwp_config = [
      'platforms'    => get_option('PixelOnWP_selected_platforms', []),
      'meta'         => get_option('PixelOnWP_meta_config', []),
      'tiktok'       => get_option('PixelOnWP_tiktok_config', []),
      'reddit'       => get_option('PixelOnWP_reddit_config', []),
      'pinterest'    => get_option('PixelOnWP_pinterest_config', []),
      'google'       => get_option('PixelOnWP_google_config', []),
      'events'       => get_option('PixelOnWP_active_events', []),
      'gtm_id'       => get_option('PixelOnWP_gtm_id', ''),
      'ga4_id'       => get_option('PixelOnWP_ga4_id', ''),
      'ga4_config'   => get_option('PixelOnWP_ga4_config', []),
      'form_tracking'=> get_option('PixelOnWP_form_tracking', []),
      'ecommerce'    => get_option('pixelonwp_ecommerce_settings', []),
    ];

    return [
      'wp_version'      => get_bloginfo('version'),
      'php_version'      => PHP_VERSION,
      'web_server'      => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field($_SERVER['SERVER_SOFTWARE']) : 'Unknown',
      'active_theme'    => [
        'name'    => $current_theme->get('Name'),
        'version' => $current_theme->get('Version'),
      ],
      'active_plugins'  => $active_plugins_list,
      'pixelonwp_config'=> $pixelonwp_config,
      'generated_at'    => current_time('mysql'),
    ];
  }

  /**
   * Seed default ticket #102 matching reference design if local list is empty.
   *
   * @return void
   */
  public function ensure_seed_data(): void
  {
    $tickets = get_option(self::OPTION_KEY, null);

    if (null === $tickets || !is_array($tickets)) {
      $diagnostics = $this->get_system_diagnostics();

      $initial_tickets = [
        '102' => [
          'id'                  => '102',
          'subject'             => 'GA4 Purchase Event Issue',
          'category'            => 'E-commerce',
          'priority'            => 'High',
          'status'              => 'Open',
          'created_at'          => date('Y-m-d H:i:s', strtotime('-2 hours')),
          'last_updated'        => 'Just Now',
          'developer_online'    => true,
          'developer_typing'    => true,
          'system_diagnostics'  => $diagnostics,
          'attachments'         => [],
          'messages'            => [
            [
              'id'        => 'msg_1',
              'sender'    => 'user',
              'name'      => 'YOU / USER',
              'timestamp' => '2 hours ago',
              'text'      => 'My purchase events are double counting in GA4 when using WooCommerce AJAX cart.',
              'attachment'=> null,
            ],
            [
              'id'        => 'msg_2',
              'sender'    => 'developer',
              'name'      => 'DEVELOPER / SUPPORT TEAM',
              'timestamp' => '1 hour ago',
              'text'      => 'Hi John, please enable \'Deduplicate Events\' in the settings tab and clear your cache.',
              'attachment'=> null,
            ],
          ],
        ],
      ];

      update_option(self::OPTION_KEY, $initial_tickets);
    }
  }

  /**
   * GET /pixelonwp/v1/support/tickets
   */
  public function rest_get_tickets(\WP_REST_Request $request): \WP_REST_Response
  {
    $tickets = get_option(self::OPTION_KEY, []);
    return new \WP_REST_Response([
      'success' => true,
      'tickets' => array_values($tickets),
    ], 200);
  }

  /**
   * GET /pixelonwp/v1/support/tickets/{id}
   */
  public function rest_get_ticket_details(\WP_REST_Request $request): \WP_REST_Response
  {
    $id = sanitize_key($request->get_param('id'));
    $tickets = get_option(self::OPTION_KEY, []);

    if (!isset($tickets[$id])) {
      return new \WP_REST_Response([
        'success' => false,
        'message' => __('Ticket not found.', 'pixel-on-wp'),
      ], 404);
    }

    return new \WP_REST_Response([
      'success' => true,
      'ticket'  => $tickets[$id],
    ], 200);
  }

  /**
   * POST /pixelonwp/v1/support/tickets
   */
  public function rest_create_ticket(\WP_REST_Request $request): \WP_REST_Response
  {
    $subject     = sanitize_text_field($request->get_param('subject') ?? '');
    $category    = sanitize_text_field($request->get_param('category') ?? 'General Bug');
    $priority    = sanitize_text_field($request->get_param('priority') ?? 'Medium');
    $description = sanitize_textarea_field($request->get_param('description') ?? '');

    if (empty($subject) || empty($description)) {
      return new \WP_REST_Response([
        'success' => false,
        'message' => __('Subject and description are required.', 'pixel-on-wp'),
      ], 400);
    }

    $tickets = get_option(self::OPTION_KEY, []);
    $new_id = (string) (count($tickets) > 0 ? (max(array_map('intval', array_keys($tickets))) + 1) : 101);

    $attachment_url = '';
    if (!empty($_FILES['attachment']['name'])) {
      $attachment_url = $this->handle_image_upload($_FILES['attachment']);
    }

    $diagnostics = $this->get_system_diagnostics();

    $new_ticket = [
      'id'                  => $new_id,
      'subject'             => $subject,
      'category'            => $category,
      'priority'            => $priority,
      'status'              => 'Open',
      'created_at'          => current_time('mysql'),
      'last_updated'        => 'Just Now',
      'developer_online'    => true,
      'developer_typing'    => false,
      'system_diagnostics'  => $diagnostics,
      'attachments'         => $attachment_url ? [$attachment_url] : [],
      'messages'            => [
        [
          'id'        => 'msg_' . time(),
          'sender'    => 'user',
          'name'      => 'YOU / USER',
          'timestamp' => 'Just Now',
          'text'      => $description,
          'attachment'=> $attachment_url,
        ],
      ],
    ];

    $tickets[$new_id] = $new_ticket;
    update_option(self::OPTION_KEY, $tickets);

    // Trigger outbound API post (placeholder for central API backend)
    $this->send_outbound_ticket_creation($new_ticket);

    return new \WP_REST_Response([
      'success' => true,
      'message' => __('Support ticket submitted successfully.', 'pixel-on-wp'),
      'ticket'  => $new_ticket,
    ], 200);
  }

  /**
   * POST /pixelonwp/v1/support/tickets/{id}/reply
   */
  public function rest_send_reply(\WP_REST_Request $request): \WP_REST_Response
  {
    $id      = sanitize_key($request->get_param('id'));
    $text    = sanitize_textarea_field($request->get_param('message') ?? '');
    $tickets = get_option(self::OPTION_KEY, []);

    if (!isset($tickets[$id])) {
      return new \WP_REST_Response([
        'success' => false,
        'message' => __('Ticket not found.', 'pixel-on-wp'),
      ], 404);
    }

    if (empty($text) && empty($_FILES['attachment']['name'])) {
      return new \WP_REST_Response([
        'success' => false,
        'message' => __('Reply message or attachment is required.', 'pixel-on-wp'),
      ], 400);
    }

    $attachment_url = '';
    if (!empty($_FILES['attachment']['name'])) {
      $attachment_url = $this->handle_image_upload($_FILES['attachment']);
    }

    $new_msg = [
      'id'        => 'msg_' . time(),
      'sender'    => 'user',
      'name'      => 'YOU / USER',
      'timestamp' => 'Just Now',
      'text'      => $text,
      'attachment'=> $attachment_url,
    ];

    $tickets[$id]['messages'][] = $new_msg;
    $tickets[$id]['last_updated'] = 'Just Now';
    $tickets[$id]['status'] = 'Open';
    $tickets[$id]['developer_typing'] = false;

    update_option(self::OPTION_KEY, $tickets);

    // Trigger outbound reply post
    $this->send_outbound_reply([
      'ticket_id'  => $id,
      'message'    => $text,
      'attachment' => $attachment_url,
      'diagnostics'=> $this->get_system_diagnostics(),
    ]);

    return new \WP_REST_Response([
      'success' => true,
      'message' => __('Reply submitted.', 'pixel-on-wp'),
      'ticket'  => $tickets[$id],
    ], 200);
  }

  /**
   * POST /pixelonwp/v1/support/tickets/{id}/close
   */
  public function rest_close_ticket(\WP_REST_Request $request): \WP_REST_Response
  {
    $id      = sanitize_key($request->get_param('id'));
    $tickets = get_option(self::OPTION_KEY, []);

    if (!isset($tickets[$id])) {
      return new \WP_REST_Response([
        'success' => false,
        'message' => __('Ticket not found.', 'pixel-on-wp'),
      ], 404);
    }

    $tickets[$id]['status'] = 'Closed';
    $tickets[$id]['last_updated'] = 'Just Now';
    $tickets[$id]['developer_typing'] = false;
    update_option(self::OPTION_KEY, $tickets);

    return new \WP_REST_Response([
      'success' => true,
      'message' => __('Ticket closed and resolved.', 'pixel-on-wp'),
      'ticket'  => $tickets[$id],
    ], 200);
  }

  /**
   * GET /pixelonwp/v1/support/status
   */
  public function rest_get_support_status(\WP_REST_Request $request): \WP_REST_Response
  {
    $id = sanitize_key($request->get_param('id') ?? '102');
    $tickets = get_option(self::OPTION_KEY, []);
    $ticket = $tickets[$id] ?? null;

    return new \WP_REST_Response([
      'success'           => true,
      'developer_online'  => true,
      'developer_typing'  => $ticket ? !empty($ticket['developer_typing']) : false,
      'ticket_status'     => $ticket ? $ticket['status'] : 'Open',
      'last_updated'      => $ticket ? $ticket['last_updated'] : 'Just Now',
    ], 200);
  }

  /**
   * AJAX Fallbacks
   */
  public function ajax_create_ticket(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    $request = new \WP_REST_Request('POST', '/pixelonwp/v1/support/tickets');
    $request->set_param('subject', sanitize_text_field($_POST['subject'] ?? ''));
    $request->set_param('category', sanitize_text_field($_POST['category'] ?? ''));
    $request->set_param('priority', sanitize_text_field($_POST['priority'] ?? ''));
    $request->set_param('description', sanitize_textarea_field($_POST['description'] ?? ''));

    $res = $this->rest_create_ticket($request);
    $data = $res->get_data();

    if ($res->get_status() === 200) {
      wp_send_json_success($data);
    } else {
      wp_send_json_error($data, $res->get_status());
    }
  }

  public function ajax_send_reply(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    $id = sanitize_key($_POST['ticket_id'] ?? '');
    $request = new \WP_REST_Request('POST', "/pixelonwp/v1/support/tickets/{$id}/reply");
    $request->set_param('id', $id);
    $request->set_param('message', sanitize_textarea_field($_POST['message'] ?? ''));

    $res = $this->rest_send_reply($request);
    $data = $res->get_data();

    if ($res->get_status() === 200) {
      wp_send_json_success($data);
    } else {
      wp_send_json_error($data, $res->get_status());
    }
  }

  public function ajax_close_ticket(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    $id = sanitize_key($_POST['ticket_id'] ?? '');
    $request = new \WP_REST_Request('POST', "/pixelonwp/v1/support/tickets/{$id}/close");
    $request->set_param('id', $id);

    $res = $this->rest_close_ticket($request);
    $data = $res->get_data();

    if ($res->get_status() === 200) {
      wp_send_json_success($data);
    } else {
      wp_send_json_error($data, $res->get_status());
    }
  }

  public function ajax_poll_support(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    $request = new \WP_REST_Request('GET', '/pixelonwp/v1/support/status');
    $request->set_param('id', sanitize_key($_GET['ticket_id'] ?? '102'));
    $res = $this->rest_get_support_status($request);

    wp_send_json_success($res->get_data());
  }

  /**
   * Securely handles image attachment uploads.
   * Restricts uploads strictly to images (jpg, jpeg, png, gif, webp).
   *
   * @param array $file_array Uploaded file superglobal.
   * @return string Attachment URL or empty string on failure.
   */
  private function handle_image_upload(array $file_array): string
  {
    if (empty($file_array['tmp_name'])) {
      return '';
    }

    // Verify file type is an image only
    $allowed_mimetypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = wp_check_filetype_and_ext($file_array['tmp_name'], $file_array['name']);

    if (!in_array($file_type['type'], $allowed_mimetypes, true)) {
      return '';
    }

    if (!function_exists('wp_handle_upload')) {
      require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $upload_overrides = ['test_form' => false];
    $movefile = wp_handle_upload($file_array, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
      return esc_url_raw($movefile['url']);
    }

    return '';
  }

  private function send_outbound_ticket_creation(array $ticket_data): void
  {
    $target_endpoint = 'https://yourmainsite.com/wp-json/tracker-hub/v1/create-ticket';
    $ticket_data['license_key'] = 'AI8NGR04dccZC7yn7nVOrRaf9n8WGsIL';

    wp_remote_post($target_endpoint, [
      'method'    => 'POST',
      'timeout'   => 5,
      'blocking'  => false,
      'headers'   => [
        'Content-Type' => 'application/json',
      ],
      'body'      => wp_json_encode($ticket_data),
    ]);
  }

  /**
   * Outbound REST API call helper for ticket replies to Central Hub.
   *
   * @param array $reply_data Reply details payload.
   * @return void
   */
  private function send_outbound_reply(array $reply_data): void
  {
    $target_endpoint = 'https://yourmainsite.com/wp-json/tracker-hub/v1/receive-reply';
    $reply_data['license_key'] = 'AI8NGR04dccZC7yn7nVOrRaf9n8WGsIL';

    wp_remote_post($target_endpoint, [
      'method'    => 'POST',
      'timeout'   => 5,
      'blocking'  => false,
      'headers'   => [
        'Content-Type' => 'application/json',
      ],
      'body'      => wp_json_encode($reply_data),
    ]);
  }
}
