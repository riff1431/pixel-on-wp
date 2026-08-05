<?php
/**
 * Fired during plugin deactivation.
 *
 * @package PixelOnWP\Includes
 * @since 1.0.0
 */

namespace PixelOnWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Deactivator Class.
 *
 * Handles cleanup tasks like clearing scheduled cron jobs upon deactivation.
 *
 * @package PixelOnWP\Includes
 * @since 1.0.0
 */
class PixelOnWP_Deactivator
{

  /**
   * Run deactivation routines.
   *
   * @since 1.0.0
   * @return void
   */
  public static function deactivate(): void
  {
    // Clear scheduled background queue cron job
    $timestamp = wp_next_scheduled('pixelonwp_background_queue_cron');
    if ($timestamp) {
      wp_unschedule_event($timestamp, 'pixelonwp_background_queue_cron');
    }

    // Flush rewrite rules if custom endpoints were registered
    flush_rewrite_rules();
  }
}