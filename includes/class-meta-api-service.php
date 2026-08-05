<?php
/**
 * Meta API Service Class.
 *
 * @package PixelOnWP\Includes
 * @since 1.0.0
 */

namespace PixelOnWP\Includes;

if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Meta_Api_Service Class.
 *
 * Integrates with Meta Graph API to retrieve campaign spend, stats, budget management,
 * custom audience synchronizations, and currency rate exchange.
 */
class PixelOnWP_Meta_Api_Service {

  /**
   * Fetch insights from Meta Marketing API.
   *
   * @param string $since Start date in YYYY-MM-DD format.
   * @param string $until End date in YYYY-MM-DD format.
   * @param int $time_increment Time increment breakdown option (0 or 1).
   * @return array Array of insights or WP_Error.
   */
  public static function fetch_campaign_insights(string $since, string $until, int $time_increment = 0): array|\WP_Error {
    $token = trim(get_option('pixelonwp_meta_access_token', ''));
    $ad_account_id = trim(get_option('pixelonwp_meta_ad_account_id', ''));

    if (empty($token) || empty($ad_account_id)) {
      return new \WP_Error('missing_credentials', __('Meta API Credentials are not configured.', 'pixel-on-wp'));
    }

    if (strpos($ad_account_id, 'act_') !== 0) {
      $ad_account_id = 'act_' . $ad_account_id;
    }

    $transient_key = 'pixelonwp_roas_' . md5($ad_account_id . '_' . $since . '_' . $until . '_' . $time_increment);
    $cached_data = get_transient($transient_key);

    if (false !== $cached_data) {
      return $cached_data;
    }

    $url = sprintf('https://graph.facebook.com/v19.0/%s/insights', rawurlencode($ad_account_id));

    $params = [
      'level'        => 'campaign',
      'fields'       => 'campaign_id,campaign_name,spend,account_currency,impressions,inline_link_clicks',
      'time_range'   => wp_json_encode(['since' => $since, 'until' => $until]),
      'limit'        => 500,
      'access_token' => $token,
    ];

    if ($time_increment > 0) {
      $params['time_increment'] = $time_increment;
    }

    $query_url = add_query_arg($params, $url);
    $response = wp_remote_get($query_url, ['timeout' => 30]);

    if (is_wp_error($response)) {
      return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (200 !== $code) {
      $error_msg = $data['error']['message'] ?? __('Unknown Meta API error.', 'pixel-on-wp');
      return new \WP_Error('meta_api_error', $error_msg);
    }

    $results = $data['data'] ?? [];
    set_transient($transient_key, $results, 12 * HOUR_IN_SECONDS);

    return $results;
  }

  /**
   * Helper wrapper to retrieve campaign insights by a date range string or custom dates.
   *
   * @param string|array $date_range Predefined range string ('today', 'last_7_days', etc.) or array ['start_date' => 'Y-m-d', 'end_date' => 'Y-m-d'].
   * @return array Array of campaign insights records, returns empty array on failure.
   */
  public static function get_campaign_insights($date_range): array {
    $since = '';
    $until = '';

    if (is_array($date_range)) {
      $since = $date_range['start_date'] ?? '';
      $until = $date_range['end_date'] ?? '';
    } else {
      switch ($date_range) {
        case 'today':
          $since = date('Y-m-d');
          $until = date('Y-m-d');
          break;
        case 'last_7_days':
          $since = date('Y-m-d', strtotime('-6 days'));
          $until = date('Y-m-d');
          break;
        case 'last_30_days':
        default:
          $since = date('Y-m-d', strtotime('-29 days'));
          $until = date('Y-m-d');
          break;
      }
    }

    if (empty($since) || empty($until)) {
      return [];
    }

    $insights = self::fetch_campaign_insights($since, $until);

    if (is_wp_error($insights) || !is_array($insights)) {
      return [];
    }

    return $insights;
  }

  /**
   * Normalize campaign spend based on currency exchange rate.
   *
   * @param float $spend Spend amount in Ad Account currency.
   * @param string $ad_currency Currency code from Ad Account (e.g. USD).
   * @return float Normalized spend in WooCommerce store currency.
   */
  public static function normalize_currency(float $spend, string $ad_currency): float {
    $store_currency = get_woocommerce_currency();
    $rate = PixelOnWP_Currency_Converter::get_exchange_rate($ad_currency, $store_currency);
    return $spend * $rate;
  }


  /**
   * Scale budget for a Meta Campaign.
   *
   * @param string $campaign_id Meta Campaign ID.
   * @param float $multiplier Multiplier for the budget (e.g. 1.20 for +20%).
   * @return bool|\WP_Error True if success, WP_Error otherwise.
   */
  public static function scale_campaign_budget(string $campaign_id, float $multiplier): bool|\WP_Error {
    $token = trim(get_option('pixelonwp_meta_access_token', ''));
    if (empty($token)) {
      return new \WP_Error('missing_token', __('Access Token is missing.', 'pixel-on-wp'));
    }

    $url = sprintf('https://graph.facebook.com/v19.0/%s', rawurlencode($campaign_id));
    $get_url = add_query_arg(['fields' => 'daily_budget,lifetime_budget', 'access_token' => $token], $url);
    $response = wp_remote_get($get_url, ['timeout' => 15]);

    if (is_wp_error($response)) {
      return $response;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $current_budget = (float)($data['daily_budget'] ?? 0.0);

    if ($current_budget <= 0) {
      return new \WP_Error('no_daily_budget', __('No daily budget found or campaign uses lifetime budget.', 'pixel-on-wp'));
    }

    $new_budget = round($current_budget * $multiplier);

    $post_response = wp_remote_post($url, [
      'body' => [
        'daily_budget' => $new_budget,
        'access_token' => $token,
      ],
      'timeout' => 15,
    ]);

    if (is_wp_error($post_response)) {
      return $post_response;
    }

    $res_data = json_decode(wp_remote_retrieve_body($post_response), true);
    return !empty($res_data['success']);
  }

  /**
   * Pause a Meta campaign or ad set.
   *
   * @param string $id Meta Object ID (Campaign or AdSet).
   * @return bool|\WP_Error True if success, WP_Error otherwise.
   */
  public static function pause_meta_object(string $id): bool|\WP_Error {
    $token = trim(get_option('pixelonwp_meta_access_token', ''));
    if (empty($token)) {
      return new \WP_Error('missing_token', __('Access Token is missing.', 'pixel-on-wp'));
    }

    $url = sprintf('https://graph.facebook.com/v19.0/%s', rawurlencode($id));
    $response = wp_remote_post($url, [
      'body' => [
        'status'       => 'PAUSED',
        'access_token' => $token,
      ],
      'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
      return $response;
    }

    $res_data = json_decode(wp_remote_retrieve_body($response), true);
    return !empty($res_data['success']);
  }

  /**
   * Sync hashed customer data to Meta Custom Audience.
   *
   * @param string $audience_id Meta Custom Audience ID.
   * @param array $hashed_users List of hashed user records.
   * @return int|\WP_Error Number of records received or WP_Error.
   */
  public static function sync_custom_audience(string $audience_id, array $hashed_users): int|\WP_Error {
    $token = trim(get_option('pixelonwp_meta_access_token', ''));
    if (empty($token)) {
      return new \WP_Error('missing_token', __('Access Token is missing.', 'pixel-on-wp'));
    }

    $url = sprintf('https://graph.facebook.com/v19.0/%s/users', rawurlencode($audience_id));

    $payload = [
      'schema' => ['EMAIL', 'PHONE', 'FN', 'LN'],
      'data'   => $hashed_users,
    ];

    $response = wp_remote_post($url, [
      'body' => [
        'payload'      => wp_json_encode($payload),
        'access_token' => $token,
      ],
      'timeout' => 25,
    ]);

    if (is_wp_error($response)) {
      return $response;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['num_received'])) {
      return (int)$data['num_received'];
    }

    $err = $data['error']['message'] ?? __('Failed custom audience sync.', 'pixel-on-wp');
    return new \WP_Error('sync_error', $err);
  }

  /**
   * Perform self-diagnostic test of credentials and permissions.
   *
   * @param string $token Access token.
   * @param string $ad_account Ad Account ID.
   * @return array Status array.
   */
  public static function run_self_diagnostics(string $token, string $ad_account): array {
    if (empty($token) || empty($ad_account)) {
      return ['success' => false, 'message' => __('Credentials are not filled.', 'pixel-on-wp')];
    }

    if (strpos($ad_account, 'act_') !== 0) {
      $ad_account = 'act_' . $ad_account;
    }

    $me_url = add_query_arg(['fields' => 'id,name', 'access_token' => $token], 'https://graph.facebook.com/v19.0/me');
    $me_res = wp_remote_get($me_url);
    if (is_wp_error($me_res)) {
      return ['success' => false, 'message' => __('Graph connection failed: ', 'pixel-on-wp') . $me_res->get_error_message()];
    }

    $me_code = wp_remote_retrieve_response_code($me_res);
    $me_body = json_decode(wp_remote_retrieve_body($me_res), true);
    if (200 !== $me_code) {
      return ['success' => false, 'message' => $me_body['error']['message'] ?? __('Invalid Access Token.', 'pixel-on-wp')];
    }

    $insights_url = add_query_arg([
      'level'        => 'campaign',
      'fields'       => 'campaign_id,spend',
      'limit'        => 1,
      'access_token' => $token,
    ], sprintf('https://graph.facebook.com/v19.0/%s/insights', $ad_account));

    $insights_res = wp_remote_get($insights_url);
    if (is_wp_error($insights_res)) {
      return ['success' => false, 'message' => __('Insights verification failed: ', 'pixel-on-wp') . $insights_res->get_error_message()];
    }

    $ins_code = wp_remote_retrieve_response_code($insights_res);
    $ins_body = json_decode(wp_remote_retrieve_body($insights_res), true);
    if (200 !== $ins_code) {
      return ['success' => false, 'message' => $ins_body['error']['message'] ?? __('Ad Account insights permission denied.', 'pixel-on-wp')];
    }
    // 3. Verify ads_management and ads_read permission scopes
    $perm_url = add_query_arg(['access_token' => $token], 'https://graph.facebook.com/v19.0/me/permissions');
    $perm_res = wp_remote_get($perm_url);
    $ads_read = false;
    $ads_management = false;

    if (!is_wp_error($perm_res) && wp_remote_retrieve_response_code($perm_res) === 200) {
      $perm_body = json_decode(wp_remote_retrieve_body($perm_res), true);
      $perms = $perm_body['data'] ?? [];
      foreach ($perms as $p) {
        if ($p['permission'] === 'ads_read' && $p['status'] === 'granted') {
          $ads_read = true;
        }
        if ($p['permission'] === 'ads_management' && $p['status'] === 'granted') {
          $ads_management = true;
        }
      }
    }

    if (!$ads_read || !$ads_management) {
      $missing = [];
      if (!$ads_read) $missing[] = 'ads_read';
      if (!$ads_management) $missing[] = 'ads_management';
      return [
        'success' => false,
        'message' => sprintf(__('Connected successfully, but missing required permissions: %s.', 'pixel-on-wp'), implode(', ', $missing)),
      ];
    }

    return [
      'success' => true,
      'message' => sprintf(__('Connected to %1$s. All permissions (%2$s) verified successfully.', 'pixel-on-wp'), $me_body['name'], 'ads_read, ads_management'),
    ];
  }
}
