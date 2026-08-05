<?php
/**
 * Facebook Platform Event Transformer.
 *
 * @package PixelOnWP\Includes\Platforms\Facebook
 */

namespace PixelOnWP\Includes\Platforms\Facebook;

if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Facebook_Transformer {

  public static function get_fb_event_data($event_name, $data) {
      $map = [
          'page_view' => 'PageView', 'view_item' => 'ViewContent', 'add_to_cart' => 'AddToCart',
          'add_to_wishlist' => 'AddToWishlist', 'begin_checkout' => 'InitiateCheckout',
          'add_payment_info' => 'AddPaymentInfo', 'purchase' => 'Purchase',
          'generate_lead' => 'Lead', 'contact' => 'Contact', 'schedule' => 'Schedule',
          'sign_up' => 'CompleteRegistration', 'search' => 'Search',
          'customize_product' => 'CustomizeProduct', 'donate' => 'Donate',
          'find_location' => 'FindLocation', 'start_trial' => 'StartTrial',
          'submit_application' => 'SubmitApplication', 'subscribe' => 'Subscribe'
      ];
      $fb_event = isset($map[$event_name]) ? $map[$event_name] : $event_name;
      
      $raw_data = [];
      $content_ids = [];
      $contents = [];
      $num_items = 0;

      if (!empty($data['items']) && is_array($data['items'])) {
          foreach ($data['items'] as $item) {
              $id = (string)($item['item_id'] ?? $item['id'] ?? $item['product_id'] ?? '');
              if ($id !== '') {
                  $content_ids[] = $id;
                  $qty = intval($item['quantity'] ?? 1);
                  $price = floatval($item['price'] ?? 0);
                  $contents[] = [
                      'id' => $id,
                      'quantity' => $qty,
                      'item_price' => $price
                  ];
                  $num_items += $qty;
              }
          }
      } elseif (!empty($data['content_ids']) && is_array($data['content_ids'])) {
          foreach ($data['content_ids'] as $id) {
              $content_ids[] = (string)$id;
              $contents[] = [
                  'id' => (string)$id,
                  'quantity' => 1
              ];
              $num_items += 1;
          }
      }

      if (isset($data['contents']) && is_string($data['contents'])) {
          $decoded = json_decode($data['contents'], true);
          if (is_array($decoded)) {
              $contents = $decoded;
          }
      } elseif (isset($data['contents']) && is_array($data['contents'])) {
          $contents = $data['contents'];
      }

      switch ($fb_event) {
          case 'AddToCart':
          case 'AddToWishlist':
          case 'ViewContent':
          case 'InitiateCheckout':
          case 'AddPaymentInfo':
              if (isset($data['value'])) $raw_data['value'] = floatval($data['value']);
              if (isset($data['currency'])) $raw_data['currency'] = sanitize_text_field($data['currency']);
              
              $raw_data['content_type'] = 'product';
              if (!empty($content_ids)) {
                  $raw_data['content_ids'] = $content_ids;
                  $raw_data['contents'] = $contents;
              }
              
              if (!empty($data['items']) && is_array($data['items'])) {
                  if (count($data['items']) === 1) {
                      $raw_data['content_name'] = sanitize_text_field($data['items'][0]['item_name'] ?? $data['items'][0]['name'] ?? '');
                      if (isset($data['items'][0]['item_category'])) {
                          $raw_data['content_category'] = sanitize_text_field($data['items'][0]['item_category']);
                      }
                  } else {
                      $raw_data['content_name'] = ($fb_event === 'InitiateCheckout' || $fb_event === 'AddPaymentInfo') ? 'Checkout Cart' : 'Multiple Products';
                      $raw_data['content_category'] = ($fb_event === 'InitiateCheckout' || $fb_event === 'AddPaymentInfo') ? 'Checkout' : 'E-commerce';
                  }
              } else {
                  if (isset($data['content_name'])) $raw_data['content_name'] = sanitize_text_field($data['content_name']);
                  if (isset($data['content_category'])) $raw_data['content_category'] = sanitize_text_field($data['content_category']);
              }
              
              if ($fb_event === 'AddToCart' || $fb_event === 'InitiateCheckout' || $fb_event === 'AddPaymentInfo' || $fb_event === 'AddToWishlist' || $fb_event === 'ViewContent') {
                  $raw_data['num_items'] = $num_items > 0 ? $num_items : 1;
              }
              break;

          case 'CompleteRegistration':
              if (isset($data['value'])) $raw_data['value'] = floatval($data['value']);
              if (isset($data['currency'])) $raw_data['currency'] = sanitize_text_field($data['currency']);
              if (isset($data['content_name'])) $raw_data['content_name'] = sanitize_text_field($data['content_name']);
              if (isset($data['status'])) $raw_data['status'] = sanitize_text_field($data['status']);
              break;

          case 'Contact':
          case 'FindLocation':
          case 'Schedule':
          case 'SubmitApplication':
              if (isset($data['content_category'])) $raw_data['content_category'] = sanitize_text_field($data['content_category']);
              if (isset($data['content_name'])) $raw_data['content_name'] = sanitize_text_field($data['content_name']);
              break;

          case 'CustomizeProduct':
              $raw_data['content_type'] = 'product';
              if (!empty($content_ids)) {
                  $raw_data['content_ids'] = $content_ids;
                  $raw_data['contents'] = $contents;
              }
              break;

          case 'Donate':
              if (isset($data['value'])) $raw_data['value'] = floatval($data['value']);
              if (isset($data['currency'])) $raw_data['currency'] = sanitize_text_field($data['currency']);
              if (isset($data['content_name'])) $raw_data['content_name'] = sanitize_text_field($data['content_name']);
              break;

          case 'Lead':
              if (isset($data['value'])) $raw_data['value'] = floatval($data['value']);
              if (isset($data['currency'])) $raw_data['currency'] = sanitize_text_field($data['currency']);
              if (isset($data['content_name'])) $raw_data['content_name'] = sanitize_text_field($data['content_name']);
              if (isset($data['content_category'])) $raw_data['content_category'] = sanitize_text_field($data['content_category']);
              break;

          case 'PageView':
              break;

          case 'Purchase':
              if (isset($data['value'])) $raw_data['value'] = floatval($data['value']);
              if (isset($data['currency'])) $raw_data['currency'] = sanitize_text_field($data['currency']);
              $raw_data['content_type'] = 'product';
              if (!empty($content_ids)) {
                  $raw_data['content_ids'] = $content_ids;
                  $raw_data['contents'] = $contents;
              }
              $raw_data['num_items'] = $num_items > 0 ? $num_items : 1;
              if (isset($data['transaction_id'])) {
                  $raw_data['content_name'] = 'Order #' . sanitize_text_field($data['transaction_id']);
              } else {
                  $raw_data['content_name'] = isset($data['content_name']) ? sanitize_text_field($data['content_name']) : 'Purchase Order';
              }
              $raw_data['content_category'] = 'E-commerce';
              break;

          case 'Search':
              if (isset($data['search_term'])) {
                  $raw_data['search_string'] = sanitize_text_field($data['search_term']);
              } elseif (isset($data['search_string'])) {
                  $raw_data['search_string'] = sanitize_text_field($data['search_string']);
              }
              if (!empty($content_ids)) $raw_data['content_ids'] = $content_ids;
              if (isset($data['content_category'])) $raw_data['content_category'] = sanitize_text_field($data['content_category']);
              if (isset($data['value'])) $raw_data['value'] = floatval($data['value']);
              if (isset($data['currency'])) $raw_data['currency'] = sanitize_text_field($data['currency']);
              break;

          case 'StartTrial':
          case 'Subscribe':
              if (isset($data['value'])) $raw_data['value'] = floatval($data['value']);
              if (isset($data['currency'])) $raw_data['currency'] = sanitize_text_field($data['currency']);
              if (isset($data['predicted_ltv'])) $raw_data['predicted_ltv'] = floatval($data['predicted_ltv']);
              break;

          default:
              foreach ($data as $k => $v) {
                  if (is_scalar($v)) {
                      $raw_data[sanitize_key($k)] = sanitize_text_field($v);
                  }
              }
              break;
      }
      return ['event_name' => $fb_event, 'custom_data' => $raw_data];
  }

  public static function get_hashed_user_data($order = null) {
      $user_data = [
         'client_ip_address' => \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_ip(),
         'client_user_agent' => \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_user_agent()
      ];
      
      if (isset($_COOKIE['_fbp'])) $user_data['fbp'] = sanitize_text_field(wp_unslash($_COOKIE['_fbp']));
      if (isset($_COOKIE['_fbc'])) $user_data['fbc'] = sanitize_text_field(wp_unslash($_COOKIE['_fbc']));

      $email = ''; $phone = ''; $fname = ''; $lname = ''; $city = ''; $state = ''; $zip = ''; $country = '';
      $gender = ''; $dob = ''; $external_id = ''; $fb_login_id = '';

      if ($order) {
          $email = $order->get_billing_email();
          $phone = $order->get_billing_phone();
          $fname = $order->get_billing_first_name();
          $lname = $order->get_billing_last_name();
          $city = $order->get_billing_city();
          $state = $order->get_billing_state();
          $zip = $order->get_billing_postcode();
          $country = $order->get_billing_country();
          if ($order->get_customer_id()) {
              $external_id = (string)$order->get_customer_id();
          }
      } else if (is_user_logged_in()) {
          $current_user = wp_get_current_user();
          $email = $current_user->user_email;
          $fname = $current_user->user_firstname;
          $lname = $current_user->user_lastname;
          $phone = get_user_meta($current_user->ID, 'billing_phone', true);
          $city = get_user_meta($current_user->ID, 'billing_city', true);
          $state = get_user_meta($current_user->ID, 'billing_state', true);
          $zip = get_user_meta($current_user->ID, 'billing_postcode', true);
          $country = get_user_meta($current_user->ID, 'billing_country', true);
          $gender = get_user_meta($current_user->ID, 'gender', true);
          $dob = get_user_meta($current_user->ID, 'date_of_birth', true);
          $fb_login_id = get_user_meta($current_user->ID, 'fb_login_id', true);
          $external_id = (string)$current_user->ID;
      } else if (function_exists('WC') && WC()->customer) {
          $email = WC()->customer->get_billing_email();
          $phone = WC()->customer->get_billing_phone();
          $fname = WC()->customer->get_billing_first_name();
          $lname = WC()->customer->get_billing_last_name();
          $city = WC()->customer->get_billing_city();
          $state = WC()->customer->get_billing_state();
          $zip = WC()->customer->get_billing_postcode();
          $country = WC()->customer->get_billing_country();
          if (WC()->customer->get_id()) {
              $external_id = (string)WC()->customer->get_id();
          }
      }

      if (!empty($email)) {
          $user_data['em'] = hash('sha256', strtolower(trim($email)));
      }
      
      if (!empty($phone)) {
          $ph = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::format_phone_e164($phone, $country);
          if (!empty($ph)) {
              $ph_digits = preg_replace('/\D/', '', $ph);
              $user_data['ph'] = hash('sha256', $ph_digits);
          }
      }
      
      if (!empty($fname)) {
          $clean_fname = preg_replace('/[^a-zA-Z]/', '', $fname);
          $user_data['fn'] = hash('sha256', strtolower($clean_fname));
      }
      
      if (!empty($lname)) {
          $clean_lname = preg_replace('/[^a-zA-Z]/', '', $lname);
          $user_data['ln'] = hash('sha256', strtolower($clean_lname));
      }
      
      if (!empty($city)) {
          $clean_city = preg_replace('/[^a-zA-Z]/', '', $city);
          $user_data['ct'] = hash('sha256', strtolower($clean_city));
      }
      
      if (!empty($state)) {
          $clean_state = strtolower(trim(preg_replace('/[^a-zA-Z]/', '', $state)));
          $user_data['st'] = hash('sha256', $clean_state);
      }
      
      if (!empty($zip)) {
          $clean_zip = strtolower(trim(preg_replace('/\s+/', '', $zip)));
          $user_data['zp'] = hash('sha256', $clean_zip);
      }
      
      if (!empty($country)) {
          $clean_country = strtolower(trim(preg_replace('/[^a-zA-Z]/', '', $country)));
          $user_data['country'] = hash('sha256', $clean_country);
      }
      
      if (!empty($gender)) {
          $ge = strtolower(trim($gender));
          if ($ge === 'male' || $ge === 'm') $user_data['ge'] = hash('sha256', 'm');
          if ($ge === 'female' || $ge === 'f') $user_data['ge'] = hash('sha256', 'f');
      }
      
      if (!empty($dob)) {
          $time = strtotime($dob);
          if ($time) $user_data['db'] = hash('sha256', gmdate('Ymd', $time));
      }
      
      if (!empty($external_id)) {
          $user_data['external_id'] = hash('sha256', trim($external_id));
      }
      
      if (!empty($fb_login_id)) {
          $user_data['fb_login_id'] = $fb_login_id;
      }

      return $user_data;
  }
}
