<?php
/**
 * Frontend Tracking Handler Class.
 *
 * @package PixelOnWP\Includes\Frontend
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Frontend;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Frontend Class.
 *
 * Enqueues frontend scripts, injects Meta Pixel base code, and outputs client-side dataLayer.
 *
 * @package PixelOnWP\Includes\Frontend
 * @since 1.0.0
 */
class PixelOnWP_Frontend
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
    $loader->add_action('init', $this, 'add_rewrite_rules');
    $loader->add_filter('query_vars', $this, 'add_query_vars');
    $loader->add_filter('template_include', $this, 'load_documentation_template');

    // Register Cookie Consent Module
    $cookie_consent = new \PixelOnWP\Includes\Frontend\PixelOnWP_Cookie_Consent();
    $cookie_consent->register_hooks($loader);

    $loader->add_action('wp_enqueue_scripts', $this, 'enqueue_frontend_assets', 999);
    $loader->add_action('wp_head', $this, 'inject_pixel_base_code', 1);
    $loader->add_action('wp_head', $this, 'inject_tiktok_base_code', 1);
    $loader->add_action('wp_head', $this, 'inject_reddit_base_code', 1);
    $loader->add_action('wp_head', $this, 'inject_pinterest_base_code', 1);

    $loader->add_action('wp_head', $this, 'inject_google_tag', 1);
    $loader->add_action('wp_head', $this, 'inject_ga4_tag', 1);
    $loader->add_action('wp_head', $this, 'inject_gtm_head', 2);
    $loader->add_action('wp_body_open', $this, 'inject_gtm_body', 1);
    $loader->add_action('wp_footer', $this, 'inject_datalayer_script', 20);

    // Custom Header & Footer Scripts
    $loader->add_action('wp_head', $this, 'inject_custom_header_code', 99);
    $loader->add_action('wp_body_open', $this, 'inject_custom_body_code', 99);
    $loader->add_action('wp_footer', $this, 'inject_custom_footer_code', 99);
  }

  private static $cached_user_data = null;
  private static $gtag_initialized = false;

  public static function get_unhashed_user_data(): array {
      if (self::$cached_user_data !== null) {
          return self::$cached_user_data;
      }

      $user_data = [];
      if (is_user_logged_in()) {
          $user = wp_get_current_user();
          if ($user) {
              $user_data['email'] = $user->user_email;
              $user_data['first_name'] = $user->user_firstname;
              $user_data['last_name'] = $user->user_lastname;
              
              if (function_exists('WC')) {
                  $user_data['phone_number'] = get_user_meta($user->ID, 'billing_phone', true);
                  $user_data['city'] = get_user_meta($user->ID, 'billing_city', true);
                  $user_data['state'] = get_user_meta($user->ID, 'billing_state', true);
                  $user_data['zip'] = get_user_meta($user->ID, 'billing_postcode', true);
                  $user_data['country'] = get_user_meta($user->ID, 'billing_country', true);
              }
          }
      }
      self::$cached_user_data = $user_data;
      return $user_data;
  }

  public static function get_hashed_user_data(): array {
      $raw = self::get_unhashed_user_data();
      $hashed = [];
      if (!empty($raw['email'])) $hashed['em'] = hash('sha256', strtolower(trim($raw['email'])));
      if (!empty($raw['phone_number'])) {
          $formatted_phone = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::format_phone_e164($raw['phone_number'], $raw['country'] ?? '');
          if (!empty($formatted_phone)) {
              $hashed['ph'] = hash('sha256', $formatted_phone);
          }
      }
      if (!empty($raw['first_name'])) $hashed['fn'] = hash('sha256', strtolower(trim($raw['first_name'])));
      if (!empty($raw['last_name'])) $hashed['ln'] = hash('sha256', strtolower(trim($raw['last_name'])));
      if (!empty($raw['city'])) $hashed['ct'] = hash('sha256', strtolower(trim($raw['city'])));
      if (!empty($raw['state'])) $hashed['st'] = hash('sha256', strtolower(trim($raw['state'])));
      if (!empty($raw['zip'])) $hashed['zp'] = hash('sha256', strtolower(trim($raw['zip'])));
      if (!empty($raw['country'])) $hashed['country'] = hash('sha256', strtolower(trim($raw['country'])));
      return $hashed;
  }

  /**
   * Add rewrite rules for documentation endpoint.
   *
   * @since 1.0.0
   * @return void
   */
  public function add_rewrite_rules(): void
  {
    add_rewrite_rule('^pixelonwp/docs/user-documents/?$', 'index.php?pixelonwp_docs=user', 'top');
    add_rewrite_rule('^pixelonwp/docs/admin-documents/?$', 'index.php?pixelonwp_docs=admin', 'top');
    add_rewrite_rule('^pixelonwp/documents/?$', 'index.php?pixelonwp_docs=user', 'top');

    // Force flush once
    if (get_option('pixelonwp_flush_rewrite_rules_v3') !== '1') {
      flush_rewrite_rules(true);
      update_option('pixelonwp_flush_rewrite_rules_v3', '1');
    }
  }

  /**
   * Add custom query variables.
   *
   * @since 1.0.0
   * @param array $vars
   * @return array
   */
  public function add_query_vars(array $vars): array
  {
    $vars[] = 'pixelonwp_docs';
    return $vars;
  }

  /**
   * Intercept template include for documentation endpoint.
   *
   * @since 1.0.0
   * @param string $template
   * @return string
   */
  public function load_documentation_template($template)
  {
    if (get_query_var('pixelonwp_docs')) {
      $custom_template = plugin_dir_path(dirname(__DIR__)) . 'templates/frontend/documentation.php';
      if (file_exists($custom_template)) {
        return $custom_template;
      }
    }
    return $template;
  }

  /**
   * Enqueue frontend styles and scripts.
   *
   * @since 1.0.0
   * @return void
   */
  public function enqueue_frontend_assets(): void
  {
    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();

    if ('1' !== $settings['enable_browser_events']) {
      return;
    }

    wp_enqueue_style(
      'wpt-frontend-css',
      plugins_url('assets/css/frontend.css', dirname(__DIR__)),
      [],
      PixelOnWP_VERSION
    );

    $events_builder = get_option('PixelOnWP_comprehensive_events', []);
    $dl_enabled = isset($events_builder['datalayer_enabled']) ? filter_var($events_builder['datalayer_enabled'], FILTER_VALIDATE_BOOLEAN) : true;
    
    $active_events = [];
    if ($dl_enabled) {
        $active_events = [
            'page_view', 'view_item_list', 'select_item', 'view_item', 'add_to_cart', 'remove_from_cart',
            'view_cart', 'begin_checkout', 'add_shipping_info', 'add_payment_info', 'purchase', 'refund',
            'view_promotion', 'select_promotion', 'begin_trial', 'subscribe',
            'generate_lead', 'contact', 'schedule', 'search', 'select_content', 'share',
            'file_download', 'video_start', 'video_progress', 'video_complete', 'sign_up', 'login'
        ];
    }
    
    $needs_listener = true;

    if ($needs_listener) {
      wp_enqueue_script(
        'wpt-datalayer-listener',
        plugins_url('assets/js/frontend/datalayer-listener.js', dirname(__DIR__)),
        ['jquery'],
        PixelOnWP_VERSION,
        true
      );
      
      $listener_config = [
          'facebook_tracking_mode' => get_option('PixelOnWP_facebook_tracking_mode', 'hybrid'),
          'tiktok_tracking_mode' => get_option('PixelOnWP_tiktok_tracking_mode', 'hybrid'),
          'reddit_tracking_mode' => get_option('PixelOnWP_reddit_tracking_mode', 'hybrid'),
          'pinterest_tracking_mode' => 'hybrid',
          'custom_route' => site_url('/' . get_option('PixelOnWP_custom_route', 'wp-json/pixelonwp/v1/collect')),
          'meta_config' => get_option('PixelOnWP_meta_config', []),
          'meta_pixels' => class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Meta_Multi_Pixel_Helper') ? \PixelOnWP\Includes\Tracking\PixelOnWP_Meta_Multi_Pixel_Helper::get_pixels() : [],
          'tiktok_config' => get_option('PixelOnWP_tiktok_config', []),
          'reddit_config' => get_option('PixelOnWP_reddit_config', []),
          'pinterest_config' => get_option('PixelOnWP_pinterest_config', []),
          'google_config' => get_option('PixelOnWP_google_config', []),
          'ga4_config' => get_option('PixelOnWP_ga4_config', []),
          'active_params' => get_option('PixelOnWP_active_params', [])
      ];
      foreach ($active_events as $evt) {
          $listener_config[$evt] = '1';
      }

      wp_localize_script('wpt-datalayer-listener', 'PixelOnWP_events', $listener_config);
      
      wp_localize_script('wpt-datalayer-listener', 'pixelonwp_frontend_vars', [
        'ajax_url' => admin_url('admin-ajax.php')
      ]);
      
      wp_add_inline_script('wpt-datalayer-listener', 'window.PixelOnWPEnabledEvents = ' . wp_json_encode($active_events) . ';', 'before');
    }

    wp_enqueue_script(
      'wpt-frontend-js',
      plugins_url('assets/js/frontend.js', dirname(__DIR__)),
      [],
      PixelOnWP_VERSION,
      true
    );

    // AI Ad Engine Frontend Tracker
    wp_enqueue_script(
      'pixelonwp-tracker',
      plugins_url('assets/js/pixelonwp-tracker.js', dirname(__DIR__)),
      [],
      PixelOnWP_VERSION,
      true
    );
    wp_localize_script('pixelonwp-tracker', 'pixelonwp_tracker_vars', [
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('pixelonwp_tracker_nonce'),
    ]);



    // Universal Tracker
    $settings_option = get_option('PixelOnWP_settings', array());
    $visual_builder_enabled = $settings_option['visual_builder_enabled'] ?? '1';

    $tracker_rules = get_option('my_plugin_tracker_rules', []);
    $ga4_custom_events = get_option('PixelOnWP_ga4_custom_events', []);
    $is_admin = current_user_can('manage_options') || current_user_can('manage_woocommerce');
    $is_visual_builder = (isset($_GET['pixelonwp_visual_builder']) || isset($_GET['wpt_visual_builder'])) && $is_admin && ('1' === $visual_builder_enabled);

    // Enqueue visual builder or tracker script if rules exist, custom events exist, platforms are configured, or visual builder is launched
    $selected_platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!empty($tracker_rules) || !empty($ga4_custom_events) || !empty($selected_platforms) || $is_visual_builder) {
      wp_enqueue_script(
        'wpt-universal-tracker',
        plugins_url('assets/js/frontend/universal-tracker.js', dirname(__DIR__)),
        [],
        PixelOnWP_VERSION,
        true
      );
      
      // Resolve global tracking credentials
      $meta_config = get_option('PixelOnWP_meta_config', []);
      $tiktok_config = get_option('PixelOnWP_tiktok_config', []);
      $google_config = get_option('PixelOnWP_google_config', []);
      $ga4_id = get_option('PixelOnWP_ga4_id', '');

       $reddit_config = get_option('PixelOnWP_reddit_config', []);
       $pinterest_config = get_option('PixelOnWP_pinterest_config', []);
       $tracker_platforms = [
         'fb_pixel_id'        => isset($meta_config['pixel_id']) ? trim($meta_config['pixel_id']) : '',
         'tt_pixel_id'        => isset($tiktok_config['pixel_id']) ? trim($tiktok_config['pixel_id']) : '',
         'reddit_pixel_id'    => isset($reddit_config['pixel_id']) ? trim($reddit_config['pixel_id']) : '',
         'pinterest_tag_id'   => isset($pinterest_config['tag_id']) ? trim($pinterest_config['tag_id']) : '',
         'google_ads_id'      => isset($google_config['conversion_id']) ? trim($google_config['conversion_id']) : '',
         'ga4_measurement_id' => trim($ga4_id)
       ];

       $detected_context = class_exists('\\PixelOnWP\\Includes\\PixelOnWP_Tracker_Context') 
         ? \PixelOnWP\Includes\PixelOnWP_Tracker_Context::get_context() 
         : ['business_model' => 'Lead-Gen', 'theme_builder' => 'Gutenberg'];

       $presets_playbooks = class_exists('\\PixelOnWP\\Includes\\PixelOnWP_Tracker_Playbooks') 
         ? \PixelOnWP\Includes\PixelOnWP_Tracker_Playbooks::get_playbooks() 
         : [];

        $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($client_ip === '127.0.0.1' || $client_ip === '::1') {
            $client_ip = '104.28.254.74';
        }

        wp_localize_script('wpt-universal-tracker', 'pixelonwp_universal_tracker_vars', [
          'rules'                  => $tracker_rules,
          'ga4_custom_events'      => $ga4_custom_events,
          'platforms'              => $tracker_platforms,
          'context'                => $detected_context,
          'playbooks'              => $presets_playbooks,
          'facebook_tracking_mode' => get_option('PixelOnWP_facebook_tracking_mode', 'hybrid'),
          'tiktok_tracking_mode'   => get_option('PixelOnWP_tiktok_tracking_mode', 'hybrid'),
          'reddit_tracking_mode'   => get_option('PixelOnWP_reddit_tracking_mode', 'hybrid'),
          'pinterest_config'       => get_option('PixelOnWP_pinterest_config', []),
          'client_ip_address'      => $client_ip,
          'client_user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? '',
          'ga4_config'             => get_option('PixelOnWP_ga4_config', []),
          'ga4_debug_mode'         => (!empty(get_option('PixelOnWP_ga4_config', [])['test_code']) || is_user_logged_in()),
          'custom_route'           => site_url('/' . get_option('PixelOnWP_custom_route', 'wp-json/pixelonwp/v1/collect')),
          'nonce'                  => wp_create_nonce('PixelOnWP_nonce')
        ]);

      // Enqueue administrator visual point-and-click overlay tool
      if ($is_visual_builder) {
        wp_enqueue_script(
          'wpt-universal-tracker-visual-builder',
          plugins_url('assets/js/frontend/visual-builder.js', dirname(__DIR__)),
          ['wpt-universal-tracker'],
          PixelOnWP_VERSION,
          true
        );
      }

      // Enqueue administrator debugger overlay
      if ($is_admin && !$is_visual_builder && ('1' === $visual_builder_enabled)) {
        wp_enqueue_script(
          'wpt-universal-tracker-debugger',
          plugins_url('assets/js/admin/live-debugger.js', dirname(__DIR__)),
          [],
          PixelOnWP_VERSION,
          true
        );
      }
    }
  }

  public function inject_pixel_base_code(): void
  {
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!is_array($platforms) || !in_array('facebook', $platforms, true)) {
      return;
    }

    $pixels = class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Meta_Multi_Pixel_Helper') 
      ? \PixelOnWP\Includes\Tracking\PixelOnWP_Meta_Multi_Pixel_Helper::get_pixels() 
      : [];

    if (empty($pixels)) {
      $meta_config = get_option('PixelOnWP_meta_config', []);
      $pixel_id = isset($meta_config['pixel_id']) ? trim($meta_config['pixel_id']) : '';
      if (!empty($pixel_id)) {
        $pixels[] = ['pixel_id' => $pixel_id];
      }
    }

    if (empty($pixels)) {
      return;
    }

    $fb_tracking_mode = get_option('PixelOnWP_facebook_tracking_mode', 'hybrid');
    if ($fb_tracking_mode === 'server') {
      return;
    }

    // Capture _fbp and _fbc cookies if available
    $fbp = isset($_COOKIE['_fbp']) ? sanitize_text_field(wp_unslash($_COOKIE['_fbp'])) : '';
    $fbc = isset($_COOKIE['_fbc']) ? sanitize_text_field(wp_unslash($_COOKIE['_fbc'])) : '';

    $cc = get_option('PixelOnWP_cookie_consent', []);
    $needs_consent = !empty($cc['enabled']);

    ?>
    <!-- PixelOnWP - Meta Pixel Base Code -->
    <script>
      (function() {
        !function (f, b, e, v, n, t, s) {
          if (f.fbq) return; n = f.fbq = function () {
            n.callMethod ?
              n.callMethod.apply(n, arguments) : n.queue.push(arguments)
          };
          if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0';
          n.queue = [];
        }(window, document, 'script');

        function runMetaPixel() {
          var f = window, b = document, e = 'script', v = 'https://connect.facebook.net/en_US/fbevents.js';
          var t = b.createElement(e); t.async = !0;
          t.src = v; var s = b.getElementsByTagName(e)[0];
          s.parentNode.insertBefore(t, s);

          <?php
            $user_data = \PixelOnWP\Includes\Tracking\PixelOnWP_Meta_Tracker::get_hashed_user_data();
            unset($user_data['client_ip_address']);
            unset($user_data['client_user_agent']);
            $user_data_arg = !empty($user_data) ? (', ' . wp_json_encode($user_data)) : '';

            foreach ($pixels as $p) {
              $pid = esc_js($p['pixel_id']);
              echo "fbq('init', '{$pid}'{$user_data_arg});\n          ";
            }
          ?>
        }
        
        <?php if ($needs_consent) : ?>
        var hasConsent = false;
        var m = document.cookie.match(new RegExp('(^| )pixelonwp_consent=([^;]+)'));
        if (m) {
          try {
            var state = JSON.parse(decodeURIComponent(m[2]));
            if (state.marketing) hasConsent = true;
          } catch(e) {
            if (m[2] === 'granted') hasConsent = true;
          }
        }
        if (hasConsent) {
          runMetaPixel();
        } else {
          window.addEventListener('pixelonwp_consent_marketing', runMetaPixel);
          window.addEventListener('pixelonwp_consent_granted', runMetaPixel);
        }
        <?php else : ?>
        runMetaPixel();
        <?php endif; ?>
      })();
    </script>
    <!-- End PixelOnWP -->
    <?php
  }

  public function inject_tiktok_base_code(): void
  {
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!is_array($platforms) || !in_array('tiktok', $platforms, true)) {
      return;
    }

    $tiktok_config = get_option('PixelOnWP_tiktok_config', []);
    $pixel_id = isset($tiktok_config['pixel_id']) ? trim($tiktok_config['pixel_id']) : '';
    if (empty($pixel_id)) {
      return;
    }

    $tt_tracking_mode = get_option('PixelOnWP_tiktok_tracking_mode', 'hybrid');
    if ($tt_tracking_mode === 'server') {
      return;
    }

    $cc = get_option('PixelOnWP_cookie_consent', []);
    $needs_consent = !empty($cc['enabled']);
    ?>
    <!-- PixelOnWP - TikTok Pixel Base Code -->
    <script>
      (function() {
        !function (w, d, t) {
          w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
        }(window, document, 'ttq');

        function runTikTokPixel() {
          ttq.load('<?php echo esc_js($pixel_id); ?>');
          <?php
            $user_data = self::get_unhashed_user_data();
              if (!empty($user_data)) {
                  $tt_emc = [];
                  if (!empty($user_data['email'])) $tt_emc['email'] = $user_data['email'];
                  if (!empty($user_data['phone_number'])) {
                      $phone = preg_replace('/[^\d+]/', '', $user_data['phone_number']);
                      if (!empty($phone)) $tt_emc['phone_number'] = $phone;
                  }
                  if (!empty($tt_emc)) {
                      echo "ttq.identify(" . wp_json_encode($tt_emc) . ");\n";
                  }
              }
          ?>
          ttq.page();
        }

        <?php if ($needs_consent) : ?>
        var hasConsent = false;
        var m = document.cookie.match(new RegExp('(^| )pixelonwp_consent=([^;]+)'));
        if (m) {
          try {
            var state = JSON.parse(decodeURIComponent(m[2]));
            if (state.marketing) hasConsent = true;
          } catch(e) {
            if (m[2] === 'granted') hasConsent = true;
          }
        }
        if (hasConsent) {
          runTikTokPixel();
        } else {
          window.addEventListener('pixelonwp_consent_marketing', runTikTokPixel);
          window.addEventListener('pixelonwp_consent_granted', runTikTokPixel);
        }
        <?php else : ?>
        runTikTokPixel();
        <?php endif; ?>
      })();
    </script>
    <!-- End PixelOnWP TikTok -->
    <?php
  }



  public function inject_reddit_base_code(): void
  {
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!is_array($platforms) || !in_array('reddit', $platforms, true)) {
      return;
    }

    $reddit_config = get_option('PixelOnWP_reddit_config', []);
    $pixel_id = isset($reddit_config['pixel_id']) ? trim($reddit_config['pixel_id']) : '';
    if (empty($pixel_id)) {
      return;
    }

    $reddit_tracking_mode = get_option('PixelOnWP_reddit_tracking_mode', 'hybrid');
    if ($reddit_tracking_mode === 'server') {
      return;
    }

    $cc = get_option('PixelOnWP_cookie_consent', []);
    $needs_consent = !empty($cc['enabled']);
    ?>
    <!-- PixelOnWP - Reddit Pixel Base Code -->
    <script>
      (function() {
        !function(w,d){if(!w.rdt){var p=w.rdt=function(){p.sendEvent?p.sendEvent.apply(p,arguments):p.callQueue.push(arguments)};p.callQueue=[];var t=d.createElement("script");t.src="https://www.redditstatic.com/ads/pixel.js",t.async=!0;var s=d.getElementsByTagName("script")[0];s.parentNode.insertBefore(t,s)}}(window,document);

        function runRedditPixel() {
          var initOptions = {"optOut":false,"useDecimalCoercion":true};
          <?php
            $user_data = self::get_unhashed_user_data();
            $email = $user_data['email'] ?? '';
            $phone = $user_data['phone_number'] ?? '';
            $country = $user_data['country'] ?? '';
            $cust_id = get_current_user_id() ? (string)get_current_user_id() : '';

            if (function_exists('is_order_received_page') && is_order_received_page()) {
                global $wp;
                if (isset($wp->query_vars['order-received'])) {
                    $order_id = absint($wp->query_vars['order-received']);
                    $order = wc_get_order($order_id);
                    if ($order) {
                        $email = $order->get_billing_email();
                        $phone = $order->get_billing_phone();
                        $country = $order->get_billing_country();
                        $cust_id = (string)$order->get_customer_id();
                    }
                }
            }

            if (!empty($email)) {
                echo "initOptions.email = '" . esc_js(hash('sha256', trim(strtolower($email)))) . "';\n";
            }
            if (!empty($phone)) {
                $formatted_phone = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::format_phone_e164($phone, $country);
                if (!empty($formatted_phone)) {
                    echo "initOptions.phone_number = '" . esc_js(hash('sha256', $formatted_phone)) . "';\n";
                }
            }
            if (!empty($cust_id) && $cust_id !== '0') {
                echo "initOptions.external_id = '" . esc_js(hash('sha256', trim($cust_id))) . "';\n";
            }
          ?>
          rdt('init', '<?php echo esc_js($pixel_id); ?>', initOptions);
        }

        <?php if ($needs_consent) : ?>
        var hasConsent = false;
        var m = document.cookie.match(new RegExp('(^| )pixelonwp_consent=([^;]+)'));
        if (m) {
          try {
            var state = JSON.parse(decodeURIComponent(m[2]));
            if (state.marketing) hasConsent = true;
          } catch(e) {
            if (m[2] === 'granted') hasConsent = true;
          }
        }
        if (hasConsent) {
          runRedditPixel();
        } else {
          window.addEventListener('pixelonwp_consent_marketing', runRedditPixel);
          window.addEventListener('pixelonwp_consent_granted', runRedditPixel);
        }
        <?php else : ?>
        runRedditPixel();
        <?php endif; ?>
      })();
    </script>
    <!-- End PixelOnWP Reddit -->
    <?php
  }

  public function inject_pinterest_base_code(): void
  {
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!is_array($platforms) || !in_array('pinterest', $platforms, true)) {
      return;
    }

    $pinterest_config = get_option('PixelOnWP_pinterest_config', []);
    $tag_id = isset($pinterest_config['tag_id']) ? trim($pinterest_config['tag_id']) : '';
    if (empty($tag_id)) {
      return;
    }

    $cc = get_option('PixelOnWP_cookie_consent', []);
    $needs_consent = !empty($cc['enabled']);
    ?>
    <!-- PixelOnWP - Pinterest Tag Base Code -->
    <script>
      (function() {
        !function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var n=window.pintrk;n.queue=[],n.version="3.0";var t=document.createElement("script");t.async=!0,t.src=e;var r=document.getElementsByTagName("script")[0];r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");

        function runPinterestPixel() {
          var initData = {};
          <?php
            $enable_em = !isset($pinterest_config['enhanced_match']) || filter_var($pinterest_config['enhanced_match'], FILTER_VALIDATE_BOOLEAN);
            if ($enable_em) {
              $user_data = self::get_unhashed_user_data();
              $email = $user_data['email'] ?? '';
              $phone = $user_data['phone_number'] ?? '';
              $fname = $user_data['first_name'] ?? '';
              $lname = $user_data['last_name'] ?? '';
              $city = $user_data['city'] ?? '';
              $state = $user_data['state'] ?? '';
              $zip = $user_data['zip'] ?? '';
              $country = $user_data['country'] ?? '';

              if (function_exists('is_order_received_page') && is_order_received_page()) {
                  global $wp;
                  if (isset($wp->query_vars['order-received'])) {
                      $order_id = absint($wp->query_vars['order-received']);
                      $order = wc_get_order($order_id);
                      if ($order) {
                          $email = $order->get_billing_email();
                          $phone = $order->get_billing_phone();
                          $fname = $order->get_billing_first_name();
                          $lname = $order->get_billing_last_name();
                          $city = $order->get_billing_city();
                          $state = $order->get_billing_state();
                          $zip = $order->get_billing_postcode();
                          $country = $order->get_billing_country();
                      }
                  }
              }

              if (!empty($email)) {
                  echo "initData.em = '" . esc_js(hash('sha256', trim(strtolower($email)))) . "';\n";
              }
              if (!empty($phone)) {
                  $formatted_phone = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::format_phone_e164($phone, $country);
                  $phone_to_hash = !empty($formatted_phone) ? $formatted_phone : $phone;
                  echo "initData.ph = '" . esc_js(hash('sha256', trim($phone_to_hash))) . "';\n";
              }
              if (!empty($fname)) {
                  echo "initData.fn = '" . esc_js(hash('sha256', trim(strtolower($fname)))) . "';\n";
              }
              if (!empty($lname)) {
                  echo "initData.ln = '" . esc_js(hash('sha256', trim(strtolower($lname)))) . "';\n";
              }
              if (!empty($city)) {
                  echo "initData.ct = '" . esc_js(hash('sha256', trim(strtolower($city)))) . "';\n";
              }
              if (!empty($state)) {
                  echo "initData.st = '" . esc_js(hash('sha256', trim(strtolower($state)))) . "';\n";
              }
              if (!empty($zip)) {
                  echo "initData.zp = '" . esc_js(hash('sha256', trim(strtolower($zip)))) . "';\n";
              }
              if (!empty($country)) {
                  echo "initData.country = '" . esc_js(hash('sha256', trim(strtolower($country)))) . "';\n";
              }
            }
          ?>
          pintrk('load', '<?php echo esc_js($tag_id); ?>', initData);
          pintrk('page');
        }

        <?php if ($needs_consent) : ?>
        var hasConsent = false;
        var m = document.cookie.match(new RegExp('(^| )pixelonwp_consent=([^;]+)'));
        if (m) {
          try {
            var state = JSON.parse(decodeURIComponent(m[2]));
            if (state.marketing) hasConsent = true;
          } catch(e) {
            if (m[2] === 'granted') hasConsent = true;
          }
        }
        if (hasConsent) {
          runPinterestPixel();
        } else {
          window.addEventListener('pixelonwp_consent_marketing', runPinterestPixel);
          window.addEventListener('pixelonwp_consent_granted', runPinterestPixel);
        }
        <?php else : ?>
        runPinterestPixel();
        <?php endif; ?>
      })();
    </script>
    <noscript>
      <img height="1" width="1" style="display:none;" alt="" src="https://ct.pinterest.com/v3/?event=init&tid=<?php echo esc_attr($tag_id); ?>&noscript=1" />
    </noscript>
    <!-- End PixelOnWP Pinterest -->
    <?php
  }

  /**
   * Inject Google Global Site Tag (gtag.js)
   *
   * @since 1.0.0
   * @return void
   */
  public function inject_google_tag(): void
  {
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!is_array($platforms) || !in_array('google', $platforms, true)) {
      return;
    }

    $google_config = get_option('PixelOnWP_google_config', []);
    $conversion_id = isset($google_config['conversion_id']) ? trim($google_config['conversion_id']) : '';
    if (empty($conversion_id)) {
      return;
    }

    $enhanced_conversions = isset($google_config['enhanced_conversions']) && $google_config['enhanced_conversions'] === true;

    ?>
    <!-- Google tag (gtag.js) - injected by PixelOnWP -->
    <?php if (!self::$gtag_initialized) : ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($conversion_id); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      <?php self::$gtag_initialized = true; ?>
    <?php else : ?>
    <script>
    <?php endif; ?>

      <?php
      if ($enhanced_conversions) {
          $user_data = self::get_unhashed_user_data();
          if (!empty($user_data)) {
              echo "gtag('set', 'user_data', " . wp_json_encode($user_data) . ");\n      ";
          }
      }
      ?>
      gtag('config', '<?php echo esc_js($conversion_id); ?>');
    </script>
    <!-- End Google tag -->
    <?php
  }

  /**
   * Inject GA4 Base Script
   */
  public function inject_ga4_tag(): void
  {
    $ga4_id = get_option('PixelOnWP_ga4_id', '');
    if (empty($ga4_id)) return;
    ?>
    <!-- GA4 Tag injected by PixelOnWP -->
    <?php if (!self::$gtag_initialized) : ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga4_id); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      <?php self::$gtag_initialized = true; ?>
    <?php else : ?>
    <script>
    <?php endif; ?>
      gtag('config', '<?php echo esc_js($ga4_id); ?>');
    </script>
    <!-- End GA4 Tag -->
    <?php
  }

  /**
   * Inject GTM Head Script
   */
  public function inject_gtm_head(): void
  {
    $gtm_id = get_option('PixelOnWP_gtm_id', '');
    if (empty($gtm_id)) return;
    ?>
    <!-- Google Tag Manager injected by PixelOnWP -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?php echo esc_js($gtm_id); ?>');</script>
    <!-- End Google Tag Manager -->
    <?php
  }

  /**
   * Inject GTM Body Noscript
   */
  public function inject_gtm_body(): void
  {
    $gtm_id = get_option('PixelOnWP_gtm_id', '');
    if (empty($gtm_id)) return;
    ?>
    <!-- Google Tag Manager (noscript) injected by PixelOnWP -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($gtm_id); ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php
  }

  /**
   * Inject client-side dataLayer initialization in wp_footer.
   *
   * @since 1.0.0
   * @return void
   */
  public function inject_datalayer_script(): void
  {
    $active_events = get_option('PixelOnWP_active_events', []);
    
    // If no events are active, no need to push datalayer
    if (empty($active_events)) {
      return;
    }

    ?>
    <script>
      window.wptDataLayer = window.wptDataLayer || [];
      <?php
      // Pass localized page context
      $context = [
        'page_type' => is_singular() ? get_post_type() : (is_archive() ? 'archive' : 'home'),
        'is_logged_in' => is_user_logged_in(),
      ];
      if (is_singular()) {
        $context['post_id'] = get_the_ID();
        $context['post_title'] = get_the_title();
      }
      ?>
      window.wptDataLayer.push(<?php echo wp_json_encode($context); ?>);
    </script>
    <?php
  }

  /**
   * Inject custom header code.
   *
   * @since 1.0.0
   * @return void
   */
  public function inject_custom_header_code(): void
  {
    $header_code = get_option('PixelOnWP_hf_header', '');
    if (!empty(trim($header_code))) {
      echo "\n<!-- PixelOnWP Custom Header Code -->\n";
      echo $header_code;
      echo "\n<!-- End PixelOnWP Custom Header Code -->\n";
    }
  }

  /**
   * Inject custom body code.
   *
   * @since 1.0.0
   * @return void
   */
  public function inject_custom_body_code(): void
  {
    $body_code = get_option('PixelOnWP_hf_body', '');
    if (!empty(trim($body_code))) {
      echo "\n<!-- PixelOnWP Custom Body Code -->\n";
      echo $body_code;
      echo "\n<!-- End PixelOnWP Custom Body Code -->\n";
    }
  }

  /**
   * Inject custom footer code.
   *
   * @since 1.0.0
   * @return void
   */
  public function inject_custom_footer_code(): void
  {
    $footer_code = get_option('PixelOnWP_hf_footer', '');
    if (!empty(trim($footer_code))) {
      echo "\n<!-- PixelOnWP Custom Footer Code -->\n";
      echo $footer_code;
      echo "\n<!-- End PixelOnWP Custom Footer Code -->\n";
    }
  }

}