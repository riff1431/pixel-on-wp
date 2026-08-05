<?php
/**
 * Fired during plugin activation to create AdScope DB tables.
 *
 * @package    PixelOnWP\Modules\Adscope
 * @since      1.0.0
 */

namespace PixelOnWP\Modules\Adscope;

if (!defined('ABSPATH')) {
    exit;
}

class Adscope_Activator {

    /**
     * Create the necessary tables for AdScope module.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pixelonwp_adscope_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            city varchar(100) DEFAULT '' NOT NULL,
            region varchar(100) DEFAULT '' NOT NULL,
            isp varchar(255) DEFAULT '' NOT NULL,
            device varchar(50) DEFAULT '' NOT NULL,
            utm_source varchar(100) DEFAULT '' NOT NULL,
            utm_medium varchar(100) DEFAULT '' NOT NULL,
            event_type varchar(50) DEFAULT 'page_view' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY ip_address (ip_address),
            KEY event_type (event_type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('pixelonwp_adscope_db_version', '1.0.0');
    }
}
