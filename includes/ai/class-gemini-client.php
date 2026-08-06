<?php
/**
 * Optimized Gemini API Client with automatic key rotation and robust request handling.
 *
 * @package PixelOnWP\Includes\Ai
 */

namespace PixelOnWP\Includes\Ai;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class PixelOnWP_Gemini_Client
{
    public static string $last_error = '';
    private const API_BASES = [
        'https://generativelanguage.googleapis.com/v1beta/models/',
        'https://generativelanguage.googleapis.com/v1/models/'
    ];

    private const FALLBACK_MODELS = [
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-2.0-flash-lite',
        'gemini-1.5-flash-8b',
        'gemini-1.5-pro',
        'gemini-2.5-flash',
        'gemini-2.5-pro',
        'gemini-3.5-flash',
        'gemini-1.5-flash-latest',
        'gemini-pro'
    ];

    /**
     * Clean and parse JSON from Gemini's response (handles markdown fences like ```json ... ```).
     *
     * @param string $text The raw model output.
     * @return array|null The decoded JSON data, or null on failure.
     */
    private static function parse_json_text(string $text): ?array
    {
        $text = trim($text);
        if (empty($text)) {
            return null;
        }

        // Try direct JSON decode
        $json = json_decode($text, true);
        if (is_array($json)) {
            return $json;
        }

        // Strip markdown code fences if present
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);
        $cleaned = trim($cleaned);

        $json = json_decode($cleaned, true);
        if (is_array($json)) {
            return $json;
        }

        // Match the first valid JSON object or array structure
        if (preg_match('/(\{.*\}|\[.*\])/s', $cleaned, $matches)) {
            $json = json_decode($matches[1], true);
            if (is_array($json)) {
                return $json;
            }
        }

        return null;
    }

    /**
     * Retrieve all available Gemini API keys for rotation.
     *
     * @return array Array of keys.
     */
    private static function get_keys(): array
    {
        $custom_key   = get_option('pixelonwp_gemini_api_key', '');
        $constant_key = defined('OMNITRACK_GEMINI_KEY') ? OMNITRACK_GEMINI_KEY : '';
        $env_key      = getenv('GEMINI_API_KEY') ?: '';

        $keys = array_filter([$custom_key, $constant_key, $env_key]);
        return !empty($keys) ? array_values($keys) : [];
    }

    /**
     * Send a prompt to the Gemini API using the key rotation stack.
     *
     * @param string $prompt      The prompt text.
     * @param float  $temperature Generation temperature.
     * @param bool   $json_mode   Whether JSON output format is requested.
     * @param int    $timeout     Request timeout in seconds.
     * @return array|null The decoded response, or null if all keys failed.
     */
    public static function generate(string $prompt, float $temperature = 0.4, bool $json_mode = true, int $timeout = 25): ?array
    {
        $keys = self::get_keys();
        if (empty($keys)) {
            error_log('[PixelOnWP AI] No Gemini keys configured for system rotation.');
            return null;
        }

        foreach ($keys as $key) {
            $res = self::generate_with_key($key, $prompt, $temperature, $json_mode, $timeout);
            if ($res) {
                return $res;
            }
        }

        error_log('[PixelOnWP AI] All Gemini rotation keys exhausted.');
        return null;
    }

    /**
     * Send a prompt to the Gemini API using a specific key.
     *
     * @param string $key         The Gemini API key.
     * @param string $prompt      The prompt text.
     * @param float  $temperature Generation temperature.
     * @param bool   $json_mode   Whether JSON output format is requested.
     * @param int    $timeout     Request timeout in seconds.
     * @return array|null The decoded response, or null on failure.
     */
    public static function generate_with_key(string $key, string $prompt, float $temperature = 0.4, bool $json_mode = true, int $timeout = 25): ?array
    {
        // Clean API key string (strip whitespace, quotes, etc.)
        $clean_key = trim($key);
        $clean_key = trim($clean_key, "'\"\t\n\r\0\x0B");

        if (empty($clean_key)) {
            return null;
        }

        // Structure the request body
        $request_body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $temperature,
            ]
        ];

        if ($json_mode) {
            $request_body['generationConfig']['response_mime_type'] = 'application/json';
        }

        // Loop through API Bases and Models to ensure compatibility
        foreach (self::API_BASES as $api_base) {
            foreach (self::FALLBACK_MODELS as $model) {
                $url = $api_base . $model . ':generateContent?key=' . $clean_key;

                $response = wp_remote_post($url, [
                    'headers'   => ['Content-Type' => 'application/json'],
                    'body'      => wp_json_encode($request_body),
                    'timeout'   => $timeout,
                    'sslverify' => false, // Bypasses SSL validation to ensure compatibility with local servers
                ]);

                if (is_wp_error($response)) {
                    $err_msg = $response->get_error_message();
                    self::$last_error = 'cURL error: ' . $err_msg;
                    error_log('[PixelOnWP AI] Gemini request failed: ' . $err_msg);
                    continue; // Try next model/base
                }

                $http_code = wp_remote_retrieve_response_code($response);
                $resp_body = wp_remote_retrieve_body($response);

                // Handle JSON-mode fallback for older models or configuration mismatches
                if ($http_code === 400 && $json_mode) {
                    $fallback_body = $request_body;
                    unset($fallback_body['generationConfig']['response_mime_type']);

                    $retry = wp_remote_post($url, [
                        'headers'   => ['Content-Type' => 'application/json'],
                        'body'      => wp_json_encode($fallback_body),
                        'timeout'   => $timeout,
                        'sslverify' => false,
                    ]);

                    if (!is_wp_error($retry)) {
                        $http_code = wp_remote_retrieve_response_code($retry);
                        $resp_body = wp_remote_retrieve_body($retry);
                    }
                }

                if ($http_code !== 200) {
                    self::$last_error = 'HTTP ' . $http_code . ': ' . $resp_body;
                    error_log("[PixelOnWP AI] Gemini API model {$model} on {$api_base} returned HTTP {$http_code}: " . substr($resp_body, 0, 150));
                    continue;
                }

                $json = json_decode($resp_body, true);
                if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                    $ai_text = $json['candidates'][0]['content']['parts'][0]['text'];
                    $parsed  = self::parse_json_text($ai_text);

                    if ($parsed) {
                        return $parsed;
                    }

                    // If JSON parsing fails but raw text is generated, return wrapped
                    return ['raw_text' => $ai_text];
                }
            }
        }

        return null;
    }
}
