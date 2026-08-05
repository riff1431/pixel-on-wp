<?php
/**
 * Universal Tracker Module.
 *
 * @package PixelOnWP\Modules\UniversalTracker
 */

namespace PixelOnWP\Modules\UniversalTracker;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_Universal_Tracker_Module {

    public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void {
        $loader->add_action('init', $this, 'init_visual_builder_checks');
        $loader->add_action('wp_ajax_pixelonwp_save_visual_tracker_rule', $this, 'save_visual_tracker_rule');
    }

    /**
     * Check permissions and nonce to activate visual builder setup overlay.
     */
    public function init_visual_builder_checks(): void {
        if (isset($_GET['pixelonwp_visual_builder']) && $_GET['pixelonwp_visual_builder'] === '1') {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this tool.', 'pixel-on-wp'), 403);
            }

            $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
            if (!wp_verify_nonce($nonce, 'pixelonwp_launch_visual_builder')) {
                wp_die(__('Invalid security credentials.', 'pixel-on-wp'), 403);
            }

            add_action('wp_enqueue_scripts', [$this, 'enqueue_visual_builder_assets']);
        }
    }

    /**
     * Enqueue Visual Builder panel scripts.
     */
    public function enqueue_visual_builder_assets(): void {
        wp_enqueue_script(
            'pixelonwp-visual-builder-panel',
            plugins_url('assets/js/visual-builder-panel.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('pixelonwp-visual-builder-panel', 'PixelOnWPVisualBuilderData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('pixelonwp_visual_builder_save_nonce')
        ]);
    }

    /**
     * AJAX action to save rules to option database key pixelonwp_visual_tracker_rules.
     */
    public function save_visual_tracker_rule(): void {
        check_ajax_referer('pixelonwp_visual_builder_save_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden.'], 403);
        }

        $rule_name = sanitize_text_field($_POST['rule_name'] ?? '');
        $selector = sanitize_text_field($_POST['selector'] ?? '');
        $event_name = sanitize_text_field($_POST['event_name'] ?? '');
        $destinations = isset($_POST['destinations']) ? array_map('sanitize_text_field', (array)$_POST['destinations']) : [];

        if (empty($rule_name) || empty($selector) || empty($event_name)) {
            wp_send_json_error(['message' => 'Missing fields.']);
        }

        $rules = get_option('pixelonwp_visual_tracker_rules', []);
        if (!is_array($rules)) {
            $rules = [];
        }

        $new_rule = [
            'id' => 'vtr_' . wp_generate_uuid4(),
            'name' => $rule_name,
            'selector' => $selector,
            'event_name' => $event_name,
            'destinations' => $destinations,
            'active' => true
        ];

        $rules[] = $new_rule;
        update_option('pixelonwp_visual_tracker_rules', $rules);

        wp_send_json_success(['message' => 'Rule saved successfully.', 'rule' => $new_rule]);
    }
}
