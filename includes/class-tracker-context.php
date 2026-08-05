<?php
/**
 * Context Detector Engine Class.
 *
 * Detects the active business environment and page builder.
 *
 * @package PixelOnWP\Includes
 * @since 1.2.0
 */

namespace PixelOnWP\Includes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Tracker_Context
{
  /**
   * Get the current active business model and theme/builder context.
   *
   * @return array
   */
  public static function get_context(): array
  {
    return [
      'business_model' => self::detect_business_model(),
      'theme_builder'  => self::detect_theme_builder(),
    ];
  }

  /**
   * Detect business model type.
   *
   * @return string E-Commerce | Lead-Gen | LMS
   */
  private static function detect_business_model(): string
  {
    // E-Commerce detection
    if (class_exists('WooCommerce') || class_exists('Easy_Digital_Downloads')) {
      return 'E-Commerce';
    }

    // LMS / Education detection
    if (class_exists('Tutor\\Tutor') || class_exists('SFWD_LMS') || class_exists('LearnPress')) {
      return 'LMS';
    }

    // Default to Lead-Gen (most standard forms or button/link tracking fits here)
    return 'Lead-Gen';
  }

  /**
   * Detect active theme / page builder signatures.
   *
   * @return string Elementor | Divi | Astra | Gutenberg
   */
  private static function detect_theme_builder(): string
  {
    if (did_action('elementor/loaded') || defined('ELEMENTOR_VERSION')) {
      return 'Elementor';
    }

    if (function_exists('et_setup_theme') || defined('ET_CORE_VERSION')) {
      return 'Divi';
    }

    if (defined('ASTRA_THEME_SETTINGS')) {
      return 'Astra';
    }

    return 'Gutenberg';
  }
}
