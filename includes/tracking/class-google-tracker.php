<?php
/**
 * Google Specific Tracker Class (Google Ads and GA4).
 *
 * @package PixelOnWP\Includes\Tracking
 */

namespace PixelOnWP\Includes\Tracking;

if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Google_Tracker {

  public static function get_hashed_user_data($order = null) {
      return \PixelOnWP\Includes\Platforms\GoogleAnalytics\PixelOnWP_GA4_Platform::get_hashed_user_data($order);
  }

  public static function get_unhashed_user_data($order = null) {
      return \PixelOnWP\Includes\Platforms\GoogleAnalytics\PixelOnWP_GA4_Platform::get_unhashed_user_data($order);
  }
}
