<?php
/**
 * Meta Facebook Specific Tracker Class.
 *
 * @package PixelOnWP\Includes\Tracking
 */

namespace PixelOnWP\Includes\Tracking;

if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Meta_Tracker {

  private function is_event_enabled($event_name) {
      $active_events = get_option('PixelOnWP_active_events', []);
      $key = strtolower($event_name);
      if ($key === 'completepurchase' || $key === 'purchase') $key = 'purchase';
      if ($key === 'submitform') $key = 'lead';

      if (isset($active_events[$key])) {
          return $active_events[$key] === '1';
      }
      return true;
  }

  public static function get_fb_event_data($event_name, $data) {
      return \PixelOnWP\Includes\Platforms\Facebook\PixelOnWP_Facebook_Transformer::get_fb_event_data($event_name, $data);
  }

  public static function get_hashed_user_data($order = null) {
      return \PixelOnWP\Includes\Platforms\Facebook\PixelOnWP_Facebook_Transformer::get_hashed_user_data($order);
  }
}
