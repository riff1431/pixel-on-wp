<?php
/**
 * Security and Fraud Prevention Class.
 *
 * @package PixelOnWP\Includes\Core
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Core;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Security Class.
 *
 * Provides IP rate-limiting, bot detection, fake/disposable email filtering, and ad-block evasion hooks.
 *
 * @package PixelOnWP\Includes\Core
 * @since 1.0.0
 */
class PixelOnWP_Security
{

  /**
   * Register hooks with WordPress.
   *
   * @since 1.0.0
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
  {
    $loader->add_action('init', $this, 'check_ip_rate_limit');
  }

  /**
   * Check IP rate limiting to prevent tracking spam or DDoS bot floods.
   *
   * @since 1.0.0
   * @return void
   */
  public function check_ip_rate_limit(): void
  {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
      $client_ip = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_ip();
      $transient_key = 'pixelonwp_rate_' . md5($client_ip);

      $requests = get_transient($transient_key);
      if (false === $requests) {
        set_transient($transient_key, 1, MINUTE_IN_SECONDS);
      } else {
        if ($requests > 60) { // Limit to 60 requests per minute per IP
          self::log_fraud_attempt($client_ip, 'Rate limit exceeded (60 req/min)');
          wp_die(esc_html__('Too many requests. Tracking rate limit exceeded.', 'pixel-on-wp'), 429);
        }
        set_transient($transient_key, $requests + 1, MINUTE_IN_SECONDS);
      }
    }
  }

  /**
   * Check if a user agent belongs to a known bot or crawler.
   *
   * @since 1.0.0
   * @param string $user_agent User agent string.
   * @return bool True if bot detected, false otherwise.
   */
  public static function is_bot(string $user_agent = ''): bool
  {
    if (empty($user_agent)) {
      $user_agent = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_user_agent();
    }

    if (empty($user_agent)) {
      return true; // Empty user agents are usually automated scrapers/bots
    }

    $bot_patterns = [
      'bot',
      'crawl',
      'spider',
      'slurp',
      'mediapartners',
      'adsbot',
      'googlebot',
      'bingbot',
      'yahoo',
      'baidu',
      'yandex',
      'duckduckbot',
      'curl',
      'wget',
      'python',
      'java',
      'phphttp',
      'libwww'
    ];

    $user_agent_lower = strtolower($user_agent);
    foreach ($bot_patterns as $pattern) {
      if (false !== strpos($user_agent_lower, $pattern)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Validate email against known disposable/temporary email domains.
   *
   * @since 1.0.0
   * @param string $email Email address to check.
   * @return bool True if disposable/fake, false if valid.
   */
  public static function is_disposable_email(string $email): bool
  {
    $email = trim($email);
    if (!is_email($email)) {
      return true;
    }

    $domain = substr(strrchr($email, "@"), 1);
    if (empty($domain)) {
      return true;
    }

    $disposable_domains = [
      'mailinator.com',
      '10minutemail.com',
      'guerrillamail.com',
      'tempmail.com',
      'trashmail.com',
      'sharklasers.com',
      'getnada.com',
      'dispostable.com',
      'yopmail.com',
      'fakemailgenerator.com'
    ];

    return in_array(strtolower($domain), $disposable_domains, true);
  }

  /**
   * Log a fraud or spam attempt to the database.
   *
   * @since 1.0.0
   * @param string $ip_address Client IP address.
   * @param string $reason     Reason for flagging.
   * @return void
   */
  public static function log_fraud_attempt(string $ip_address, string $reason): void
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_fraud_logs';

    // Ensure table exists before inserting
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return;
    }

    $wpdb->insert(
      $table,
      [
        'ip_address' => sanitize_text_field($ip_address),
        'reason' => sanitize_text_field($reason),
        'user_agent' => \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_user_agent(),
        'created_at' => current_time('mysql'),
      ],
      ['%s', '%s', '%s', '%s']
    );
  }
}