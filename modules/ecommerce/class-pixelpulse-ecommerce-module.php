<?php
/**
 * Isolated eCommerce Module Loader
 *
 * @package PixelOnWP\Ecommerce
 */

namespace PixelOnWP\Ecommerce;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_Ecommerce_Module
{
    public function __construct()
    {
        $this->load_dependencies();
    }

    private function load_dependencies(): void
    {
        require_once __DIR__ . '/class-PixelOnWP-ecommerce-tools.php';
    }

    public function register_hooks($loader): void
    {
        // Tools/AJAX
        // Load and initialize Ecommerce Tools
        if (class_exists('\\PixelOnWP\\Ecommerce\\PixelOnWP_Ecommerce_Tools')) {
            $tools = new PixelOnWP_Ecommerce_Tools();
            $tools->register_hooks($loader);
        }

        // Load WhatsApp Order Messaging
        require_once __DIR__ . '/class-whatsapp-order-messaging.php';
        if (class_exists('\\PixelOnWP\\Ecommerce\\PixelOnWP_WhatsApp_Order_Messaging')) {
            new PixelOnWP_WhatsApp_Order_Messaging();
        }


    }
}
