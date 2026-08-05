<?php
/**
 * Admin Dashboard Template.
 *
 * @package PixelOnWP\Templates\Admin
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

?>
<div class="wrap wpt-admin-wrap" id="wpt-admin-app">
  <!-- SPA Application mounts here -->
  <div style="padding: 40px; text-align: center; color: #0458C7;">
      <p><?php esc_html_e('Loading PixelOnWP...', 'pixel-on-wp'); ?></p>
  </div>
</div>