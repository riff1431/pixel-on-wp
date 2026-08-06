<?php
/**
 * OpenRouter API Client.
 *
 * Provides access to free OpenRouter models (like Llama 3 8B) for zero-cost AI generation.
 *
 * @package PixelOnWP\Includes\Ai
 */

namespace PixelOnWP\Includes\Ai;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class PixelOnWP_OpenRouter_Client
{
    public static string $last_error = '';
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    
    private const FALLBACK_MODELS = [
        'nvidia/nemotron-nano-9b-v2:free',
        'google/gemma-4-31b-it:free',
        'openai/gpt-oss-20b:free',
        'openrouter/free'
    ];

    /**
     * Get the user-provided OpenRouter API key.
     */
    private static function get_key(): string
    {
        return get_option('pixelonwp_openrouter_api_key', '');
    }

    /**
     * Check if a user-provided key is available.
     */
    public static function is_available(): bool
    {
        $key = self::get_key();
        return !empty($key);
    }

    /**
     * Helper to clean and parse JSON from the text response.
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

        // Match first valid JSON object or array structure
        if (preg_match('/(\{.*\}|\[.*\])/s', $cleaned, $matches)) {
            $json = json_decode($matches[1], true);
            if (is_array($json)) {
                return $json;
            }
        }

        return null;
    }

    /**
     * Send a prompt to OpenRouter API.
     *
     * @param string $prompt      The prompt text.
     * @param float  $temperature Temperature for generation.
     * @param bool   $json_mode   Whether JSON output is requested.
     * @param int    $timeout     Request timeout in seconds.
     * @return array|null Parsed JSON response, or null on failure.
     */
    public static function generate(string $prompt, float $temperature = 0.4, bool $json_mode = true, int $timeout = 60, string $custom_key = ''): ?array
    {
        $key = !empty($custom_key) ? $custom_key : self::get_key();
        if (empty($key)) {
            return null;
        }

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an expert direct-response copywriter. You must respond with valid JSON only. Do not wrap the JSON output in markdown formatting or code blocks.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        foreach (self::FALLBACK_MODELS as $model) {
            $request_body = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ];

            $response = wp_remote_post(self::API_URL, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $key,
                    'HTTP-Referer' => esc_url_raw(home_url()),
                    'X-Title' => 'PixelOnWP'
                ],
                'body' => wp_json_encode($request_body),
                'timeout' => $timeout,
                'sslverify' => false,
            ]);

            if (is_wp_error($response)) {
                $err_msg = $response->get_error_message();
                self::$last_error = 'cURL error: ' . $err_msg;
                error_log('[PixelOnWP AI] OpenRouter model ' . $model . ' failed: ' . $err_msg);
                continue; // Try next model
            }

            $http_code = wp_remote_retrieve_response_code($response);
            $resp_body = wp_remote_retrieve_body($response);

            if ($http_code !== 200) {
                self::$last_error = 'HTTP ' . $http_code . ': ' . $resp_body;
                error_log('[PixelOnWP AI] OpenRouter model ' . $model . ' returned HTTP ' . $http_code . ': ' . $resp_body);
                continue; // Try next model
            }

            $json = json_decode($resp_body, true);

            if (isset($json['choices'][0]['message']['content'])) {
                $ai_text = $json['choices'][0]['message']['content'];
                $ai_json = self::parse_json_text($ai_text);

                if ($ai_json) {
                    return $ai_json;
                }

                error_log('[PixelOnWP AI] OpenRouter JSON parsing failed for response: ' . $ai_text);
                return ['raw_text' => $ai_text];
            }
        }

        error_log('[PixelOnWP AI] OpenRouter: All models exhausted or failed to return valid completion');
        return null;
    }
}
