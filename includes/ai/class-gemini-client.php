<?php
/**
 * Gemini API Client with automatic key rotation.
 * 
 * Tries each API key in order. If one returns 429 (quota exceeded)
 * or 403 (invalid), it automatically moves to the next key.
 *
 * @package PixelOnWP\Includes\Ai
 */

namespace PixelOnWP\Includes\Ai;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_Gemini_Client
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private const MODEL = 'gemini-3.5-flash';

    /**
     * All available API keys for rotation.
     */
    private static function get_keys(): array
    {
        $custom_key = get_option('pixelonwp_gemini_api_key', '');
        $constant_key = defined('OMNITRACK_GEMINI_KEY') ? OMNITRACK_GEMINI_KEY : '';
        $env_key = getenv('GEMINI_API_KEY') ?: '';

        $keys = array_filter([$custom_key, $constant_key, $env_key]);
        return !empty($keys) ? array_values($keys) : [];
    }

    /**
     * Send a prompt to Gemini API with automatic key rotation.
     *
     * @param string $prompt The text prompt to send.
     * @param float  $temperature Temperature for generation (0.0 - 1.0).
     * @param bool   $json_mode Whether to request JSON output.
     * @param int    $timeout Request timeout in seconds.
     * @return array|null Parsed JSON response or null on failure.
     */
    public static function generate(string $prompt, float $temperature = 0.4, bool $json_mode = true, int $timeout = 25): ?array
    {
        $keys = self::get_keys();
        $last_error = '';

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

        $body_json = wp_json_encode($request_body);

        foreach ($keys as $index => $key) {
            $url = self::API_BASE . self::MODEL . ':generateContent?key=' . $key;

            $response = wp_remote_post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => $body_json,
                'timeout' => $timeout,
            ]);

            // Network error — try next key
            if (is_wp_error($response)) {
                $last_error = 'WP Error: ' . $response->get_error_message();
                continue;
            }

            $http_code = wp_remote_retrieve_response_code($response);
            $resp_body = wp_remote_retrieve_body($response);

            // 429 = quota exceeded, 403 = invalid key — try next
            if ($http_code === 429 || $http_code === 403) {
                $last_error = "Key #{$index} returned HTTP {$http_code}";
                continue;
            }

            // 404 = model not found — no point trying other keys
            if ($http_code === 404) {
                $last_error = "Model not found (404)";
                break;
            }

            // Any other non-200 — try next
            if ($http_code !== 200) {
                $last_error = "Key #{$index} returned HTTP {$http_code}";
                continue;
            }

            // Parse the response
            $json = json_decode($resp_body, true);

            if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                $ai_text = $json['candidates'][0]['content']['parts'][0]['text'];
                $ai_json = json_decode($ai_text, true);

                if ($ai_json) {
                    return $ai_json;
                }

                // If not valid JSON but we got text, return it wrapped
                return ['raw_text' => $ai_text];
            }

            $last_error = "Key #{$index}: Could not parse AI response";
        }

        // All keys exhausted
        error_log('[PixelOnWP AI] All API keys exhausted. Last error: ' . $last_error);
        return null;
    }

    /**
     * Send a prompt using a specific API key (for user-provided keys).
     *
     * @param string $key         The API key to use.
     * @param string $prompt      The text prompt to send.
     * @param float  $temperature Temperature for generation.
     * @param bool   $json_mode   Whether to request JSON output.
     * @param int    $timeout     Request timeout in seconds.
     * @return array|null Parsed JSON response or null on failure.
     */
    public static function generate_with_key(string $key, string $prompt, float $temperature = 0.4, bool $json_mode = true, int $timeout = 25): ?array
    {
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

        $url = self::API_BASE . self::MODEL . ':generateContent?key=' . $key;

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($request_body),
            'timeout' => $timeout,
        ]);

        if (is_wp_error($response)) {
            error_log('[PixelOnWP AI] User Gemini key WP Error: ' . $response->get_error_message());
            return null;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code !== 200) {
            error_log('[PixelOnWP AI] User Gemini key returned HTTP ' . $http_code);
            return null;
        }

        $resp_body = wp_remote_retrieve_body($response);
        $json = json_decode($resp_body, true);

        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_text = $json['candidates'][0]['content']['parts'][0]['text'];
            $ai_json = json_decode($ai_text, true);

            if ($ai_json) {
                return $ai_json;
            }

            return ['raw_text' => $ai_text];
        }

        return null;
    }
}
