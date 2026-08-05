<?php
/**
 * AI Provider Router.
 *
 * Smart routing between AI providers with automatic fallback:
 * User Gemini → User ChatGPT → Inbuilt Gemini → null (caller handles dummy)
 *
 * @package PixelOnWP\Includes\Ai
 */

namespace PixelOnWP\Includes\Ai;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_AI_Provider
{
    /**
     * Generate AI response using the best available provider.
     *
     * Tries providers in order:
     * 1. User-provided Gemini API key
     * 2. User-provided ChatGPT API key
     * 3. Inbuilt Gemini keys (existing rotation system)
     * 4. Returns null (caller should use dummy data)
     *
     * @param string $prompt      The text prompt to send.
     * @param float  $temperature Temperature for generation.
     * @param bool   $json_mode   Whether to request JSON output.
     * @param int    $timeout     Request timeout in seconds.
     * @return array|null Parsed JSON response or null on failure.
     */
    public static function generate(string $prompt, float $temperature = 0.4, bool $json_mode = true, int $timeout = 25): ?array
    {
        // 1. Try user-provided Gemini key first
        $user_gemini_key = get_option('pixelonwp_gemini_api_key', '');
        if (!empty($user_gemini_key)) {
            $result = PixelOnWP_Gemini_Client::generate_with_key($user_gemini_key, $prompt, $temperature, $json_mode, $timeout);
            if ($result && !isset($result['raw_text'])) {
                return $result;
            }
        }

        // 2. Try user-provided ChatGPT key
        if (PixelOnWP_ChatGPT_Client::is_available()) {
            $result = PixelOnWP_ChatGPT_Client::generate($prompt, $temperature, $json_mode, $timeout);
            if ($result && !isset($result['raw_text'])) {
                return $result;
            }
        }

        // 3. Try inbuilt Gemini keys (existing rotation system)
        $result = PixelOnWP_Gemini_Client::generate($prompt, $temperature, $json_mode, $timeout);
        if ($result && !isset($result['raw_text'])) {
            return $result;
        }

        // 4. All providers failed — return null so caller uses dummy data
        return null;
    }

    /**
     * Get the current AI provider status for display.
     *
     * @return array Provider status info.
     */
    public static function get_status(): array
    {
        $user_gemini = get_option('pixelonwp_gemini_api_key', '');
        $user_chatgpt = get_option('pixelonwp_chatgpt_api_key', '');

        return [
            'gemini_configured' => !empty($user_gemini),
            'chatgpt_configured' => !empty($user_chatgpt),
            'inbuilt_available' => true, // Always available (may be rate-limited)
            'active_provider' => !empty($user_gemini) ? 'gemini' : (!empty($user_chatgpt) ? 'chatgpt' : 'inbuilt'),
        ];
    }
}
