<?php
/**
 * GA4 Setup Modal View template.
 *
 * @package PixelOnWP\Includes\Admin\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

$ga4_options = get_option('PixelOnWP_ga4_config', [
    'setup_type'     => 'basic',
    'measurement_id' => '',
    'api_secret'     => '',
    'test_code'      => '',
    'events'         => []
]);

$events_control = get_option('pixelonwp_ga4_events_control', $ga4_options['events'] ?? []);

$standard_events = [
    'view_item_list' => 'view_item_list',
    'select_item' => 'select_item',
    'view_item' => 'view_item',
    'add_to_cart' => 'add_to_cart',
    'remove_from_cart' => 'remove_from_cart',
    'view_cart' => 'view_cart',
    'begin_checkout' => 'begin_checkout',
    'add_shipping_info' => 'add_shipping_info',
    'add_payment_info' => 'add_payment_info',
    'purchase' => 'purchase',
    'refund' => 'refund'
];
?>
<div class="pixelonwp-modal-overlay ga4-setup-modal" style="display: none;">
    <div class="pixelonwp-modal-content">
        <div class="modal-header">
            <h3>Google Analytics 4 Setup</h3>
            <button type="button" class="close-modal">&times;</button>
        </div>
        <form id="pixelonwp-ga4-config-form">
            <?php wp_nonce_field('pixelonwp_ga4_setup_nonce', 'ga4_nonce'); ?>
            <div class="modal-body">
                <div class="form-group">
                    <label>Setup Type</label>
                    <label><input type="radio" name="setup_type" value="basic" <?php checked($ga4_options['setup_type'] !== 'advanced'); ?>> Basic Setup (GA4 ID Only)</label>
                    <label><input type="radio" name="setup_type" value="advanced" <?php checked($ga4_options['setup_type'], 'advanced'); ?>> Advanced Setup (GA4 ID + API Secret + Server Control)</label>
                </div>
                <div class="form-group">
                    <label>GA4 Measurement ID *</label>
                    <input type="text" name="measurement_id" value="<?php echo esc_attr($ga4_options['measurement_id']); ?>" required>
                </div>
                <div class="form-group advanced-field" style="<?php echo $ga4_options['setup_type'] === 'advanced' ? '' : 'display:none;'; ?>">
                    <label>Measurement Protocol API Secret *</label>
                    <input type="text" name="api_secret" value="<?php echo esc_attr($ga4_options['api_secret']); ?>">
                </div>
                <div class="form-group">
                    <label>Test Event Code / Debug Mode (Optional)</label>
                    <input type="text" name="test_code" value="<?php echo esc_attr($ga4_options['test_code']); ?>">
                </div>

                <h4>Events Control</h4>
                <div class="events-control-list">
                    <?php foreach ($standard_events as $event_id => $event_name) : 
                        $cfg = $events_control[$event_id] ?? ['browser' => true, 'server' => true];
                        $browser_enabled = !isset($cfg['browser']) || $cfg['browser'];
                        $server_enabled = !isset($cfg['server']) || $cfg['server'];
                    ?>
                        <div class="event-control-row" style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span><?php echo esc_html($event_name); ?></span>
                            <div>
                                <label><input type="checkbox" name="events_ctrl[<?php echo esc_attr($event_id); ?>][browser]" value="1" <?php checked($browser_enabled); ?>> Browser</label>
                                <label><input type="checkbox" name="events_ctrl[<?php echo esc_attr($event_id); ?>][server]" value="1" <?php checked($server_enabled); ?>> Server</label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-save">Save Configuration</button>
            </div>
        </form>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    $('input[name="setup_type"]').change(function() {
        if ($(this).val() === 'advanced') {
            $('.advanced-field').show();
        } else {
            $('.advanced-field').hide();
        }
    });
});
</script>
