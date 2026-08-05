<?php
/**
 * Multi-Channel Product Feed Generator
 *
 * @package PixelOnWP\Ecommerce
 */

namespace PixelOnWP\Ecommerce;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_Product_Feed_Generator
{
    public function __construct()
    {
        add_action('wp_ajax_pixelonwp_generate_feed', [$this, 'ajax_generate_feed']);
    }

    public function ajax_generate_feed()
    {
        check_ajax_referer('PixelOnWP_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
        }

        if (!class_exists('WooCommerce')) {
            wp_send_json_error(['message' => __('WooCommerce is not active.', 'pixel-on-wp')]);
        }

        $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : 'google';
        $feed_name = isset($_POST['feed_name']) ? sanitize_text_field($_POST['feed_name']) : 'product-feed';
        $stock_filter = isset($_POST['stock_filter']) ? sanitize_text_field($_POST['stock_filter']) : 'all';

        try {
            $file_url = $this->generate_feed_file($platform, $feed_name, $stock_filter);
            if ($file_url) {
                $ext = $platform === 'tiktok' ? '.json' : ($platform === 'meta' ? '.csv' : '.xml');
                wp_send_json_success([
                    'url' => $file_url,
                    'file_name' => sanitize_file_name($feed_name) . $ext
                ]);
            } else {
                wp_send_json_error(['message' => __('Feed generation failed.', 'pixel-on-wp')]);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function generate_feed_file($platform = 'google', $feed_name = 'product-feed', $stock_filter = 'all')
    {
        if (!class_exists('WooCommerce')) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $feed_dir = $upload_dir['basedir'] . '/pixelonwp-feeds';
        if (!file_exists($feed_dir)) {
            wp_mkdir_p($feed_dir);
        }

        $index_file = $feed_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // silence is golden');
        }

        $data = $this->fetch_product_data($stock_filter);

        switch ($platform) {
            case 'google':
            case 'pinterest':
                $ext = '.xml';
                $file_name = sanitize_file_name($feed_name) . $ext;
                $file_path = $feed_dir . '/' . $file_name;
                $this->generate_xml_feed($data, $file_path, $platform);
                break;
            case 'meta':
                $ext = '.csv';
                $file_name = sanitize_file_name($feed_name) . $ext;
                $file_path = $feed_dir . '/' . $file_name;
                $this->generate_csv_feed($data, $file_path);
                break;
            case 'tiktok':
                $ext = '.json';
                $file_name = sanitize_file_name($feed_name) . $ext;
                $file_path = $feed_dir . '/' . $file_name;
                $this->generate_json_feed($data, $file_path);
                break;
            default:
                return false;
        }

        return $upload_dir['baseurl'] . '/pixelonwp-feeds/' . $file_name;
    }

    private function fetch_product_data($stock_filter)
    {
        $args = [
            'status' => 'publish',
            'limit' => -1, // Note: In a production environment with massive catalogs, this should be paginated/batched.
            'return' => 'objects',
        ];

        if ($stock_filter === 'instock') {
            $args['stock_status'] = 'instock';
        }

        $products = wc_get_products($args);
        $items = [];

        foreach ($products as $product) {
            if ($product->is_type('variable')) {
                $children = $product->get_children();
                foreach ($children as $child_id) {
                    $variation = wc_get_product($child_id);
                    if (!$variation || $variation->get_status() !== 'publish') {
                        continue;
                    }
                    if ($stock_filter === 'instock' && !$variation->is_in_stock()) {
                        continue;
                    }
                    
                    $items[] = $this->format_product_data($variation, $product);
                }
            } else {
                // Simple or other types
                $items[] = $this->format_product_data($product, null);
            }
        }

        return $items;
    }

    private function format_product_data($product, $parent = null)
    {
        $id = $product->get_id();
        $item_group_id = $parent ? $parent->get_id() : $id;
        
        $title = $product->get_name();
        // If variation, append variation attributes to title for uniqueness if needed, but WooCommerce usually includes them in get_name()
        
        $description = wp_strip_all_tags(strip_shortcodes($product->get_description()));
        if (empty($description)) {
            $description = wp_strip_all_tags(strip_shortcodes($product->get_short_description()));
        }
        if (empty($description) && $parent) {
            $description = wp_strip_all_tags(strip_shortcodes($parent->get_short_description()));
        }
        if (empty($description)) {
            $description = $title;
        }

        // Truncate desc for safety
        $description = mb_substr($description, 0, 5000);

        $availability = $product->is_in_stock() ? 'in_stock' : 'out_of_stock';
        $condition = 'new';
        $price = wc_get_price_including_tax($product);
        $currency = get_woocommerce_currency();
        $price_formatted = number_format((float)$price, 2, '.', '') . ' ' . $currency;
        
        $link = $product->get_permalink();
        
        $image_id = $product->get_image_id();
        if (!$image_id && $parent) {
            $image_id = $parent->get_image_id();
        }
        $image_link = $image_id ? wp_get_attachment_url($image_id) : '';

        // Attributes (Color, Size, Brand)
        $brand = '';
        $color = '';
        $size = '';

        $attributes = $product->get_attributes();
        foreach ($attributes as $name => $attribute) {
            $val = '';
            if ($product->is_type('variation')) {
                // Variation attributes
                $val = $product->get_attribute($name);
            } else {
                if ($attribute->is_taxonomy()) {
                    $terms = wc_get_product_terms($id, $name, ['fields' => 'names']);
                    $val = implode('/', $terms);
                } else {
                    $val = implode('/', $attribute->get_options());
                }
            }
            
            $name_lower = strtolower($name);
            if (strpos($name_lower, 'brand') !== false) $brand = $val;
            if (strpos($name_lower, 'color') !== false) $color = $val;
            if (strpos($name_lower, 'size') !== false) $size = $val;
        }

        // Default brand if empty
        if (empty($brand)) {
            $brand = get_bloginfo('name');
        }

        return [
            'id' => $id,
            'item_group_id' => $item_group_id,
            'title' => $title,
            'description' => $description,
            'availability' => $availability,
            'condition' => $condition,
            'price' => $price_formatted,
            'link' => $link,
            'image_link' => $image_link,
            'brand' => $brand,
            'color' => $color,
            'size' => $size
        ];
    }

    private function generate_xml_feed($items, $file_path, $platform)
    {
        $xml = new \XMLWriter();
        $xml->openURI($file_path);
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        
        $xml->startElement('channel');
        $xml->writeElement('title', get_bloginfo('name') . ' Product Feed');
        $xml->writeElement('link', site_url());
        $xml->writeElement('description', 'Product catalog generated by PixelOnWP');

        foreach ($items as $item) {
            $xml->startElement('item');
            
            $xml->writeElement('g:id', $item['id']);
            if ($item['id'] != $item['item_group_id']) {
                $xml->writeElement('g:item_group_id', $item['item_group_id']);
            }
            
            $xml->startElement('g:title');
            $xml->writeCdata($item['title']);
            $xml->endElement(); // g:title
            
            $xml->startElement('g:description');
            $xml->writeCdata($item['description']);
            $xml->endElement(); // g:description
            
            $xml->writeElement('g:availability', $item['availability'] === 'in_stock' ? 'in stock' : 'out of stock');
            $xml->writeElement('g:condition', $item['condition']);
            $xml->writeElement('g:price', $item['price']);
            $xml->writeElement('g:link', $item['link']);
            if (!empty($item['image_link'])) {
                $xml->writeElement('g:image_link', $item['image_link']);
            }
            if (!empty($item['brand'])) {
                $xml->writeElement('g:brand', $item['brand']);
            }
            if (!empty($item['color'])) {
                $xml->writeElement('g:color', $item['color']);
            }
            if (!empty($item['size'])) {
                $xml->writeElement('g:size', $item['size']);
            }

            $xml->endElement(); // item
        }

        $xml->endElement(); // channel
        $xml->endElement(); // rss
        $xml->endDocument();
        $xml->flush();
    }

    private function generate_csv_feed($items, $file_path)
    {
        $fp = fopen($file_path, 'w');
        
        // Meta headers
        $headers = ['id', 'item_group_id', 'title', 'description', 'availability', 'condition', 'price', 'link', 'image_link', 'brand', 'color', 'size'];
        fputcsv($fp, $headers);

        foreach ($items as $item) {
            $row = [
                $item['id'],
                $item['item_group_id'],
                $item['title'],
                $item['description'],
                $item['availability'] === 'in_stock' ? 'in stock' : 'out of stock',
                $item['condition'],
                $item['price'],
                $item['link'],
                $item['image_link'],
                $item['brand'],
                $item['color'],
                $item['size']
            ];
            fputcsv($fp, $row);
        }

        fclose($fp);
    }

    private function generate_json_feed($items, $file_path)
    {
        // TikTok can accept JSON format. We just dump the structured array.
        $json_data = json_encode(['products' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($file_path, $json_data);
    }
}
