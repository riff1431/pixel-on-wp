<?php
/**
 * Native Tracker Class (Theme-Wise independent).
 *
 * @package PixelOnWP\Includes\Tracking
 */

namespace PixelOnWP\Includes\Tracking;

if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Native_Tracker {
  
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader) {
    // Standard Page Level Events and eCommerce are handled by Event Controller
    
    // Dynamic Order Status Tracking
    $loader->add_action('woocommerce_order_status_changed', $this, 'track_order_status_change', 10, 4);

    // Registration
    $loader->add_action('user_register', $this, 'track_complete_registration', 10, 1);

    // Generic JS listeners for Form Tracking, etc.
    $loader->add_action('wp_footer', $this, 'inject_generic_js_listeners', 99);
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
      return true; // Default enabled
  }
  private function get_product_identifier($product) {
      if (!$product) return '';
      $setting = get_option('pixelonwp_settings', []);
      $identifier_type = isset($setting['product_identifier']) ? $setting['product_identifier'] : 'id';
      
      if ($identifier_type === 'sku') {
          $sku = $product->get_sku();
          if (!empty($sku)) {
              return (string)$sku;
          }
      }
      return (string)$product->get_id();
  }


  public function track_page_view() {
    $event_id = 'evt_' . wp_generate_uuid4();
    $data = [
      'page_type' => is_front_page() ? 'home' : (is_single() ? 'post' : 'page'),
      'page_location' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '')
    ];
    $this->output_event_script('page_view', $event_id, $data);
  }

  public function track_search() {
    if (is_search()) {
       global $wp_query;
       $event_id = 'evt_' . wp_generate_uuid4();
       $items = [];
       $value = 0;
       
       if ($wp_query->have_posts()) {
           foreach ($wp_query->posts as $post) {
               if (function_exists('wc_get_product')) {
                   $product = wc_get_product($post->ID);
                   if ($product) {
                       $items[] = [
                           'item_id' => $this->get_product_identifier($product),
                           'item_name' => $product->get_name(),
                           'price' => (float)$product->get_price(),
                           'quantity' => 1
                       ];
                       $value += (float)$product->get_price();
                   }
               }
           }
       }
       
       $data = [
         'search_term' => get_search_query(),
         'items' => $items,
         'value' => $value,
         'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD'
       ];
       $this->output_event_script('search', $event_id, $data);
    }
  }

  public function track_view_content() {
    global $product;
    if (!$product) return;
    
    $event_id = 'evt_' . wp_generate_uuid4();
    $data = [
      'value' => (float)$product->get_price(),
      'currency' => get_woocommerce_currency(),
      'items' => [
          [
              'item_id' => $this->get_product_identifier($product),
              'item_name' => $product->get_name(),
              'price' => (float)$product->get_price(),
              'quantity' => 1
          ]
      ]
    ];
    
    $cats = wp_get_post_terms($product->get_id(), 'product_cat');
    if (!empty($cats) && !is_wp_error($cats)) {
      $data['items'][0]['item_category'] = $cats[0]->name;
    }

    $this->output_event_script('view_item', $event_id, $data);
  }

  public function track_view_cart() {
    if (function_exists('is_cart') && is_cart()) {
        $event_id = 'evt_' . wp_generate_uuid4();
        
        $contents = [];
        
        if (function_exists('WC') && WC()->cart) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $contents[] = [
                    'item_id' => $this->get_product_identifier($product),
                    'item_name' => $product->get_name(),
                    'price' => (float)$product->get_price(),
                    'quantity' => $cart_item['quantity']
                ];
            }
        }
        
        $data = [
            'value' => function_exists('WC') && WC()->cart ? (float)WC()->cart->get_total('edit') : 0,
            'currency' => get_woocommerce_currency(),
            'items' => $contents
        ];
        
        $this->output_event_script('view_cart', $event_id, $data);
    }
  }

  public function track_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    $event_id = 'evt_' . wp_generate_uuid4();
    $product = wc_get_product($product_id);
    if (!$product) return;
    
    $data = [
      'value' => (float)$product->get_price(),
      'currency' => get_woocommerce_currency(),
      'items' => [
         [
             'item_id' => $this->get_product_identifier($product),
             'item_name' => $product->get_name(),
             'price' => (float)$product->get_price(),
             'quantity' => $quantity
         ]
      ]
    ];
    
    $this->output_event_script('add_to_cart', $event_id, $data);
    $this->dispatch_server_event('add_to_cart', $event_id, $data);
  }

  public function track_initiate_checkout() {
    if (!function_exists('WC') || !WC()->cart) return;
    $event_id = 'evt_' . wp_generate_uuid4();
    
    $contents = [];
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $contents[] = [
            'item_id' => $this->get_product_identifier($product),
            'item_name' => $product->get_name(),
            'price' => (float)$product->get_price(),
            'quantity' => $cart_item['quantity']
        ];
    }

    $data = [
      'value' => (float)WC()->cart->get_total('edit'),
      'currency' => get_woocommerce_currency(),
      'items' => $contents
    ];
    
    $this->output_event_script('begin_checkout', $event_id, $data);
  }

  public function track_purchase($order_id) {
    $active_events = get_option('PixelOnWP_active_events', []);
    $dynamic_status_enabled = isset($active_events['dynamicstatus']) ? $active_events['dynamicstatus'] : '1';

    if ($dynamic_status_enabled === '1' || $dynamic_status_enabled === 'yes' || $dynamic_status_enabled === true) {
        return;
    }
    
    $order = wc_get_order($order_id);
    if (!$order) return;
    if ($order->get_meta('_wpt_tracked')) return;

    $event_id = 'evt_' . wp_generate_uuid4();
    
    $contents = [];
    foreach ($order->get_items() as $item_id => $item) {
      $product = $item->get_product();
      $pid = $product ? $this->get_product_identifier($product) : (string)$item->get_product_id();
      $contents[] = [
          'item_id' => $pid,
          'item_name' => $item->get_name(),
          'price' => (float)$order->get_item_total($item, false, false),
          'quantity' => $item->get_quantity()
      ];
    }

    $data = [
      'transaction_id' => (string)$order->get_id(),
      'value' => (float)$order->get_total(),
      'currency' => $order->get_currency(),
      'items' => $contents
    ];
    
    $this->output_event_script('purchase', $event_id, $data);
    $this->dispatch_server_event('purchase', $event_id, $data, $order);

    $order->update_meta_data('_wpt_tracked', 'yes');
    $order->save();
  }

  public function track_complete_registration($user_id) {
     $event_id = 'evt_' . wp_generate_uuid4();
     $data = [
       'method' => 'User Registration'
     ];
     // Using session to output on next load or send server-side directly
     $this->dispatch_server_event('sign_up', $event_id, $data);
  }

  public function inject_generic_js_listeners() {
      // Handles generic JS triggers for Wishlist, AddPaymentInfo, Lead/Contact Forms, Downloads
      ?>
      <script>
      (function(){
          const activeEvents = <?php echo wp_json_encode(get_option('PixelOnWP_active_events', [])); ?>;
          const formTrackingData = <?php echo wp_json_encode(get_option('PixelOnWP_form_tracking', [
              'wpforms' => '1', 'contact_form_7' => '1', 'gravity_forms' => '1', 'fluent_forms' => '1',
              'formidable_forms' => '1', 'ninja_forms' => '1', 'forminator' => '1', 'jetformbuilder' => '1',
              'metform' => '1', 'kali_forms' => '1', 'optinmonster' => '1', 'bloom' => '1',
              'thrive_leads' => '1', 'mailpoet' => '1', 'hustle' => '1', 'icegram' => '1', 'sumo' => '1', 'elementor' => '1'
          ])); ?>;
          function isEventEnabled(evName) {
              let key = evName.toLowerCase();
              return activeEvents[key] !== '0';
          }

          const storeCallingCode = <?php 
              $default_country = get_option('woocommerce_default_country');
              $country_code = '';
              if (!empty($default_country)) {
                  $parts = explode(':', $default_country);
                  $country_code = $parts[0];
              }
              $calling_code = '';
              if (!empty($country_code)) {
                  if (class_exists('WC_Countries')) {
                      $wc_countries = new \WC_Countries();
                      $calling_codes = $wc_countries->get_country_calling_code($country_code);
                      if (!empty($calling_codes)) {
                          $calling_code = is_array($calling_codes) ? $calling_codes[0] : $calling_codes;
                      }
                  }
                  if (empty($calling_code)) {
                      $common = [
                          'BD' => '880', 'US' => '1', 'GB' => '44', 'AE' => '971', 'IN' => '91', 
                          'AU' => '61', 'CA' => '1', 'MY' => '60', 'SG' => '65', 'PK' => '92',
                          'SA' => '966', 'ZA' => '27'
                      ];
                      $calling_code = $common[strtoupper($country_code)] ?? '';
                  }
              }
              echo json_encode(preg_replace('/\D/', '', $calling_code));
          ?>;

          function formatPhoneE164(phone) {
              if (!phone) return '';
              let clean = phone.replace(/[^0-9+]/g, '');
              if (!clean) return '';
              if (clean.startsWith('+')) return clean;

              let countrySelect = document.querySelector('select[name="billing_country"], #billing_country');
              let callingCode = '';
              if (countrySelect && countrySelect.value) {
                  const commonCodes = {
                      'BD': '880', 'US': '1', 'GB': '44', 'AE': '971', 'IN': '91', 
                      'AU': '61', 'CA': '1', 'MY': '60', 'SG': '65', 'PK': '92',
                      'SA': '966', 'ZA': '27'
                  };
                  callingCode = commonCodes[countrySelect.value.toUpperCase()] || '';
              }
              
              if (!callingCode) {
                  callingCode = storeCallingCode || '';
              }

              if (callingCode) {
                  if (clean.startsWith('0')) {
                      clean = clean.substring(1);
                  }
                  if (!clean.startsWith(callingCode)) {
                      clean = callingCode + clean;
                  }
              }
              return '+' + clean;
          }

          async function hashSHA256(message) {
              if (!message) return '';
              try {
                  const msgBuffer = new TextEncoder().encode(message.toLowerCase().trim());
                  const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
                  const hashArray = Array.from(new Uint8Array(hashBuffer));
                  return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
              } catch(e) { return ''; }
          }

          function extractFormData(form) {
              let data = { email: '', phone: '', first_name: '', last_name: '' };
              if (!form || typeof form.querySelectorAll !== 'function') form = document;
              try {
                  const inputs = form.querySelectorAll('input, select, textarea');
                  inputs.forEach(input => {
                      let name = (input.name || input.id || '').toLowerCase();
                      let val = input.value.trim();
                      if (!val) return;
                      if (name.includes('email') || input.type === 'email') data.email = val;
                      else if (name.includes('phone') || name.includes('tel')) data.phone = val;
                      else if (name.includes('first') && name.includes('name')) data.first_name = val;
                      else if (name.includes('last') && name.includes('name')) data.last_name = val;
                      else if (name.includes('name') && !data.first_name) {
                          let parts = val.split(' ');
                          data.first_name = parts[0];
                          if (parts.length > 1) data.last_name = parts.slice(1).join(' ');
                      }
                  });
              } catch(e) {}
              return data;
          }

          async function sendWptEvent(eventName, data, rawUserData = null) {
              const eventId = 'evt_' + Math.random().toString(36).substr(2, 9);
              data = data || {};
              
              let dlPayload = {
                  event: eventName,
                  event_id: eventId,
                  ecommerce: data
              };
              
              if (rawUserData && (rawUserData.email || rawUserData.phone)) {
                  dlPayload.user_data = {
                      email_address: rawUserData.email,
                      phone_number: rawUserData.phone,
                      address: {
                          first_name: rawUserData.first_name,
                          last_name: rawUserData.last_name
                      }
                  };
              }
              
              window.dataLayer = window.dataLayer || [];
              window.dataLayer.push(dlPayload);

              if (!isEventEnabled(eventName)) return;
              function mapData(ev, d, platform) {
                  d = d || {};

                  const isFb = platform === 'fb';
                  const mapFb = { 
                      'AddToCart': 'AddToCart', 'add_to_cart': 'AddToCart',
                      'InitiateCheckout': 'InitiateCheckout', 'begin_checkout': 'InitiateCheckout',
                      'Purchase': 'Purchase', 'purchase': 'Purchase',
                      'Lead': 'Lead', 'generate_lead': 'Lead',
                      'ViewContent': 'ViewContent', 'view_item': 'ViewContent',
                      'Search': 'Search', 'search': 'Search',
                      'Contact': 'Contact', 'contact': 'Contact'
                  };
                  const mapTt = { 
                      'AddToCart': 'AddToCart', 'add_to_cart': 'AddToCart',
                      'InitiateCheckout': 'InitiateCheckout', 'begin_checkout': 'InitiateCheckout',
                      'Purchase': 'CompletePayment', 'purchase': 'CompletePayment',
                      'Lead': 'SubmitForm', 'generate_lead': 'SubmitForm',
                      'ViewContent': 'ViewContent', 'view_item': 'ViewContent',
                      'Search': 'Search', 'search': 'Search',
                      'Contact': 'Contact', 'contact': 'Contact'
                  };
                  
                  const targetEvent = isFb ? (mapFb[ev] || ev) : (mapTt[ev] || ev);
                  let targetData = {};
                  if (d.currency) targetData.currency = d.currency;
                  if (d.value !== undefined) targetData.value = d.value;
                  if (d.transaction_id) targetData.order_id = d.transaction_id;
                  if (d.search_term) targetData[isFb ? 'search_string' : 'query'] = d.search_term;
                  
                  if (d.items && d.items.length > 0) {
                      targetData.content_type = 'product';
                      if (isFb) {
                          targetData.content_ids = d.items.map(i => String(i.item_id || i.id || i.product_id || i.productId));
                          targetData.contents = d.items.map(i => ({id: String(i.item_id || i.id || i.product_id || i.productId), quantity: i.quantity || 1, item_price: i.price || i.item_price || 0}));
                          if (d.items.length === 1) {
                              targetData.content_name = d.items[0].item_name || d.items[0].name || d.items[0].product_name;
                              targetData.content_category = d.items[0].item_category || d.items[0].category;
                          }
                          targetData.num_items = targetData.contents.reduce((sum, item) => sum + item.quantity, 0);
                      } else {
                          let ttData = {};
                          ttData.content_type = 'product';
                          ttData.contents = d.items.map(i => ({
                              content_id: String(i.item_id || i.id || i.product_id || i.productId),
                              content_name: i.item_name || i.name || i.product_name,
                              price: i.price || i.item_price || 0,
                              quantity: i.quantity || 1
                          }));
                          if (d.items.length === 1) {
                              const itm = ttData.contents[0];
                              ttData.content_id = itm.content_id;
                              ttData.content_ids = [itm.content_id];
                              ttData.content_name = itm.content_name;
                              ttData.price = itm.price;
                              ttData.quantity = itm.quantity;
                              if (d.items[0].item_category || d.items[0].category) {
                                  ttData.content_category = d.items[0].item_category || d.items[0].category;
                              }
                          } else {
                              ttData.content_ids = ttData.contents.map(itm => itm.content_id);
                          }
                          targetData = Object.assign({}, targetData, ttData);
                      }
                  }
                  return { ev: targetEvent, data: targetData };
              }

              // Hash user data for server-side & pixels
              let hashedUserData = {};
              if (rawUserData && (rawUserData.email || rawUserData.phone)) {
                  if (rawUserData.email) hashedUserData.em = await hashSHA256(rawUserData.email);
                  if (rawUserData.phone) {
                      hashedUserData.ph = await hashSHA256(formatPhoneE164(rawUserData.phone));
                  }
                  if (rawUserData.first_name) hashedUserData.fn = await hashSHA256(rawUserData.first_name);
                  if (rawUserData.last_name) hashedUserData.ln = await hashSHA256(rawUserData.last_name);
              }

              // Trigger hybrid collect endpoint
              fetch('<?php echo esc_url(rest_url(ltrim(str_replace('wp-json/', '', get_option('PixelOnWP_custom_route', 'pixelonwp/v1/collect')), '/'))); ?>', {
                  method: 'POST',
                  headers: {'Content-Type': 'application/json'},
                  body: JSON.stringify({
                      event: eventName,
                      event_id: eventId,
                      custom_data: data,
                      user_data: Object.keys(hashedUserData).length > 0 ? hashedUserData : undefined
                  })
              }).catch(e => console.error(e));

              // Client side output if available
              if (typeof fbq !== 'undefined') {
                  let mapped = mapData(eventName, data, 'fb');
                  if (Object.keys(hashedUserData).length > 0) {
                      // Push to fbq as advanced matching logic would handle if needed, CAPI will process it either way.
                      // Advanced matching is usually init, but we can set it.
                      // Note: We avoid manual fbq('set') here to prevent global override if unintended, 
                      // but CAPI guarantees tracking.
                  }
                  fbq('track', mapped.ev, mapped.data, {eventID: eventId});
              }
               if (typeof ttq !== 'undefined') {
                  let mapped = mapData(eventName, data, 'tt');
                  if (Object.keys(hashedUserData).length > 0) {
                      // TikTok requires phone number hashed with digits only, no + sign
                      let rawPhone = rawUserData.phone ? formatPhoneE164(rawUserData.phone).replace(/\D/g, '') : '';
                      let ttHashedPhone = rawPhone ? await hashSHA256(rawPhone) : '';
                      ttq.identify({
                          email: hashedUserData.em || '',
                          phone_number: ttHashedPhone || hashedUserData.ph || ''
                      });
                  }
                  if (mapped.ev === 'Pageview') {
                      ttq.page();
                  } else {
                      ttq.track(mapped.ev, mapped.data, {event_id: eventId});
                  }
              }

              // Trigger gtag/Google Ads
              if (typeof gtag !== 'undefined') {
                  const googleConfig = <?php echo wp_json_encode(get_option('PixelOnWP_google_config', [])); ?>;
                  const convId = googleConfig.conversion_id ? googleConfig.conversion_id.trim() : '';
                  if (convId) {
                      const gEvents = googleConfig.events || [];
                      let label = '';
                      const normalizedEvent = eventName.replace(/_/g, '').toLowerCase();
                      for (let ev of gEvents) {
                          if (ev.name && ev.name.replace(/_/g, '').toLowerCase() === normalizedEvent && ev.label) {
                              label = ev.label.trim();
                              break;
                          }
                      }
                      if (!label && (normalizedEvent === 'purchase' || normalizedEvent === 'completepurchase') && googleConfig.conversion_label) {
                          label = googleConfig.conversion_label.trim();
                      }
                      
                      let gData = Object.assign({}, data);
                      if (label) {
                          gData.send_to = convId + '/' + label;
                      } else {
                          gData.send_to = convId;
                      }
                      
                      let gEventName = eventName;
                      if (normalizedEvent === 'addtocart') gEventName = 'add_to_cart';
                      else if (normalizedEvent === 'viewcontent' || normalizedEvent === 'viewitem') gEventName = 'view_item';
                      else if (normalizedEvent === 'initiatecheckout' || normalizedEvent === 'begincheckout') gEventName = 'begin_checkout';
                      else if (normalizedEvent === 'addpaymentinfo') gEventName = 'add_payment_info';
                      else if (normalizedEvent === 'purchase' || normalizedEvent === 'completepurchase') gEventName = 'purchase';
                      else if (normalizedEvent === 'lead' || normalizedEvent === 'submitform' || normalizedEvent === 'generatelead') gEventName = 'generate_lead';
                      else if (normalizedEvent === 'completeregistration' || normalizedEvent === 'signup') gEventName = 'sign_up';
                      else if (normalizedEvent === 'search') gEventName = 'search';
                      else if (normalizedEvent === 'contact') gEventName = 'contact';
                      else if (normalizedEvent === 'schedule') gEventName = 'schedule';
                      
                      gtag('event', gEventName, gData);
                  }
              }

              // Trigger Pinterest Tag if available
              if (typeof window.pintrk !== 'undefined') {
                  const normalizedPin = eventName.replace(/_/g, '').toLowerCase();
                  const pinMap = {
                      'addtocart': 'addtocart', 'add_to_cart': 'addtocart',
                      'begincheckout': 'initiatecheckout', 'initiatecheckout': 'initiatecheckout',
                      'purchase': 'checkout', 'completepurchase': 'checkout',
                      'generatelead': 'lead', 'lead': 'lead', 'submitform': 'lead',
                      'signup': 'signup', 'completeregistration': 'signup',
                      'search': 'search', 'contact': 'lead', 'schedule': 'lead',
                      'addpaymentinfo': 'pagevisit', 'addtowishlist': 'pagevisit',
                      'viewitem': 'pagevisit', 'viewcontent': 'pagevisit'
                  };
                  const pinEvt = pinMap[normalizedPin] || 'custom';
                  // Skip generic page_view only (base code fires it)
                  if (normalizedPin !== 'pageview' && normalizedPin !== 'page_view') {
                      let pinData = { event_id: eventId };
                      if (data.value !== undefined) pinData.value = parseFloat(data.value);
                      if (data.currency) pinData.currency = String(data.currency);
                      if ((pinEvt === 'checkout' || pinEvt === 'addtocart') && data.items && data.items.length) {
                          pinData.order_quantity = data.items.reduce(function(s, i) { return s + (i.quantity || 1); }, 0);
                          pinData.line_items = data.items.map(function(i) {
                              return { product_name: i.item_name || i.name || '', product_id: String(i.item_id || i.id || ''), product_price: parseFloat(i.price || 0), product_quantity: parseInt(i.quantity || 1, 10) };
                          });
                      }
                      if (pinEvt === 'pagevisit' && data.items && data.items.length) {
                          pinData.line_items = data.items.map(function(i) {
                              return { product_name: i.item_name || i.name || '', product_id: String(i.item_id || i.id || ''), product_price: parseFloat(i.price || 0), product_quantity: parseInt(i.quantity || 1, 10) };
                          });
                      }
                      if (pinEvt === 'search') pinData.search_query = String(data.search_term || '');
                      if (pinEvt === 'lead' || pinEvt === 'signup') pinData.lead_type = String(data.form_name || data.method || eventName);
                      window.pintrk('track', pinEvt, pinData);
                  }
              }
          }

          document.addEventListener('submit', function(e) {
              if (e.target.tagName === 'FORM') {
                  if (e.target.classList.contains('cart')) {
                      // WooCommerce Single Product Add to Cart Form
                      let viewContentData = {};
                      if (window.dataLayer) {
                          let vc = window.dataLayer.find(item => item.event === 'view_item');
                          if (vc && vc.ecommerce) viewContentData = vc.ecommerce;
                      }
                      sendWptEvent('AddToCart', viewContentData);
                      return;
                  }
                  
                  // Form Plugin Specific Logic for plugins that don't have good success events
                  let formClass = e.target.className || '';
                  if (typeof formClass !== 'string') {
                      formClass = formClass.toString();
                  }
                  let formId = e.target.id || '';
                  let isTracked = false;
                  let formName = '';
                  
                  const isEnabled = (key) => formTrackingData[key] === '1' || formTrackingData[key] === true;

                  if (isEnabled('optinmonster') && (formClass.includes('om-campaign') || formId.includes('om-'))) { isTracked = true; formName = 'OptinMonster'; }
                  else if (isEnabled('bloom') && formClass.includes('et_bloom_form_content')) { isTracked = true; formName = 'Bloom'; }
                  else if (isEnabled('thrive_leads') && formClass.includes('thrv_lead_generation')) { isTracked = true; formName = 'Thrive Leads'; }
                  else if (isEnabled('mailpoet') && formClass.includes('mailpoet_form')) { isTracked = true; formName = 'MailPoet'; }
                  else if (isEnabled('hustle') && formClass.includes('hustle-form')) { isTracked = true; formName = 'Hustle'; }
                  else if (isEnabled('icegram') && formClass.includes('ig_form_container')) { isTracked = true; formName = 'Icegram'; }
                  else if (isEnabled('sumo') && (formClass.includes('sumome-react-wysiwyg-component') || formClass.includes('sumo-form-wrapper'))) { isTracked = true; formName = 'Sumo'; }

                  if (isTracked) {
                      let extractedData = extractFormData(e.target);
                      sendWptEvent('generate_lead', { method: 'Form Submission', form_name: formName }, extractedData);
                  }
              }
          });

          // Accurate Form Success Listeners
          const isEnabled = (key) => formTrackingData[key] === '1' || formTrackingData[key] === true;

          function handleFormSuccess(formName, formElement) {
              let extractedData = formElement ? extractFormData(formElement) : null;
              sendWptEvent('generate_lead', { method: 'Form Submission', form_name: formName }, extractedData);
          }

          // Contact Form 7 Native Event
          document.addEventListener('wpcf7mailsent', function(e) {
              if (isEnabled('contact_form_7')) handleFormSuccess('Contact Form 7', e.target);
          }, false);
          document.addEventListener('wpcf7submit', function(e) {
              // Fallback just in case
          }, false);

          // Wait for DOM to attach jQuery events
          document.addEventListener('DOMContentLoaded', function() {
              if (typeof jQuery !== 'undefined') {
                  // WPForms
                  jQuery(document).on('wpformsAjaxSubmitSuccess wpforms_form_success wpforms_submitted', function(e) {
                      if (isEnabled('wpforms')) handleFormSuccess('WPForms', e.target);
                  });
                  // Gravity Forms
                  jQuery(document).on('gform_confirmation_loaded gform_submission', function(e, formId) {
                      if (isEnabled('gravity_forms')) {
                          let form = document.querySelector('#gform_' + formId);
                          handleFormSuccess('Gravity Forms', form);
                      }
                  });
                  // Fluent Forms
                  jQuery(document).on('fluentform_submission_success fluentform_submitted', function(e) {
                      if (isEnabled('fluent_forms')) handleFormSuccess('Fluent Forms', e.target);
                  });
                  // Formidable Forms
                  jQuery(document).on('frm_form_submitted formidable_submitted', function(e, form) {
                      if (isEnabled('formidable_forms')) handleFormSuccess('Formidable Forms', form || e.target);
                  });
                  // Ninja Forms
                  jQuery(document).on('nfFormSubmitResponse nfFormSubmit ninja_forms_submit', function(e) {
                      if (isEnabled('ninja_forms')) handleFormSuccess('Ninja Forms', e.target);
                  });
                  // Forminator
                  jQuery(document).on('forminator_form_submit forminator:form:submit', function(e) {
                      if (isEnabled('forminator')) handleFormSuccess('Forminator', e.target);
                  });
                  // JetFormBuilder
                  jQuery(document).on('jet-form-builder/success jetformbuilder_submitted', function(e) {
                      if (isEnabled('jetformbuilder')) handleFormSuccess('JetFormBuilder', e.target);
                  });
                  // MetForm
                  jQuery(document).on('metform_form_submit metform_submitted', function(e) {
                      if (isEnabled('metform')) handleFormSuccess('MetForm', e.target);
                  });
                  // Kali Forms
                  jQuery(document).on('kaliforms_form_submitted kaliforms_submit', function(e) {
                      if (isEnabled('kali_forms')) handleFormSuccess('Kali Forms', e.target);
                  });
                  // Elementor Forms
                  jQuery(document).on('submit_success', function(e) {
                      if (isEnabled('elementor') && e.target && e.target.classList && e.target.classList.contains('elementor-form')) {
                          handleFormSuccess('Elementor Forms', e.target);
                      }
                  });
              } else {
                  // Native Fallbacks for JS-driven forms
                  const formEvents = [
                      { ev: 'wpforms_submitted', id: 'wpforms', name: 'WPForms' },
                      { ev: 'wpforms_form_success', id: 'wpforms', name: 'WPForms' },
                      { ev: 'gform_confirmation_loaded', id: 'gravity_forms', name: 'Gravity Forms' },
                      { ev: 'gform_submission', id: 'gravity_forms', name: 'Gravity Forms' },
                      { ev: 'fluentform_submission_success', id: 'fluent_forms', name: 'Fluent Forms' },
                      { ev: 'fluentform_submitted', id: 'fluent_forms', name: 'Fluent Forms' },
                      { ev: 'frm_form_submitted', id: 'formidable_forms', name: 'Formidable Forms' },
                      { ev: 'formidable_submitted', id: 'formidable_forms', name: 'Formidable Forms' },
                      { ev: 'nfFormSubmitResponse', id: 'ninja_forms', name: 'Ninja Forms' },
                      { ev: 'nfFormSubmit', id: 'ninja_forms', name: 'Ninja Forms' },
                      { ev: 'ninja_forms_submit', id: 'ninja_forms', name: 'Ninja Forms' },
                      { ev: 'forminator_form_submit', id: 'forminator', name: 'Forminator' },
                      { ev: 'forminator:form:submit', id: 'forminator', name: 'Forminator' },
                      { ev: 'jet-form-builder/success', id: 'jetformbuilder', name: 'JetFormBuilder' },
                      { ev: 'jetformbuilder_submitted', id: 'jetformbuilder', name: 'JetFormBuilder' },
                      { ev: 'metform_form_submit', id: 'metform', name: 'MetForm' },
                      { ev: 'metform_submitted', id: 'metform', name: 'MetForm' },
                      { ev: 'kaliforms_form_submitted', id: 'kali_forms', name: 'Kali Forms' },
                      { ev: 'kaliforms_submit', id: 'kali_forms', name: 'Kali Forms' },
                      { ev: 'submit_success', id: 'elementor', name: 'Elementor Forms' }
                  ];
                  formEvents.forEach(fe => {
                      document.addEventListener(fe.ev, function(e) {
                          if (isEnabled(fe.id)) {
                              if (fe.id === 'elementor' && (!e.target || !e.target.classList || !e.target.classList.contains('elementor-form'))) return;
                              handleFormSuccess(fe.name, e.target);
                          }
                      }, false);
                  });
              }
          });

          // AJAX and Single Page Add To Cart clicks are now handled exclusively via PHP and datalayer-listener.js to ensure accurate value/currency.

          // AddPaymentInfo hook on WooCommerce checkout place order button
          const placeOrderBtn = document.querySelector('#place_order');
          if (placeOrderBtn) {
              placeOrderBtn.addEventListener('click', function() {
                  let checkoutData = { payment_type: 'Checkout Step' };
                  if (window.dataLayer) {
                      let ic = window.dataLayer.find(item => item.event === 'begin_checkout');
                      if (ic && ic.ecommerce) checkoutData = ic.ecommerce;
                  }
                  sendWptEvent('add_payment_info', checkoutData);
              });
          }

          // Wishlist common intercept (YITH and generic .add_to_wishlist)
          document.addEventListener('click', function(e) {
              let target = e.target.closest('.add_to_wishlist, .yith-wcwl-add-button');
              if (target) {
                  let pid = target.getAttribute('data-product-id') || target.getAttribute('data-product_id');
                  let items = pid ? [{ item_id: pid }] : [];
                  sendWptEvent('add_to_wishlist', { items: items });
              }
              
              // Downloads
              if (e.target.tagName === 'A' && e.target.hasAttribute('download')) {
                  sendWptEvent('file_download', { file_name: e.target.getAttribute('download') || 'File Download' });
              }
          });
      })();
      </script>
      <?php
  }

  public function track_order_status_change($order_id, $old_status, $new_status, $order) {
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    $reddit = get_option('PixelOnWP_reddit_config', []);
    $reddit_ds = in_array('reddit', $platforms, true) && (!isset($reddit['events']['DynamicStatus']) || filter_var($reddit['events']['DynamicStatus'], FILTER_VALIDATE_BOOLEAN));

    if (!$this->is_event_enabled('DynamicStatus') && !$reddit_ds) {
        return;
    }

    $event_name = '';
    switch ($new_status) {
        case 'pending':
            $event_name = 'PendingOrder';
            break;
        case 'processing':
            $event_name = 'ProcessingOrder';
            break;
        case 'on-hold':
            $event_name = 'OnHoldOrder';
            break;
        case 'completed':
            $event_name = 'Purchase';
            break;
        case 'cancelled':
            $event_name = 'CancelledOrder';
            break;
        case 'refunded':
            $event_name = 'Refund';
            break;
        default:
            // Do not track other random custom statuses by default to avoid noise
            return;
    }
    
    // To prevent duplicate 'completed' / Purchase if it was already tracked via frontend
    if ($new_status === 'completed' && $order->get_meta('_wpt_tracked')) {
        return;
    }

    $event_id = 'evt_' . wp_generate_uuid4();
    
    $contents = [];
    foreach ($order->get_items() as $item_id => $item) {
      $product = $item->get_product();
      $pid = $product ? $this->get_product_identifier($product) : (string)$item->get_product_id();
      
      // GA4 structure for background hook
      $contents[] = [
          'item_id' => $pid,
          'item_name' => $item->get_name(),
          'price' => (float)$order->get_item_total($item, false, false),
          'quantity' => $item->get_quantity()
      ];
    }

    $data = [
      'transaction_id' => (string)$order->get_id(),
      'value' => (float)$order->get_total(),
      'currency' => $order->get_currency(),
      'items' => $contents
    ];
    
    // Only dispatch to server-side since this is a backend background hook
    $this->dispatch_server_event($event_name, $event_id, $data, $order);
    
    if ($new_status === 'completed') {
        $order->update_meta_data('_wpt_tracked', 'yes');
        $order->save();
    }
  }

  private function dispatch_server_event($event_name, $event_id, $data, $order = null) {
      if (!$this->is_event_enabled($event_name)) {
          return;
      }
      $user_data = self::get_hashed_user_data($order);
      
      // We are in the backend context (Order status change hook). 
      // Do NOT loopback to local REST API (which can fail due to auth/context). 
      // Instead, dispatch directly to the platforms.
      
      $event_data = [
          'event_name' => $event_name,
          'event_id' => $event_id,
          'user_data' => $user_data,
          'custom_data' => $data
      ];

      // TEMPORARY DEBUG: Log the payload
      error_log("DynamicStatus: Dispatching Event [$event_name] to Server APIs. Payload: " . wp_json_encode($event_data));

      // 1. Meta CAPI Dispatch
      $meta_config = get_option('PixelOnWP_meta_config', []);
      $fb_dynamic_status = !isset($meta_config['events']['DynamicStatus']) || filter_var($meta_config['events']['DynamicStatus'], FILTER_VALIDATE_BOOLEAN);
      if ($fb_dynamic_status && class_exists('\\PixelOnWP\\Includes\\Capi\\PixelOnWP_Capi_Dispatcher')) {
          \PixelOnWP\Includes\Capi\PixelOnWP_Capi_Dispatcher::dispatch($event_data);
      }
      
      // 2. TikTok Events API Dispatch
      $platforms = get_option('PixelOnWP_selected_platforms', []);
      if (in_array('tiktok', $platforms, true)) {
          if (class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_TikTok_Tracker')) {
              \PixelOnWP\Includes\Tracking\PixelOnWP_TikTok_Tracker::dispatch_tt_server_event_static($event_name, $event_id, $data, $order, $user_data);
          }
      }      // Pinterest Conversions API Dispatch
      if (in_array('pinterest', $platforms, true)) {
          if (class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Pinterest_Tracker')) {
              \PixelOnWP\Includes\Tracking\PixelOnWP_Pinterest_Tracker::dispatch_pinterest_server_event_static($event_name, $event_id, $data, $order, $user_data);
          }
      }

      // 3. GA4 Server-Side Measurement Protocol Dispatch
      if (in_array('ga4', $platforms, true)) {
          $ga4_event = '';
          if ($event_name === 'Purchase') $ga4_event = 'purchase';
          elseif ($event_name === 'Refund') $ga4_event = 'refund';
          
          if ($ga4_event !== '') {
              do_action('pixelonwp_track_event', $ga4_event, $data, $user_data, $event_id);
          }
      }

  }

  private function output_event_script($event_name, $event_id, $data) {
    // Inject Advanced Event Match Quality Parameters (AEMQ)
    $user_data_hashed = self::get_hashed_user_data();
    $user_data_unhashed = self::get_unhashed_user_data();
    $aemq = array_merge($user_data_hashed, $user_data_unhashed);

    echo "<script>\n";
    echo "window.dataLayer = window.dataLayer || [];\n";
    echo "window.dataLayer.push(" . wp_json_encode([
        'event' => $event_name,
        'event_id' => $event_id,
        'ecommerce' => $data,
        'user_data' => $aemq
    ]) . ");\n";
    echo "</script>\n";

    if (!$this->is_event_enabled($event_name)) {
        return;
    }

    $platforms = get_option('PixelOnWP_selected_platforms', []);
    
    echo "<script>\n";
    echo "(function() {\n";
    echo "    const data = " . wp_json_encode($data) . ";\n";
    echo "    const aemq = " . (empty($aemq) ? '{}' : wp_json_encode($aemq)) . ";\n";
    echo "    const eventId = '" . esc_js($event_id) . "';\n";
    echo "    const ga4Event = '" . esc_js($event_name) . "';\n";
    
    echo "    function mapGA4ToFB(ev, d) {
        d = d || {};
        const map = {
            'page_view': 'PageView', 'view_item': 'ViewContent', 'add_to_cart': 'AddToCart',
            'add_to_wishlist': 'AddToWishlist', 'begin_checkout': 'InitiateCheckout',
            'add_payment_info': 'AddPaymentInfo', 'purchase': 'Purchase',
            'generate_lead': 'Lead', 'contact': 'Contact', 'schedule': 'Schedule',
            'sign_up': 'CompleteRegistration', 'search': 'Search',
            'customize_product': 'CustomizeProduct', 'donate': 'Donate',
            'find_location': 'FindLocation', 'start_trial': 'StartTrial',
            'submit_application': 'SubmitApplication', 'subscribe': 'Subscribe'
        };
        const fbEvent = map[ev] || ev;
        let fbData = {};
        
        let content_ids = [];
        let contents = [];
        let num_items = 0;
        
        if (d.items && Array.isArray(d.items)) {
            d.items.forEach(item => {
                const id = String(item.item_id || item.id || item.product_id || item.productId || '');
                if (id) {
                    content_ids.push(id);
                    const qty = parseInt(item.quantity || 1, 10);
                    const price = parseFloat(item.price || 0);
                    contents.push({ id: id, quantity: qty, item_price: price });
                    num_items += qty;
                }
            });
        } else if (d.content_ids && Array.isArray(d.content_ids)) {
            d.content_ids.forEach(id => {
                content_ids.push(String(id));
                contents.push({ id: String(id), quantity: 1 });
                num_items += 1;
            });
        }

        switch (fbEvent) {
            case 'AddPaymentInfo':
                if (d.value !== undefined) fbData.value = parseFloat(d.value);
                if (d.currency) fbData.currency = String(d.currency);
                if (content_ids.length > 0) {
                    fbData.content_ids = content_ids;
                    fbData.content_type = 'product';
                    fbData.contents = contents;
                }
                break;
            case 'AddToCart':
            case 'AddToWishlist':
            case 'ViewContent':
                if (d.value !== undefined) fbData.value = parseFloat(d.value);
                if (d.currency) fbData.currency = String(d.currency);
                if (content_ids.length > 0) {
                    fbData.content_ids = content_ids;
                    fbData.content_type = 'product';
                    fbData.contents = contents;
                }
                if (d.items && Array.isArray(d.items) && d.items.length === 1) {
                    fbData.content_name = String(d.items[0].item_name || d.items[0].name || '');
                    if (d.items[0].item_category) fbData.content_category = String(d.items[0].item_category);
                } else if (d.content_name) {
                    fbData.content_name = String(d.content_name);
                    if (d.content_category) fbData.content_category = String(d.content_category);
                }
                if (fbEvent === 'AddToCart') {
                    fbData.num_items = num_items;
                }
                break;
            case 'CompleteRegistration':
                if (d.value !== undefined) fbData.value = parseFloat(d.value);
                if (d.currency) fbData.currency = String(d.currency);
                if (d.content_name) fbData.content_name = String(d.content_name);
                if (d.status) fbData.status = String(d.status);
                break;
            case 'Contact':
            case 'FindLocation':
            case 'Schedule':
            case 'SubmitApplication':
                if (d.content_category) fbData.content_category = String(d.content_category);
                if (d.content_name) fbData.content_name = String(d.content_name);
                break;
            case 'CustomizeProduct':
                if (content_ids.length > 0) {
                    fbData.content_ids = content_ids;
                    fbData.content_type = 'product';
                    fbData.contents = contents;
                }
                break;
            case 'Donate':
                if (d.value !== undefined) fbData.value = parseFloat(d.value);
                if (d.currency) fbData.currency = String(d.currency);
                if (d.content_name) fbData.content_name = String(d.content_name);
                break;
            case 'InitiateCheckout':
                if (d.value !== undefined) fbData.value = parseFloat(d.value);
                if (d.currency) fbData.currency = String(d.currency);
                if (content_ids.length > 0) {
                    fbData.content_ids = content_ids;
                    fbData.content_type = 'product';
                    fbData.contents = contents;
                }
                fbData.num_items = num_items;
                break;
            case 'Lead':
                if (d.value !== undefined) fbData.value = parseFloat(d.value);
                if (d.currency) fbData.currency = String(d.currency);
                if (d.content_name) fbData.content_name = String(d.content_name);
                if (d.content_category) fbData.content_category = String(d.content_category);
                break;
            case 'PageView':
                break;
            case 'Purchase':
                if (d.value !== undefined) fbData.value = parseFloat(d.value);
                if (d.currency) fbData.currency = String(d.currency);
                if (content_ids.length > 0) {
                    fbData.content_ids = content_ids;
                    fbData.content_type = 'product';
                    fbData.contents = contents;
                }
                fbData.num_items = num_items;
                if (d.items && Array.isArray(d.items) && d.items.length === 1) {
                    fbData.content_name = String(d.items[0].item_name || d.items[0].name || '');
                } else if (d.content_name) {
                    fbData.content_name = String(d.content_name);
                }
                break;
            case 'Search':
                if (d.search_term || d.search_string) fbData.search_string = String(d.search_term || d.search_string);
                if (content_ids.length > 0) fbData.content_ids = content_ids;
                if (d.content_category) fbData.content_category = String(d.content_category);
                if (d.value !== undefined) fbData.value = parseFloat(d.value);
                if (d.currency) fbData.currency = String(d.currency);
                break;
            case 'StartTrial':
            case 'Subscribe':
                if (d.value !== undefined) fbData.value = parseFloat(d.value);
                if (d.currency) fbData.currency = String(d.currency);
                if (d.predicted_ltv) fbData.predicted_ltv = parseFloat(d.predicted_ltv);
                break;
            default:
                Object.assign(fbData, d);
                break;
        }
        return { fbEvent, fbData };
    }\n";

    echo "    function mapGA4ToTT(ev, d) {
        d = d || {};
        const map = {
            'page_view': 'Pageview', 'view_item': 'ViewContent', 'add_to_cart': 'AddToCart',
            'add_to_wishlist': 'AddToWishlist', 'begin_checkout': 'InitiateCheckout',
            'add_payment_info': 'AddPaymentInfo', 'purchase': 'Purchase',
            'generate_lead': 'SubmitForm', 'contact': 'Contact', 'schedule': 'Contact',
            'sign_up': 'CompleteRegistration', 'search': 'Search'
        };
        const ttEvent = map[ev] || ev;
        let ttData = {};
        if (d.currency) ttData.currency = d.currency;
        if (d.value !== undefined) ttData.value = d.value;
        if (d.search_term) ttData.query = d.search_term;
        
        if (d.items && d.items.length > 0) {
            ttData.content_type = 'product';
            ttData.contents = d.items.map(i => ({
                content_id: String(i.item_id || i.id || i.product_id || i.productId), 
                content_name: i.item_name || i.name || i.product_name, 
                price: i.price || i.item_price || 0, 
                quantity: i.quantity || 1
            }));
            
            // For backward compatibility with some older TT pixel versions, also send single item fields if it's one item
            if (d.items.length === 1) {
                ttData.content_id = ttData.contents[0].content_id;
                ttData.content_name = ttData.contents[0].content_name;
                ttData.price = ttData.contents[0].price;
                ttData.quantity = ttData.contents[0].quantity;
            }
        }
        return { ttEvent, ttData };
    }\n";

    if (in_array('facebook', $platforms, true)) {
        echo "    if(typeof fbq !== 'undefined') { 
            const fb = mapGA4ToFB(ga4Event, data);
            const fbOptions = Object.assign({eventID: eventId}, aemq || {});
            fbq('track', fb.fbEvent, fb.fbData, fbOptions); 
        }\n";
    }
    if (in_array('tiktok', $platforms, true)) {
        echo "    if(typeof ttq !== 'undefined') { 
            const tt = mapGA4ToTT(ga4Event, data);
            if (aemq && Object.keys(aemq).length > 0) {
                const ttEmc = {};
                if (aemq.email) ttEmc.email = aemq.email;
                if (aemq.phone_number) ttEmc.phone_number = aemq.phone_number;
                if (Object.keys(ttEmc).length > 0) ttq.identify(ttEmc);
            }
            if (tt.ttEvent === 'Pageview') {
                ttq.page();
            } else {
                ttq.track(tt.ttEvent, tt.ttData, {event_id: eventId}); 
            }
        }\n";
    }
    if (in_array('ga4', $platforms, true)) {
        $ga4_options = \PixelOnWP\Includes\Platforms\GoogleAnalytics\PixelOnWP_GA4_Admin_Options::get_options();
        $ga4_id = get_option('PixelOnWP_ga4_id', '');
        $ga4_events_control = $ga4_options['events'] ?? [];
        
        $ga4_browser_enabled = true;
        if (isset($ga4_events_control[$event_name])) {
            $ga4_browser_enabled = !empty($ga4_events_control[$event_name]['browser']);
        }

        if ($ga4_browser_enabled && !empty($ga4_id)) {
            $debug_mode = (!empty($ga4_options['test_code']) || is_user_logged_in()) ? 'true' : 'false';
            echo "    if(typeof gtag !== 'undefined') {\n";
            echo "        let gData = Object.assign({}, data);\n";
            echo "        gData.send_to = '" . esc_js($ga4_id) . "';\n";
            echo "        gData.event_id = eventId;\n";
            echo "        if (" . $debug_mode . ") { gData.debug_mode = true; }\n";
            echo "        gtag('event', ga4Event, gData);\n";
            echo "    }\n";
        }
    }
    if (in_array('google', $platforms, true)) {
        $google_config = get_option('PixelOnWP_google_config', []);
        $google_events = isset($google_config['events']) && is_array($google_config['events']) ? $google_config['events'] : [];
        $conversion_id = isset($google_config['conversion_id']) ? $google_config['conversion_id'] : '';
        
        echo "    if(typeof gtag !== 'undefined') {\n";
        echo "        const gConfigEvents = " . wp_json_encode($google_events) . ";\n";
        echo "        const convId = '" . esc_js($conversion_id) . "';\n";
        echo "        const fallbackLabel = '" . esc_js(isset($google_config['conversion_label']) ? $google_config['conversion_label'] : '') . "';\n";
        echo "        let label = '';\n";
        echo "        for (let ev of gConfigEvents) { if (ev.name === ga4Event && ev.label) { label = ev.label; break; } }\n";
        echo "        if (!label && ga4Event === 'purchase' && fallbackLabel) { label = fallbackLabel; }\n";
        echo "        let gData = Object.assign({}, data);\n";
        echo "        if (convId) { if (label) { gData.send_to = convId + '/' + label; } else { gData.send_to = convId; } }\n";
        echo "        gtag('event', ga4Event, gData);\n";
        echo "    }\n";
    }
    
    if (in_array('pinterest', $platforms, true)) {
        $pin_map_php = [
            'page_view' => 'pagevisit', 'view_item' => 'pagevisit', 'add_to_cart' => 'addtocart',
            'begin_checkout' => 'initiatecheckout', 'purchase' => 'checkout',
            'generate_lead' => 'lead', 'contact' => 'lead', 'sign_up' => 'signup',
            'search' => 'search', 'schedule' => 'lead', 'add_payment_info' => 'pagevisit'
        ];
        $pin_event = isset($pin_map_php[$event_name]) ? $pin_map_php[$event_name] : 'custom';
        
        // Only skip the generic page_view (base code fires pagevisit via pintrk('page')).
        // view_item, add_to_wishlist etc. fire pagevisit WITH product data.
        $is_generic_page_view = ($event_name === 'page_view');
        
        if (!$is_generic_page_view) {
            echo "    if(typeof window.pintrk !== 'undefined') {\n";
            echo "        var pinData = { event_id: eventId };\n";
            echo "        if (data.value !== undefined) pinData.value = parseFloat(data.value);\n";
            echo "        if (data.currency) pinData.currency = String(data.currency);\n";
            if ($pin_event === 'checkout' || $pin_event === 'addtocart') {
                echo "        var pinItemCount = 0;\n";
                echo "        if (data.items && Array.isArray(data.items)) {\n";
                echo "            data.items.forEach(function(item) { pinItemCount += parseInt(item.quantity || 1, 10); });\n";
                echo "        }\n";
                echo "        if (pinItemCount > 0) pinData.order_quantity = pinItemCount;\n";
                echo "        if (data.items && Array.isArray(data.items)) {\n";
                echo "            pinData.line_items = data.items.map(function(item) {\n";
                echo "                return { product_name: item.item_name || item.name || '', product_id: String(item.item_id || item.id || ''), product_price: parseFloat(item.price || 0), product_quantity: parseInt(item.quantity || 1, 10) };\n";
                echo "            });\n";
                echo "        }\n";
            }
            // Product data for pagevisit (view_item)
            if ($pin_event === 'pagevisit') {
                echo "        if (data.items && Array.isArray(data.items)) {\n";
                echo "            pinData.line_items = data.items.map(function(item) {\n";
                echo "                return { product_name: item.item_name || item.name || '', product_id: String(item.item_id || item.id || ''), product_price: parseFloat(item.price || 0), product_quantity: parseInt(item.quantity || 1, 10) };\n";
                echo "            });\n";
                echo "        }\n";
            }
            if ($pin_event === 'search') {
                echo "        if (data.search_term) pinData.search_query = String(data.search_term);\n";
            }
            if ($pin_event === 'lead' || $pin_event === 'signup') {
                echo "        pinData.lead_type = '" . esc_js($event_name) . "';\n";
            }
            echo "        window.pintrk('track', '" . esc_js($pin_event) . "', pinData);\n";
            echo "    }\n";
        }
    }

    $custom_route = get_option('PixelOnWP_custom_route', 'pixelonwp/v1/collect');
    $custom_route_clean = ltrim(str_replace('wp-json/', '', $custom_route), '/');
    $fetch_url = rest_url($custom_route_clean);
    
    echo "    fetch('" . esc_url($fetch_url) . "', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            event: ga4Event,
            event_id: eventId,
            custom_data: data
        })
    }).catch(e => console.error(e));\n";
    echo "})();\n";
    echo "</script>\n";
  }

  public static function get_hashed_user_data($order = null) {
      // Delegate to platform specific tracker for Meta/Google/TikTok
      return \PixelOnWP\Includes\Tracking\PixelOnWP_Meta_Tracker::get_hashed_user_data($order);
  }

  public static function get_unhashed_user_data($order = null) {
      return \PixelOnWP\Includes\Tracking\PixelOnWP_Google_Tracker::get_unhashed_user_data($order);
  }
}
