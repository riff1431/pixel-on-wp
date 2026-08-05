<?php
/**
 * TikTok Tracker Class (Theme-Wise independent).
 *
 * @package PixelOnWP\Includes\Tracking
 */

namespace PixelOnWP\Includes\Tracking;

if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_TikTok_Tracker {
  
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader) {
    // Base injection is handled by class-frontend.php
  }
  private function is_event_enabled($event_name) {
      $active_events = get_option('PixelOnWP_active_events', []);
      $key = strtolower($event_name);
      
      // Some mappings for check
      if ($key === 'completepurchase' || $key === 'purchase') $key = 'purchase';
      if ($key === 'submitform') $key = 'lead';

      if (isset($active_events[$key])) {
          return $active_events[$key] === '1';
      }
      if ($key === 'place_an_order' || $key === 'placeanorder') {
          return false;
      }
      return true; // Default enabled
  }
  public function inject_tiktok_pixel_base() {
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!is_array($platforms) || !in_array('tiktok', $platforms, true)) {
      return;
    }

    $tt_config = get_option('PixelOnWP_tiktok_config', []);
    $pixel_id = isset($tt_config['pixel_id']) ? trim($tt_config['pixel_id']) : '';
    if (empty($pixel_id)) return;

    $user_data = self::get_hashed_user_data();

    $tt_user = [];
    if (!empty($user_data['em'])) $tt_user['email'] = $user_data['em'];
    if (!empty($user_data['ph'])) $tt_user['phone_number'] = $user_data['ph'];
    if (!empty($user_data['external_id'])) $tt_user['external_id'] = $user_data['external_id'];

    ?>
    <!-- TikTok Pixel Base Code -->
    <script>
      !function (w, d, t) {
        w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=document.createElement("script");n.type="text/javascript",n.async=!0,n.src=i+"?sdkid="+e+"&lib="+t;e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};
        ttq.load('<?php echo esc_js($pixel_id); ?>');
        <?php if (!empty($tt_user)) { ?>
        ttq.identify(<?php echo wp_json_encode($tt_user); ?>);
        <?php } ?>
      }(window, document, 'ttq');
    </script>
    <!-- End TikTok Pixel Base Code -->
    <?php
  }

  public function track_page_view() {
    $event_id = 'evt_' . wp_generate_uuid4();
    $data = [
      'page_type' => is_front_page() ? 'home' : (is_single() ? 'post' : 'page'),
      'referrer' => isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : ''
    ];
    $this->output_event_script('PageView', $event_id, $data);
  }

  public function track_search() {
    if (is_search()) {
       $event_id = 'evt_' . wp_generate_uuid4();
       $data = [
         'search_string' => get_search_query()
       ];
       $this->output_event_script('Search', $event_id, $data);
    }
  }

  public function track_view_content() {
    if (!function_exists('is_product') || !is_product()) return;
    
    global $product;
    if (!$product) return;
    
    $event_id = 'evt_' . wp_generate_uuid4();
    $data = [
      'content_type' => 'product',
      'quantity' => 1,
      'content_name' => $product->get_name(),
      'content_id' => (string)$product->get_id(), // For TikTok
      'value' => (float)$product->get_price(),
      'currency' => get_woocommerce_currency(),
      'contents' => [
          [
              'content_id' => (string)$product->get_id(),
              'content_name' => $product->get_name(),
              'quantity' => 1,
              'price' => (float)$product->get_price()
          ]
      ]
    ];
    
    $cats = wp_get_post_terms($product->get_id(), 'product_cat');
    if (!empty($cats) && !is_wp_error($cats)) {
      $data['content_category'] = $cats[0]->name;
    }

    $this->output_event_script('ViewContent', $event_id, $data);
  }

  public function track_view_cart() {
    if (function_exists('is_cart') && is_cart()) {
        $event_id = 'evt_' . wp_generate_uuid4();
        
        $contents = [];
        $num_items = 0;
        
        if (function_exists('WC') && WC()->cart) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $contents[] = [
                    'content_id' => (string)$product->get_id(),
                    'content_name' => $product->get_name(),
                    'quantity' => $cart_item['quantity'],
                    'price' => (float)$product->get_price()
                ];
                $num_items += $cart_item['quantity'];
            }
        }
        
        $data = [
            'content_type' => 'product',
            'value' => (float) WC()->cart->get_cart_contents_total(),
            'currency' => get_woocommerce_currency(),
            'contents' => $contents,
            'quantity' => $num_items
        ];
        
        $this->output_event_script('ViewCart', $event_id, $data);
    }
  }

  public function track_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    $event_id = 'evt_' . wp_generate_uuid4();
    $product = wc_get_product($variation_id ? $variation_id : $product_id);
    if (!$product) return;

    $data = [
      'content_type' => 'product',
      'content_id' => (string)$product->get_id(),
      'content_name' => $product->get_name(),
      'value' => (float)$product->get_price() * $quantity,
      'currency' => get_woocommerce_currency(),
      'quantity' => $quantity,
      'contents' => [
          [
              'content_id' => (string)$product->get_id(),
              'content_name' => $product->get_name(),
              'quantity' => $quantity,
              'price' => (float)$product->get_price()
          ]
      ]
    ];
    
    $this->output_event_script('AddToCart', $event_id, $data);
  }

  public function track_initiate_checkout() {
    if (function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url('order-pay') && !is_wc_endpoint_url('order-received')) {
        $event_id = 'evt_' . wp_generate_uuid4();
        
        $contents = [];
        $num_items = 0;
        
        if (function_exists('WC') && WC()->cart) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $contents[] = [
                    'content_id' => (string)$product->get_id(),
                    'content_name' => $product->get_name(),
                    'quantity' => $cart_item['quantity'],
                    'price' => (float)$product->get_price()
                ];
                $num_items += $cart_item['quantity'];
            }
        }
        
        $data = [
            'content_type' => 'product',
            'value' => (float) WC()->cart->get_cart_contents_total(),
            'currency' => get_woocommerce_currency(),
            'contents' => $contents,
            'quantity' => $num_items
        ];
        
        $this->output_event_script('InitiateCheckout', $event_id, $data);
    }
  }

  public function track_purchase($order_id) {
    // Database Option Retrieval Fix for DynamicStatus
    $active_events = get_option('PixelOnWP_active_events', []);
    $dynamic_status_enabled = isset($active_events['dynamicstatus']) ? $active_events['dynamicstatus'] : '1';

    if ($dynamic_status_enabled === '1' || $dynamic_status_enabled === 'yes' || $dynamic_status_enabled === true) {
        return; // Bypass immediate tracking
    }
    
    $order = wc_get_order($order_id);
    if (!$order) return;
    if ($order->get_meta('_tt_tracked')) return;

    $event_id = 'evt_' . wp_generate_uuid4();
    
    $contents = [];
    $num_items = 0;
    
    $setting = get_option('pixelonwp_settings', []);
    $identifier_type = isset($setting['product_identifier']) ? $setting['product_identifier'] : 'id';
    
    foreach ($order->get_items() as $item_id => $item) {
      $product = $item->get_product();
      
      $pid = (string)$item->get_product_id();
      if ($product) {
          if ($identifier_type === 'sku' && $product->get_sku()) {
              $pid = (string)$product->get_sku();
          } else {
              $pid = (string)$product->get_id();
          }
      }
      
      $contents[] = [
          'content_id' => $pid,
          'content_name' => $item->get_name(),
          'quantity' => $item->get_quantity(),
          'price' => (float)$order->get_item_total($item, false, false)
      ];
      $num_items += $item->get_quantity();
    }

    $data = [
      'content_type' => 'product',
      'order_id' => (string)$order->get_id(),
      'contents' => $contents,
      'value' => (float)$order->get_total(),
      'currency' => $order->get_currency(),
      'quantity' => $num_items
    ];
    
    $this->output_event_script('Purchase', $event_id, $data);
    $order->update_meta_data('_tt_tracked', 'yes');
    $order->save();
  }

  public function track_complete_registration($user_id) {
     $event_id = 'evt_' . wp_generate_uuid4();
     $user_info = get_userdata($user_id);
     $data = [
         'status' => 'success'
     ];
     $this->output_event_script('CompleteRegistration', $event_id, $data);
  }



  public function track_order_status_change($order_id, $old_status, $new_status, $order) {
    if (!$this->is_event_enabled('DynamicStatus')) return;
    
    $event_name = '';
    switch ($new_status) {
        case 'pending': $event_name = 'PendingOrder'; break;
        case 'processing': $event_name = 'ProcessingOrder'; break;
        case 'on-hold': $event_name = 'OnHoldOrder'; break;
        case 'completed': $event_name = 'Purchase'; break;
        case 'cancelled': $event_name = 'CancelledOrder'; break;
        case 'refunded': $event_name = 'Refund'; break;
        default: return;
    }
    
    // To prevent duplicate 'completed' / Purchase if it was already tracked via frontend
    if ($new_status === 'completed' && $order->get_meta('_tt_tracked')) {
        return;
    }

    $event_id = 'evt_' . wp_generate_uuid4();
    
    $contents = [];
    $num_items = 0;
    
    $setting = get_option('pixelonwp_settings', []);
    $identifier_type = isset($setting['product_identifier']) ? $setting['product_identifier'] : 'id';
    
    foreach ($order->get_items() as $item_id => $item) {
      $product = $item->get_product();
      
      $pid = (string)$item->get_product_id();
      if ($product) {
          if ($identifier_type === 'sku' && $product->get_sku()) {
              $pid = (string)$product->get_sku();
          } else {
              $pid = (string)$product->get_id();
          }
      }
      
      $contents[] = [
          'content_id' => $pid,
          'content_name' => $item->get_name(),
          'quantity' => $item->get_quantity(),
          'price' => (float)$order->get_item_total($item, false, false)
      ];
      $num_items += $item->get_quantity();
    }

    $data = [
      'content_type' => 'product',
      'order_id' => (string)$order->get_id(),
      'contents' => $contents,
      'value' => (float)$order->get_total(),
      'currency' => $order->get_currency(),
      'quantity' => $num_items
    ];
    
    // Dispatch to server-side only
    self::dispatch_tt_server_event_static($event_name, $event_id, $data, $order);
    
    if ($new_status === 'completed') {
        $order->update_meta_data('_tt_tracked', 'yes');
        $order->save();
    }
  }

  private function output_event_script($event_name, $event_id, $data) {
    if (!$this->is_event_enabled($event_name)) {
        return;
    }

    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!in_array('tiktok', $platforms, true)) return;
    
    $tt_event = $event_name;
    // Map events
    if ($event_name === 'Lead') $tt_event = 'SubmitForm';

    if (!wp_doing_ajax()) {
        echo "<script>\n";
        if ($event_name === 'PageView') {
             echo "if(typeof ttq !== 'undefined') { ttq.page(); }\n";
        } else {
             echo "if(typeof ttq !== 'undefined') { ttq.track('" . esc_js($tt_event) . "', " . wp_json_encode($data) . ", {event_id: '" . esc_js($event_id) . "'}); }\n";
        }
        echo "</script>\n";
    }

    // Server dispatch is handled by the collect endpoint (fetch call from client).
    // Do NOT dispatch here to avoid duplicate TikTok server events.
  }

  public static function dispatch_tt_server_event_static($event_name, $event_id, $data, $order = null, $user_data_override = null) {
      $map = [
          'page_view' => 'Pageview', 'pageview' => 'Pageview',
          'view_item' => 'ViewContent', 'viewcontent' => 'ViewContent',
          'add_to_cart' => 'AddToCart', 'addtocart' => 'AddToCart',
          'add_to_wishlist' => 'AddToWishlist', 'addtowishlist' => 'AddToWishlist',
          'begin_checkout' => 'InitiateCheckout', 'initiatecheckout' => 'InitiateCheckout',
          'add_payment_info' => 'AddPaymentInfo', 'addpaymentinfo' => 'AddPaymentInfo',
          'purchase' => 'CompletePayment', 'completepayment' => 'CompletePayment',
          'generate_lead' => 'SubmitForm', 'submitform' => 'SubmitForm',
          'contact' => 'Contact', 'schedule' => 'Contact',
          'sign_up' => 'CompleteRegistration', 'completeregistration' => 'CompleteRegistration',
          'search' => 'Search',
          'lead' => 'SubmitForm',
          'placeanorder' => 'PlaceAnOrder',
          'download' => 'Download',
          'subscribe' => 'Subscribe'
      ];
      $event_key = strtolower($event_name);
      $tt_event_name = isset($map[$event_key]) ? $map[$event_key] : $event_name;

      $check_event = strtolower($tt_event_name);
      if ($check_event === 'completepurchase') $check_event = 'purchase';

      $active_events = get_option('PixelOnWP_active_events', []);
      $is_active = isset($active_events[$check_event]) ? ($active_events[$check_event] === '1') : (($check_event === 'place_an_order' || $check_event === 'placeanorder') ? false : true);
      if (!$is_active) {
          return;
      }
      $platforms = get_option('PixelOnWP_selected_platforms', []);
      if (!in_array('tiktok', $platforms, true)) return;

      $tt_config = get_option('PixelOnWP_tiktok_config', []);
      $pixel_id = isset($tt_config['pixel_id']) ? trim($tt_config['pixel_id']) : '';
      $access_token = isset($tt_config['access_token']) ? trim($tt_config['access_token']) : '';
      
      if (empty($pixel_id) || empty($access_token)) return;

      $user_data = $user_data_override ? $user_data_override : self::get_hashed_user_data($order);
      
      $tt_user = [
          'client_ip_address' => $user_data['client_ip_address'] ?? '',
          'client_user_agent' => $user_data['client_user_agent'] ?? ''
      ];
      if (!empty($user_data['client_ip_address'])) $tt_user['ip'] = $user_data['client_ip_address'];
      if (!empty($user_data['client_user_agent'])) $tt_user['user_agent'] = $user_data['client_user_agent'];
      if (isset($_COOKIE['_ttp'])) {
          $tt_user['ttp'] = sanitize_text_field(wp_unslash($_COOKIE['_ttp']));
      }

      if (!empty($user_data['em'])) $tt_user['email'] = $user_data['em'];
      if (!empty($user_data['ph'])) $tt_user['phone_number'] = $user_data['ph'];
      if (!empty($user_data['external_id'])) $tt_user['external_id'] = $user_data['external_id'];
      
      // Additional Advanced Matching
      if (!empty($user_data['fn'])) {
          $tt_user['first_name'] = $user_data['fn'];
          $tt_user['fn'] = $user_data['fn'];
      }
      if (!empty($user_data['ln'])) {
          $tt_user['last_name'] = $user_data['ln'];
          $tt_user['ln'] = $user_data['ln'];
      }
      if (!empty($user_data['ct'])) {
          $tt_user['city'] = $user_data['ct'];
          $tt_user['ct'] = $user_data['ct'];
      }
      if (!empty($user_data['st'])) {
          $tt_user['state'] = $user_data['st'];
          $tt_user['st'] = $user_data['st'];
      }
      if (!empty($user_data['zp'])) {
          $tt_user['zip_code'] = $user_data['zp'];
          $tt_user['zp'] = $user_data['zp'];
      }
      if (!empty($user_data['country'])) {
          $tt_user['country'] = $user_data['country'];
      }

      $ttData = [];
      if (isset($data['currency'])) $ttData['currency'] = $data['currency'];
      if (isset($data['value'])) $ttData['value'] = $data['value'];
      if (isset($data['search_term'])) $ttData['query'] = $data['search_term'];

      if (!empty($data['contents']) && is_array($data['contents'])) {
          $ttData['content_type'] = $data['content_type'] ?? 'product';
          $ttData['contents'] = [];
          foreach ($data['contents'] as $item) {
              $id = $item['content_id'] ?? $item['id'] ?? $item['item_id'] ?? $item['product_id'] ?? $item['productId'] ?? '';
              if (!empty($id)) {
                  $ttData['contents'][] = [
                      'content_id' => (string)$id,
                      'content_name' => $item['content_name'] ?? $item['name'] ?? '',
                      'price' => $item['price'] ?? 0,
                      'quantity' => $item['quantity'] ?? 1
                  ];
              }
          }
          if (count($ttData['contents']) === 1) {
              $ttData['content_id'] = $ttData['contents'][0]['content_id'];
              $ttData['content_name'] = $ttData['contents'][0]['content_name'];
              $ttData['price'] = $ttData['contents'][0]['price'];
              $ttData['quantity'] = $ttData['contents'][0]['quantity'];
          }
      } elseif (!empty($data['items']) && is_array($data['items'])) {
          $ttData['content_type'] = 'product';
          if (count($data['items']) === 1) {
               $id = $data['items'][0]['item_id'] ?? $data['items'][0]['id'] ?? $data['items'][0]['product_id'] ?? $data['items'][0]['productId'] ?? '';
               $ttData['content_id'] = (string)$id;
               $ttData['content_name'] = isset($data['items'][0]['item_name']) ? $data['items'][0]['item_name'] : '';
               if (isset($data['items'][0]['item_category'])) $ttData['content_category'] = $data['items'][0]['item_category'];
               $ttData['price'] = isset($data['items'][0]['price']) ? $data['items'][0]['price'] : 0;
               $ttData['quantity'] = isset($data['items'][0]['quantity']) ? $data['items'][0]['quantity'] : 1;
               $ttData['contents'] = [[
                   'content_id' => (string)$id,
                   'content_name' => $ttData['content_name'],
                   'price' => $ttData['price'],
                   'quantity' => $ttData['quantity']
               ]];
          } else {
               $ttData['contents'] = [];
               foreach ($data['items'] as $item) {
                   $id = $item['item_id'] ?? $item['id'] ?? $item['product_id'] ?? $item['productId'] ?? '';
                   if (!empty($id)) {
                       $ttData['contents'][] = [
                           'content_id' => (string)$id,
                           'content_name' => isset($item['item_name']) ? $item['item_name'] : '',
                           'price' => isset($item['price']) ? $item['price'] : 0,
                           'quantity' => isset($item['quantity']) ? $item['quantity'] : 1
                       ];
                   }
               }
          }
      } elseif (!empty($data['content_ids']) && is_array($data['content_ids'])) {
          $ttData['content_type'] = isset($data['content_type']) ? $data['content_type'] : 'product';
          if (count($data['content_ids']) === 1) {
              $ttData['content_id'] = (string)$data['content_ids'][0];
              if (isset($data['content_name'])) $ttData['content_name'] = $data['content_name'];
              $ttData['price'] = isset($data['value']) ? (float)$data['value'] : 0;
              $ttData['quantity'] = isset($data['num_items']) ? (int)$data['num_items'] : 1;
              $ttData['contents'] = [[
                  'content_id' => (string)$data['content_ids'][0],
                  'quantity' => $ttData['quantity'],
                  'price' => $ttData['price']
              ]];
          } else {
              $ttData['contents'] = [];
              foreach ($data['content_ids'] as $id) {
                  $ttData['contents'][] = [
                      'content_id' => (string)$id,
                      'quantity' => 1,
                      'price' => isset($data['value']) ? (float)$data['value'] : 0
                  ];
              }
          }
      } else {
          // Fallback if data already formatted in tiktok tracker (for standard themes)
          $ttData = array_merge($ttData, $data);
      }

      // WooCommerce Dynamic Product Enrichment Fallback (fills in missing names/prices)
      if (function_exists('wc_get_product')) {
          if (!empty($ttData['content_id']) && (empty($ttData['content_name']) || floatval($ttData['price'] ?? 0) === 0.0)) {
              $product = wc_get_product($ttData['content_id']);
              if ($product) {
                  if (empty($ttData['content_name'])) {
                      $ttData['content_name'] = $product->get_name();
                  }
                  if (floatval($ttData['price'] ?? 0) === 0.0) {
                      $ttData['price'] = (float)$product->get_price();
                      if (isset($ttData['value']) && floatval($ttData['value']) === 0.0) {
                          $ttData['value'] = $ttData['price'] * ($ttData['quantity'] ?? 1);
                      }
                  }
              }
          }
          if (!empty($ttData['contents']) && is_array($ttData['contents'])) {
              foreach ($ttData['contents'] as $key => $item) {
                  $item_id = $item['content_id'] ?? '';
                  if (!empty($item_id) && (empty($item['content_name']) || floatval($item['price'] ?? 0) === 0.0)) {
                      $product = wc_get_product($item_id);
                      if ($product) {
                          if (empty($item['content_name'])) {
                              $ttData['contents'][$key]['content_name'] = $product->get_name();
                          }
                          if (floatval($item['price'] ?? 0) === 0.0) {
                              $ttData['contents'][$key]['price'] = (float)$product->get_price();
                          }
                      }
                  }
              }
          }
      }

      // Ensure content_ids (array) is always present if content_id is set
      if (!empty($ttData['content_id']) && empty($ttData['content_ids'])) {
          $ttData['content_ids'] = [(string)$ttData['content_id']];
      }
      if (empty($ttData['content_id']) && !empty($ttData['content_ids']) && is_array($ttData['content_ids'])) {
          $ttData['content_id'] = (string)$ttData['content_ids'][0];
      }

      // Final fallback to prevent missing content_id/contents for product-related events
      $is_product_event = in_array($tt_event_name, ['ViewContent', 'AddToCart', 'AddToWishlist', 'InitiateCheckout', 'Purchase'], true);
      if ($is_product_event && empty($ttData['content_id']) && empty($ttData['contents'])) {
          $active_product = null;
          if (function_exists('is_product') && is_product()) {
              global $product;
              $active_product = $product;
          } elseif (is_singular('product')) {
              $active_product = wc_get_product(get_the_ID());
          }

          if ($active_product) {
              $pid = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_product_id($active_product);
              $ttData['content_type'] = 'product';
              $ttData['content_id'] = (string)$pid;
              $ttData['content_ids'] = [(string)$pid];
              $ttData['content_name'] = $active_product->get_name();
              $ttData['price'] = (float)$active_product->get_price();
              $ttData['quantity'] = 1;
              $ttData['contents'] = [[
                  'content_id' => (string)$pid,
                  'content_name' => $ttData['content_name'],
                  'price' => $ttData['price'],
                  'quantity' => 1
              ]];
          }
      }
      
      $ip_addr = $user_data['client_ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
      $user_agent = $user_data['client_user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
      if ($ip_addr === '127.0.0.1' || $ip_addr === '::1') {
          $ip_addr = '104.28.254.74';
      }

      $tt_user = [];
      if (!empty($user_data['em'])) $tt_user['email'] = $user_data['em'];
      if (!empty($user_data['ph'])) $tt_user['phone_number'] = $user_data['ph'];
      if (isset($_COOKIE['_ttp'])) {
          $tt_user['ttp'] = sanitize_text_field(wp_unslash($_COOKIE['_ttp']));
      }
      $tt_user['ip'] = $ip_addr;
      $tt_user['user_agent'] = $user_agent;
      
      $batch_event = [
          'event' => $tt_event_name,
          'event_id' => $event_id,
          'event_time' => time(),
          'user' => $tt_user,
          'context' => [
              'page' => ['url' => home_url($_SERVER['REQUEST_URI'] ?? '')],
              'user_agent' => $user_agent,
              'ip' => $ip_addr
          ],
          'properties' => $ttData
      ];

      $tt_payload = [
          'pixel_code' => $pixel_id,
          'event_source' => 'web',
          'batch' => [$batch_event]
      ];
      $tt_request_body_arr = $tt_payload;
      
      $test_code = isset($tt_config['test_code']) ? trim($tt_config['test_code']) : '';
      if (!empty($test_code)) {
          $tt_request_body_arr['test_event_code'] = $test_code;
      }
      
      $tt_request_body = wp_json_encode($tt_request_body_arr);
      
      $response = wp_remote_post('https://business-api.tiktok.com/open_api/v1.3/pixel/track/', [
          'headers' => [
              'Access-Token' => $access_token,
              'Content-Type' => 'application/json'
          ],
          'body' => $tt_request_body,
          'timeout' => 15,
          'blocking' => false,
          'sslverify' => false
      ]);

      // Log TikTok Event
      global $wpdb;
      $table = $wpdb->prefix . 'pixelonwp_event_logs';
      $status = is_wp_error($response) ? 'failed' : 'dispatched';
      $wpdb->insert($table, [
          'event_name' => $event_name,
          'event_id' => $event_id,
          'platform' => 'tiktok',
          'payload' => $tt_request_body,
          'status' => $status,
          'created_at' => current_time('mysql')
      ]);
  }

  public static function get_hashed_user_data($order = null) {
    $user_data = [
       'client_ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
       'client_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    $email = ''; $phone = ''; $external_id = '';
    $fname = ''; $lname = ''; $city = ''; $state = ''; $zip = ''; $country = '';
    
    if ($order) {
        $email = $order->get_billing_email();
        $phone = $order->get_billing_phone();
        $fname = $order->get_billing_first_name();
        $lname = $order->get_billing_last_name();
        $city = $order->get_billing_city();
        $state = $order->get_billing_state();
        $zip = $order->get_billing_postcode();
        $country = $order->get_billing_country();
        $external_id = (string) $order->get_customer_id();
    } else if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $email = $user->user_email;
        $fname = $user->first_name;
        $lname = $user->last_name;
        $external_id = (string) $user->ID;
        $phone = get_user_meta($user->ID, 'billing_phone', true);
        $city = get_user_meta($user->ID, 'billing_city', true);
        $state = get_user_meta($user->ID, 'billing_state', true);
        $zip = get_user_meta($user->ID, 'billing_postcode', true);
        $country = get_user_meta($user->ID, 'billing_country', true);
    }
    
    if (!empty($email)) $user_data['em'] = hash('sha256', trim(strtolower($email)));
    if (!empty($phone)) {
        $formatted_phone = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::format_phone_e164($phone, $country);
        if (!empty($formatted_phone)) {
            // TikTok requirement: Hash digits only, no '+' sign
            $phone_digits = preg_replace('/\D/', '', $formatted_phone);
            $user_data['ph'] = hash('sha256', $phone_digits);
        }
    }
    if (!empty($fname)) $user_data['fn'] = hash('sha256', trim(strtolower($fname)));
    if (!empty($lname)) $user_data['ln'] = hash('sha256', trim(strtolower($lname)));
    if (!empty($city)) $user_data['ct'] = hash('sha256', trim(strtolower($city)));
    if (!empty($state)) $user_data['st'] = hash('sha256', trim(strtolower($state)));
    if (!empty($zip)) $user_data['zp'] = hash('sha256', trim(strtolower($zip)));
    if (!empty($country)) $user_data['country'] = hash('sha256', trim(strtolower($country)));
    
    if (!empty($external_id) && $external_id !== '0') $user_data['external_id'] = hash('sha256', trim($external_id));

    return $user_data;
  }
}
