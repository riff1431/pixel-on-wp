<?php
/**
 * Dependency Injection Container Class.
 *
 * @package PixelOnWP\Includes\Core
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PixelOnWP_Container Class.
 *
 * Manages service instantiation and resolution across the plugin.
 *
 * @package PixelOnWP\Includes\Core
 * @since 1.0.0
 */
class PixelOnWP_Container {

    /**
     * Array of instantiated services.
     *
     * @var array
     */
    private array $services = [];

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->register_services();
    }

    /**
     * Register core services in the container.
     *
     * @since 1.0.0
     * @return void
     */
    private function register_services(): void {
        // Admin menu service
        $this->services['admin_menu'] = function() {
            return new \PixelOnWP\Includes\Admin\PixelOnWP_Admin_Menu();
        };

        // Frontend service
        $this->services['frontend'] = function() {
            return new \PixelOnWP\Includes\Frontend\PixelOnWP_Frontend();
        };

        // Tracking manager service
        $this->services['tracking_manager'] = function() {
            return new \PixelOnWP\Includes\Tracking\PixelOnWP_Tracking_Manager();
        };

        // Logger service
        $this->services['logger'] = function() {
            return new \PixelOnWP\Includes\Core\PixelOnWP_Logger();
        };
    }

    /**
     * Retrieve a service instance by key.
     *
     * @since 1.0.0
     * @param string $key Service key.
     * @return object|null Service instance or null if not found.
     */
    public function get( string $key ): ?object {
        if ( isset( $this->services[$key] ) ) {
            if ( is_callable( $this->services[$key] ) ) {
                $this->services[$key] = call_user_func( $this->services[$key] );
            }
            return $this->services[$key];
        }
        return null;
    }

    /**
     * Check if a service is registered.
     *
     * @since 1.0.0
     * @param string $key Service key.
     * @return bool True if registered, false otherwise.
     */
    public function has( string $key ): bool {
        return isset( $this->services[$key] );
    }
}