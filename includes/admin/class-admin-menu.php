<?php
/**
 * Admin Menu Class.
 *
 * @package PixelOnWP\Includes\Admin
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Admin_Menu Class.
 *
 * Registers admin menus, submenus, settings pages, and enqueue assets.
 *
 * @package PixelOnWP\Includes\Admin
 * @since 1.0.0
 */
class PixelOnWP_Admin_Menu
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
    $loader->add_action('admin_menu', $this, 'add_admin_menu');
    $loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
    $loader->add_action('wp_ajax_pixelonwp_save_settings', $this, 'ajax_save_settings');
    add_filter('user_has_cap', [$this, 'filter_user_capabilities_for_plugin'], 10, 3);
  }

  /**
   * Add admin menu and submenus.
   *
   * @since 1.0.0
   * @return void
   */
  public function add_admin_menu(): void
  {
    $capability = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_admin_capability();

    // Dynamic 3D SVG icon data URI for main menu item
    $main_icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">' .
      '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" fill="#e11d48"/>' .
      '<path d="M3.27 6.96L12 12.01l8.73-5.05" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' .
      '<line x1="12" y1="22.08" x2="12" y2="12" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"/>' .
      '</svg>'
    );

    add_menu_page(
      __('PixelOnWP', 'pixel-on-wp'),
      __('PixelOnWP', 'pixel-on-wp'),
      $capability,
      'pixelonwp-dashboard',
      [$this, 'render_dashboard_page'],
      $main_icon_svg,
      56
    );

    // 1. Dashboard
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Dashboard', 'pixel-on-wp'),
      '<span class="dashicons dashicons-dashboard"></span> ' . __('Dashboard', 'pixel-on-wp'),
      $capability,
      'pixelonwp-dashboard',
      [$this, 'render_dashboard_page']
    );

    // 2. Setup Wizard
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Setup Wizard', 'pixel-on-wp'),
      '<span class="dashicons dashicons-admin-customizer"></span> ' . __('Setup Wizard', 'pixel-on-wp'),
      $capability,
      'wpt-setup',
      [$this, 'render_dashboard_page']
    );

    // 3. License Activation
    add_submenu_page(
      'pixelonwp-dashboard',
      __('License Activation', 'pixel-on-wp'),
      '<span class="dashicons dashicons-lock"></span> ' . __('License Activation', 'pixel-on-wp'),
      $capability,
      'wpt-license',
      [$this, 'render_dashboard_page']
    );

    // 4. Server-Side & ITP
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Server-Side & ITP', 'pixel-on-wp'),
      '<span class="dashicons dashicons-database-export"></span> ' . __('Server-Side & ITP', 'pixel-on-wp'),
      $capability,
      'wpt-server-side',
      [$this, 'render_dashboard_page']
    );

    // 5. Event Manager
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Event Manager', 'pixel-on-wp'),
      '<span class="dashicons dashicons-edit-page"></span> ' . __('Event Manager', 'pixel-on-wp'),
      $capability,
      'wpt-events',
      [$this, 'render_dashboard_page']
    );

    // 6. GTM Integration
    add_submenu_page(
      'pixelonwp-dashboard',
      __('GTM Integration', 'pixel-on-wp'),
      '<span class="dashicons dashicons-tag"></span> ' . __('GTM Integration', 'pixel-on-wp'),
      $capability,
      'wpt-gtmsetup',
      [$this, 'render_dashboard_page']
    );

    // 7. Universal Tracker
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Universal Tracker', 'pixel-on-wp'),
      '<span class="dashicons dashicons-location-alt"></span> ' . __('Universal Tracker', 'pixel-on-wp'),
      $capability,
      'wpt-universal-tracker',
      [$this, 'render_dashboard_page']
    );

    // 8. AI Ad Engine
    add_submenu_page(
      'pixelonwp-dashboard',
      __('AI Ad Engine', 'pixel-on-wp'),
      '<span class="dashicons dashicons-chart-area"></span> ' . __('AI Ad Engine', 'pixel-on-wp'),
      $capability,
      'wpt-ai-engine',
      [$this, 'render_dashboard_page']
    );

    // 9. Fraud Prevention
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Fraud Prevention', 'pixel-on-wp'),
      '<span class="dashicons dashicons-shield"></span> ' . __('Fraud Prevention', 'pixel-on-wp'),
      $capability,
      'wpt-fraud',
      [$this, 'render_dashboard_page']
    );

    // 10. eCommerce Tools
    add_submenu_page(
      'pixelonwp-dashboard',
      __('eCommerce Tools', 'pixel-on-wp'),
      '<span class="dashicons dashicons-cart"></span> ' . __('eCommerce Tools', 'pixel-on-wp'),
      $capability,
      'wpt-ecommerce',
      [$this, 'render_dashboard_page']
    );

    // 11. Ad Attribution & ROAS
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Ad Attribution & ROAS', 'pixel-on-wp'),
      '<span class="dashicons dashicons-analytics"></span> ' . __('Ad Attribution & ROAS', 'pixel-on-wp'),
      $capability,
      'pixelonwp-roas',
      [new \PixelOnWP\Admin\PixelOnWP_Roas_Admin_Ui(), 'render_dashboard']
    );

    // 12. DataLayer & Settings
    add_submenu_page(
      'pixelonwp-dashboard',
      __('DataLayer & Settings', 'pixel-on-wp'),
      '<span class="dashicons dashicons-admin-generic"></span> ' . __('DataLayer & Settings', 'pixel-on-wp'),
      $capability,
      'wpt-settings',
      [$this, 'render_settings_page']
    );

    // 13. Diagnostics & Logs
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Diagnostics & Logs', 'pixel-on-wp'),
      '<span class="dashicons dashicons-pulse"></span> ' . __('Diagnostics & Logs', 'pixel-on-wp'),
      $capability,
      'wpt-diagnostics',
      [$this, 'render_diagnostics_page']
    );

    // 14. UTM Builder
    add_submenu_page(
      'pixelonwp-dashboard',
      __('UTM Builder', 'pixel-on-wp'),
      '<span class="dashicons dashicons-admin-links"></span> ' . __('UTM Builder', 'pixel-on-wp'),
      $capability,
      'wpt-utm-builder',
      [$this, 'render_dashboard_page']
    );

    // 15. Header & Footer
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Header & Footer', 'pixel-on-wp'),
      '<span class="dashicons dashicons-editor-code"></span> ' . __('Header & Footer', 'pixel-on-wp'),
      $capability,
      'wpt-header-footer',
      [$this, 'render_dashboard_page']
    );

    // 16. Cookie Consent v2
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Cookie Consent v2', 'pixel-on-wp'),
      '<span class="dashicons dashicons-privacy"></span> ' . __('Cookie Consent v2', 'pixel-on-wp'),
      $capability,
      'wpt-cookie-consent',
      [$this, 'render_dashboard_page']
    );

    // 17. Admin Docs
    add_submenu_page(
      'pixelonwp-dashboard',
      __('All Features (Admin Docs)', 'pixel-on-wp'),
      '<span class="dashicons dashicons-media-document"></span> ' . __('Admin Docs', 'pixel-on-wp'),
      $capability,
      'pixel-admin-docs',
      [$this, 'render_admin_docs_page']
    );

    // 18. Documentation
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Documentation', 'pixel-on-wp'),
      '<span class="dashicons dashicons-welcome-learn-more"></span> ' . __('Documentation', 'pixel-on-wp'),
      $capability,
      'pixelonwp-documentation',
      [$this, 'render_documentation_page']
    );

    // 19. Clear History
    add_submenu_page(
      'pixelonwp-dashboard',
      __('Clear History', 'pixel-on-wp'),
      '<span class="dashicons dashicons-trash"></span> ' . __('Clear History', 'pixel-on-wp'),
      $capability,
      'wpt-reset',
      [$this, 'render_dashboard_page']
    );
  }

  /**
   * Enqueue admin styles and scripts.
   *
   * @since 1.0.0
   * @param string $hook Current admin page hook.
   * @return void
   */
  public function enqueue_admin_assets(string $hook): void
  {
    if (strpos($hook, 'pixelonwp') === false && strpos($hook, 'pixel-on-wp') === false && strpos($hook, 'wpt-') === false) {
      return;
    }

    wp_enqueue_style(
      'wpt-admin-global',
      plugins_url('assets/css/admin-global.css', dirname(__DIR__)),
      [],
      time()
    );

    wp_enqueue_script(
      'wpt-admin-app',
      plugins_url('assets/js/app.js', dirname(__DIR__)),
      [],
      time(),
      true
    );    
    // Add type="module" to app.js
    add_filter('script_loader_tag', function($tag, $handle, $src) {
      if ( 'wpt-admin-app' === $handle ) {
          // Remove any existing type attribute (single or double quotes)
          $clean_tag = preg_replace('/type=[\'"][^\'"]*[\'"]/', '', $tag);
          return str_replace( '<script ', '<script type="module" ', $clean_tag );
      }
      return $tag;
    }, 10, 3);

    wp_localize_script(
      'wpt-admin-app',
      'pixelonwp_admin_vars',
      [
        'rest_url' => esc_url_raw(rest_url('pixelonwp/v1')),
        'nonce' => wp_create_nonce('PixelOnWP_nonce'),
        'ajaxurl' => admin_url('admin-ajax.php'),
        'plugin_url' => plugin_dir_url(dirname(__DIR__)),
        'config' => [
          'platforms' => get_option('PixelOnWP_selected_platforms', []),
          'meta' => get_option('PixelOnWP_meta_config', ['pixel_id' => '', 'capi_token' => '', 'test_code' => '']),
          'tiktok' => get_option('PixelOnWP_tiktok_config', ['pixel_id' => '', 'access_token' => '', 'test_code' => '']),
          'reddit' => get_option('PixelOnWP_reddit_config', ['pixel_id' => '', 'access_token' => '', 'test_code' => '', 'events' => []]),
          'pinterest' => get_option('PixelOnWP_pinterest_config', ['tag_id' => '', 'ad_account_id' => '', 'access_token' => '', 'enhanced_match' => true, 'first_party_cookies' => true, 'test_mode' => false, 'events' => [], 'mappings' => []]),
          'google' => get_option('PixelOnWP_google_config', ['conversion_id' => '', 'conversion_label' => '']),
          'events' => get_option('PixelOnWP_active_events', []),
          'gtm_id' => get_option('PixelOnWP_gtm_id', ''),
          'ga4_id' => get_option('PixelOnWP_ga4_id', ''),
          'ga4_config' => get_option('PixelOnWP_ga4_config', ['setup_type' => 'basic', 'measurement_id' => '', 'api_secret' => '', 'test_code' => '', 'events' => [], 'custom_events' => []]),
          'ga4_custom_events' => get_option('PixelOnWP_ga4_custom_events', []),
          'active_params' => get_option('PixelOnWP_active_params', []),
          'events_builder' => get_option('PixelOnWP_comprehensive_events', [
            'page_view' => true,
            'view_item_list' => true,
            'view_item' => true,
            'view_cart' => true,
            'begin_checkout' => true,
            'add_to_cart' => true,
            'purchase' => true
          ]),
          'form_tracking' => get_option('PixelOnWP_form_tracking', [
            'wpforms' => '1',
            'contact_form_7' => '1',
            'gravity_forms' => '1',
            'fluent_forms' => '1',
            'formidable_forms' => '1',
            'ninja_forms' => '1',
            'forminator' => '1',
            'jetformbuilder' => '1',
            'metform' => '1',
            'kali_forms' => '1',
            'optinmonster' => '1',
            'bloom' => '1',
            'thrive_leads' => '1',
            'mailpoet' => '1',
            'hustle' => '1',
            'icegram' => '1',
            'sumo' => '1'
          ]),
          'ecommerce' => get_option('pixelonwp_ecommerce_settings', ['push_enabled' => '1']),
          'vapid_keys' => get_option('pixelonwp_vapid_keys', []),
          'header_footer' => [
            'header' => get_option('PixelOnWP_hf_header', ''),
            'body' => get_option('PixelOnWP_hf_body', ''),
            'footer' => get_option('PixelOnWP_hf_footer', '')
          ],
          'cookie_consent' => get_option('PixelOnWP_cookie_consent', []),
          'tracker_rules' => get_option('my_plugin_tracker_rules', []),
          'tracker_platforms' => get_option('my_plugin_tracker_platforms', [
            'fb_pixel_id' => '', 'fb_access_token' => '', 'tt_pixel_id' => '', 'google_ads_id' => '', 'ga4_measurement_id' => ''
          ]),
          'config' => [
            'facebook_tracking_mode' => get_option('PixelOnWP_facebook_tracking_mode', 'hybrid'),
            'tiktok_tracking_mode' => get_option('PixelOnWP_tiktok_tracking_mode', 'hybrid'),
            'reddit_tracking_mode' => get_option('PixelOnWP_reddit_tracking_mode', 'hybrid'),
            'custom_route' => get_option('PixelOnWP_custom_route', 'wp-json/pixelonwp/v1/collect'),
          ],
        ],
        'fraud_settings' => get_option('PixelOnWP_fraud_settings', [
          'enable_fraud_check' => '0',
          'risk_threshold' => 70,
          'warning_message' => 'Your order cannot be processed due to a high rate of returned parcels on this phone number.',
          'support_phone' => '',
          'pathao_token' => '',
          'steadfast_key' => '',
          'steadfast_secret' => '',
          'redx_token' => ''
        ])
      ]
    );
  }

  /**
   * Render the SPA root page.
   *
   * @since 1.0.0
   * @return void
   */
  public function render_dashboard_page(): void
  {
    include plugin_dir_path(dirname(__DIR__)) . 'templates/admin/dashboard.php';
  }

  /**
   * Render the Admin Docs page.
   *
   * @since 1.2.0
   * @return void
   */
  public function render_admin_docs_page(): void
  {
    $template_path = plugin_dir_path(dirname(__DIR__)) . 'templates/admin/docs.php';
    if (file_exists($template_path)) {
        include $template_path;
    }
  }

  /**
   * Render the Documentation page.
   *
   * @since 1.1.0
   * @return void
   */
  public function render_documentation_page(): void
  {
    include plugin_dir_path(dirname(__DIR__)) . 'templates/admin/documentation.php';
  }

  /**
   * Render the SPA root page (Settings).
   *
   * @since 1.0.0
   * @return void
   */
  public function render_settings_page(): void
  {
    include plugin_dir_path(dirname(__DIR__)) . 'templates/admin/dashboard.php';
  }

  /**
   * Render the SPA root page (Diagnostics).
   *
   * @since 1.0.0
   * @return void
   */
  public function render_diagnostics_page(): void
  {
    include plugin_dir_path(dirname(__DIR__)) . 'templates/admin/dashboard.php';
  }

  /**
   * AJAX handler for saving settings.
   *
   * @since 1.0.0
   * @return void
   */
  public function ajax_save_settings(): void
  {
    check_ajax_referer('pixelonwp_admin_nonce', 'nonce');

    $capability = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_admin_capability();
    if (!current_user_can($capability)) {
      wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
    }

    $settings = get_option('pixelonwp_settings', []);

    if (isset($_POST['pixel_id'])) {
      $settings['pixel_id'] = sanitize_text_field(wp_unslash($_POST['pixel_id']));
    }
    if (isset($_POST['access_token'])) {
      $settings['access_token'] = sanitize_text_field(wp_unslash($_POST['access_token']));
    }
    if (isset($_POST['test_event_code'])) {
      $settings['test_event_code'] = sanitize_text_field(wp_unslash($_POST['test_event_code']));
    }
    $settings['enable_browser_events'] = isset($_POST['enable_browser_events']) ? '1' : '0';
    $settings['enable_server_events'] = isset($_POST['enable_server_events']) ? '1' : '0';
    $settings['advanced_matching'] = isset($_POST['advanced_matching']) ? '1' : '0';
    $settings['event_deduplication'] = isset($_POST['event_deduplication']) ? '1' : '0';
    $settings['datalayer_enabled'] = isset($_POST['datalayer_enabled']) ? '1' : '0';

    update_option('pixelonwp_settings', $settings);

    wp_send_json_success(['message' => __('Settings updated successfully.', 'pixel-on-wp')]);
  }

  /**
   * Dynamically grant manage_options capability to users who have manage_woocommerce
   * during PixelOnWP AJAX actions and page routing.
   *
   * @param array $allcaps
   * @param array $caps
   * @param array $args
   * @return array
   */
  public function filter_user_capabilities_for_plugin(array $allcaps, array $caps, array $args): array
  {
    if (isset($args[0]) && $args[0] === 'manage_options') {
      // Check if we are doing a PixelOnWP AJAX action
      $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';
      $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
      
      $is_plugin_ajax = $action && (stripos($action, 'PixelOnWP_') === 0 || stripos($action, 'pixelonwp_') === 0);
      $is_plugin_page = $page && (stripos($page, 'pixel-on-wp') === 0 || stripos($page, 'pixelonwp-') === 0 || stripos($page, 'wpt-') === 0);

      if ($is_plugin_ajax || $is_plugin_page) {
        if (isset($allcaps['manage_woocommerce']) && $allcaps['manage_woocommerce']) {
          $allcaps['manage_options'] = true;
        }
      }
    }
    return $allcaps;
  }
}