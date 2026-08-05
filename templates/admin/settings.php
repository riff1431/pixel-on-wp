<?php
/**
 * Admin Settings Template.
 *
 * @package PixelOnWP\Templates\Admin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

if (isset($_POST['pixelonwp_save_settings']) && check_admin_referer('pixelonwp_settings_nonce_action', 'pixelonwp_settings_nonce')) {
  $settings = array(
    'pixel_id' => sanitize_text_field($_POST['pixel_id'] ?? ''),
    'capi_token' => sanitize_text_field($_POST['capi_token'] ?? ''),
    'ga4_measurement_id' => sanitize_text_field($_POST['ga4_measurement_id'] ?? ''),
    'gtm_container_id' => sanitize_text_field($_POST['gtm_container_id'] ?? ''),
    'visual_builder_enabled' => isset($_POST['visual_builder_enabled']) ? '1' : '0',
  );
  update_option('PixelOnWP_settings', $settings);
  echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved successfully.</strong></p></div>';
}

$settings = get_option('PixelOnWP_settings', array());
$pixel_id = $settings['pixel_id'] ?? '';
$capi_token = $settings['capi_token'] ?? '';
$ga4_id = $settings['ga4_measurement_id'] ?? '';
$gtm_id = $settings['gtm_container_id'] ?? '';
$visual_builder_enabled = $settings['visual_builder_enabled'] ?? '1';
?>

<div class="wrap wpt-admin-wrap">
  <h1>Plugin Settings</h1>
  <p class="description">Configure your tracking options, Pixel IDs, and API tokens here.</p>

  <form method="post" action=""
    style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 20px; max-width: 800px;">
    <?php wp_nonce_field('pixelonwp_settings_nonce_action', 'pixelonwp_settings_nonce'); ?>

    <table class="form-table">
      <tr>
        <th scope="row"><label for="pixel_id">Meta Pixel ID</label></th>
        <td>
          <input name="pixel_id" type="text" id="pixel_id" value="<?php echo esc_attr($pixel_id); ?>"
            class="regular-text" placeholder="e.g., 123456789012345">
          <p class="description">Enter your numeric Meta Pixel ID.</p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="capi_token">Meta Conversions API (CAPI) Token</label></th>
        <td>
          <textarea name="capi_token" id="capi_token" rows="3"
            class="large-text"><?php echo esc_textarea($capi_token); ?></textarea>
          <p class="description">Enter your Meta System User Access Token for Server-Side tracking.</p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="ga4_measurement_id">GA4 Measurement ID</label></th>
        <td>
          <input name="ga4_measurement_id" type="text" id="ga4_measurement_id" value="<?php echo esc_attr($ga4_id); ?>"
            class="regular-text" placeholder="e.g., G-XXXXXXXXXX">
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="gtm_container_id">Google Tag Manager ID</label></th>
        <td>
          <input name="gtm_container_id" type="text" id="gtm_container_id" value="<?php echo esc_attr($gtm_id); ?>"
            class="regular-text" placeholder="e.g., GTM-XXXXXXX">
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="visual_builder_enabled">Visual Event Setup Tool</label></th>
        <td>
          <label>
            <input name="visual_builder_enabled" type="checkbox" id="visual_builder_enabled" value="1" <?php checked('1', $visual_builder_enabled); ?>>
            Enable the Point-and-Click Visual Event Setup Tool and Live Inspector on the frontend.
          </label>
        </td>
      </tr>
    </table>

    <p class="submit">
      <input type="submit" name="pixelonwp_save_settings" id="submit" class="button button-primary" value="Save Changes">
    </p>
  </form>
</div>