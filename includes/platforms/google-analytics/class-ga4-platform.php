<?php
/**
 * GA4 Platform Event Transformer.
 *
 * @package PixelOnWP\Includes\Platforms\GoogleAnalytics
 */

namespace PixelOnWP\Includes\Platforms\GoogleAnalytics;

if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_GA4_Platform {

  public static function get_hashed_user_data($order = null) {
      $user_data = [
         'client_ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
         'client_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
      ];

      $email = ''; $phone = ''; $fname = ''; $lname = ''; $city = ''; $state = ''; $zip = ''; $country = '';
      $address_1 = ''; $address_2 = '';

      if ($order) {
          $email = $order->get_billing_email();
          $phone = $order->get_billing_phone();
          $fname = $order->get_billing_first_name();
          $lname = $order->get_billing_last_name();
          $city = $order->get_billing_city();
          $state = $order->get_billing_state();
          $zip = $order->get_billing_postcode();
          $country = $order->get_billing_country();
          $address_1 = $order->get_billing_address_1();
          $address_2 = $order->get_billing_address_2();
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
          $address_1 = get_user_meta($current_user->ID, 'billing_address_1', true);
          $address_2 = get_user_meta($current_user->ID, 'billing_address_2', true);
      } else if (function_exists('WC') && WC()->customer) {
          $email = WC()->customer->get_billing_email();
          $phone = WC()->customer->get_billing_phone();
          $fname = WC()->customer->get_billing_first_name();
          $lname = WC()->customer->get_billing_last_name();
          $city = WC()->customer->get_billing_city();
          $state = WC()->customer->get_billing_state();
          $zip = WC()->customer->get_billing_postcode();
          $country = WC()->customer->get_billing_country();
          $address_1 = WC()->customer->get_billing_address_1();
          $address_2 = WC()->customer->get_billing_address_2();
      }

      $data = [];
      if (!empty($email)) $data['email'] = hash('sha256', strtolower(trim($email)));
      if (!empty($phone)) {
          $ph = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::format_phone_e164($phone, $country);
          if (!empty($ph)) {
              $data['phone_number'] = hash('sha256', $ph);
          }
      }
      
      $address = [];
      if (!empty($fname)) $address['first_name'] = hash('sha256', strtolower(trim($fname)));
      if (!empty($lname)) $address['last_name'] = hash('sha256', strtolower(trim($lname)));
      
      $street = trim(($address_1 ?? '') . ' ' . ($address_2 ?? ''));
      if (!empty($street)) $address['street'] = hash('sha256', strtolower($street));
      
      if (!empty($city)) $address['city'] = hash('sha256', strtolower(trim($city)));
      if (!empty($state)) $address['region'] = hash('sha256', strtolower(trim($state)));
      if (!empty($zip)) $address['postal_code'] = hash('sha256', strtolower(trim($zip)));
      if (!empty($country)) $address['country'] = hash('sha256', strtolower(trim($country)));
      
      if (!empty($address)) {
          $data['address'] = $address;
      }

      return array_merge($user_data, $data);
  }

  public static function get_unhashed_user_data($order = null) {
      $email = ''; $phone = ''; $fname = ''; $lname = ''; $city = ''; $state = ''; $zip = ''; $country = '';
      $address_1 = ''; $address_2 = '';
      
      if ($order) {
          $email = $order->get_billing_email();
          $phone = $order->get_billing_phone();
          $fname = $order->get_billing_first_name();
          $lname = $order->get_billing_last_name();
          $city = $order->get_billing_city();
          $state = $order->get_billing_state();
          $zip = $order->get_billing_postcode();
          $country = $order->get_billing_country();
          $address_1 = $order->get_billing_address_1();
          $address_2 = $order->get_billing_address_2();
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
          $address_1 = get_user_meta($current_user->ID, 'billing_address_1', true);
          $address_2 = get_user_meta($current_user->ID, 'billing_address_2', true);
      } else if (function_exists('WC') && WC()->customer) {
          $email = WC()->customer->get_billing_email();
          $phone = WC()->customer->get_billing_phone();
          $fname = WC()->customer->get_billing_first_name();
          $lname = WC()->customer->get_billing_last_name();
          $city = WC()->customer->get_billing_city();
          $state = WC()->customer->get_billing_state();
          $zip = WC()->customer->get_billing_postcode();
          $country = WC()->customer->get_billing_country();
          $address_1 = WC()->customer->get_billing_address_1();
          $address_2 = WC()->customer->get_billing_address_2();
      }

      $user_data = [];
      if (!empty($email)) $user_data['email'] = strtolower(trim($email));
      if (!empty($phone)) {
          $formatted_phone = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::format_phone_e164($phone, $country);
          if (!empty($formatted_phone)) {
              $user_data['phone_number'] = $formatted_phone;
          }
      }
      
      $address = [];
      if (!empty($fname)) $address['first_name'] = strtolower(trim($fname));
      if (!empty($lname)) $address['last_name'] = strtolower(trim($lname));
      
      $street = trim(($address_1 ?? '') . ' ' . ($address_2 ?? ''));
      if (!empty($street)) $address['street'] = strtolower($street);
      
      if (!empty($city)) $address['city'] = strtolower(trim($city));
      if (!empty($state)) $address['region'] = strtolower(trim($state));
      if (!empty($zip)) $address['postal_code'] = strtolower(trim($zip));
      if (!empty($country)) $address['country'] = strtolower(trim($country));
      
      if (!empty($address)) {
          $user_data['address'] = $address;
      }

      return $user_data;
  }
}
