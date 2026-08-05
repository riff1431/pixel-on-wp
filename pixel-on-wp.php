<?php
/**
 * Plugin Name: PixelOnWP
 * Plugin URI: https://huipper.com
 * Description: Enterprise-grade server-side tracking, Meta Pixel, CAPI, GTM, GA4, DataLayer, and WooCommerce tracking suite for WordPress.
 * Version: 1.0.0
 * Author: Huipper
 * Author URI: https://huipper.com
 * Text Domain: pixel-on-wp
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace PixelOnWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Main plugin constants.
 */
if (!defined('PixelOnWP_VERSION')) {
  define('PixelOnWP_VERSION', '1.0.0');
}

if (!defined('OMNITRACK_GEMINI_KEY')) {
  define('OMNITRACK_GEMINI_KEY', getenv('GEMINI_API_KEY') ?: '');
}

define('PixelOnWP_FILE', __FILE__);
define('PixelOnWP_PATH', plugin_dir_path(__FILE__));
define('PixelOnWP_URL', plugin_dir_url(__FILE__));
define('PixelOnWP_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class.
 *
 * Implements the Singleton pattern to bootstrap core plugin functionality,
 * dependency management, and lifecycle hooks.
 *
 * @package PixelOnWP
 * @since 1.0.0
 */
final class PixelOnWP_Main
{

  /**
   * Single instance of the class.
   *
   * @var PixelOnWP_Main|null
   */
  private static ?PixelOnWP_Main $instance = null;

  /**
   * Dependency injection container instance.
   *
   * @var object|null
   */
  private ?object $container = null;

  /**
   * Retrieves the main instance of the plugin.
   *
   * @since 1.0.0
   * @return PixelOnWP_Main
   */
  public static function get_instance(): PixelOnWP_Main
  {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Constructor. Private to enforce Singleton pattern.
   *
   * @since 1.0.0
   */
  private function __construct()
  {
    $this->init_autoloader();
    $this->register_lifecycle_hooks();
    add_action('plugins_loaded', array($this, 'init'), 0);
  }

  /**
   * Initializes core autoloader and lifecycle management files.
   *
   * @since 1.0.0
   * @return void
   */
  private function init_autoloader(): void
  {
    if (file_exists(PixelOnWP_PATH . 'vendor/autoload.php')) {
      require_once PixelOnWP_PATH . 'vendor/autoload.php';
    } else {
      spl_autoload_register(function ($class) {
        $prefix = 'PixelOnWP\\';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }
        $relative_class = substr($class, strlen($prefix));
        $parts = explode('\\', $relative_class);
        $class_name = array_pop($parts);
        $dir_parts = array_map(function($part) {
            return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $part));
        }, $parts);
        
        $file_name = strtolower(str_replace('_', '-', $class_name));
        $file_name = str_replace('pixelonwp-', '', $file_name);
        $file_name = 'class-' . $file_name . '.php';
        
        $file = rtrim(PixelOnWP_PATH, '/') . '/' . implode('/', $dir_parts) . '/' . $file_name;
        
        if (file_exists($file)) {
            require $file;
        }
      });
    }

    require_once PixelOnWP_PATH . 'includes/class-loader.php';
    require_once PixelOnWP_PATH . 'includes/class-activator.php';
    require_once PixelOnWP_PATH . 'includes/class-deactivator.php';
  }

  /**
   * Registers activation and deactivation hooks.
   *
   * @since 1.0.0
   * @return void
   */
  private function register_lifecycle_hooks(): void
  {
    register_activation_hook(PixelOnWP_FILE, array('\\PixelOnWP\\PixelOnWP_Activator', 'activate'));
    register_deactivation_hook(PixelOnWP_FILE, array('\\PixelOnWP\\PixelOnWP_Deactivator', 'deactivate'));
  }

  /**
   * Initializes the plugin components once all plugins are loaded.
   *
   * @since 1.0.0
   * @return void
   */
  public function init(): void
  {
    // Load text domain for localization.
    load_plugin_textdomain('pixel-on-wp', false, dirname(PixelOnWP_BASENAME) . '/languages');

    // Initialize core container if available.
    if (class_exists('\\PixelOnWP\\Includes\\Core\\PixelOnWP_Container')) {
      $this->container = new \PixelOnWP\Includes\Core\PixelOnWP_Container();
    }

    // Initialize and run the loader.
    if (class_exists('\\PixelOnWP\\PixelOnWP_Loader')) {
      $loader = new \PixelOnWP\PixelOnWP_Loader();

      // Register Admin Menu & Settings
      if (class_exists('\\PixelOnWP\\Includes\\Admin\\PixelOnWP_Admin_Menu')) {
        $admin_menu = new \PixelOnWP\Includes\Admin\PixelOnWP_Admin_Menu();
        $admin_menu->register_hooks($loader);
      }

      // Register Frontend
      if (class_exists('\\PixelOnWP\\Includes\\Frontend\\PixelOnWP_Frontend')) {
        $plugin_frontend = new \PixelOnWP\Includes\Frontend\PixelOnWP_Frontend();
        $plugin_frontend->register_hooks($loader);
      }

      // Register Diagnostics
      if (class_exists('\\PixelOnWP\\Includes\\Diagnostics\\PixelOnWP_Diagnostics')) {
        $diagnostics = new \PixelOnWP\Includes\Diagnostics\PixelOnWP_Diagnostics();
        $diagnostics->register_hooks($loader);
      }

      // Register License Manager
      if (class_exists('\\PixelOnWP\\Includes\\Licensing\\PixelOnWP_License_Manager')) {
        $license_manager = new \PixelOnWP\Includes\Licensing\PixelOnWP_License_Manager();
        $license_manager->register_hooks($loader);
      }

      // Register Cron Manager
      if (class_exists('\\PixelOnWP\\Includes\\Cron\\PixelOnWP_Cron_Manager')) {
        $cron_manager = new \PixelOnWP\Includes\Cron\PixelOnWP_Cron_Manager();
        $cron_manager->register_hooks($loader);
      }

      // Register Queue Processor
      if (class_exists('\\PixelOnWP\\Includes\\Queue\\PixelOnWP_Queue_Processor')) {
        $queue_processor = new \PixelOnWP\Includes\Queue\PixelOnWP_Queue_Processor();
        $queue_processor->register_hooks($loader);
      }

      // Register Legacy Event Controller
      if (class_exists('\\PixelOnWP\\Includes\\Controllers\\PixelOnWP_Event_Controller')) {
        $event_controller = new \PixelOnWP\Includes\Controllers\PixelOnWP_Event_Controller();
        $event_controller->register_hooks($loader);
      }
      
      // Register Native Tracker (Facebook specific)
      if (class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Native_Tracker')) {
        $native_tracker = new \PixelOnWP\Includes\Tracking\PixelOnWP_Native_Tracker();
        $native_tracker->register_hooks($loader);
      }

      // Register Isolated TikTok Tracker
      if (class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_TikTok_Tracker')) {
        $tiktok_tracker = new \PixelOnWP\Includes\Tracking\PixelOnWP_TikTok_Tracker();
        $tiktok_tracker->register_hooks($loader);
      }

      // Register Isolated Reddit Tracker
      if (class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Reddit_Tracker')) {
        $reddit_tracker = new \PixelOnWP\Includes\Tracking\PixelOnWP_Reddit_Tracker();
        $reddit_tracker->register_hooks($loader);
      }
      // Register Isolated Pinterest Tracker
      if (class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Pinterest_Tracker')) {
        $pinterest_tracker = new \PixelOnWP\Includes\Tracking\PixelOnWP_Pinterest_Tracker();
        $pinterest_tracker->register_hooks($loader);
      }
      // Hook GA4 Browser Tracker
      if (class_exists('\\PixelOnWP\\Includes\\Platforms\\GoogleAnalytics\\PixelOnWP_GA4_Browser_Tracker')) {
        \PixelOnWP\Includes\Platforms\GoogleAnalytics\PixelOnWP_GA4_Browser_Tracker::enqueue_scripts();
      }

      // Hook GA4 Server Tracker
      if (class_exists('\\PixelOnWP\\Includes\\Platforms\\GoogleAnalytics\\PixelOnWP_GA4_Server_Tracker')) {
        add_action('pixelonwp_track_event', array('\\PixelOnWP\\Includes\\Platforms\\GoogleAnalytics\\PixelOnWP_GA4_Server_Tracker', 'handle_track_event_hook'), 10, 4);
      }
      
      // Register Admin AJAX Handler
      if (class_exists('\\PixelOnWP\\Includes\\Admin\\PixelOnWP_Admin_Ajax')) {
        $admin_ajax = new \PixelOnWP\Includes\Admin\PixelOnWP_Admin_Ajax();
        $admin_ajax->register_hooks($loader);
      }

      // Register Server Endpoint
      if (class_exists('\\PixelOnWP\\Includes\\Server\\PixelOnWP_Server_Endpoint')) {
        $server_endpoint = new \PixelOnWP\Includes\Server\PixelOnWP_Server_Endpoint();
        $server_endpoint->register_hooks($loader);
      }
      // Register Fraud Prevention
      if (class_exists('\\PixelOnWP\\Includes\\Security\\PixelOnWP_Fraud_Prevention')) {
        $fraud_prevention = new \PixelOnWP\Includes\Security\PixelOnWP_Fraud_Prevention();
        $fraud_prevention->register_hooks($loader);
      }

      // Register Ad Tracker (Attribution)
      if (class_exists('\\PixelOnWP\\Includes\\PixelOnWP_Ad_Tracker')) {
        $ad_tracker = new \PixelOnWP\Includes\PixelOnWP_Ad_Tracker();
        $ad_tracker->register_hooks($loader);
      }

      // Register ROAS Admin UI
      if (is_admin() && class_exists('\\PixelOnWP\\Admin\\PixelOnWP_Roas_Admin_Ui')) {
        $roas_ui = new \PixelOnWP\Admin\PixelOnWP_Roas_Admin_Ui();
        $roas_ui->register_hooks($loader);
      }
      // Register Auto Rules & Automation Engine
      if (class_exists('\\PixelOnWP\\Includes\\PixelOnWP_Auto_Rules_Engine')) {
        $auto_rules_engine = new \PixelOnWP\Includes\PixelOnWP_Auto_Rules_Engine();
        $auto_rules_engine->register_hooks($loader);
      }
      // Register Custom Audience Sync
      if (class_exists('\\PixelOnWP\\Includes\\PixelOnWP_Audience_Sync')) {
        $audience_sync = new \PixelOnWP\Includes\PixelOnWP_Audience_Sync();
        $audience_sync->register_hooks($loader);
      }

      // Register Cron Sync
      if (class_exists('\\PixelOnWP\\Includes\\PixelOnWP_Cron_Sync')) {
        $cron_sync = new \PixelOnWP\Includes\PixelOnWP_Cron_Sync();
        $cron_sync->register_hooks($loader);
      }

      // Register Analytics & Math Engine
      if (class_exists('\\PixelOnWP\\Includes\\PixelOnWP_Analytics_Engine')) {
        $analytics_engine = new \PixelOnWP\Includes\PixelOnWP_Analytics_Engine();
        $analytics_engine->register_hooks($loader);
      }

      // Load Currency Converter Helper
      class_exists('\\PixelOnWP\\Includes\\PixelOnWP_Currency_Converter');

      // Register Ad Attribution Admin Metabox
      if (is_admin() && class_exists('\\PixelOnWP\\Admin\\PixelOnWP_Order_Metabox')) {
        $order_metabox = new \PixelOnWP\Admin\PixelOnWP_Order_Metabox();
        $order_metabox->register_hooks($loader);
      }
      // Register AI Ad Engine
      if (class_exists('\\PixelOnWP\\Includes\\Ai\\PixelOnWP_AI_Engine')) {
        $ai_engine = new \PixelOnWP\Includes\Ai\PixelOnWP_AI_Engine();
        $ai_engine->register_hooks($loader);
      }

      if (class_exists('\\PixelOnWP\\Includes\\Ai\\PixelOnWP_Ad_Generator')) {
        $ad_generator = new \PixelOnWP\Includes\Ai\PixelOnWP_Ad_Generator();
        $ad_generator->register_hooks($loader);
      }

      if (class_exists('\\PixelOnWP\\Includes\\Ai\\PixelOnWP_Search_Analyzer')) {
        $search_analyzer = new \PixelOnWP\Includes\Ai\PixelOnWP_Search_Analyzer();
        $search_analyzer->register_hooks($loader);
      }

      if (class_exists('\\PixelOnWP\\Includes\\Ai\\PixelOnWP_Fraud_Predictor')) {
        $fraud_predictor = new \PixelOnWP\Includes\Ai\PixelOnWP_Fraud_Predictor();
        $fraud_predictor->register_hooks($loader);
      }

      // Load eCommerce module if present
      $ecommerce_file = plugin_dir_path(__FILE__) . 'modules/ecommerce/class-pixelpulse-ecommerce-tools.php';
      if (file_exists($ecommerce_file)) {
          require_once $ecommerce_file;
          if (class_exists('\\PixelOnWP\\Ecommerce\\PixelOnWP_Ecommerce_Tools')) {
              new \PixelOnWP\Ecommerce\PixelOnWP_Ecommerce_Tools();
          }
      }

      // Load WhatsApp Order Messaging
      $whatsapp_file = plugin_dir_path(__FILE__) . 'modules/ecommerce/class-whatsapp-order-messaging.php';
      if (file_exists($whatsapp_file)) {
          require_once $whatsapp_file;
          if (class_exists('\\PixelOnWP\\Ecommerce\\PixelOnWP_WhatsApp_Order_Messaging')) {
              new \PixelOnWP\Ecommerce\PixelOnWP_WhatsApp_Order_Messaging();
          }
      }

      // Load Product Feed Generator
      $feed_file = plugin_dir_path(__FILE__) . 'modules/ecommerce/class-product-feed-generator.php';
      if (file_exists($feed_file)) {
          require_once $feed_file;
          if (class_exists('\\PixelOnWP\\Ecommerce\\PixelOnWP_Product_Feed_Generator')) {
              new \PixelOnWP\Ecommerce\PixelOnWP_Product_Feed_Generator();
          }
      }

      // Load AdScope module if present
      $adscope_tracker_file = plugin_dir_path(__FILE__) . 'modules/adscope/class-adscope-tracker.php';
      if (file_exists($adscope_tracker_file)) {
          require_once $adscope_tracker_file;
          if (class_exists('\\PixelOnWP\\Modules\\Adscope\\Adscope_Tracker')) {
              new \PixelOnWP\Modules\Adscope\Adscope_Tracker();
          }
      }

      $adscope_admin_file = plugin_dir_path(__FILE__) . 'modules/adscope/class-adscope-admin.php';
      if (file_exists($adscope_admin_file)) {
          require_once $adscope_admin_file;
          if (class_exists('\\PixelOnWP\\Modules\\Adscope\\Adscope_Admin')) {
              new \PixelOnWP\Modules\Adscope\Adscope_Admin();
          }
      }



      $loader->run();
      
      // TEMPORARY: Run dummy data generator if requested
      add_action('admin_init', function() {
          // Force re-run: delete old flag so it runs again with fixed code
          if (get_option('pixelonwp_dummy_data_v2') !== '1') {
              // Clear all AI caches
              delete_transient('pixelonwp_ai_insights_cache');
              delete_transient('pixelonwp_ai_search_demand_cache');
              delete_transient('pixelonwp_ai_fraud_cache');
              
              require_once plugin_dir_path(__FILE__) . 'includes/ai/generate-dummy-data.php';
              update_option('pixelonwp_dummy_data_v2', '1');
          }
      });
    }

    /**
     * Fires after PixelOnWP has been fully initialized.
     *
     * @since 1.0.0
     */
    do_action('PixelOnWP_loaded');
  }

  /**
   * Prevent cloning of the instance.
   *
   * @since 1.0.0
   * @return void
   */
  private function __clone()
  {
  }

  /**
   * Prevent unserializing of the instance.
   *
   * @since 1.0.0
   * @throws \Exception Unserializing instances of this class is forbidden.
   */
  public function __wakeup()
  {
    throw new \Exception('Unserializing instances of this class is forbidden.');
  }
}

/**
 * Returns the main instance of PixelOnWP_Main.
 *
 * @since 1.0.0
 * @return PixelOnWP_Main
 */
function PixelOnWP_init(): PixelOnWP_Main
{
  return PixelOnWP_Main::get_instance();
}

// Kick off the plugin execution.
PixelOnWP_init();

// Fallback admin menu removed to prevent duplicates.