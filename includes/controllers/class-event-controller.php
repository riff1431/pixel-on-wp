<?php
/**
 * WooCommerce Event Controller.
 *
 * Captures WooCommerce events and dispatches them to the CAPI handler.
 *
 * @package PixelOnWP\Includes\Controllers
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Controllers;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Event_Controller
{
  /**
   * Events queued for injection in the footer.
   */
  private array $queued_events = [];

  /**
   * Registry to prevent duplicate event dispatches in PHP.
   */
  private static array $dispatched_event_hashes = [];

  /**
   * Register hooks.
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
  {
    // -- Global / Standard WP Events --
    $loader->add_action('wp', $this, 'track_standard_wp_events');
    $loader->add_action('wp_head', $this, 'process_queued_browser_events', 1);
    $loader->add_action('wp_footer', $this, 'output_queued_events', 99);

    // -- WooCommerce Routing --
    // Hook into WooCommerce template redirect for ViewContent, ViewCategory, and ViewCart
    $loader->add_action('template_redirect', $this, 'track_view_content');
    $loader->add_action('template_redirect', $this, 'track_view_item_list');
    $loader->add_action('template_redirect', $this, 'track_view_cart');
    
    // Hook into checkout start (template_redirect ensures Block Themes don't bypass it)
    $loader->add_action('template_redirect', $this, 'track_begin_checkout');
    
    // Hook into WooCommerce add/remove cart
    $loader->add_action('woocommerce_add_to_cart', $this, 'track_add_to_cart', 10, 6);
    $loader->add_action('woocommerce_remove_cart_item', $this, 'track_remove_from_cart', 10, 2);

    // Hook into WooCommerce order complete (Thank You page)
    $loader->add_action('woocommerce_thankyou', $this, 'track_purchase');

    // Hook into WooCommerce AJAX add to cart fragments
    $loader->add_filter('woocommerce_add_to_cart_fragments', $this, 'track_ajax_add_to_cart', 10, 1);

    // -- Easy Digital Downloads (EDD) Routing --
    if (class_exists('Easy_Digital_Downloads')) {
      $loader->add_action('edd_pre_add_to_cart', $this, 'track_edd_add_to_cart', 10, 2);
      $loader->add_action('edd_complete_purchase', $this, 'track_edd_purchase');
    }
  }

  /**
   * Check if an event is active in settings.
   */
  private function is_event_active(string $event_name): bool
  {
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!is_array($platforms)) return false;
    if (in_array('facebook', $platforms, true)) {
        $meta = get_option('PixelOnWP_meta_config', []);
        $default_val = ($event_name === 'PlaceAnOrder') ? false : true;
        if (isset($meta['events'][$event_name])) {
            if (filter_var($meta['events'][$event_name], FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        } elseif ($default_val) {
            return true;
        }
    }
    
    if (in_array('tiktok', $platforms, true)) {
        $tiktok = get_option('PixelOnWP_tiktok_config', []);
        $tt_event = $event_name === 'PageView' ? 'Pageview' : $event_name;
        $default_val = ($tt_event === 'PlaceAnOrder') ? false : true;
        if (isset($tiktok['events'][$tt_event])) {
            if (filter_var($tiktok['events'][$tt_event], FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        } elseif ($default_val) {
            return true;
        }
    }

    if (in_array('reddit', $platforms, true)) {
        $reddit = get_option('PixelOnWP_reddit_config', []);
        $reddit_event = $event_name === 'PageView' ? 'PageVisit' : $event_name;
        if (!isset($reddit['events'][$reddit_event]) || filter_var($reddit['events'][$reddit_event], FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }
    }

    if (in_array('google', $platforms, true)) {
        $google_config = get_option('PixelOnWP_google_config', []);
        $conversion_id = isset($google_config['conversion_id']) ? trim($google_config['conversion_id']) : '';
        if (!empty($conversion_id)) {
            return true;
        }
    }

    if (in_array('ga4', $platforms, true)) {
        $ga4 = get_option('PixelOnWP_ga4_config', []);
        $ga4_id = trim($ga4['measurement_id'] ?? get_option('PixelOnWP_ga4_id', ''));
        if (!empty($ga4_id)) {
            return true;
        }
    }

    return false;
  }

  /**
   * Check if an event is active in EITHER Facebook/Google settings or DataLayer Builder.
   */
  private function is_any_event_active(string $fb_event, string $ga4_event): bool
  {
    $events_builder = get_option('PixelOnWP_comprehensive_events', []);
    $dl_enabled = isset($events_builder['datalayer_enabled']) ? filter_var($events_builder['datalayer_enabled'], FILTER_VALIDATE_BOOLEAN) : true;
    
    if ($dl_enabled) {
        $ecommerce_ga4_events = ['page_view', 'view_item_list', 'view_item', 'view_cart', 'begin_checkout', 'add_to_cart', 'purchase', 'remove_from_cart'];
        if (in_array($ga4_event, $ecommerce_ga4_events, true)) {
            return true;
        }
    }
    
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (is_array($platforms) && in_array('google', $platforms, true)) {
        return true;
    }
    
    if ($fb_event === 'Purchase') {
        $reddit_purchase_enabled = filter_var(get_option('pixelonwp_reddit_enable_purchase', '0'), FILTER_VALIDATE_BOOLEAN);
        $reddit = get_option('PixelOnWP_reddit_config', []);
        $is_reddit_purchase_standard_active = !isset($reddit['events']['Purchase']) || filter_var($reddit['events']['Purchase'], FILTER_VALIDATE_BOOLEAN);
        if (is_array($platforms) && in_array('reddit', $platforms, true) && ($is_reddit_purchase_standard_active || $reddit_purchase_enabled)) {
            return true;
        }
    }
    
    if ($this->is_event_active($fb_event)) return true;
    
    return false;
  }

  /**
   * Track ViewContent on single product pages.
   */
  public function track_view_content(): void
  {
    if (!function_exists('is_product') || !is_product() || !$this->is_any_event_active('ViewContent', 'view_item')) {
      return;
    }

    global $product;
    if (!is_a($product, 'WC_Product')) {
      $product = wc_get_product(get_the_ID());
    }

    if (!$product) {
      return;
    }

    $event_data = [
      'event_name' => 'ViewContent',
      'event_id' => 'evt_' . wp_generate_uuid4(),
      'custom_data' => [
        'currency' => get_woocommerce_currency(),
        'value' => (float) $product->get_price(),
        'content_ids' => [\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_product_id($product)],
        'content_name' => $product->get_name(),
        'items' => [
          [
            'item_id' => \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_product_id($product),
            'item_name' => $product->get_name(),
            'price' => (float) $product->get_price(),
            'quantity' => 1
          ]
        ]
      ]
    ];

    $this->dispatch_to_capi($event_data);
    $this->inject_browser_event($event_data);
  }

  /**
   * Track AddToCart.
   */
  public function track_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data): void
  {
    if (!$this->is_any_event_active('AddToCart', 'add_to_cart')) {
      return;
    }

    $product = wc_get_product($variation_id ? $variation_id : $product_id);
    if (!$product) {
      return;
    }

    $event_data = [
      'event_name' => 'AddToCart',
      'event_id' => 'evt_' . wp_generate_uuid4(),
      'custom_data' => [
        'currency' => get_woocommerce_currency(),
        'value' => (float) $product->get_price() * $quantity,
        'content_ids' => [\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_product_id($product)],
        'content_name' => $product->get_name(),
        'num_items' => $quantity,
        'items' => [
          [
            'item_id' => \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_product_id($product),
            'item_name' => $product->get_name(),
            'price' => (float) $product->get_price(),
            'quantity' => $quantity
          ]
        ]
      ]
    ];

    if ($this->is_duplicate_event($event_data)) {
        return;
    }

    $this->dispatch_to_capi($event_data);
    
    if (wp_doing_ajax()) {
        $this->queue_browser_event_in_session($event_data);
    } else {
        $this->inject_browser_event($event_data);
    }
  }

  /**
   * Track Purchase.
   */
  public function track_purchase($order_id): void
  {
    error_log('PixelOnWP debug: track_purchase executing for order ' . $order_id);
    if (!$this->is_any_event_active('Purchase', 'purchase')) {
      error_log('PixelOnWP debug: is_any_event_active Purchase returned false');
      return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
      error_log('PixelOnWP debug: order not found');
      return;
    }
    if ($order->get_meta('_PixelOnWP_tracked') === '1') {
      error_log('PixelOnWP debug: order already tracked');
      return;
    }

    $content_ids = [];
    $items_data = [];
    $num_items = 0;
    foreach ($order->get_items() as $item) {
      $product = $item->get_product();
      $content_ids[] = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_product_id(wc_get_product($item->get_product_id()));
      $items_data[] = [
        'item_id' => (string) $item->get_product_id(),
        'item_name' => $item->get_name(),
        'price' => $product ? (float) $product->get_price() : 0,
        'quantity' => $item->get_quantity()
      ];
      $num_items += $item->get_quantity();
    }

    $event_data = [
      'event_name' => 'Purchase',
      'event_id' => 'evt_' . wp_generate_uuid4(),
      'custom_data' => [
        'currency' => $order->get_currency(),
        'value' => (float) $order->get_total(),
        'content_ids' => $content_ids,
        'num_items' => $num_items,
        'items' => $items_data,
      ],
      'user_data' => [
        'em' => $order->get_billing_email(),
        'fn' => $order->get_billing_first_name(),
        'ln' => $order->get_billing_last_name(),
        'ph' => $order->get_billing_phone(),
        'ct' => $order->get_billing_city(),
        'st' => $order->get_billing_state(),
        'zp' => $order->get_billing_postcode(),
        'country' => $order->get_billing_country(),
        'client_ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'client_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'fbc' => $_COOKIE['_fbc'] ?? '',
        'fbp' => $_COOKIE['_fbp'] ?? '',
      ]
    ];

    $this->dispatch_to_capi($event_data);
    $this->inject_browser_event($event_data);

    update_post_meta($order_id, '_PixelOnWP_tracked', '1');
  }

  /**
   * Track view_item_list (Shop/Category pages)
   */
  public function track_view_item_list(): void
  {
    if (!function_exists('is_shop') || (!is_shop() && !is_product_category() && !is_product_tag()) || !$this->is_any_event_active('ViewCategory', 'view_item_list')) {
      return;
    }
    
    global $wp_query;
    $content_ids = [];
    $items_data = [];
    if (isset($wp_query->posts) && is_array($wp_query->posts)) {
      foreach ($wp_query->posts as $post) {
        $product = wc_get_product($post->ID);
        if ($product) {
          $content_ids[] = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_product_id($product);
          $items_data[] = [
            'item_id' => \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_product_id($product),
            'item_name' => $product->get_name(),
            'price' => (float) $product->get_price(),
            'quantity' => 1
          ];
        }
      }
    }

    $event_data = [
      'event_name' => 'ViewCategory',
      'event_id' => 'evt_' . wp_generate_uuid4(),
      'custom_data' => [
        'content_ids' => $content_ids,
        'content_type' => 'product',
        'items' => $items_data,
      ]
    ];
    $this->dispatch_to_capi($event_data);
    $this->inject_browser_event($event_data);
  }

  /**
   * Track view_cart (Cart page)
   */
  public function track_view_cart(): void
  {
    if (!function_exists('is_cart') || !is_cart() || !$this->is_any_event_active('ViewCart', 'view_cart') || !WC()->cart) {
      return;
    }
    
    $content_ids = [];
    $items_data = [];
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
      $product = wc_get_product($cart_item['product_id']);
      if ($product) {
        $content_ids[] = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_product_id(wc_get_product($cart_item['product_id']));
        $items_data[] = [
          'item_id' => (string) $cart_item['product_id'],
          'item_name' => $product->get_name(),
          'price' => (float) $product->get_price(),
          'quantity' => $cart_item['quantity']
        ];
      }
    }

    $event_data = [
      'event_name' => 'ViewCart',
      'event_id' => 'evt_' . wp_generate_uuid4(),
      'custom_data' => [
        'currency' => get_woocommerce_currency(),
        'value' => (float) WC()->cart->get_cart_contents_total(),
        'content_ids' => $content_ids,
        'content_type' => 'product',
        'items' => $items_data,
      ]
    ];
    $this->dispatch_to_capi($event_data);
    $this->inject_browser_event($event_data);
  }

  /**
   * Track begin_checkout
   */
  public function track_begin_checkout(): void
  {
    if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
      return;
    }
    if (!function_exists('is_checkout') || !is_checkout() || !$this->is_any_event_active('InitiateCheckout', 'begin_checkout') || !WC()->cart) {
      return;
    }
    
    $content_ids = [];
    $items_data = [];
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
      $product = wc_get_product($cart_item['product_id']);
      if ($product) {
        $content_ids[] = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_product_id(wc_get_product($cart_item['product_id']));
        $items_data[] = [
          'item_id' => (string) $cart_item['product_id'],
          'item_name' => $product->get_name(),
          'price' => (float) $product->get_price(),
          'quantity' => $cart_item['quantity']
        ];
      }
    }

    $event_data = [
      'event_name' => 'InitiateCheckout',
      'event_id' => 'evt_' . wp_generate_uuid4(),
      'custom_data' => [
        'currency' => get_woocommerce_currency(),
        'value' => (float) WC()->cart->get_cart_contents_total(),
        'content_ids' => $content_ids,
        'content_type' => 'product',
        'num_items' => WC()->cart->get_cart_contents_count(),
        'items' => $items_data,
      ]
    ];
    $this->dispatch_to_capi($event_data);
    $this->inject_browser_event($event_data);
  }

  /**
   * Track remove_from_cart
   */
  public function track_remove_from_cart($cart_item_key, $cart): void
  {
    if (!$this->is_any_event_active('RemoveFromCart', 'remove_from_cart')) {
      return;
    }
    
    $cart_item = $cart->cart_contents[$cart_item_key] ?? null;
    if (!$cart_item) return;

    $product = wc_get_product($cart_item['product_id']);
    if (!$product) return;

    $event_data = [
      'event_name' => 'RemoveFromCart',
      'event_id' => 'evt_' . wp_generate_uuid4(),
      'custom_data' => [
        'currency' => get_woocommerce_currency(),
        'value' => (float) $product->get_price() * $cart_item['quantity'],
        'content_ids' => [\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_product_id($product)],
        'content_type' => 'product',
        'items' => [
          [
            'item_id' => (string) $product->get_id(),
            'item_name' => $product->get_name(),
            'price' => (float) $product->get_price(),
            'quantity' => $cart_item['quantity']
          ]
        ]
      ]
    ];
    $this->dispatch_to_capi($event_data);
    $this->inject_browser_event($event_data);
  }

  /**
   * Track Standard WP Events (Page Views)
   */
  public function track_standard_wp_events(): void
  {
    if (!$this->is_any_event_active('PageView', 'page_view') && !$this->is_any_event_active('ViewContent', 'view_page')) {
      return;
    }

    $event_name = 'PageView';

    $event_data = [
      'event_name' => $event_name,
      'event_id' => 'evt_' . wp_generate_uuid4(),
      'custom_data' => [
        'page_title' => get_the_title(),
        'page_location' => get_permalink(),
      ]
    ];
    // Dispatch to CAPI and Browser
    $this->dispatch_to_capi($event_data);
    $this->inject_browser_event($event_data);
  }

  /**
   * Track EDD Add To Cart
   */
  public function track_edd_add_to_cart($download_id, $options): void
  {
    if (!$this->is_any_event_active('AddToCart', 'add_to_cart')) return;

    $price = edd_get_download_price($download_id);

    $event_data = [
      'event_name' => 'AddToCart',
      'event_id' => 'evt_' . wp_generate_uuid4(),
      'custom_data' => [
        'currency' => edd_get_currency(),
        'value' => (float) $price,
        'content_ids' => [(string) $download_id],
        'content_type' => 'product',
        'content_name' => get_the_title($download_id),
        'items' => [
          [
            'item_id' => (string) $download_id,
            'item_name' => get_the_title($download_id),
            'price' => (float) $price,
            'quantity' => 1
          ]
        ]
      ]
    ];
    $this->dispatch_to_capi($event_data);
    $this->inject_browser_event($event_data);
  }

  /**
   * Track EDD Purchase
   */
  public function track_edd_purchase($payment_id): void
  {
    if (!$this->is_any_event_active('Purchase', 'purchase')) return;

    $payment = new \EDD_Payment($payment_id);
    if (!$payment || $payment->get_meta('_PixelOnWP_tracked') === '1') return;

    $content_ids = [];
    $items_data = [];
    foreach ($payment->cart_details as $item) {
      $content_ids[] = (string) $item['id'];
      $items_data[] = [
        'item_id' => (string) $item['id'],
        'item_name' => $item['name'],
        'price' => (float) $item['price'],
        'quantity' => $item['quantity']
      ];
    }

    $event_data = [
      'event_name' => 'Purchase',
      'event_id' => 'evt_' . wp_generate_uuid4(),
      'custom_data' => [
        'currency' => $payment->currency,
        'value' => (float) $payment->total,
        'content_ids' => $content_ids,
        'content_type' => 'product',
        'items' => $items_data,
      ],
      'user_data' => [
        'em' => $payment->email,
        'fn' => $payment->first_name,
        'ln' => $payment->last_name,
      ]
    ];

    if ($this->is_duplicate_event($event_data)) {
        return;
    }

    $this->dispatch_to_capi($event_data);
    $this->inject_browser_event($event_data);

    $payment->update_meta('_PixelOnWP_tracked', '1');
  }

  /**
   * Dispatch event to CAPI.
   */
  private function dispatch_to_capi(array $event_data): void
  {
    $tracking_mode = get_option('PixelOnWP_tracking_mode', 'hybrid');
    if ($tracking_mode === 'browser') {
      return;
    }

    $ga4_event = '';
    $event_name = $event_data['event_name'];
    if ($event_name === 'PageView') $ga4_event = 'page_view';
    if ($event_name === 'ViewContent') $ga4_event = 'view_item';
    if ($event_name === 'AddToCart') $ga4_event = 'add_to_cart';
    if ($event_name === 'Purchase') $ga4_event = 'purchase';
    if ($event_name === 'ViewCategory') $ga4_event = 'view_item_list';
    if ($event_name === 'ViewCart') $ga4_event = 'view_cart';
    if ($event_name === 'InitiateCheckout') $ga4_event = 'begin_checkout';
    if ($event_name === 'RemoveFromCart') $ga4_event = 'remove_from_cart';

    // Strictly enforce platform-specific toggles for Server-Side events
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    
    // Meta CAPI
    $is_fb_platform = is_array($platforms) && in_array('facebook', $platforms, true);
    $meta_config = get_option('PixelOnWP_meta_config', []);
    $is_fb_active = $is_fb_platform && (!isset($meta_config['events'][$event_name]) || filter_var($meta_config['events'][$event_name], FILTER_VALIDATE_BOOLEAN));
    $fb_tracking_mode = get_option('PixelOnWP_facebook_tracking_mode', 'hybrid');
    
    if ($is_fb_active && $fb_tracking_mode !== 'browser' && class_exists('\\PixelOnWP\\Includes\\Capi\\PixelOnWP_Capi_Dispatcher')) {
      if ($event_name === 'Purchase') {
        $meta_config = get_option('PixelOnWP_meta_config', []);
        $fb_dynamic_status = !isset($meta_config['events']['DynamicStatus']) || filter_var($meta_config['events']['DynamicStatus'], FILTER_VALIDATE_BOOLEAN);
        if ($fb_dynamic_status) {
          $is_fb_active = false;
        }
      }
      if ($is_fb_active) {
        \PixelOnWP\Includes\Capi\PixelOnWP_Capi_Dispatcher::dispatch($event_data);
      }
    }    // TikTok Events API
    $is_tt_platform = is_array($platforms) && in_array('tiktok', $platforms, true);
    $tiktok_config = get_option('PixelOnWP_tiktok_config', []);
    $tt_event = '';
    if ($event_name === 'PageView') $tt_event = 'Pageview';
    elseif ($event_name === 'ViewContent') $tt_event = 'ViewContent';
    elseif ($event_name === 'AddToCart') $tt_event = 'AddToCart';
    elseif ($event_name === 'Purchase') $tt_event = 'CompletePayment';
    elseif ($event_name === 'InitiateCheckout') $tt_event = 'InitiateCheckout';
    elseif ($event_name === 'Search') $tt_event = 'Search';
    elseif ($event_name === 'AddPaymentInfo') $tt_event = 'AddPaymentInfo';
    elseif ($event_name === 'AddToWishlist') $tt_event = 'AddToWishlist';
    elseif ($event_name === 'PlaceAnOrder') $tt_event = 'PlaceAnOrder';
    elseif ($event_name === 'Contact') $tt_event = 'Contact';
    elseif ($event_name === 'Download') $tt_event = 'Download';
    elseif ($event_name === 'SubmitForm') $tt_event = 'SubmitForm';
    elseif ($event_name === 'CompleteRegistration') $tt_event = 'CompleteRegistration';
    elseif ($event_name === 'Subscribe') $tt_event = 'Subscribe';

    $is_tt_active = $is_tt_platform && $tt_event !== '' && (!isset($tiktok_config['events'][$tt_event]) || filter_var($tiktok_config['events'][$tt_event], FILTER_VALIDATE_BOOLEAN));
    
    if ($is_tt_active && class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_TikTok_Tracker')) {
      if ($event_name === 'Purchase') {
        $tt_dynamic_status = !isset($tiktok_config['events']['DynamicStatus']) || filter_var($tiktok_config['events']['DynamicStatus'], FILTER_VALIDATE_BOOLEAN);
        if ($tt_dynamic_status) {
          $is_tt_active = false;
        }
      }
      if ($is_tt_active) {
        \PixelOnWP\Includes\Tracking\PixelOnWP_TikTok_Tracker::dispatch_tt_server_event_static($event_name, $event_data['event_id'], $event_data['custom_data']);
      }
    }
    // Reddit Events API
    $is_reddit_platform = is_array($platforms) && in_array('reddit', $platforms, true);
    $reddit_config = get_option('PixelOnWP_reddit_config', []);
    
    $reddit_event_map = [
      'PageView' => 'PageVisit',
      'ViewCart' => 'PageVisit',
      'ViewContent' => 'ViewContent',
      'Search' => 'Search',
      'AddToCart' => 'AddToCart',
      'AddToWishlist' => 'AddToWishlist',
      'Purchase' => 'Purchase',
      'Lead' => 'Lead',
      'SignUp' => 'SignUp',
      'CompleteRegistration' => 'SignUp'
    ];
    $reddit_event = isset($reddit_event_map[$event_name]) ? $reddit_event_map[$event_name] : 'Custom';

    $is_reddit_active = $is_reddit_platform && $reddit_event !== '' && (!isset($reddit_config['events'][$reddit_event]) || filter_var($reddit_config['events'][$reddit_event], FILTER_VALIDATE_BOOLEAN));
    $reddit_tracking_mode = get_option('PixelOnWP_reddit_tracking_mode', 'hybrid');

    if ($is_reddit_platform && $reddit_event === 'Purchase') {
      $reddit_purchase_enabled = filter_var(get_option('pixelonwp_reddit_enable_purchase', '0'), FILTER_VALIDATE_BOOLEAN);
      if ($is_reddit_active || $reddit_purchase_enabled) {
        $is_reddit_active = true;
      } else {
        $is_reddit_active = false;
      }
    }

    if ($is_reddit_active && $reddit_tracking_mode !== 'browser' && class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Reddit_Tracker')) {
      if ($is_reddit_active) {
        \PixelOnWP\Includes\Tracking\PixelOnWP_Reddit_Tracker::dispatch_reddit_server_event_static($event_name, $event_data['event_id'], $event_data['custom_data']);
      }
    }

    // GA4 Server-Side Measurement Protocol
    $is_ga4_platform = is_array($platforms) && in_array('ga4', $platforms, true);
    if ($is_ga4_platform && $ga4_event !== '') {
        $ga4_config = get_option('PixelOnWP_ga4_config', []);
        $ga4_events_control = get_option('pixelonwp_ga4_events_control', $ga4_config['events'] ?? []);
        $server_enabled = true;
        if (isset($ga4_events_control[$ga4_event])) {
            $val = $ga4_events_control[$ga4_event];
            if (is_array($val)) {
                $server_enabled = !isset($val['server']) || filter_var($val['server'], FILTER_VALIDATE_BOOLEAN);
            } else {
                $server_enabled = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            }
        }
        if ($server_enabled) {
            $user_data = [];
            if (function_exists('wp_get_current_user')) {
                $user = wp_get_current_user();
                if ($user && $user->ID) {
                    $user_data['email'] = $user->user_email;
                }
            }
            if ($event_name === 'Purchase' && !empty($event_data['custom_data']['transaction_id'])) {
                $order_id = $event_data['custom_data']['transaction_id'];
                if (function_exists('wc_get_order')) {
                    $order = wc_get_order($order_id);
                    if ($order) {
                        $user_data['email'] = $order->get_billing_email();
                        $user_data['phone'] = $order->get_billing_phone();
                    }
                }
            }
            do_action('pixelonwp_track_event', $ga4_event, $event_data['custom_data'] ?? [], $user_data, $event_data['event_id']);
        }
    }
  }

  /**
   * Generate a unique hash for an event to prevent duplicates.
   */
  private function generate_event_hash(array $event_data): string
  {
      $name = $event_data['event_name'];
      $val = $event_data['custom_data']['value'] ?? 0;
      $id = $event_data['custom_data']['content_ids'][0] ?? '';
      return md5($name . $val . $id);
  }

  /**
   * Check if an event was already processed in PHP.
   */
  private function is_duplicate_event(array $event_data): bool
  {
      $hash = $this->generate_event_hash($event_data);
      if (isset(self::$dispatched_event_hashes[$hash])) {
          return true;
      }
      self::$dispatched_event_hashes[$hash] = true;
      return false;
  }

  /**
   * Inject queued events into WooCommerce AJAX add to cart fragments.
   */
  public function track_ajax_add_to_cart(array $fragments): array
  {
    if (function_exists('WC') && isset(WC()->session)) {
      $queued_events = WC()->session->get('PixelOnWP_queued_events', []);
      if (!empty($queued_events)) {
        // Encode events and output as a data attribute instead of inline script
        // This prevents aggressive themes from stripping script tags from AJAX responses
        $encoded_events = esc_attr(wp_json_encode($queued_events));
        
        $fragments['div.PixelOnWP-ajax-scripts'] = '<div class="PixelOnWP-ajax-scripts" data-pixelonwp-events="' . $encoded_events . '"></div>';
        
        // Clear queue after injecting into fragments
        WC()->session->set('PixelOnWP_queued_events', []);
      }
    }
    return $fragments;
  }

  private function inject_browser_event(array $event_data): void
  {
      $d = $event_data['custom_data'] ?? [];
      $json_data = wp_json_encode($event_data['custom_data']);
      $event_id = esc_js($event_data['event_id']);
      $event_name = esc_js($event_data['event_name']);
      
      // Map Facebook Event to GA4 Event
      $ga4_event = '';
      if ($event_name === 'PageView') $ga4_event = 'page_view';
      if ($event_name === 'ViewContent') $ga4_event = 'view_item';
      if ($event_name === 'AddToCart') $ga4_event = 'add_to_cart';
      if ($event_name === 'Purchase') $ga4_event = 'purchase';
      if ($event_name === 'ViewCategory') $ga4_event = 'view_item_list';
      if ($event_name === 'ViewCart') $ga4_event = 'view_cart';
      if ($event_name === 'InitiateCheckout') $ga4_event = 'begin_checkout';
      if ($event_name === 'RemoveFromCart') $ga4_event = 'remove_from_cart';

      $events_builder = get_option('PixelOnWP_comprehensive_events', []);
      $dl_enabled = isset($events_builder['datalayer_enabled']) ? filter_var($events_builder['datalayer_enabled'], FILTER_VALIDATE_BOOLEAN) : true;
      $should_push_dl = ($dl_enabled && $ga4_event !== '');

      $tracking_mode = get_option('PixelOnWP_tracking_mode', 'hybrid');
      $platforms = get_option('PixelOnWP_selected_platforms', []);
      
      $is_fb_platform = is_array($platforms) && in_array('facebook', $platforms, true);
      $meta_config = get_option('PixelOnWP_meta_config', []);
      $is_fb_active = $is_fb_platform && (!isset($meta_config['events'][$event_name]) || filter_var($meta_config['events'][$event_name], FILTER_VALIDATE_BOOLEAN));
      $fb_tracking_mode = get_option('PixelOnWP_facebook_tracking_mode', 'hybrid');
      $should_fire_fbq = $is_fb_active && $fb_tracking_mode !== 'server';
      if ($event_name === 'Purchase') {
          $fb_dynamic_status = !isset($meta_config['events']['DynamicStatus']) || filter_var($meta_config['events']['DynamicStatus'], FILTER_VALIDATE_BOOLEAN);
          if ($fb_dynamic_status) {
              $should_fire_fbq = false;
          }
      }
      
      $is_tt_platform = is_array($platforms) && in_array('tiktok', $platforms, true);
      $tiktok_config = get_option('PixelOnWP_tiktok_config', []);
      $tt_event = '';
      if ($event_name === 'PageView') $tt_event = 'Pageview';
      elseif ($event_name === 'ViewContent') $tt_event = 'ViewContent';
      elseif ($event_name === 'AddToCart') $tt_event = 'AddToCart';
      elseif ($event_name === 'Purchase') $tt_event = 'CompletePayment';
      elseif ($event_name === 'InitiateCheckout') $tt_event = 'InitiateCheckout';
      elseif ($event_name === 'Search') $tt_event = 'Search';
      elseif ($event_name === 'AddPaymentInfo') $tt_event = 'AddPaymentInfo';
      elseif ($event_name === 'AddToWishlist') $tt_event = 'AddToWishlist';
      elseif ($event_name === 'PlaceAnOrder') $tt_event = 'PlaceAnOrder';
      elseif ($event_name === 'Contact') $tt_event = 'Contact';
      elseif ($event_name === 'Download') $tt_event = 'Download';
      elseif ($event_name === 'SubmitForm') $tt_event = 'SubmitForm';
      elseif ($event_name === 'CompleteRegistration') $tt_event = 'CompleteRegistration';
      elseif ($event_name === 'Subscribe') $tt_event = 'Subscribe';
      
      $is_tt_active = $is_tt_platform && $tt_event !== '' && (!isset($tiktok_config['events'][$tt_event]) || filter_var($tiktok_config['events'][$tt_event], FILTER_VALIDATE_BOOLEAN));
      $should_fire_ttq = $is_tt_active;
      if ($event_name === 'Purchase') {
          $tt_dynamic_status = !isset($tiktok_config['events']['DynamicStatus']) || filter_var($tiktok_config['events']['DynamicStatus'], FILTER_VALIDATE_BOOLEAN);
          if ($tt_dynamic_status) {
              $should_fire_ttq = false;
          }
      }

      $is_reddit_platform = is_array($platforms) && in_array('reddit', $platforms, true);
      $reddit_config = get_option('PixelOnWP_reddit_config', []);
      
      $reddit_event_map = [
        'PageView' => 'PageVisit',
        'ViewCart' => 'PageVisit',
        'ViewContent' => 'ViewContent',
        'Search' => 'Search',
        'AddToCart' => 'AddToCart',
        'AddToWishlist' => 'AddToWishlist',
        'Purchase' => 'Purchase',
        'Lead' => 'Lead',
        'SignUp' => 'SignUp',
        'CompleteRegistration' => 'SignUp'
      ];
      $reddit_event = isset($reddit_event_map[$event_name]) ? $reddit_event_map[$event_name] : 'Custom';
      
      $is_reddit_active = $is_reddit_platform && $reddit_event !== '' && (!isset($reddit_config['events'][$reddit_event]) || filter_var($reddit_config['events'][$reddit_event], FILTER_VALIDATE_BOOLEAN));
      if ($is_reddit_platform && $reddit_event === 'Purchase') {
          $reddit_purchase_enabled = filter_var(get_option('pixelonwp_reddit_enable_purchase', '0'), FILTER_VALIDATE_BOOLEAN);
          if ($is_reddit_active || $reddit_purchase_enabled) {
              $is_reddit_active = true;
          } else {
              $is_reddit_active = false;
          }
      }
      $reddit_tracking_mode = get_option('PixelOnWP_reddit_tracking_mode', 'hybrid');
      $should_fire_rdt = $is_reddit_active && $reddit_tracking_mode !== 'server';

      $reddit_data = [
        'conversionId' => $event_id
      ];

      if ($reddit_event === 'Custom') {
        $reddit_data['customEventName'] = $event_name;
      }

      // Add searchQuery only for Search
      if ($reddit_event === 'Search' && isset($event_data['custom_data']['search_term'])) {
        $reddit_data['searchQuery'] = $event_data['custom_data']['search_term'];
      }

      // Populate products and itemCount only for allowed events
      $supports_products = in_array($reddit_event, ['ViewContent', 'AddToCart', 'AddToWishlist', 'Purchase', 'Custom'], true);
      $supports_value_currency = in_array($reddit_event, ['Purchase', 'Custom'], true);

      if ($supports_products) {
          $reddit_products = [];
          $reddit_item_count = 0;
          $raw_items = [];
          if (!empty($event_data['custom_data']['items']) && is_array($event_data['custom_data']['items'])) {
              $raw_items = $event_data['custom_data']['items'];
          } elseif (!empty($event_data['custom_data']['contents']) && is_array($event_data['custom_data']['contents'])) {
              $raw_items = $event_data['custom_data']['contents'];
          }

          if (!empty($raw_items)) {
              foreach ($raw_items as $item) {
                  $p_id = $item['item_id'] ?? $item['id'] ?? $item['product_id'] ?? $item['productId'] ?? $item['content_id'] ?? '';
                  if (empty($p_id)) continue;
                  
                  $qty = intval($item['quantity'] ?? 1);
                  $reddit_item_count += $qty;
                  
                  $reddit_products[] = [
                      'id' => (string)$p_id,
                      'name' => $item['item_name'] ?? $item['name'] ?? $item['content_name'] ?? '',
                      'category' => $item['item_category'] ?? $item['category'] ?? $item['content_category'] ?? '',
                      'itemPrice' => (float)($item['price'] ?? $item['item_price'] ?? 0.0),
                      'quantity' => $qty
                  ];
              }
          }

          if (!empty($reddit_products)) {
              $reddit_data['products'] = $reddit_products;
              if ($supports_value_currency) {
                  $reddit_data['itemCount'] = $reddit_item_count;
              }
          }
      }

      if ($supports_value_currency) {
          if (isset($event_data['custom_data']['currency'])) {
            $reddit_data['currency'] = $event_data['custom_data']['currency'];
          }
          if (isset($event_data['custom_data']['value'])) {
            $reddit_data['value'] = (float)$event_data['custom_data']['value'];
          }
          if (empty($reddit_data['itemCount'])) {
              $reddit_data['itemCount'] = isset($reddit_item_count) && $reddit_item_count > 0 ? $reddit_item_count : 1;
          }
      }

      if ($reddit_event === 'Purchase') {
          $reddit_data = [
              'value'     => (float)($event_data['custom_data']['value'] ?? 0.0),
              'currency'  => $event_data['custom_data']['currency'] ?? 'USD',
              'itemCount' => (int)(isset($reddit_item_count) && $reddit_item_count > 0 ? $reddit_item_count : 1)
          ];
          if (!empty($event_id)) {
              $reddit_data['conversionId'] = $event_id;
          }
      }

      $reddit_json_data = wp_json_encode($reddit_data);

      $is_google_platform = is_array($platforms) && in_array('google', $platforms, true);
      $google_config = get_option('PixelOnWP_google_config', []);
      $conversion_id = isset($google_config['conversion_id']) ? trim($google_config['conversion_id']) : '';
      $google_events = isset($google_config['events']) && is_array($google_config['events']) ? $google_config['events'] : [];
      $fallback_label = isset($google_config['conversion_label']) ? trim($google_config['conversion_label']) : '';
      $should_fire_google = $is_google_platform && !empty($conversion_id);

      $is_ga4_platform = is_array($platforms) && in_array('ga4', $platforms, true);
      $ga4_config = get_option('PixelOnWP_ga4_config', []);
      $ga4_id = trim($ga4_config['measurement_id'] ?? get_option('PixelOnWP_ga4_id', ''));
      $is_ga4_active = $is_ga4_platform && !empty($ga4_id);
      
      $should_fire_ga4_gtag = false;
      $ga4_gtag_payload_json = '{}';
      if ($is_ga4_active && $ga4_event !== '') {
          $ga4_events_control = get_option('pixelonwp_ga4_events_control', $ga4_config['events'] ?? []);
          $browser_enabled = true;
          if (isset($ga4_events_control[$ga4_event])) {
              $val = $ga4_events_control[$ga4_event];
              if (is_array($val)) {
                  $browser_enabled = !isset($val['browser']) || filter_var($val['browser'], FILTER_VALIDATE_BOOLEAN);
              } else {
                  $browser_enabled = filter_var($val, FILTER_VALIDATE_BOOLEAN);
              }
          }
          if ($browser_enabled) {
              $should_fire_ga4_gtag = true;
              
              $ga4_gtag_payload = [];
              $is_ecommerce = in_array($ga4_event, ['view_item', 'add_to_cart', 'purchase', 'view_item_list', 'view_cart', 'begin_checkout', 'remove_from_cart']);
              if ($is_ecommerce) {
                  $ga4_gtag_payload = [
                      'currency' => $event_data['custom_data']['currency'] ?? 'USD',
                      'value' => $event_data['custom_data']['value'] ?? 0,
                      'items' => []
                  ];
                  if (!empty($event_data['custom_data']['items'])) {
                      $ga4_gtag_payload['items'] = $event_data['custom_data']['items'];
                  }
                  if (!empty($event_data['custom_data']['transaction_id'])) {
                      $ga4_gtag_payload['transaction_id'] = $event_data['custom_data']['transaction_id'];
                  }
              } else {
                  $ga4_gtag_payload = $event_data['custom_data'] ?? [];
              }
              if (!empty($ga4_config['test_code']) || is_user_logged_in()) {
                  $ga4_gtag_payload['debug_mode'] = true;
              }
              $ga4_gtag_payload_json = wp_json_encode($ga4_gtag_payload);
          }
      }

      $is_ecommerce = in_array($ga4_event, ['view_item', 'add_to_cart', 'purchase', 'view_item_list', 'view_cart', 'begin_checkout', 'remove_from_cart']);

      $payload = [
        'event' => $ga4_event,
        'event_id' => $event_id
      ];

      // Attach Ecommerce Schema or Standard Parameters
      if ($is_ecommerce) {
        $ga4_ecommerce = [
          'currency' => $event_data['custom_data']['currency'] ?? 'USD',
          'value' => $event_data['custom_data']['value'] ?? 0,
          'items' => []
        ];
        
        if (!empty($event_data['custom_data']['items'])) {
          foreach ($event_data['custom_data']['items'] as $item) {
            $ga4_ecommerce['items'][] = $item;
          }
        } elseif (!empty($event_data['custom_data']['content_ids'])) {
          foreach ($event_data['custom_data']['content_ids'] as $id) {
            $ga4_ecommerce['items'][] = [
              'item_id' => $id,
              'item_name' => $event_data['custom_data']['content_name'] ?? 'Product',
            ];
          }
        }
        $payload['ecommerce'] = $ga4_ecommerce;
      } else {
        // Map non-ecommerce parameters (e.g. page_title, page_location)
        foreach ($event_data['custom_data'] as $key => $val) {
          $payload[$key] = $val;
        }
      }

      $gtm_payload = wp_json_encode($payload);

      // -- Build Facebook-formatted data from custom_data --
      $fb_mapped = \PixelOnWP\Includes\Tracking\PixelOnWP_Meta_Tracker::get_fb_event_data($event_name, $event_data['custom_data'] ?? []);
      $fb_json_data = wp_json_encode($fb_mapped['custom_data']);

      // -- Build TikTok-formatted data from custom_data --
      $tt_data = [];
      if (isset($d['currency'])) $tt_data['currency'] = $d['currency'];
      if (isset($d['value'])) $tt_data['value'] = $d['value'];
      if (isset($d['search_term'])) $tt_data['query'] = $d['search_term'];

      if (!empty($d['contents']) && is_array($d['contents'])) {
          $tt_data['content_type'] = $d['content_type'] ?? 'product';
          $tt_data['contents'] = [];
          foreach ($d['contents'] as $item) {
              $id = $item['content_id'] ?? $item['id'] ?? $item['item_id'] ?? $item['product_id'] ?? $item['productId'] ?? '';
              if (!empty($id)) {
                  $tt_data['contents'][] = [
                      'content_id' => (string)$id,
                      'content_name' => $item['content_name'] ?? $item['name'] ?? '',
                      'price' => $item['price'] ?? 0,
                      'quantity' => $item['quantity'] ?? 1
                  ];
              }
          }
          if (count($tt_data['contents']) === 1) {
              $tt_data['content_id'] = $tt_data['contents'][0]['content_id'];
              $tt_data['content_ids'] = [$tt_data['contents'][0]['content_id']];
              $tt_data['content_name'] = $tt_data['contents'][0]['content_name'];
              $tt_data['price'] = $tt_data['contents'][0]['price'];
              $tt_data['quantity'] = $tt_data['contents'][0]['quantity'];
          }
      } elseif (!empty($d['items']) && is_array($d['items'])) {
          $tt_data['content_type'] = 'product';
          if (count($d['items']) === 1) {
               $id = $d['items'][0]['item_id'] ?? $d['items'][0]['id'] ?? $d['items'][0]['product_id'] ?? $d['items'][0]['productId'] ?? '';
               $tt_data['content_id'] = (string)$id;
               $tt_data['content_ids'] = [(string)$id];
               $tt_data['content_name'] = $d['items'][0]['item_name'] ?? $d['items'][0]['name'] ?? '';
               if (isset($d['items'][0]['item_category'])) $tt_data['content_category'] = $d['items'][0]['item_category'];
               $tt_data['price'] = $d['items'][0]['price'] ?? 0;
               $tt_data['quantity'] = $d['items'][0]['quantity'] ?? 1;
               $tt_data['contents'] = [[
                   'content_id' => (string)$id,
                   'content_name' => $tt_data['content_name'],
                   'price' => $tt_data['price'],
                   'quantity' => $tt_data['quantity']
               ]];
          } else {
               $tt_data['contents'] = [];
               $tt_data['content_ids'] = [];
               foreach ($d['items'] as $item) {
                   $id = $item['item_id'] ?? $item['id'] ?? $item['product_id'] ?? $item['productId'] ?? '';
                   if (!empty($id)) {
                       $tt_data['content_ids'][] = (string)$id;
                       $tt_data['contents'][] = [
                           'content_id' => (string)$id,
                           'content_name' => $item['content_name'] ?? $item['name'] ?? '',
                           'price' => $item['price'] ?? 0,
                           'quantity' => $item['quantity'] ?? 1
                       ];
                   }
               }
          }
      } elseif (!empty($d['content_ids']) && is_array($d['content_ids'])) {
          $tt_data['content_type'] = $d['content_type'] ?? 'product';
          $tt_data['content_ids'] = [];
          foreach ($d['content_ids'] as $id) {
              $tt_data['content_ids'][] = (string)$id;
          }
          if (count($tt_data['content_ids']) === 1) {
              $tt_data['content_id'] = (string)$tt_data['content_ids'][0];
              $tt_data['content_name'] = $d['content_name'] ?? 'Product';
              $tt_data['price'] = $d['price'] ?? ($d['value'] ?? 0);
              $tt_data['quantity'] = $d['quantity'] ?? 1;
              $tt_data['contents'] = [[
                  'content_id' => $tt_data['content_id'],
                  'content_name' => $tt_data['content_name'],
                  'price' => $tt_data['price'],
                  'quantity' => $tt_data['quantity']
              ]];
          } else {
              $tt_data['contents'] = [];
              foreach ($tt_data['content_ids'] as $id) {
                  $tt_data['contents'][] = [
                      'content_id' => (string)$id,
                      'price' => $d['price'] ?? 0,
                      'quantity' => 1
                  ];
              }
          }
      }

      // WooCommerce Dynamic Product Enrichment Fallback for inject_browser_event
      if (function_exists('wc_get_product')) {
          if (!empty($tt_data['content_id']) && (empty($tt_data['content_name']) || floatval($tt_data['price'] ?? 0) === 0.0)) {
              $product = wc_get_product($tt_data['content_id']);
              if ($product) {
                  if (empty($tt_data['content_name'])) {
                      $tt_data['content_name'] = $product->get_name();
                  }
                  if (floatval($tt_data['price'] ?? 0) === 0.0) {
                      $tt_data['price'] = (float)$product->get_price();
                      if (isset($tt_data['value']) && floatval($tt_data['value']) === 0.0) {
                          $tt_data['value'] = $tt_data['price'] * ($tt_data['quantity'] ?? 1);
                      }
                  }
              }
          }
      }

      $tt_json_data = wp_json_encode($tt_data);

      $google_script = '';
      if ($should_fire_google) {
          $label = '';
          $normalized_event = strtolower($ga4_event);
          foreach ($google_events as $ev) {
              if (isset($ev['name']) && strtolower($ev['name']) === $normalized_event && !empty($ev['label'])) {
                  $label = trim($ev['label']);
                  break;
              }
          }
          if (empty($label) && $normalized_event === 'purchase' && !empty($fallback_label)) {
              $label = $fallback_label;
          }
          
          $gData = isset($payload['ecommerce']) ? $payload['ecommerce'] : [];
          if (empty($gData)) {
              $gData = $payload;
              if (isset($gData['event'])) unset($gData['event']);
              if (isset($gData['event_id'])) unset($gData['event_id']);
          }
          
          if (!empty($label)) {
              $gData['send_to'] = $conversion_id . '/' . $label;
          } else {
              $gData['send_to'] = $conversion_id;
          }
          
          $g_json_data = wp_json_encode($gData);
          $google_script = "
          if(typeof window.gtag !== 'undefined') {
            gtag('event', '{$ga4_event}', {$g_json_data});
          }
          ";
      }

      $script = "<script>
        window.PixelOnWP_Fired = window.PixelOnWP_Fired || {};
        if (!window.PixelOnWP_Fired['{$ga4_event}_{$event_id}']) {
          window.PixelOnWP_Fired['{$ga4_event}_{$event_id}'] = true;
          console.log('PixelOnWP System: Injecting Event ->', '{$ga4_event}');
          
          // Meta Pixel Push
          " . ($should_fire_fbq ? "
          if(typeof window.fbq !== 'undefined') {
            fbq('track', '{$event_name}', {$fb_json_data}, {eventID: '{$event_id}'});
          }
          " : "") . "
        // TikTok Pixel Push
        " . ($should_fire_ttq && $tt_event ? "
        if(typeof window.ttq !== 'undefined') {
          if ('{$tt_event}' === 'Pageview') {
            ttq.page();
          } else {
            ttq.track('{$tt_event}', {$tt_json_data}, {event_id: '{$event_id}'});
          }
        }
        " : "") . "
        
        // Reddit Pixel Push
        " . ($should_fire_rdt && $reddit_event ? "
        if(typeof window.rdt !== 'undefined') {
          rdt('track', '{$reddit_event}', {$reddit_json_data});
        }
        " : "") . "
        
        // Google Ads Push
        " . ($should_fire_google ? $google_script : "") . "

        // GA4 Browser Push
        " . ($should_fire_ga4_gtag ? "
        if(typeof window.gtag !== 'undefined') {
          gtag('event', '{$ga4_event}', {$ga4_gtag_payload_json});
        }
        " : "") . "
        
        // GTM DataLayer Push
        " . ($should_push_dl ? "
        window.dataLayer = window.dataLayer || [];
        if ('{$ga4_event}' !== '') {
          " . ($is_ecommerce ? "window.dataLayer.push({ ecommerce: null }); // Clear previous" : "") . "
          window.dataLayer.push({$gtm_payload});
          console.log('PixelOnWP DataLayer Pushed:', '{$ga4_event}', {$gtm_payload});
        }
        " : "") . "
        } else {
          console.warn('PixelOnWP System: Duplicate Event Prevented ->', '{$ga4_event}', '{$event_id}');
        }
      </script>";
      
      $this->queued_events[$event_name] = $script;
  }

  /**
   * Queue browser event in session for next page load.
   */
  private function queue_browser_event_in_session(array $event_data): void
  {
    if (function_exists('WC') && isset(WC()->session)) {
      $queued = WC()->session->get('PixelOnWP_queued_events', []);
      $queued[] = $event_data;
      WC()->session->set('PixelOnWP_queued_events', $queued);
    } else {
      if (!session_id() && !headers_sent()) {
        session_start();
      }
      if (!isset($_SESSION['PixelOnWP_queued_events'])) {
        $_SESSION['PixelOnWP_queued_events'] = [];
      }
      $_SESSION['PixelOnWP_queued_events'][] = $event_data;
    }
  }

  /**
   * Process queued events on the next page load.
   */
  public function process_queued_browser_events(): void
  {
    $queued_events = [];
    if (function_exists('WC') && isset(WC()->session)) {
      $queued_events = WC()->session->get('PixelOnWP_queued_events', []);
      WC()->session->set('PixelOnWP_queued_events', []); // clear
    } elseif (isset($_SESSION['PixelOnWP_queued_events'])) {
      $queued_events = $_SESSION['PixelOnWP_queued_events'];
      $_SESSION['PixelOnWP_queued_events'] = []; // clear
    }

    if (!empty($queued_events)) {
      foreach ($queued_events as $event_data) {
        // Output each queued event using our dynamic lifecycle injector
        $this->inject_browser_event($event_data);
      }
    }
  }
  
  /**
   * Output all queued events at once in the footer.
   * This deduplicates events by name and ensures scripts are printed safely after body load.
   */
  public function output_queued_events(): void
  {
    // Always print the placeholder container for WooCommerce AJAX fragments to find
    echo '<div class="PixelOnWP-ajax-scripts"></div>' . "\n";

    if (empty($this->queued_events)) {
      return;
    }
    
    foreach ($this->queued_events as $event_name => $script) {
      echo $script . "\n";
    }
    
    // Clear queue after output
    $this->queued_events = [];
  }
}