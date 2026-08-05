<?php
/**
 * Core Plugin Orchestrator Class.
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
 * PixelOnWP_Plugin Class.
 *
 * Manages the registration and initialization of all modules, hooks, and services.
 *
 * @package PixelOnWP\Includes\Core
 * @since 1.0.0
 */
class PixelOnWP_Plugin
{

  /**
   * Loader instance for registering hooks.
   *
   * @var \PixelOnWP\PixelOnWP_Loader
   */
  protected \PixelOnWP\PixelOnWP_Loader $loader;

  /**
   * DI Container instance.
   *
   * @var PixelOnWP_Container
   */
  protected PixelOnWP_Container $container;

  /**
   * Initialize the plugin core.
   *
   * @since 1.0.0
   * @param \PixelOnWP\PixelOnWP_Loader $loader    Loader instance.
   * @param PixelOnWP_Container                   $container Container instance.
   */
  public function __construct(\PixelOnWP\PixelOnWP_Loader $loader, PixelOnWP_Container $container)
  {
    $this->loader = $loader;
    $this->container = $container;

    $this->define_admin_hooks();
    $this->define_frontend_hooks();
    $this->define_tracking_hooks();
  }

  /**
   * Register all admin-related hooks.
   *
   * @since 1.0.0
   * @return void
   */
  private function define_admin_hooks(): void
  {
    if (is_admin()) {
      $admin_menu = $this->container->get('admin_menu');
      if ($admin_menu && method_exists($admin_menu, 'register_hooks')) {
        $admin_menu->register_hooks($this->loader);
      }
    }
  }

  /**
   * Register all frontend-related hooks.
   *
   * @since 1.0.0
   * @return void
   */
  private function define_frontend_hooks(): void
  {
    $frontend = $this->container->get('frontend');
    if ($frontend && method_exists($frontend, 'register_hooks')) {
      $frontend->register_hooks($this->loader);
    }
  }

  /**
   * Register all tracking and server-side integration hooks.
   *
   * @since 1.0.0
   * @return void
   */
  private function define_tracking_hooks(): void
  {
    $tracking_manager = $this->container->get('tracking_manager');
    if ($tracking_manager && method_exists($tracking_manager, 'register_hooks')) {
      $tracking_manager->register_hooks($this->loader);
    }
  }

  /**
   * Run the loader to execute all registered hooks.
   *
   * @since 1.0.0
   * @return void
   */
  public function run(): void
  {
    $this->loader->run();
  }
}