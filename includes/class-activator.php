<?php
/**
 * Fired during plugin activation.
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
 * PixelOnWP_Activator Class.
 *
 * Handles database table creation, default option initialization, and cron scheduling upon activation.
 *
 * @package PixelOnWP\Includes
 * @since 1.0.0
 */
class PixelOnWP_Activator
{

  /**
   * Run activation routines.
   *
   * @since 1.0.0
   * @return void
   */
  public static function activate(): void
  {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    // Event logs table
    $table_event_logs = $wpdb->prefix . 'pixelonwp_event_logs';
    $sql_event_logs = "CREATE TABLE {$table_event_logs} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_name VARCHAR(100) NOT NULL,
            event_id VARCHAR(255) NOT NULL,
            platform VARCHAR(50) NOT NULL DEFAULT 'facebook',
            payload LONGTEXT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            retry_count INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY status (status)
        ) {$charset_collate};";

    dbDelta($sql_event_logs);

    // Fraud logs table
    $table_fraud_logs = $wpdb->prefix . 'pixelonwp_fraud_logs';
    $sql_fraud_logs = "CREATE TABLE {$table_fraud_logs} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            reason VARCHAR(255) NOT NULL,
            request_data TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY created_at (created_at)
        ) {$charset_collate};";

    dbDelta($sql_fraud_logs);

    // Fraud API Cache table
    $table_fraud_cache = $wpdb->prefix . 'PixelOnWP_fraud_cache';
    $sql_fraud_cache = "CREATE TABLE {$table_fraud_cache} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            phone_number VARCHAR(20) NOT NULL,
            courier_data LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY phone_number (phone_number),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
    dbDelta($sql_fraud_cache);

    // Consent logs table
    $table_consent_logs = $wpdb->prefix . 'pixelonwp_consent_logs';
    $sql_consent_logs = "CREATE TABLE {$table_consent_logs} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            ip_hash varchar(255) NOT NULL,
            country varchar(10) NOT NULL,
            policy_version varchar(50) NOT NULL,
            status varchar(50) NOT NULL,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

    dbDelta($sql_consent_logs);

    // Queue table
    $table_queue = $wpdb->prefix . 'pixelonwp_queue';
    $sql_queue = "CREATE TABLE {$table_queue} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            task_type VARCHAR(100) NOT NULL,
            payload LONGTEXT NOT NULL,
            attempts INT(11) NOT NULL DEFAULT 0,
            status VARCHAR(50) NOT NULL DEFAULT 'queued',
            scheduled_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY scheduled_at (scheduled_at)
        ) {$charset_collate};";

    dbDelta($sql_queue);

    // Visitor Intelligence table (AI Ad Engine)
    $table_visitor_intel = $wpdb->prefix . 'pixelonwp_visitor_intelligence';
    $sql_visitor_intel = "CREATE TABLE {$table_visitor_intel} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            visitor_hash VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            location_info JSON NULL,
            device_context JSON NULL,
            activity_log LONGTEXT NULL,
            last_active DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY visitor_hash (visitor_hash),
            KEY last_active (last_active)
        ) {$charset_collate};";

    dbDelta($sql_visitor_intel);

    // Load eCommerce module activator if present
    $ecommerce_activator_file = dirname(__DIR__) . '/modules/ecommerce/class-PixelOnWP-ecommerce-activator.php';
    if (file_exists($ecommerce_activator_file)) {
        require_once $ecommerce_activator_file;
        if (class_exists('\\PixelOnWP\\Ecommerce\\PixelOnWP_Ecommerce_Activator')) {
            \PixelOnWP\Ecommerce\PixelOnWP_Ecommerce_Activator::activate();
        }
    }

    // Load AdScope module activator if present
    $adscope_activator_file = dirname(__DIR__) . '/modules/adscope/class-adscope-activator.php';
    if (file_exists($adscope_activator_file)) {
        require_once $adscope_activator_file;
        if (class_exists('\\PixelOnWP\\Modules\\Adscope\\Adscope_Activator')) {
            \PixelOnWP\Modules\Adscope\Adscope_Activator::activate();
        }
    }

    // Set default plugin options if not already set
    if (false === get_option('pixelonwp_settings')) {
      $default_settings = [
        'pixel_id' => '',
        'access_token' => '',
        'test_event_code' => '',
        'enable_browser_events' => '1',
        'enable_server_events' => '1',
        'advanced_matching' => '1',
        'event_deduplication' => '1',
        'datalayer_enabled' => '1',
      ];
      update_option('pixelonwp_settings', $default_settings);
    }

    // Schedule background queue cron job if not already scheduled
    if (!wp_next_scheduled('pixelonwp_background_queue_cron')) {
      wp_schedule_event(time(), 'hourly', 'pixelonwp_background_queue_cron');
    }

    // Flag to flush rewrite rules on next load
    update_option('pixelonwp_flush_rewrite_rules', 1);

    // Update plugin version option
    update_option('pixelonwp_version', PixelOnWP_VERSION);
  }
}