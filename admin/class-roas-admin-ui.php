<?php
/**
 * Return on Ad Spend (ROAS) & Automation Admin UI Class.
 *
 * @package PixelOnWP\Admin
 * @since 1.0.0
 */

namespace PixelOnWP\Admin;

if (!defined('ABSPATH')) {
  exit;
}

use PixelOnWP\Includes\PixelOnWP_Meta_Api_Service;
use PixelOnWP\Includes\PixelOnWP_Audience_Sync;
use PixelOnWP\Includes\PixelOnWP_Cron_Sync;

/**
 * PixelOnWP_Roas_Admin_Ui Class.
 *
 * Handles the rendering, saving, and AJAX callbacks for the Ad Optimization & Analytics Engine.
 */
class PixelOnWP_Roas_Admin_Ui {

  /**
   * Register hooks.
   *
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void {
    $loader->add_action('wp_ajax_roas_verify_meta_api', $this, 'ajax_verify_meta_api');
  }

  /**
   * AJAX handler to verify Meta API connection and permissions.
   *
   * @return void
   */
  public function ajax_verify_meta_api(): void {
    check_ajax_referer('pixelonwp_roas_nonce', 'nonce');

    if (!current_user_can(\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_admin_capability())) {
      wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
    }

    $token = isset($_POST['meta_access_token']) ? sanitize_text_field(wp_unslash($_POST['meta_access_token'])) : '';
    $ad_account = isset($_POST['meta_ad_account_id']) ? sanitize_text_field(wp_unslash($_POST['meta_ad_account_id'])) : '';

    $res = PixelOnWP_Meta_Api_Service::run_self_diagnostics($token, $ad_account);

    if ($res['success']) {
      wp_send_json_success(['message' => $res['message']]);
    } else {
      wp_send_json_error(['message' => $res['message']]);
    }
  }

  /**
   * Render the main dashboard panel page.
   *
   * @return void
   */
  public function render_dashboard(): void {
    if (!current_user_can(\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_admin_capability())) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'pixel-on-wp'));
    }

    $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'dashboard';

    // Handle Form Submissions (POST Routing)
    $this->handle_form_submissions();

    // Settings fields
    $meta_token = get_option('pixelonwp_meta_access_token', '');
    $meta_ad_account = get_option('pixelonwp_meta_ad_account_id', '');
    $exchange_rates = get_option('pixelonwp_currency_exchange_rates', []);
    $ad_currency = get_option('pixelonwp_base_ad_currency', 'USD');
    $store_currency = get_woocommerce_currency();

    // Determine Date Range filter
    $filter = isset($_GET['date_range']) ? sanitize_text_field(wp_unslash($_GET['date_range'])) : 'last_30_days';
    $start_date = '';
    $end_date   = '';

    switch ($filter) {
      case 'today':
        $start_date = date('Y-m-d');
        $end_date   = date('Y-m-d');
        break;
      case 'last_7_days':
        $start_date = date('Y-m-d', strtotime('-6 days'));
        $end_date   = date('Y-m-d');
        break;
      case 'last_30_days':
      default:
        $start_date = date('Y-m-d', strtotime('-29 days'));
        $end_date   = date('Y-m-d');
        break;
      case 'custom':
        $start_date = isset($_GET['start_date']) ? sanitize_text_field(wp_unslash($_GET['start_date'])) : date('Y-m-d', strtotime('-29 days'));
        $end_date = isset($_GET['end_date']) ? sanitize_text_field(wp_unslash($_GET['end_date'])) : date('Y-m-d');
        break;
    }

    // Load campaign insights data (pulled from local cron sync cache fallback first)
    $insights = [];
    $meta_error = '';
    $cached_entry = get_option('pixelonwp_cached_meta_insights', []);

    if (!empty($meta_token) && !empty($meta_ad_account)) {
      if (!empty($cached_entry['data']) && $filter === 'last_30_days') {
        $insights = $cached_entry['data'];
      } else {
        $insights_response = PixelOnWP_Meta_Api_Service::fetch_campaign_insights($start_date, $end_date);
        if (is_wp_error($insights_response)) {
          $meta_error = $insights_response->get_error_message();
        } else {
          $insights = $insights_response;
          if (!empty($insights[0]['account_currency'])) {
            $ad_currency = strtoupper($insights[0]['account_currency']);
          }
        }
      }
    } else {
      $meta_error = __('Meta API Credentials are not configured. Go to settings tab.', 'pixel-on-wp');
    }

    $needs_exchange_rate = ($ad_currency !== $store_currency);

    ?>
    <div class="wrap pixelonwp-roas-dashboard-wrap">
      <h1 class="wp-heading-inline"><?php esc_html_e('Ad Optimization & Analytics Engine', 'pixel-on-wp'); ?></h1>
      <hr class="wp-header-end">

      <style>
        .pixelonwp-metric-cards {
          display: flex;
          flex-wrap: wrap;
          gap: 20px;
          margin-bottom: 30px;
        }
        .pixelonwp-metric-cards .card {
          flex: 1 1 calc(25% - 20px);
          min-width: 220px;
          background: #fff;
          padding: 20px;
          border-radius: 6px;
          box-shadow: 0 1px 3px rgba(0,0,0,0.1);
          box-sizing: border-box;
        }
        .pixelonwp-responsive-table-wrapper {
          width: 100%;
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
          margin-top: 15px;
        }
        .pixelonwp-responsive-table-wrapper table {
          table-layout: auto !important;
          min-width: 800px !important;
          width: 100% !important;
          display: table !important;
        }

        @media (max-width: 1024px) {
          .pixelonwp-metric-cards .card {
            flex: 1 1 calc(50% - 20px);
          }
        }
        @media (max-width: 782px) {
          .form-table tr {
            display: block;
            margin-bottom: 15px;
          }
          .form-table th, .form-table td {
            display: block;
            width: 100% !important;
            box-sizing: border-box;
            padding: 5px 0 !important;
          }
          .nav-tab-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
          }
          .nav-tab {
            flex: 1 1 auto;
            text-align: center;
            margin: 0 !important;
          }
        }
        @media (max-width: 480px) {
          .pixelonwp-metric-cards .card {
            flex: 1 1 100%;
          }
        }
      </style>

      <!-- Navigation Tabs -->
      <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <a href="?page=pixelonwp-roas&tab=dashboard" class="nav-tab <?php echo ($active_tab === 'dashboard') ? 'nav-tab-active' : ''; ?>">
          <?php esc_html_e('ROAS Dashboard', 'pixel-on-wp'); ?>
        </a>
        <a href="?page=pixelonwp-roas&tab=automation" class="nav-tab <?php echo ($active_tab === 'automation') ? 'nav-tab-active' : ''; ?>">
          <?php esc_html_e('Auto-Rules & Automation', 'pixel-on-wp'); ?>
        </a>
        <a href="?page=pixelonwp-roas&tab=audiences" class="nav-tab <?php echo ($active_tab === 'audiences') ? 'nav-tab-active' : ''; ?>">
          <?php esc_html_e('Audience Syncing', 'pixel-on-wp'); ?>
        </a>
        <a href="?page=pixelonwp-roas&tab=settings" class="nav-tab <?php echo ($active_tab === 'settings') ? 'nav-tab-active' : ''; ?>">
          <?php esc_html_e('Meta Integration Settings', 'pixel-on-wp'); ?>
        </a>
      </h2>

      <!-- Tab: Settings -->
      <?php if ($active_tab === 'settings') : ?>
        <div style="background: #fff; padding: 25px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 800px;">
          <h2><?php esc_html_e('Meta Marketing API Credentials', 'pixel-on-wp'); ?></h2>
          <form method="post" action="">
            <?php wp_nonce_field('pixelonwp_save_roas_settings', 'pixelonwp_roas_settings_nonce'); ?>
            <table class="form-table" role="presentation">
              <tbody>
                <tr>
                  <th scope="row"><label for="meta_access_token"><?php esc_html_e('Meta System User Token', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="meta_access_token" type="password" id="meta_access_token" value="<?php echo esc_attr($meta_token); ?>" class="regular-text" style="width: 100%;">
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="meta_ad_account_id"><?php esc_html_e('Meta Ad Account ID', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="meta_ad_account_id" type="text" id="meta_ad_account_id" value="<?php echo esc_attr($meta_ad_account); ?>" class="regular-text">
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="pixelonwp_base_ad_currency"><?php esc_html_e('Ad Account Currency', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <select name="pixelonwp_base_ad_currency" id="pixelonwp_base_ad_currency">
                      <?php 
                      $currencies = \PixelOnWP\Includes\PixelOnWP_Currency_Converter::get_all_currencies();
                      foreach ($currencies as $code => $name) {
                        echo '<option value="' . esc_attr($code) . '" ' . selected($ad_currency, $code, false) . '>' . esc_html($name) . '</option>';
                      }
                      ?>
                    </select>
                  </td>
                </tr>
                <?php if ($needs_exchange_rate) : 
                  $rate_key = $ad_currency . '_' . $store_currency;
                  $rate_val = isset($exchange_rates[$rate_key]) ? (float)$exchange_rates[$rate_key] : 1.0;
                  ?>
                  <tr>
                    <th scope="row"><label for="exchange_rate_value"><?php esc_html_e('Currency Exchange Rate', 'pixel-on-wp'); ?></label></th>
                    <td>
                      1 <?php echo esc_html($ad_currency); ?> = 
                      <input name="exchange_rates[<?php echo esc_attr($rate_key); ?>]" type="number" step="0.0001" id="exchange_rate_value" value="<?php echo esc_attr($rate_val); ?>" style="width: 100px;">
                      <?php echo esc_html($store_currency); ?>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>

            <p class="submit">
              <input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e('Save Configuration', 'pixel-on-wp'); ?>">
              <button type="button" id="pixelonwp-verify-api" class="button button-secondary" style="margin-left: 10px;"><?php esc_html_e('Verify API & Permissions', 'pixel-on-wp'); ?></button>
            </p>
          </form>

          <div id="diagnostic-result" style="margin-top: 20px; display: none; padding: 15px; border-radius: 4px;"></div>

          <script>
            jQuery(document).ready(function($) {
              $('#pixelonwp-verify-api').on('click', function() {
                const btn = $(this);
                const resultDiv = $('#diagnostic-result');
                btn.prop('disabled', true).text('<?php echo esc_js(__('Verifying...', 'pixel-on-wp')); ?>');
                
                $.ajax({
                  url: ajaxurl,
                  type: 'POST',
                  data: {
                    action: 'roas_verify_meta_api',
                    nonce: '<?php echo esc_js(wp_create_nonce('pixelonwp_roas_nonce')); ?>',
                    meta_access_token: $('#meta_access_token').val(),
                    meta_ad_account_id: $('#meta_ad_account_id').val()
                  },
                  success: function(response) {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Verify API & Permissions', 'pixel-on-wp')); ?>');
                    resultDiv.show().css({
                      'background': response.success ? '#e7f4e4' : '#fbeae5',
                      'color': response.success ? '#2e6b23' : '#a22d1f',
                      'border': '1px solid ' + (response.success ? '#d0e9c6' : '#f5c6cb')
                    }).html(response.data.message);
                  },
                  error: function() {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Verify API & Permissions', 'pixel-on-wp')); ?>');
                    resultDiv.show().css({
                      'background': '#fbeae5',
                      'color': '#a22d1f',
                      'border': '1px solid #f5c6cb'
                    }).text('<?php echo esc_js(__('An unknown error occurred during validation.', 'pixel-on-wp')); ?>');
                  }
                });
              });
            });
          </script>
        </div>

      <!-- Tab: Automation -->
      <?php elseif ($active_tab === 'automation') : ?>
        <div style="background: #fff; padding: 25px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 800px;">
          <h2><?php esc_html_e('Auto-Rules & Campaign Automation', 'pixel-on-wp'); ?></h2>
          <form method="post" action="">
            <?php wp_nonce_field('pixelonwp_save_automation', 'pixelonwp_automation_nonce'); ?>
            <table class="form-table" role="presentation">
              <tbody>
                <tr>
                  <th scope="row"><?php esc_html_e('High-ROAS Auto-Scale Rule', 'pixel-on-wp'); ?></th>
                  <td>
                    <label>
                      <input type="checkbox" name="enable_autoscale" value="1" <?php checked(get_option('pixelonwp_enable_autoscale', '0'), '1'); ?>>
                      <?php esc_html_e('Automatically increase daily budget by 20% if campaign ROAS is high over last 3 days.', 'pixel-on-wp'); ?>
                    </label>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="autoscale_threshold"><?php esc_html_e('Scaling ROAS Threshold', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input type="number" step="0.1" name="autoscale_threshold" id="autoscale_threshold" value="<?php echo esc_attr(get_option('pixelonwp_autoscale_threshold', '3.0')); ?>" style="width: 80px;">
                    <span>x</span>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><?php esc_html_e('Low-ROAS Budget Cut/Pause Rule', 'pixel-on-wp'); ?></th>
                  <td>
                    <label>
                      <input type="checkbox" name="enable_budgetcut" value="1" <?php checked(get_option('pixelonwp_enable_budgetcut', '0'), '1'); ?>>
                      <?php esc_html_e('Cut campaign budget by 50% or set status to PAUSED if campaign ROAS falls below threshold.', 'pixel-on-wp'); ?>
                    </label>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="budgetcut_threshold"><?php esc_html_e('Budget Cut ROAS Threshold', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input type="number" step="0.1" name="budgetcut_threshold" id="budgetcut_threshold" value="<?php echo esc_attr(get_option('pixelonwp_budgetcut_threshold', '1.0')); ?>" style="width: 80px;">
                    <span>x</span>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><?php esc_html_e('Out-of-Stock Ad Pauser', 'pixel-on-wp'); ?></th>
                  <td>
                    <label>
                      <input type="checkbox" name="enable_stock_pauser" value="1" <?php checked(get_option('pixelonwp_enable_stock_pauser', '0'), '1'); ?>>
                      <?php esc_html_e('Pause matching Meta ad campaign/adset when product stock reaches out-of-stock status.', 'pixel-on-wp'); ?>
                    </label>
                  </td>
                </tr>
              </tbody>
            </table>

            <p class="submit">
              <input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e('Save Automation Settings', 'pixel-on-wp'); ?>">
            </p>
          </form>

          <hr style="margin: 30px 0;">
          <h3><?php esc_html_e('Recent Automation Activity Logs', 'pixel-on-wp'); ?></h3>
          <div class="pixelonwp-responsive-table-wrapper">
            <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
              <thead>
                <tr>
                  <th><?php esc_html_e('Time', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Target', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Action Type', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Message Log', 'pixel-on-wp'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $logs = get_option('pixelonwp_automation_logs', []);
                if (!empty($logs)) :
                  foreach (array_reverse($logs) as $log) : ?>
                    <tr>
                      <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $log['timestamp'])); ?></td>
                      <td><code><?php echo esc_html($log['campaign_id']); ?></code> (<?php echo esc_html($log['name'] ?? ''); ?>)</td>
                      <td><span style="font-weight: 600;"><?php echo esc_html($log['action']); ?></span></td>
                      <td><?php echo esc_html($log['message']); ?></td>
                    </tr>
                  <?php endforeach;
                else : ?>
                  <tr><td colspan="4" style="text-align: center; color: #999;"><?php esc_html_e('No automation logs generated yet.', 'pixel-on-wp'); ?></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>


      <!-- Tab: Audiences -->
      <?php elseif ($active_tab === 'audiences') : ?>
        <div style="background: #fff; padding: 25px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 800px;">
          <h2><?php esc_html_e('Meta Custom Audience Syncing', 'pixel-on-wp'); ?></h2>
          <form method="post" action="">
            <?php wp_nonce_field('pixelonwp_save_audiences', 'pixelonwp_audiences_nonce'); ?>
            <table class="form-table" role="presentation">
              <tbody>
                <tr>
                  <th scope="row"><label for="meta_vip_audience_id"><?php esc_html_e('VIP Custom Audience ID', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="meta_vip_audience_id" type="text" id="meta_vip_audience_id" value="<?php echo esc_attr(get_option('pixelonwp_meta_vip_audience_id', '')); ?>" class="regular-text">
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="vip_spend_threshold"><?php esc_html_e('VIP Spend Threshold', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="vip_spend_threshold" type="number" id="vip_spend_threshold" value="<?php echo esc_attr(get_option('pixelonwp_vip_spend_threshold', '10000')); ?>" style="width: 120px;">
                    <span><?php echo esc_html($store_currency); ?></span>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="meta_abandon_audience_id"><?php esc_html_e('Cart Abandoners Audience ID', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="meta_abandon_audience_id" type="text" id="meta_abandon_audience_id" value="<?php echo esc_attr(get_option('pixelonwp_meta_abandon_audience_id', '')); ?>" class="regular-text">
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="meta_purchasers_audience_id"><?php esc_html_e('Recent Purchasers Audience ID', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="meta_purchasers_audience_id" type="text" id="meta_purchasers_audience_id" value="<?php echo esc_attr(get_option('pixelonwp_meta_purchasers_audience_id', '')); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Customers completing checkout will be pushed to exclude them from active prospecting.', 'pixel-on-wp'); ?></p>
                  </td>
                </tr>
              </tbody>
            </table>

            <p class="submit">
              <input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e('Save Custom Audiences', 'pixel-on-wp'); ?>">
              <button type="button" id="pixelonwp-sync-vip-btn" class="button button-secondary" style="margin-left: 10px;"><?php esc_html_e('Sync VIP List Now', 'pixel-on-wp'); ?></button>
            </p>
          </form>
          <script>
            jQuery(document).ready(function($) {
              $('#pixelonwp-sync-vip-btn').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).text('<?php echo esc_js(__('Syncing VIP list...', 'pixel-on-wp')); ?>');
                
                $.ajax({
                  url: ajaxurl,
                  type: 'POST',
                  data: {
                    action: 'roas_sync_vip_audience',
                    nonce: '<?php echo esc_js(wp_create_nonce('pixelonwp_roas_nonce')); ?>'
                  },
                  success: function(response) {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Sync VIP List Now', 'pixel-on-wp')); ?>');
                    alert(response.data.message || (response.success ? '<?php echo esc_js(__('Sync completed.', 'pixel-on-wp')); ?>' : '<?php echo esc_js(__('Sync failed.', 'pixel-on-wp')); ?>'));
                  },
                  error: function() {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Sync VIP List Now', 'pixel-on-wp')); ?>');
                    alert('<?php echo esc_js(__('An error occurred during sync.', 'pixel-on-wp')); ?>');
                  }
                });
              });
            });
          </script>
        </div>

      <!-- Tab: Dashboard -->
      <?php else : ?>
        <div class="tablenav top" style="margin: 20px 0; background: #fff; padding: 15px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center;">
          <form method="get" action="" style="display: inline-block;">
            <input type="hidden" name="page" value="pixelonwp-roas">
            <label for="date_range" style="font-weight: 600; margin-right: 10px;"><?php esc_html_e('Date Range:', 'pixel-on-wp'); ?></label>
            <select name="date_range" id="date_range" onchange="if(this.value === 'custom') { document.getElementById('custom-date-fields').style.display = 'inline-block'; } else { document.getElementById('custom-date-fields').style.display = 'none'; this.form.submit(); }">
              <option value="today" <?php selected($filter, 'today'); ?>><?php esc_html_e('Today', 'pixel-on-wp'); ?></option>
              <option value="last_7_days" <?php selected($filter, 'last_7_days'); ?>><?php esc_html_e('Last 7 Days', 'pixel-on-wp'); ?></option>
              <option value="last_30_days" <?php selected($filter, 'last_30_days'); ?>><?php esc_html_e('Last 30 Days', 'pixel-on-wp'); ?></option>
              <option value="custom" <?php selected($filter, 'custom'); ?>><?php esc_html_e('Custom Date Range', 'pixel-on-wp'); ?></option>
            </select>

            <div id="custom-date-fields" style="display: <?php echo ($filter === 'custom') ? 'inline-block' : 'none'; ?>; margin-left: 15px;">
              <label for="start_date"><?php esc_html_e('From:', 'pixel-on-wp'); ?></label>
              <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date); ?>">
              
              <label for="end_date" style="margin-left: 10px;"><?php esc_html_e('To:', 'pixel-on-wp'); ?></label>
              <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date); ?>">
              
              <input type="submit" class="button button-secondary" value="<?php esc_attr_e('Apply', 'pixel-on-wp'); ?>" style="margin-left: 10px;">
            </div>
          </form>

          <button type="button" id="pixelonwp-force-sync-btn" class="button button-secondary"><?php esc_html_e('Force Cache Sync Now', 'pixel-on-wp'); ?></button>
          <script>
            jQuery(document).ready(function($) {
              $('#pixelonwp-force-sync-btn').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).text('<?php echo esc_js(__('Syncing...', 'pixel-on-wp')); ?>');
                
                $.ajax({
                  url: ajaxurl,
                  type: 'POST',
                  data: {
                    action: 'roas_force_cache_sync',
                    nonce: '<?php echo esc_js(wp_create_nonce('pixelonwp_roas_nonce')); ?>'
                  },
                  success: function(response) {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Force Cache Sync Now', 'pixel-on-wp')); ?>');
                    if (response.success) {
                      window.location.reload();
                    } else {
                      alert(response.data.message || '<?php echo esc_js(__('Sync failed.', 'pixel-on-wp')); ?>');
                    }
                  },
                  error: function() {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Force Cache Sync Now', 'pixel-on-wp')); ?>');
                    alert('<?php echo esc_js(__('An error occurred during sync.', 'pixel-on-wp')); ?>');
                  }
                });
              });
            });
          </script>
        </div>

        <?php if (!empty($meta_error)) : ?>
          <div class="notice notice-error"><p><?php echo esc_html($meta_error); ?></p></div>
        <?php endif; ?>

        <!-- Aggregations from WooCommerce -->
        <?php
        $order_args = [
          'limit'        => -1,
          'status'       => ['completed', 'processing', 'on-hold'],
          'date_created' => $start_date . '...' . $end_date,
          'meta_key'     => '_tracked_ad_campaign_id',
          'meta_compare' => 'EXISTS',
        ];
        $orders = wc_get_orders($order_args);

        $campaign_stats = [];
        $total_impressions = 0;
        $total_clicks = 0;

        // Initialize Meta stats
        $total_spend = 0.0;
        if (empty($meta_error) && is_array($insights)) {
          foreach ($insights as $campaign) {
            $c_id    = $campaign['campaign_id'] ?? '';
            $c_name  = $campaign['campaign_name'] ?? $c_id;
            $spend   = (float)($campaign['spend'] ?? 0.0);
            $currency = $campaign['account_currency'] ?? 'USD';
            $impressions = (int)($campaign['impressions'] ?? 0);
            $clicks = (int)($campaign['inline_link_clicks'] ?? 0);

            $normalized_spend = PixelOnWP_Meta_Api_Service::normalize_currency($spend, $currency);
            $total_spend += $normalized_spend;
            $total_impressions += $impressions;
            $total_clicks += $clicks;

            if (!empty($c_id)) {
              $campaign_stats[$c_id] = [
                'name'        => $c_name,
                'conversions' => 0,
                'revenue'     => 0.0,
                'spend'       => $normalized_spend,
                'impressions' => $impressions,
                'clicks'      => $clicks,
              ];
            }
          }
        }

        // Loop orders to associate WooCommerce conversions
        $product_stats = [];
        foreach ($orders as $order) {
          $c_id = $order->get_meta('_tracked_ad_campaign_id');
          $c_name = $order->get_meta('_tracked_ad_campaign_name') ?: $c_id;

          if (!isset($campaign_stats[$c_id])) {
            $campaign_stats[$c_id] = [
              'name'        => $c_name,
              'conversions' => 0,
              'revenue'     => 0.0,
              'spend'       => 0.0,
              'impressions' => 0,
              'clicks'      => 0,
            ];
          }
          $campaign_stats[$c_id]['conversions']++;
          $campaign_stats[$c_id]['revenue'] += (float)$order->get_total();

          // Aggregate products
          foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $prod_id = $product->get_id();
            $prod_key = $prod_id . '_' . $c_id;
            if (!isset($product_stats[$prod_key])) {
              $product_stats[$prod_key] = [
                'sku'         => $product->get_sku() ?: __('No SKU', 'pixel-on-wp'),
                'name'        => $product->get_name(),
                'qty'         => 0,
                'revenue'     => 0.0,
                'campaign_id' => $c_id,
              ];
            }
            $product_stats[$prod_key]['qty'] += (int)$item->get_quantity();
            $product_stats[$prod_key]['revenue'] += (float)$item->get_total();
          }
        }

        $total_conversions = 0;
        $total_revenue = 0.0;
        foreach ($campaign_stats as $stat) {
          $total_conversions += $stat['conversions'];
          $total_revenue     += $stat['revenue'];
        }

        // Calculations
        $overall_roas = $total_spend > 0 ? ($total_revenue / $total_spend) : 0.0;
        $overall_cpa = $total_conversions > 0 ? ($total_spend / $total_conversions) : 0.0;
        $overall_aov = $total_conversions > 0 ? ($total_revenue / $total_conversions) : 0.0;
        $overall_ctr = $total_impressions > 0 ? ($total_clicks / $total_impressions) * 100 : 0.0;
        $overall_cpc = $total_clicks > 0 ? ($total_spend / $total_clicks) : 0.0;
        ?>

        <!-- Extended Stat Cards -->
        <div class="pixelonwp-metric-cards" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
          <div class="card" style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #0073aa;">
            <h3 style="margin: 0 0 10px; font-weight: 500; color: #666;"><?php esc_html_e('Attributed Orders', 'pixel-on-wp'); ?></h3>
            <span style="font-size: 28px; font-weight: bold; color: #333;"><?php echo esc_html($total_conversions); ?></span>
          </div>
          <div class="card" style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #46b450;">
            <h3 style="margin: 0 0 10px; font-weight: 500; color: #666;"><?php esc_html_e('Attributed Revenue', 'pixel-on-wp'); ?></h3>
            <span style="font-size: 28px; font-weight: bold; color: #333;"><?php echo wc_price($total_revenue); ?></span>
          </div>
          <div class="card" style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #d54e21;">
            <h3 style="margin: 0 0 10px; font-weight: 500; color: #666;"><?php esc_html_e('Meta Campaign Spend', 'pixel-on-wp'); ?></h3>
            <span style="font-size: 28px; font-weight: bold; color: #333;"><?php echo wc_price($total_spend); ?></span>
          </div>
          <div class="card" style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid <?php echo ($overall_roas >= 1.0) ? '#46b450' : '#d54e21'; ?>;">
            <h3 style="margin: 0 0 10px; font-weight: 500; color: #666;"><?php esc_html_e('True Campaign ROAS', 'pixel-on-wp'); ?></h3>
            <span style="font-size: 28px; font-weight: bold; color: <?php echo ($overall_roas >= 1.0) ? '#46b450' : '#d54e21'; ?>;"><?php echo $overall_roas > 0 ? esc_html(number_format($overall_roas, 2)) . 'x' : 'N/A'; ?></span>
          </div>
        </div>

        <div class="pixelonwp-metric-cards" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
          <div class="card" style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #72777c;">
            <h3 style="margin: 0 0 10px; font-weight: 500; color: #666;"><?php esc_html_e('CPA (Cost per Acquisition)', 'pixel-on-wp'); ?></h3>
            <span style="font-size: 28px; font-weight: bold; color: #333;"><?php echo wc_price($overall_cpa); ?></span>
          </div>
          <div class="card" style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #72777c;">
            <h3 style="margin: 0 0 10px; font-weight: 500; color: #666;"><?php esc_html_e('AOV (Average Order Value)', 'pixel-on-wp'); ?></h3>
            <span style="font-size: 28px; font-weight: bold; color: #333;"><?php echo wc_price($overall_aov); ?></span>
          </div>
          <div class="card" style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #72777c;">
            <h3 style="margin: 0 0 10px; font-weight: 500; color: #666;"><?php esc_html_e('Ad CTR (Link Clicks)', 'pixel-on-wp'); ?></h3>
            <span style="font-size: 28px; font-weight: bold; color: #333;"><?php echo esc_html(number_format($overall_ctr, 2)) . '%'; ?></span>
          </div>
          <div class="card" style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #72777c;">
            <h3 style="margin: 0 0 10px; font-weight: 500; color: #666;"><?php esc_html_e('CPC (Cost per Click)', 'pixel-on-wp'); ?></h3>
            <span style="font-size: 28px; font-weight: bold; color: #333;"><?php echo wc_price($overall_cpc); ?></span>
          </div>
        </div>

        <!-- Table: Campaigns -->
        <div style="background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
          <h2><?php esc_html_e('Attribution Metrics by Campaign', 'pixel-on-wp'); ?></h2>
          <div class="pixelonwp-responsive-table-wrapper">
            <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top: 15px;">
              <thead>
                <tr>
                  <th><?php esc_html_e('Campaign Name', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Campaign ID', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Orders', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Attributed Revenue', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Ad Spend', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('CPA', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('AOV', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Real ROAS', 'pixel-on-wp'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($campaign_stats)) : ?>
                  <?php foreach ($campaign_stats as $id => $data) : 
                    $spend = (float)$data['spend'];
                    $rev = (float)$data['revenue'];
                    $roas = $spend > 0 ? ($rev / $spend) : 0.0;
                    $cpa = $data['conversions'] > 0 ? ($spend / $data['conversions']) : 0.0;
                    $aov = $data['conversions'] > 0 ? ($rev / $data['conversions']) : 0.0;
                    $roas_color = ($roas >= 1.0) ? '#46b450' : '#d54e21';
                    ?>
                    <tr>
                      <td><strong><?php echo esc_html($data['name']); ?></strong></td>
                      <td><code style="font-size: 11px;"><?php echo esc_html($id); ?></code></td>
                      <td style="text-align: right;"><?php echo esc_html($data['conversions']); ?></td>
                      <td style="text-align: right; font-weight: 600;"><?php echo wc_price($rev); ?></td>
                      <td style="text-align: right; color: #d54e21;"><?php echo wc_price($spend); ?></td>
                      <td style="text-align: right;"><?php echo wc_price($cpa); ?></td>
                      <td style="text-align: right;"><?php echo wc_price($aov); ?></td>
                      <td style="text-align: right; font-weight: bold; color: <?php echo esc_attr($roas_color); ?>;">
                        <?php echo $spend > 0 ? esc_html(number_format($roas, 2)) . 'x' : 'N/A'; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else : ?>
                  <tr><td colspan="8" style="text-align: center; color: #999;"><?php esc_html_e('No campaign data found.', 'pixel-on-wp'); ?></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <!-- Table: Products -->
        <div style="background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
          <h2><?php esc_html_e('Product Performance Attribution', 'pixel-on-wp'); ?></h2>
          <div class="pixelonwp-responsive-table-wrapper">
            <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top: 15px;">
              <thead>
                <tr>
                  <th><?php esc_html_e('Product SKU', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Product Name', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Units Sold via Ads', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Generated Revenue', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Associated Campaign ID', 'pixel-on-wp'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($product_stats)) : ?>
                  <?php foreach ($product_stats as $key => $p_data) : ?>
                    <tr>
                      <td><code><?php echo esc_html($p_data['sku']); ?></code></td>
                      <td><strong><?php echo esc_html($p_data['name']); ?></strong></td>
                      <td style="text-align: right;"><?php echo esc_html($p_data['qty']); ?></td>
                      <td style="text-align: right; font-weight: 600;"><?php echo wc_price($p_data['revenue']); ?></td>
                      <td><code style="font-size: 11px;"><?php echo esc_html($p_data['campaign_id']); ?></code></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else : ?>
                  <tr><td colspan="5" style="text-align: center; color: #999;"><?php esc_html_e('No product conversion attribution logs found.', 'pixel-on-wp'); ?></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <?php
  }

  /**
   * Routes form POST submissions for dashboard configuration sub-tabs.
   *
   * @return void
   */
  private function handle_form_submissions(): void {
    // 1. Settings tab save
    if (isset($_POST['pixelonwp_roas_settings_nonce']) && wp_verify_nonce(wp_unslash($_POST['pixelonwp_roas_settings_nonce']), 'pixelonwp_save_roas_settings')) {
      update_option('pixelonwp_meta_access_token', sanitize_text_field(wp_unslash($_POST['meta_access_token'])));
      update_option('pixelonwp_meta_ad_account_id', sanitize_text_field(wp_unslash($_POST['meta_ad_account_id'])));
      update_option('pixelonwp_base_ad_currency', sanitize_text_field(wp_unslash($_POST['pixelonwp_base_ad_currency'])));

      $rates = [];
      if (isset($_POST['exchange_rates']) && is_array($_POST['exchange_rates'])) {
        foreach ($_POST['exchange_rates'] as $pair => $rate) {
          $rates[sanitize_text_field($pair)] = (float)$rate;
        }
      }
      update_option('pixelonwp_currency_exchange_rates', $rates);
      
      echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Meta Integration credentials updated.', 'pixel-on-wp') . '</p></div>';
    }

    // 2. Automation tab save
    if (isset($_POST['pixelonwp_automation_nonce']) && wp_verify_nonce(wp_unslash($_POST['pixelonwp_automation_nonce']), 'pixelonwp_save_automation')) {
      update_option('pixelonwp_enable_autoscale', isset($_POST['enable_autoscale']) ? '1' : '0');
      update_option('pixelonwp_enable_budgetcut', isset($_POST['enable_budgetcut']) ? '1' : '0');
      update_option('pixelonwp_enable_stock_pauser', isset($_POST['enable_stock_pauser']) ? '1' : '0');
      update_option('pixelonwp_autoscale_threshold', (float)$_POST['autoscale_threshold']);
      update_option('pixelonwp_budgetcut_threshold', (float)$_POST['budgetcut_threshold']);

      echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Automation rules successfully saved.', 'pixel-on-wp') . '</p></div>';
    }

    // 3. Custom Audiences tab save / manual VIP sync
    if (isset($_POST['pixelonwp_audiences_nonce']) && wp_verify_nonce(wp_unslash($_POST['pixelonwp_audiences_nonce']), 'pixelonwp_save_audiences')) {
      update_option('pixelonwp_meta_vip_audience_id', sanitize_text_field(wp_unslash($_POST['meta_vip_audience_id'])));
      update_option('pixelonwp_vip_spend_threshold', (float)$_POST['vip_spend_threshold']);
      update_option('pixelonwp_meta_abandon_audience_id', sanitize_text_field(wp_unslash($_POST['meta_abandon_audience_id'])));
      update_option('pixelonwp_meta_purchasers_audience_id', sanitize_text_field(wp_unslash($_POST['meta_purchasers_audience_id'])));

      if (isset($_POST['sync_vip_now'])) {
        $vip_synced = PixelOnWP_Audience_Sync::sync_vip_segment();
        if (is_wp_error($vip_synced)) {
          echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($vip_synced->get_error_message()) . '</p></div>';
        } else {
          echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Custom Audience VIP list manually synced. Recieved %1$d users.', 'pixel-on-wp'), $vip_synced) . '</p></div>';
        }
      } else {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Audience configuration successfully saved.', 'pixel-on-wp') . '</p></div>';
      }
    }

    // 4. Force sync action on dashboard top panel
    if (isset($_POST['roas_sync_now_btn']) && isset($_POST['pixelonwp_sync_nonce']) && wp_verify_nonce(wp_unslash($_POST['pixelonwp_sync_nonce']), 'pixelonwp_roas_sync_now')) {
      $cron = new PixelOnWP_Cron_Sync();
      $cron->perform_manual_sync();
      echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Insights cache successfully synced and rebuilt.', 'pixel-on-wp') . '</p></div>';
    }
  }
}
