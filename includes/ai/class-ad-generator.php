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
                $result[] = ['id' => $dp['id'], 'name' => $dp['name'], 'price' => '$' . $dp['price']];
            }
            wp_send_json_success($result);
        }

        $products = wc_get_products($args);
        $result = [];

        foreach ($products as $product) {
            $result[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => wc_price($product->get_price())
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

        if ($ai_json) {
            wp_send_json_success($ai_json);
        }

        // AI failed — return dummy ad copy based on product name
        $name = $product_data['name'];
        $price = $product_data['price'];
        
        $dummy_response = [
            'is_demo' => true,
            'recommended_strategy' => "CARD HEADER\nPlatform: {$platform} Ads\nCampaign Objective: Conversions / Sales\nCampaign Type: Performance Max / DPA\nFunnel Stage: BOF (Bottom of Funnel)\nBusiness Type: E-commerce\nProduct Category: {$categories_str}\nBudget Range: \$50-\$100/day\n\nAUDIENCE\nTarget Audience: High-intent buyers interested in lifestyle upgrades.\nBroad/Interest/Lookalike: Broad + 1% Lookalike of Purchasers.\nCustom Audience: Website visitors (30 days).\nRetargeting Audience: Cart abandoners (7 days).\nAudience Exclusions: Past purchasers (30 days).\n\nCREATIVE STRATEGY\nCreative Format: Dynamic Product Ads (DPA) + Short-form Video\nHook Strategy: Problem-Solution pattern interrupt.\nStory Angle: Stop overpaying for inferior products.\nOffer Strategy: 10% off first purchase.\nCTA Strategy: Shop Now.\nUGC Strategy: Unboxing + Testimonial.\nCarousel Strategy: Highlight 3 core benefits.\nVideo Duration: 15-30s.\n\nAD COPY\nPrimary Text (Short): Upgrade your routine with {$name}. Only \${$price} today.\nPrimary Text (Medium): Stop settling for less. {$name} is engineered for peak performance and everyday reliability. Get yours for \${$price} plus free shipping!\nPrimary Text (Long): We all know the frustration of buying something that just doesn't last. That's exactly why we built {$name}. It's designed to deliver premium quality without the insane markup. Over 10,000 customers have already upgraded. Will you? Click to claim your exclusive \${$price} offer today.\nHeadlines (5 variations):\n1. 🔥 {$name} — Finally Back in Stock!\n2. The Ultimate Upgrade: {$name}\n3. Stop Overpaying. Try {$name} Today.\n4. Engineered for Perfection.\n5. Don't Miss Out: \${$price} Special.\nHooks (10 variations):\n1. You've been doing it wrong this whole time.\n2. Stop scrolling if you want to fix [problem].\n3. I can't believe I didn't buy this sooner.\n4. TikTok made me buy it and I have zero regrets.\n5. If you struggle with [Problem], you NEED this.\n6. Things in my room that just make sense.\n7. I tested the viral {$name} so you don't have to.\n8. Don't buy [competitor] until you see this.\n9. The secret to [benefit] is finally here.\n10. Why is everyone obsessed with this?\nCTA Variations (5 variations): Shop Now, Claim Offer, Get Yours, Discover More, Try Risk-Free.\n\nOPTIMIZATION & SCALING\nOptimization Goal: Maximize Conversions (Purchases).\nWinning Metrics: CPA < \$25, ROAS > 2.5x.\nPause Conditions: Spend > \$50 with 0 ATC.\nCreative Refresh Rules: Swap creatives every 14 days or if CTR drops below 1%.\nA/B Testing: Test 3 hooks against 1 winning body.\nHorizontal & Vertical Scaling: Increase budget by 20% every 2 days on winning ad sets. Duplicate winning ad sets to new broad audiences.\nBudget Rules: Start at \$50/day. Scale winners, kill losers fast.\n\nRETARGETING & TRACKING\nVideo Viewers: Retarget 50% video viewers.\nWebsite Visitors: Retarget 30-day visitors with a discount.\nATC: Retarget 7-day cart abandoners with urgency.\nIC: Retarget initiate checkout with social proof.\nExisting Customers: Upsell/Cross-sell after 30 days.\nMeta Pixel/CAPI/GA4/GTM/Server-side Tracking: Ensure all events are tracked accurately for maximum algorithm efficiency.\n\nKPI BENCHMARKS & PRO METRICS\nCPM: < \$15\nCTR: > 1.5%\nCPC: < \$1.00\nCPA: < \$25\nROAS: > 2.5x\nFrequency: 1.5 - 2.5\nCompetitor Analysis: High competition, differentiate on quality.\nSeasonality Impact: Evergreen product.\nAI Score (1-10): 9\nMarket Saturation: Moderate\nCreative Fatigue Score: Low (new campaign)\nRisk Analysis: Low risk with structured testing.\nExpected Learning Phase: 3-5 days.\nBudget Efficiency Score: High"
        ];

        if ($platform === 'meta') {
            $meta_card = "CARD HEADER\nPlatform: Meta Ads\nCampaign Objective: Awareness\nCampaign Type: Reach / Brand Awareness\nFunnel Stage: TOF (Top of Funnel)\nBusiness Type: E-commerce\nProduct Category: {$categories_str}\nBudget Range: \$20-\$50/day\n\nAUDIENCE\nTarget Audience: Broad audience interested in lifestyle upgrades.\nBroad/Interest/Lookalike: Broad targeting only.\nCustom Audience: None.\nRetargeting Audience: None.\nAudience Exclusions: Past purchasers, Website visitors (30 days).\n\nCREATIVE STRATEGY\nCreative Format: High-quality Lifestyle Image / Short Video\nHook Strategy: Visually stunning product showcase.\nStory Angle: Introducing the new standard for {$name}.\nOffer Strategy: Brand positioning (No discount).\nCTA Strategy: Learn More.\nUGC Strategy: Aesthetic lifestyle integration.\nCarousel Strategy: Brand story and core values.\nVideo Duration: 6-15s.\n\nAD COPY\nPrimary Text (Short): Discover {$name}, the ultimate lifestyle upgrade.\nPrimary Text (Medium): Experience the difference with {$name}. Crafted for excellence and designed to elevate your everyday.\nPrimary Text (Long): (Detailed brand story introducing {$name}).\nHeadlines (5 variations): Meet {$name} | The New Standard | Upgrade Your Life | Experience Premium | Discover Excellence.\nHooks (10 variations): (Visually focused hooks capturing attention without aggressive selling).\nCTA Variations (5 variations): Learn More, See Details, Explore, Discover, Watch Video.\n\nOPTIMIZATION & SCALING\nOptimization Goal: Maximize Reach / Ad Recall Lift.\nWinning Metrics: CPM < \$5, Cost per ThruPlay < \$0.05.\nPause Conditions: Frequency > 3 without engagement.\nCreative Refresh Rules: Refresh creatives every 14 days to prevent fatigue.\nA/B Testing: Test image vs. video.\nHorizontal & Vertical Scaling: Broaden age targeting to lower CPM.\nBudget Rules: Keep budget steady for consistent brand presence.\n\nRETARGETING & TRACKING\nVideo Viewers: Build audience of 25%+ viewers for MOF/BOF campaigns.\nWebsite Visitors: Push engaged users to traffic/conversion campaigns.\nMeta Pixel/CAPI/GA4/GTM/Server-side Tracking: Track brand awareness lift.\n\nKPI BENCHMARKS & PRO METRICS\nCPM: < \$5\nCTR: > 0.5%\nCPC: < \$1.50\nAI Score (1-10): 8\nMarket Saturation: Low (Awareness)\nCreative Fatigue Score: Medium\nRisk Analysis: Low risk (cheap impressions).\nExpected Learning Phase: 1-2 days.\nBudget Efficiency Score: Moderate (Top of funnel investment).";
            
            $dummy_response['tof_cold'] = str_replace("Awareness", "TOF Cold Prospecting", $meta_card);
            $dummy_response['mof_consideration'] = str_replace("Awareness", "MOF Consideration", $meta_card);
            $dummy_response['bof_conversion'] = str_replace("Awareness", "BOF Conversion", $meta_card);
            $dummy_response['retargeting'] = str_replace("Awareness", "Retargeting Recovery", $meta_card);
            $dummy_response['flash_sale'] = str_replace("Awareness", "Flash Sale Urgency", $meta_card);
            
        } elseif ($platform === 'tiktok') {
            $tiktok_card = "CARD HEADER\nPlatform: TikTok Ads\nCampaign Objective: Reach\nFunnel Stage: TOF (Top of Funnel)\nProduct Category: {$categories_str}\nBudget Range: \$30-\$60/day\n\nAUDIENCE & CREATIVE\nTarget Audience: Gen Z & Millennials.\nBroad/Interest/Lookalike/Retargeting: Broad targeting to let the algorithm optimize.\nCreative Type: Trending Audio UGC.\nCreator Style: Authentic, relatable, native TikTok vibe.\nSpark Ads: Yes, boost organic viral posts.\nStorytelling Angle: 'TikTok made me buy it'.\nProduct Demo: Fast-paced unboxing.\n\nVIDEO STRUCTURE (Second-by-Second)\nHook (0-3s): Disruptive visual + text overlay ('POV: You found the perfect {$name}').\nProblem (3-10s): Showing the struggle before the product.\nProduct Demo (10-20s): Fast cuts showing {$name} in action.\nBenefits (20-30s): Pointing to text bubbles with key features.\nSocial Proof (30-40s): Reaction shot or showing results.\nCTA (40-60s): 'Link in bio to get yours!'\n\nAD COPY\nCaption: I can't believe I lived without this! 😍 #{$name} #musthave\nShort Script: Stop scrolling! This is the {$name} and you need it.\nMedium Script: (Full 30s script focusing on the problem/solution dynamic).\nLong Script: (Detailed 60s storytelling script).\nHooks (10 variations): (Trending audio hooks, visual pattern interrupts).\nCTA Variations: Shop Now, Learn More, Get Yours.\n\nOPTIMIZATION & SCALING\nOptimization Goal: Maximize Reach.\nWinning Metrics: CPM < \$4, 3-Second View Rate > 30%.\nPause Conditions: 3-Second View Rate < 15%.\nCreative Refresh Rules: Refresh weekly due to fast TikTok fatigue.\nHorizontal/Vertical Scaling: Use Spark Ads on viral creator posts to scale quickly.\nBudget Rules: Test quickly, kill losers faster.\n\nRETARGETING & TRACKING\nVideo/Product Viewers: Retarget 6-second video viewers in a conversion campaign.\nTikTok Pixel/Events API/GA4/GTM/Server-side Tracking: Ensure TikTok Pixel is firing correctly.\n\nKPI BENCHMARKS & PRO METRICS\nCPM: < \$4\nCTR: > 1.0%\nAI Score (1-10): 9\nCreative Fatigue Score: High (TikTok native)\nExpected Learning Phase: 1-3 days.";
            
            $dummy_response['spark_ads'] = str_replace("Reach", "Spark Ads Amplification", $tiktok_card);
            $dummy_response['in_feed_ugc'] = str_replace("Reach", "In-Feed Native UGC", $tiktok_card);
            $dummy_response['pov_problem'] = str_replace("Reach", "POV Problem-Solver", $tiktok_card);
            $dummy_response['before_after'] = str_replace("Reach", "Before & After Transformation", $tiktok_card);
            $dummy_response['grwm'] = str_replace("Reach", "GRWM Product Integration", $tiktok_card);

        } elseif ($platform === 'google') {
            $google_card = "CARD HEADER\nPlatform: Google Ads\nCampaign Type: Search Campaign\nCampaign Objective: Leads / Sales\nFunnel Stage: MOF / BOF\nBusiness Type: E-commerce\nProduct Category: {$categories_str}\nBudget Range: \$50-\$150/day\n\nSEARCH INTENT\nIntent: Commercial / Transactional.\n\nKEYWORD STRATEGY\nHigh-intent: 'buy {$name}', '{$name} discount', 'best premium {$name}'.\nLong-tail: 'where to buy {$name} online with free shipping'.\nCompetitor: '[Competitor Brand] alternative'.\nBrand: '{$name} official site'.\nNegative Keywords: 'free', 'cheap', 'used', 'how to make'.\n\nCREATIVE / ASSETS\nHeadlines (15 variations): Buy {$name} Online | Only \${$price} Today | Fast & Free Shipping | Premium Quality Guaranteed | etc.\nDescriptions (4 variations): Experience the best with {$name}. Order today for fast shipping and a 30-day guarantee. Upgrade your lifestyle now.\nCallouts: Free Shipping, 30-Day Returns, Premium Quality, 24/7 Support.\nStructured Snippets: Types: Wireless, Ergonomic, Organic.\nSitelinks: Shop Now, Reviews, About Us, Contact.\nProduct Feed: Sync via Google Merchant Center.\nVideo Assets: N/A for Search.\n\nAUDIENCE & OPTIMIZATION\nAudience Signals: In-market for related categories.\nCustomer Match: Upload past purchasers list for exclusion.\nRemarketing: RLSA (Remarketing Lists for Search Ads) for past visitors.\nQuality Score: Aim for > 7/10 on core keywords.\nImpression Share: Target > 70% on Brand terms.\nCTR: Target > 5%.\nCPC: Target < \$2.00.\nCPA: Target < \$25.\nROAS: Target > 2.5x.\nSearch Term Report: Review weekly to add negative keywords.\nAsset Performance: Optimize based on 'Best' performing assets.\n\nSCALING, RETARGETING & TRACKING\nBudget/Bid Scaling: Increase Target CPA slightly to win more auctions when scaling.\nKeyword Expansion: Add converting search terms as exact match keywords.\nCart Abandoners: Retarget with higher bids (RLSA).\nPast Customers: N/A.\nGA4/GTM/Enhanced Conversions/Google Tag/Server-side Tracking: Ensure Enhanced Conversions are active for accurate measurement.\n\nLANDING PAGE & PRO METRICS\nHero Section: Clear H1 matching ad headline, high-quality product image.\nOffer: Prominent \${$price} and 'Free Shipping' banner.\nCTA: 'Add to Cart' above the fold.\nTrust Signals: 5-star reviews, secure checkout badges.\nFAQ: Address shipping and return policies.\nCompetitor Analysis: High search competition, differentiate on ad copy quality.\nAI Score (1-10): 9\nExpected Learning Phase: 5-7 days.";
            
            $dummy_response['pmax'] = str_replace("Search Campaign", "Performance Max (PMax)", $google_card);
            $dummy_response['high_intent_search'] = str_replace("Search Campaign", "High-Intent Commercial Search", $google_card);
            $dummy_response['shopping_campaign'] = str_replace("Search Campaign", "Shopping Campaign", $google_card);
            $dummy_response['youtube_video'] = str_replace("Search Campaign", "YouTube In-Stream Video", $google_card);
            $dummy_response['display_remarketing'] = str_replace("Search Campaign", "Display Remarketing", $google_card);
        }

        wp_send_json_success($dummy_response);
    }
}
