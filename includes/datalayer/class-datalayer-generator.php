<?php
/**
 * DataLayer Generator Class.
 *
 * @package PixelOnWP\Includes\DataLayer
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\DataLayer;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_DataLayer_Generator Class.
 *
 * Compiles and outputs structured dataLayer objects for e-commerce and site interactions.
 *
 * @package PixelOnWP\Includes\DataLayer
 * @since 1.0.0
 */
class PixelOnWP_DataLayer_Generator
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
    $loader->add_action('wp_head', $this, 'render_datalayer_object', 2);
  }

  /**
   * Render the global dataLayer JavaScript object in the page head.
   *
   * @since 1.0.0
   * @return void
   */
  public function render_datalayer_object(): void
  {
    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();

    if ('1' !== $settings['datalayer_enabled']) {
      return;
    }

    $datalayer_data = [
      'page' => [
        'type' => is_singular() ? get_post_type() : (is_archive() ? 'archive' : 'home'),
        'title' => wp_get_document_title(),
        'url' => esc_url_raw(home_url(add_query_arg([]))),
        'logged_in' => is_user_logged_in(),
      ],
    ];

    if (is_singular()) {
      $datalayer_data['post'] = [
        'id' => get_the_ID(),
        'title' => get_the_title(),
        'type' => get_post_type(),
      ];
    }

    // Allow third-party extensions or WooCommerce hooks to enrich dataLayer
    $datalayer_data = apply_filters('pixelonwp_datalayer_attributes', $datalayer_data);

    ?>
    <script>
      window.wptDataLayer = window.wptDataLayer || [];
      window.wptDataLayer.push(<?php echo wp_json_encode($datalayer_data); ?>);
    </script>
    <?php
  }

  /**
   * Generate standard e-commerce product array structure for dataLayer.
   *
   * @since 1.0.0
   * @param int   $product_id Product ID.
   * @param int   $quantity   Quantity.
   * @param float $price      Price.
   * @return array            Formatted product data structure.
   */
  public static function format_product_data(int $product_id, int $quantity = 1, float $price = 0.00): array
  {
    $product = function_exists('wc_get_product') ? wc_get_product($product_id) : null;

    if (!$product) {
      return [
        'id' => $product_id,
        'quantity' => $quantity,
        'price' => $price,
      ];
    }

    return [
      'id' => (string) $product->get_id(),
      'name' => $product->get_name(),
      'price' => (float) ($price > 0 ? $price : $product->get_price()),
      'quantity' => $quantity,
      'category' => self::get_product_categories($product->get_id()),
    ];
  }

  /**
   * Retrieve comma-separated product categories.
   *
   * @since 1.0.0
   * @param int $product_id Product ID.
   * @return string         Categories string.
   */
  private static function get_product_categories(int $product_id): string
  {
    $terms = get_the_terms($product_id, 'product_cat');
    if (!$terms || is_wp_error($terms)) {
      return '';
    }

    $names = wp_list_pluck($terms, 'name');
    return implode(', ', $names);
  }
}