<?php
/**
 * TikTok Platform Event Transformer.
 *
 * @package PixelOnWP\Includes\Platforms\TikTok
 */

namespace PixelOnWP\Includes\Platforms\TikTok;

if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_TikTok_Platform {

  public static function dispatch_tt_server_event_static($event_name, $event_id, $custom_data = [], $order = null, $user_data = null) {
      if (class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_TikTok_Tracker')) {
          \PixelOnWP\Includes\Tracking\PixelOnWP_TikTok_Tracker::dispatch_tt_server_event_static($event_name, $event_id, $custom_data, $order, $user_data);
      }
  }
}
