<?php
/**
 * AI Ad Generator Class for generating Ad copy via AI providers.
 *
 * @package PixelOnWP\Includes\Ai
 */

namespace PixelOnWP\Includes\Ai;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_Ad_Generator
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=';

    public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
    {
        $loader->add_action('wp_ajax_pixelonwp_get_wc_products', $this, 'get_wc_products');
        $loader->add_action('wp_ajax_pixelonwp_generate_ad_copy', $this, 'generate_ad_copy');
    }

    public function get_wc_products(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'PixelOnWP_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        $dummy_products = [
            101 => ['id' => 101, 'name' => 'Premium Wireless Headphones', 'price' => 199.99, 'description' => 'High-fidelity noise-canceling wireless headphones with 40-hour battery life.', 'categories' => ['Electronics', 'Audio']],
            102 => ['id' => 102, 'name' => 'Ergonomic Desk Chair', 'price' => 249.00, 'description' => 'Adjustable ergonomic office chair with lumbar support and breathable mesh.', 'categories' => ['Furniture', 'Office']],
            103 => ['id' => 103, 'name' => 'Organic Matcha Green Tea', 'price' => 29.95, 'description' => 'Ceremonial grade organic matcha sourced directly from Japan.', 'categories' => ['Health', 'Groceries']]
        ];

        if (!class_exists('WooCommerce')) {
            $result = [];
            foreach ($dummy_products as $dp) {
                $result[] = [
                    'id' => $dp['id'],
                    'name' => $dp['name'],
                    'price' => '$' . $dp['price'],
                    'image' => wc_placeholder_img_src('thumbnail')
                ];
            }
            wp_send_json_success($result);
        }

        $args = ['status' => 'publish', 'limit' => -1];
        $products = wc_get_products($args);
        $result = [];

        foreach ($products as $product) {
            $image_id = $product->get_image_id();
            $image_url = '';
            if ($image_id) {
                $src = wp_get_attachment_image_src($image_id, 'thumbnail');
                if ($src && isset($src[0])) {
                    $image_url = $src[0];
                }
            }
            if (empty($image_url) && function_exists('wc_placeholder_img_src')) {
                $image_url = wc_placeholder_img_src('thumbnail');
            }
            
            // Clean title string: remove HTML, extra quotes, slashes, and artifacts
            $raw_name = $product->get_name();
            $clean_name = wp_strip_all_tags($raw_name);
            $clean_name = str_replace(["'\" />", '" />', "'/>", '"/>', "'", '"'], '', $clean_name);
            $clean_name = trim($clean_name);

            // Clean price string: decode HTML entities (e.g. &#2547; or &nbsp;) into clean currency text
            $raw_price = wc_price($product->get_price());
            $clean_price = html_entity_decode(wp_strip_all_tags($raw_price), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $clean_price = trim(str_replace("\xc2\xa0", ' ', $clean_price));

            $result[] = [
                'id' => $product->get_id(),
                'name' => $clean_name ?: 'Untitled Product',
                'price' => $clean_price,
                'image' => esc_url_raw($image_url)
            ];
        }

        wp_send_json_success($result);
    }

    public function generate_ad_copy(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'PixelOnWP_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $regen_count = isset($_POST['regen_count']) ? intval($_POST['regen_count']) : 0;
        
        $dummy_products = [
            101 => ['id' => 101, 'name' => 'Premium Wireless Headphones', 'price' => 199.99, 'description' => 'High-fidelity noise-canceling wireless headphones with 40-hour battery life.', 'categories' => ['Electronics', 'Audio']],
            102 => ['id' => 102, 'name' => 'Ergonomic Desk Chair', 'price' => 249.00, 'description' => 'Adjustable ergonomic office chair with lumbar support and breathable mesh.', 'categories' => ['Furniture', 'Office']],
            103 => ['id' => 103, 'name' => 'Organic Matcha Green Tea', 'price' => 29.95, 'description' => 'Ceremonial grade organic matcha sourced directly from Japan.', 'categories' => ['Health', 'Groceries']]
        ];

        if (!$product_id) {
            wp_send_json_error(['message' => 'Invalid product']);
        }

        if (!class_exists('WooCommerce') || isset($dummy_products[$product_id])) {
            if (!isset($dummy_products[$product_id])) {
                wp_send_json_error(['message' => 'Dummy Product not found']);
            }
            $product_data = $dummy_products[$product_id];
        } else {
            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error(['message' => 'Product not found']);
            }
            $product_data = [
                'name' => $product->get_name(),
                'description' => wp_strip_all_tags($product->get_short_description()),
                'price' => $product->get_price(),
                'categories' => wp_list_pluck(get_the_terms($product->get_id(), 'product_cat'), 'name')
            ];
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pixelonwp_visitor_intelligence';
        $recent_searches = [];
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name) {
            $logs = $wpdb->get_results("SELECT activity_log FROM {$table_name} ORDER BY last_active DESC LIMIT 20", ARRAY_A);
            foreach ($logs as $log_row) {
                $log = json_decode($log_row['activity_log'], true);
                if (!empty($log['searches'])) {
                    foreach ($log['searches'] as $s) {
                        $recent_searches[] = $s['query'];
                    }
                }
            }
        }
        $recent_searches = array_unique($recent_searches);

        $categories_str = implode(', ', $product_data['categories']);
        $recent_searches_str = wp_json_encode(array_slice($recent_searches, 0, 10));

        $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : 'meta';

        $base_prompt = <<<EOD
System Role: You are an elite, world-class Media Buyer, Chief Marketing Officer (CMO), and Direct-Response Copywriter with 15+ years of experience. Your task is to process the selected product and platform, and generate enterprise-grade, fully working advertising strategies and production-ready ad copies.

CRITICAL ARCHITECTURE RULES:
1. When a user selects a specific platform (Meta, TikTok, or Google), you must generate AT LEAST 6+ DISTINCT STRATEGY CARDS for that platform, plus 1 special "★ RECOMMENDED" card pinned at the top that represents the most accurate, high-converting strategy based on the product.
2. Every single card must strictly follow its respective platform's multi-point professional structure (covering Header, Audience, Creative Strategy, Full Ad Copies, Optimization, Scaling, Retargeting, Tracking, and KPIs/Metrics) as outlined below. Never use generic filler text; make every copy and strategy fully actionable and realistic for 2026 digital marketing standards.

Selected Product Details:
- Title: {$product_data['name']}
- Description: {$product_data['description']}
- Category: {$categories_str}
- Price: \${$product_data['price']}

Website Pixel & Visitor Intelligence Data:
- Recent Website Search Trends: {$recent_searches_str}

EOD;

        if ($platform === 'google') {
            $platform_prompt = <<<EOD
PLATFORM: GOOGLE ADS
========================
Generate 10 separate strategy cards (one for EACH category below) PLUS 1 "Recommended Strategy" card:
1. Performance Max (PMax) - Omnichannel Asset Group Dominance
2. High-Intent Commercial Search Campaign (Exact & Phrase Match)
3. Responsive Search Ads (RSA) - Problem/Solution Angle
4. Shopping Campaign - Product Feed Optimization
5. Competitor Conquesting Search Strategy
6. Brand Defense & Retargeting Search
7. YouTube In-Stream Video - Direct Response Hook
8. Display Remarketing - Dynamic Banner Ads
9. Local / Geotargeted Intent Search
10. Bottom-of-Funnel Dynamic Search Ads (DSA)

SECTION 3: GOOGLE ADS STRUCTURE (Must be applied to all Google cards)
====================================================================
- CARD HEADER: Platform Name, Campaign Type, Campaign Objective, Funnel Stage, Business Type, Product Category, Budget Range.
- SEARCH INTENT: Informational, Commercial, Transactional, Navigational.
- KEYWORD STRATEGY: High-intent, Long-tail, Competitor, Brand, and Negative Keywords matrix.
- CREATIVE / ASSETS: Headlines (15 variations), Descriptions (4 variations), Callouts, Structured Snippets, Sitelinks, Product Feed, Video Assets.
- AUDIENCE & OPTIMIZATION: Audience Signals, Customer Match, Remarketing, Quality Score, Impression Share, CTR, CPC, CPA, ROAS, Search Term Report, Asset Performance.
- SCALING, RETARGETING & TRACKING: Budget/Bid Scaling, Keyword Expansion, Cart Abandoners, Past Customers, GA4, GTM, Enhanced Conversions, Google Tag, Server-side Tracking.
- LANDING PAGE: Hero Section, Offer, CTA, Trust Signals, FAQ.

EOD;
        } elseif ($platform === 'tiktok') {
            $platform_prompt = <<<EOD
PLATFORM: TIKTOK ADS
========================
Generate 10 separate strategy cards (one for EACH category below) PLUS 1 "Recommended Strategy" card:
1. Spark Ads - Organic Post Amplification
2. In-Feed Native UGC - 'TikTok Made Me Buy It' Style
3. POV Problem-Solver Video Strategy
4. Before & After Transformation Showcase
5. GRWM (Get Ready With Me) Product Integration
6. Creator Duet & Green Screen Social Proof
7. Top-of-Funnel Trend Jacking & Viral Hook Loop
8. Bottom-of-Funnel Limited-Time Offer & Urgency
9. Retargeting - Product Viewers & Video Viewers
10. Interactive Curiosity Loop & Unboxing Series

SECTION 2: TIKTOK ADS STRUCTURE (Must be applied to all TikTok cards)
====================================================================
- CARD HEADER: Platform Name, Campaign Objective, Funnel Stage, Product Category, Budget Range.
- AUDIENCE & CREATIVE: Broad/Interest/Lookalike/Retargeting Audiences, Creative Type, UGC Style, Creator Style, Spark Ads, Storytelling Angle, Product Demo.
- VIDEO STRUCTURE (Second-by-Second): Hook (0-3s), Problem (3-10s), Product Demo (10-20s), Benefits (20-30s), Social Proof (30-40s), CTA (40-60s).
- AD COPY: Caption, Short Script, Medium Script, Long Script, Hooks (10 variations), CTA Variations.
- OPTIMIZATION & SCALING: Hook Rate, Hold Rate, Watch Time, Completion Rate, CTR, CPA, ROAS, Creative Refresh Rules, Horizontal/Vertical Scaling, Budget Rules.
- RETARGETING & TRACKING: Video/Product Viewers, ATC, Existing Customers, TikTok Pixel, Events API, GA4, GTM, Server-side Tracking.

EOD;
        } else {
            $platform_prompt = <<<EOD
PLATFORM: META ADS (Facebook & Instagram)
========================
Generate 10 separate strategy cards (one for EACH category below) PLUS 1 "Recommended Strategy" card:
1. Advantage+ Shopping Campaign (ASC) - Broad Scale
2. Top-of-Funnel (TOF) - Cold Prospecting (Problem-Agitate-Solve)
3. Mid-of-Funnel (MOF) - Consideration & Founder Story
4. Bottom-of-Funnel (BOF) - Conversion & Direct Offer
5. Advantage+ Catalog Ads / Dynamic Product Ads (DPA)
6. Lookalike Audience Scaling (1% to 5% LAL)
7. Retargeting - Add-to-Cart / Cart Abandoner Recovery
8. UGC & Creator Partnership Ads (Whitelisting / Partnership)
9. Carousel Engagement & Multi-Angle Product Story
10. Flash Sale / Urgency & Scarcity Campaign

SECTION 1: META ADS STRUCTURE (Must be applied to all Meta cards)
====================================================================
- CARD HEADER: Platform Name, Campaign Objective, Campaign Type, Funnel Stage (TOF/MOF/BOF), Business Type, Product Category, Budget Range.
- AUDIENCE: Target Audience, Broad/Interest/Lookalike, Custom Audience, Retargeting Audience, Audience Exclusions.
- CREATIVE STRATEGY: Creative Format, Hook Strategy, Story Angle, Offer Strategy, CTA Strategy, UGC Strategy, Carousel Strategy, Video Duration.
- AD COPY: Primary Text (Short), Primary Text (Medium), Primary Text (Long), Headlines (5 variations), Hooks (10 variations), CTA Variations (5 variations).
- OPTIMIZATION & SCALING: Optimization Goal, Winning Metrics, Pause Conditions, Creative Refresh Rules, A/B Testing, Horizontal & Vertical Scaling, Budget Rules.
- RETARGETING & TRACKING: Video Viewers, Website Visitors, ATC, IC, Existing Customers, Meta Pixel, CAPI, GA4, GTM, Server-side Tracking.
- KPI BENCHMARKS: CPM, CTR, CPC, CPA, ROAS, Frequency.

EOD;
        }

        $instructions = <<<EOD
PRO-LEVEL OPTIONAL METRICS TO INCLUDE PER CARD:
- Competitor Analysis, Seasonality Impact, AI Score (1-10), Market Saturation, Creative Fatigue Score, Risk Analysis, Expected Learning Phase, Budget Efficiency Score.

REGENERATION & ITERATION CONTROL
If regen_count (Currently: {$regen_count}) is greater than 0, do not repeat the same copies. Provide distinct, highly accurate 100% new variations. Increment the psychological intensity, test bolder pattern-interrupt hooks, and provide sharper, deeply customized direct-response variants based on the iteration number.

Return ONLY a strict JSON object (NO markdown wrappers) where the keys are short, unique category names (e.g. "asc_broad", "tof_cold", "recommended_strategy") and the values are strings representing the meticulously formatted card (with line breaks) following the EXACT STRUCTURE provided above.

Example JSON structure:
{
    "recommended_strategy": "CARD HEADER\\nPlatform: Meta...\\n\\nAUDIENCE\\n...",
    "asc_broad": "CARD HEADER\\nPlatform: Meta...\\n\\nAUDIENCE\\n...",
    "tof_cold": "CARD HEADER\\nPlatform: Meta...\\n\\nAUDIENCE\\n..."
}
EOD;

        $prompt = $base_prompt . $platform_prompt . $instructions;

        // Use AI Provider router
        $ai_json = PixelOnWP_AI_Provider::generate($prompt, 0.7);

        if ($ai_json && !isset($ai_json['raw_text'])) {
            wp_send_json_success($ai_json);
        }

        // Active provider / API key invalid or failed — return explicit error
        $status = PixelOnWP_AI_Provider::get_status();
        $active = $status['active_provider'];
        
        $error_msg = __('AI generation failed. ', 'pixel-on-wp');
        if ($active === 'chatgpt') {
            $error_msg .= __('Your OpenAI ChatGPT API key is invalid or quota exceeded. Please check your API configuration.', 'pixel-on-wp');
        } elseif ($active === 'gemini') {
            $error_msg .= __('Your Google Gemini API key is invalid or quota exceeded. Please check your API configuration.', 'pixel-on-wp');
        } else {
            $error_msg .= __('Inbuilt AI rate limit exceeded or service unavailable. Please configure your own API key in settings.', 'pixel-on-wp');
        }

        wp_send_json_error(['message' => $error_msg, 'provider' => $active]);
    }
}
