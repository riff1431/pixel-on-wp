<?php
/**
 * ChatGPT (OpenAI) API Client.
 *
 * Provides a static generate() method matching the Gemini client interface,
 * so all AI consumers can switch providers transparently.
 *
 * @package PixelOnWP\Includes\Ai
 */

namespace PixelOnWP\Includes\Ai;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_ChatGPT_Client
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';
    private const MODEL = 'gpt-4o-mini';

    /**
     * Get the user-provided OpenAI API key.
     */
    private static function get_key(): string
    {
        return get_option('pixelonwp_chatgpt_api_key', '');
    }

    /**
     * Check if a user-provided key is available.
     */
    public static function is_available(): bool
    {
        return !empty(self::get_key());
    }

    /**
     * Send a prompt to OpenAI ChatGPT API.
     *
     * @param string $prompt     The text prompt to send.
     * @param float  $temperature Temperature for generation (0.0 - 2.0).
     * @param bool   $json_mode  Whether to request JSON output.
     * @param int    $timeout    Request timeout in seconds.
     * @return array|null Parsed JSON response or null on failure.
     */
    public static function generate(string $prompt, float $temperature = 0.4, bool $json_mode = true, int $timeout = 25): ?array
    {
        $key = self::get_key();
        if (empty($key)) {
            return null;
        }

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful AI assistant. Always respond with valid JSON when asked.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        $request_body = [
            'model' => self::MODEL,
            'messages' => $messages,
            'temperature' => $temperature,
        ];

        if ($json_mode) {
            $request_body['response_format'] = ['type' => 'json_object'];
        }

        $response = wp_remote_post(self::API_URL, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $key,
            ],
            'body' => wp_json_encode($request_body),
            'timeout' => $timeout,
        ]);

        if (is_wp_error($response)) {
            error_log('[PixelOnWP AI] ChatGPT WP Error: ' . $response->get_error_message());
            return null;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $resp_body = wp_remote_retrieve_body($response);

        if ($http_code !== 200) {
            error_log('[PixelOnWP AI] ChatGPT returned HTTP ' . $http_code . ': ' . $resp_body);
            return null;
        }

        $json = json_decode($resp_body, true);

        if (isset($json['choices'][0]['message']['content'])) {
            $ai_text = $json['choices'][0]['message']['content'];
            $ai_json = json_decode($ai_text, true);

            if ($ai_json) {
                return $ai_json;
            }

            // If not valid JSON but we got text, return it wrapped
            return ['raw_text' => $ai_text];
        }

        error_log('[PixelOnWP AI] ChatGPT: Could not parse response');
        return null;
    }
}
