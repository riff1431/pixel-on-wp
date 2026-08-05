<?php
/**
 * Dynamic Currency Converter Service.
 *
 * @package PixelOnWP\Includes
 * @since 1.0.0
 */

namespace PixelOnWP\Includes;

if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Currency_Converter Class.
 *
 * Manages live exchange rate conversions via external API and handles manual overrides.
 */
class PixelOnWP_Currency_Converter {

  /**
   * Fetch live exchange rate from API or cache, with manual user input fallback.
   *
   * @param string $from Source currency (e.g. USD).
   * @param string $to Target WooCommerce store currency (e.g. BDT).
   * @return float Exchange conversion rate.
   */
  public static function get_exchange_rate(string $from, string $to): float {
    $from = strtoupper(trim($from));
    $to = strtoupper(trim($to));

    if (empty($from) || empty($to) || $from === $to) {
      return 1.0;
    }

    // Try fetching from Live API
    $live_rate = self::get_live_exchange_rate($from, $to);
    if ($live_rate > 0.0) {
      return $live_rate;
    }

    // Fallback: Check manual input setting
    $rates = get_option('pixelonwp_currency_exchange_rates', []);
    $rate_key = $from . '_' . $to;
    if (isset($rates[$rate_key]) && (float)$rates[$rate_key] > 0.0) {
      return (float)$rates[$rate_key];
    }

    return 1.0;
  }

  /**
   * Retrieve live exchange rate from Frankfurter API.
   *
   * @param string $from Source currency code.
   * @param string $to Target currency code.
   * @return float Exchange rate, 0.0 on failure.
   */
  public static function get_live_exchange_rate(string $from, string $to): float {
    $from = strtoupper(trim($from));
    $to = strtoupper(trim($to));

    if ($from === $to) {
      return 1.0;
    }

    $transient_key = 'pixelonwp_live_rate_' . $from . '_' . $to;
    $cached_rate = get_transient($transient_key);

    if (false !== $cached_rate) {
      return (float)$cached_rate;
    }

    $url = sprintf('https://api.frankfurter.app/latest?from=%s&to=%s', rawurlencode($from), rawurlencode($to));
    $response = wp_remote_get($url, ['timeout' => 10]);

    if (is_wp_error($response)) {
      return 0.0;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $rate = isset($data['rates'][$to]) ? (float)$data['rates'][$to] : 0.0;

    if ($rate > 0.0) {
      // Store transient for 24 hours
      set_transient($transient_key, $rate, DAY_IN_SECONDS);
      return $rate;
    }

    return 0.0;
  }

  /**
   * Return list of all major worldwide currency ISO codes.
   *
   * @return array Array of currency codes and names.
   */
  public static function get_all_currencies(): array {
    return [
      'USD' => __('USD - US Dollar', 'pixel-on-wp'),
      'BDT' => __('BDT - Bangladeshi Taka', 'pixel-on-wp'),
      'EUR' => __('EUR - Euro', 'pixel-on-wp'),
      'GBP' => __('GBP - British Pound Sterling', 'pixel-on-wp'),
      'AUD' => __('AUD - Australian Dollar', 'pixel-on-wp'),
      'CAD' => __('CAD - Canadian Dollar', 'pixel-on-wp'),
      'INR' => __('INR - Indian Rupee', 'pixel-on-wp'),
      'JPY' => __('JPY - Japanese Yen', 'pixel-on-wp'),
      'CNY' => __('CNY - Chinese Yuan', 'pixel-on-wp'),
      'AED' => __('AED - UAE Dirham', 'pixel-on-wp'),
      'SAR' => __('SAR - Saudi Riyal', 'pixel-on-wp'),
      'CHF' => __('CHF - Swiss Franc', 'pixel-on-wp'),
      'NZD' => __('NZD - New Zealand Dollar', 'pixel-on-wp'),
      'SGD' => __('SGD - Singapore Dollar', 'pixel-on-wp'),
      'HKD' => __('HKD - Hong Kong Dollar', 'pixel-on-wp'),
      'NOK' => __('NOK - Norwegian Krone', 'pixel-on-wp'),
      'KRW' => __('KRW - South Korean Won', 'pixel-on-wp'),
      'TRY' => __('TRY - Turkish Lira', 'pixel-on-wp'),
      'RUB' => __('RUB - Russian Ruble', 'pixel-on-wp'),
      'BRL' => __('BRL - Brazilian Real', 'pixel-on-wp'),
      'ZAR' => __('ZAR - South African Rand', 'pixel-on-wp'),
      'DKK' => __('DKK - Danish Krone', 'pixel-on-wp'),
      'PLN' => __('PLN - Polish Zloty', 'pixel-on-wp'),
      'TWD' => __('TWD - New Taiwan Dollar', 'pixel-on-wp'),
      'THB' => __('THB - Thai Baht', 'pixel-on-wp'),
      'IDR' => __('IDR - Indonesian Rupiah', 'pixel-on-wp'),
      'HUF' => __('HUF - Hungarian Forint', 'pixel-on-wp'),
      'CZK' => __('CZK - Czech Koruna', 'pixel-on-wp'),
      'ILS' => __('ILS - Israeli New Shekel', 'pixel-on-wp'),
      'CLP' => __('CLP - Chilean Peso', 'pixel-on-wp'),
      'PHP' => __('PHP - Philippine Peso', 'pixel-on-wp'),
      'COP' => __('COP - Colombian Peso', 'pixel-on-wp'),
      'MYR' => __('MYR - Malaysian Ringgit', 'pixel-on-wp'),
      'RON' => __('RON - Romanian Leu', 'pixel-on-wp'),
      'PKR' => __('PKR - Pakistani Rupee', 'pixel-on-wp'),
      'KWD' => __('KWD - Kuwaiti Dinar', 'pixel-on-wp'),
      'QAR' => __('QAR - Qatari Riyal', 'pixel-on-wp'),
      'EGP' => __('EGP - Egyptian Pound', 'pixel-on-wp'),
    ];
  }
}
