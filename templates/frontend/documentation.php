<?php
/**
 * PixelOnWP Responsive Documentation Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determine document mode
$doc_mode = get_query_var('pixelonwp_docs', 'user');
if ($doc_mode !== 'admin') {
    $doc_mode = 'user';
}

$title = $doc_mode === 'admin' ? 'PixelOnWP Admin Developer Docs' : 'PixelOnWP User Documentation';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($title); ?></title>
    <!-- Tailwind CSS for modern responsive layout -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        html { scroll-padding-top: 100px; }
        .active-link {
            color: #2563eb !important;
            font-weight: 600 !important;
            border-left: 2px solid #2563eb;
            padding-left: 8px;
        }
    </style>
</head>
<body class="text-slate-800 antialiased">
    
    <!-- Navbar -->
    <header class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-slate-200">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-600 p-2 rounded-lg">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="font-bold text-xl text-slate-900 tracking-tight">PixelOnWP Docs</span>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="text-slate-500 hover:text-slate-900 font-medium text-sm transition">Back to Website</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=pixelonwp-roas')); ?>" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm hover:shadow transition-all">Go to Dashboard</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col lg:flex-row gap-10">
        
        <!-- Sidebar Navigation -->
        <aside class="w-full lg:w-72 flex-shrink-0 mb-6 lg:mb-0">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 lg:sticky lg:top-28 max-h-[70vh] lg:max-h-[calc(100vh-8rem)] overflow-y-auto">
                
                <div class="mb-6">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Guide Version</h3>
                    <div class="flex flex-col gap-2">
                        <a href="<?php echo esc_url(home_url('/pixelonwp/docs/user-documents')); ?>" class="text-sm font-medium <?php echo $doc_mode === 'user' ? 'text-blue-600 font-semibold' : 'text-slate-600'; ?>">User Setup Guide</a>
                        <a href="<?php echo esc_url(home_url('/pixelonwp/docs/admin-documents')); ?>" class="text-sm font-medium <?php echo $doc_mode === 'admin' ? 'text-blue-600 font-semibold' : 'text-slate-600'; ?>">Admin Developer Guide</a>
                    </div>
                </div>

                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Table of Contents</h3>
                <ul class="space-y-2">
                    <?php if ($doc_mode === 'user') : ?>
                        <li><a href="#meta" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">1. Meta Pixel & CAPI</a></li>
                        <li><a href="#tiktok" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">2. TikTok Events API</a></li>
                        <li><a href="#reddit" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">3. Reddit Pixel & API</a></li>
                        <li><a href="#ga4" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">4. Google Analytics 4</a></li>
                        <li><a href="#google-ads" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">5. Google Ads Tracking</a></li>
                        <li><a href="#gtm" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">6. Google Tag Manager</a></li>
                        <li><a href="#event-manager" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">7. Event Manager Settings</a></li>
                        <li><a href="#ecommerce" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">8. eCommerce DataLayer</a></li>
                        <li><a href="#roas" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">9. ROAS & Analytics</a></li>
                        <li><a href="#auto-rules" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">10. Auto-Rules Engine</a></li>
                        <li><a href="#custom-audience" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">11. Custom Audiences</a></li>
                        <li><a href="#currency" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">12. Currency Converter</a></li>
                        <li><a href="#diagnostics" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">13. Diagnostics & Logs</a></li>
                        <li><a href="#itp" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">14. ITP Safeguards</a></li>
                        <li><a href="#fraud" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">15. Fraud Prevention</a></li>
                        <li><a href="#consent" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">16. Cookie Consent v2</a></li>
                        <li><a href="#utm" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">17. UTM Link Builder</a></li>
                        <li><a href="#script-injector" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">18. Header Script Injector</a></li>
                        <li><a href="#tracker" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">19. Universal Tracker</a></li>
                        <li><a href="#whatsapp" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">20. WhatsApp Messaging</a></li>
                        <li><a href="#feed" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">21. Product XML Feed</a></li>
                        <li><a href="#license" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">22. License Manager</a></li>
                        <li><a href="#ai-engine" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">23. AI Gemini Engine</a></li>
                    <?php else : ?>
                        <li><a href="#arch" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">1. Core Loader Architecture</a></li>
                        <li><a href="#db-schema" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">2. Database Schema Schemas</a></li>
                        <li><a href="#capi-dispatches" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">3. CAPI Dispatch Requests</a></li>
                        <li><a href="#dedup-hashing" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">4. Deduplication & Hashing</a></li>
                        <li><a href="#itp-safeguards" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">5. ITP Cookie Mechanics</a></li>
                        <li><a href="#ai-triggers" class="nav-link text-slate-600 hover:text-blue-600 font-medium block transition py-1 text-sm">6. Gemini AI Logic APIs</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-100 p-8 lg:p-14 max-w-none">
            
            <?php if ($doc_mode === 'user') : ?>
                <!-- USER DOCUMENTS CONTENT -->
                <h1 class="text-4xl font-extrabold text-slate-900 mb-6 leading-tight">PixelOnWP Setup & Operational Manual</h1>
                <p class="text-xl text-slate-600 mb-10 leading-relaxed">Exhaustive operational configuration documentation mapping all 23 integrated tracking modules.</p>

                <!-- 1. Meta -->
                <section id="meta" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">1. Meta (Facebook) Pixel & CAPI</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Get Credentials</h3>
                    <p class="text-slate-600 mb-4">Go to Meta Events Manager -> Data Sources. Copy the Pixel ID. Navigate to the Settings tab, scroll down to the Conversions API, and click "Generate Access Token". Copy the token.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Setup</h3>
                    <p class="text-slate-600 mb-4">Input Pixel ID, CAPI token, and optional Test Event Code inside the Setup Wizard. Enable Advanced Matching features.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Loads browser scripts for standard pixel triggers, while server-side events are sent via cURL requests to ensure conversion tracking even when ad blockers are active.</p>
                </section>

                <!-- 2. TikTok -->
                <section id="tiktok" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">2. TikTok Pixel & Events API</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Get Credentials</h3>
                    <p class="text-slate-600 mb-4">Go to TikTok Ads Manager -> Assets -> Events -> Web Events. Select your pixel to copy the ID and generate an Access Token under Settings.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Setup</h3>
                    <p class="text-slate-600 mb-4">Add your TikTok Pixel ID and Access Token to the settings panel.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Fires client tracking calls alongside server dispatches using specific TikTok event parameters.</p>
                </section>

                <!-- 3. Reddit -->
                <section id="reddit" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">3. Reddit Pixel & Conversions API</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Get Credentials</h3>
                    <p class="text-slate-600 mb-4">Navigate to Reddit Ads Manager -> Events Manager. Copy the Pixel ID and generate a Conversions API Bearer Token.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Setup</h3>
                    <p class="text-slate-600 mb-4">Enter Pixel ID and Bearer Token on the Reddit configuration card.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Triggers standard events (like PageVisit and Purchase) via browser code and routes them through the Reddit Conversions API.</p>
                </section>

                <!-- 4. GA4 -->
                <section id="ga4" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">4. Google Analytics 4 (GA4)</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Get Credentials</h3>
                    <p class="text-slate-600 mb-4">Navigate to Google Analytics Property Admin -> Data Streams. Copy the Measurement ID, then go to Measurement Protocol API Secrets to generate a secret key.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Setup</h3>
                    <p class="text-slate-600 mb-4">Input Measurement ID and API Secret inside the GA4 settings card.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Enqueues standard Google Analytics tags client-side and dispatches server-side events using GA4 Measurement Protocol requests.</p>
                </section>

                <!-- 5. Google Ads -->
                <section id="google-ads" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">5. Google Ads Conversion Tracking</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Get Credentials</h3>
                    <p class="text-slate-600 mb-4">Go to Google Ads -> Conversions, select your conversion action, and find GTM instructions to copy the Conversion ID and Label.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Setup</h3>
                    <p class="text-slate-600 mb-4">Input Conversion ID, Label, and enable Enhanced Conversions.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Fires browser-side tags and hashes customer attributes using SHA-256 for Enhanced Conversions tracking.</p>
                </section>

                <!-- 6. GTM -->
                <section id="gtm" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">6. Google Tag Manager (GTM)</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Get Credentials</h3>
                    <p class="text-slate-600 mb-4">Copy the GTM Container ID (e.g. `GTM-XXXXXX`) from your GTM dashboard.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Setup</h3>
                    <p class="text-slate-600 mb-4">Enter GTM ID inside the container settings card.</p>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Injects GTM script codes and noscript iframe blocks into the page.</p>
                </section>

                <!-- 7. Event Manager -->
                <section id="event-manager" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">7. Event Manager Settings</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How to Setup</h3>
                    <p class="text-slate-600">Toggle checkboxes to enable or disable tracking triggers for specific platform endpoints.</p>
                </section>

                <!-- 8. eCommerce -->
                <section id="ecommerce" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">8. eCommerce Tracking & DataLayer</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Automatically maps product variation details, enqueues AJAX cart additions, and pushes clean data layers onto the frontend.</p>
                </section>

                <!-- 9. ROAS -->
                <section id="roas" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">9. ROAS Dashboard & Analytics Engine</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Retrieves campaign spend metrics and calculates math variables (CPA, AOV, CTR, CPC, ROAS) using WooCommerce HPOS order history.</p>
                </section>

                <!-- 10. Auto-Rules -->
                <section id="auto-rules" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">10. Auto-Rules & Automation</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Uses a daily background cron loop to check campaign performance. Automatically increases budgets by 20% for high-performing ads, cuts budgets by 50% for low-performing ads, and pauses ads when products go out of stock.</p>
                </section>

                <!-- 11. Custom Audience -->
                <section id="custom-audience" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">11. Custom Audience Syncing</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Syncs VIP customer cohorts to ad platforms in hashed formats based on spend thresholds. Automatically excludes recent buyers and tracks abandoned checkouts.</p>
                </section>

                <!-- 12. Currency -->
                <section id="currency" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">12. Dynamic Currency Converter</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Queries the Frankfurter API for exchange rates, caching them in transients for 24 hours. Falls back to manual settings on API timeouts.</p>
                </section>

                <!-- 13. Diagnostics -->
                <section id="diagnostics" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">13. Diagnostics & Logs</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Logs CAPI payloads and response codes in the database, and provides self-diagnostic credentials testing validation.</p>
                </section>

                <!-- 14. ITP -->
                <section id="itp" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">14. ITP & First-Party Cookies</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Sets tracking cookies (like `_fbp`) server-side via PHP headers on page redirect, bypassing Safari's 7-day storage limit.</p>
                </section>

                <!-- 15. Fraud -->
                <section id="fraud" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">15. Fraud Prevention</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Identifies bots and proxy users, blocking suspicious traffic from firing tracking scripts.</p>
                </section>

                <!-- 16. Consent -->
                <section id="consent" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">16. Cookie Consent v2</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Queues tracking events until the user accepts the cookie consent banner.</p>
                </section>

                <!-- 17. UTM -->
                <section id="utm" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">17. UTM Builder</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Compiles custom campaign parameters into tracking URLs.</p>
                </section>

                <!-- 18. Script Injector -->
                <section id="script-injector" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">18. Header & Footer Injector</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Injects custom scripts into page templates without modifying theme files.</p>
                </section>

                <!-- 19. Tracker -->
                <section id="tracker" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">19. Universal Tracker</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Loads scripts dynamically and displays a Visual Builder Panel inside an isolated shadow DOM container.</p>
                </section>

                <!-- 20. WhatsApp -->
                <section id="whatsapp" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">20. WhatsApp Order Messaging</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Automatically sends WhatsApp alerts to customers when their order status changes.</p>
                </section>

                <!-- 21. Feed -->
                <section id="feed" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">21. Product Feed Generator</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Generates product catalog XML feeds for Meta and Google, updating them on a set schedule.</p>
                </section>

                <!-- 22. License -->
                <section id="license" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">22. License Manager</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Validates license keys and restricts access to plugin settings on validation failure.</p>
                </section>

                <!-- 23. AI Engine -->
                <section id="ai-engine" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">23. AI Gemini Engine</h2>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">How It Works</h3>
                    <p class="text-slate-600">Uses Gemini 1.5 Flash to power exit-intent discount popups, analyze user search trends, identify bot traffic, and write ad copy.</p>
                </section>

            <?php else : ?>
                <!-- ADMIN DEVELOPER DOCUMENTS CONTENT -->
                <h1 class="text-4xl font-extrabold text-slate-900 mb-6 leading-tight">PixelOnWP Developer & System Architecture</h1>
                <p class="text-xl text-slate-600 mb-10 leading-relaxed">Technical systems architecture documentation for plugin developers and administrators.</p>

                <!-- 1. Core Loader -->
                <section id="arch" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">1. Core Loader Architecture</h2>
                    <p class="text-slate-600 mb-4">The plugin uses namespace autoloaders linked to class files. The core loader (<code>class-loader.php</code>) registers actions and filters, while activator classes handle database schema setup.</p>
                </section>

                <!-- 2. DB Schema -->
                <section id="db-schema" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">2. Database Schema Schemas</h2>
                    <p class="text-slate-600 mb-4">Creates <code>wp_pixelonwp_visitor_intelligence</code> to log user behaviors and VPN checks, and <code>wp_pixelonwp_event_logs</code> to store Conversions API payloads and response codes.</p>
                </section>

                <!-- 3. CAPI Dispatches -->
                <section id="capi-dispatches" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">3. CAPI Dispatch Requests</h2>
                    <p class="text-slate-600 mb-4">Sends server-side payloads using cURL requests. Meta CAPI requests use HTTP Bearer tokens, while GA4 requests route via Google's Measurement Protocol.</p>
                </section>

                <!-- 4. Dedup Hashing -->
                <section id="dedup-hashing" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">4. Deduplication & Hashing</h2>
                    <p class="text-slate-600 mb-4">Generates a unique <code>event_id</code> on each action. Sends this ID to both the browser pixel and server CAPI to deduplicate events. Customer details are hashed using SHA-256 before transmission.</p>
                </section>

                <!-- 5. ITP Safeguards -->
                <section id="itp-safeguards" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">5. ITP Cookie Mechanics</h2>
                    <p class="text-slate-600 mb-4">Sets persistent first-party cookies (e.g. `_fbp`) server-side via PHP `setcookie()` during page redirects to prevent Safari's 7-day storage limit from expiring customer sessions.</p>
                </section>

                <!-- 6. Gemini AI -->
                <section id="ai-triggers" class="mb-16 scroll-mt-24">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">6. Gemini AI Logic APIs</h2>
                    <p class="text-slate-600 mb-4">Connects to Gemini 1.5 Flash using <code>OMNITRACK_GEMINI_KEY</code>. Analyzes page parameters and visitor actions to generate coupon popups, identify inventory gaps, and detect proxy behavior.</p>
                </section>
            <?php endif; ?>

        </main>
    </div>

    <footer class="bg-white border-t border-slate-200 mt-12 py-10">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-slate-500 text-sm font-medium">&copy; <?php echo date('Y'); ?> PixelOnWP Documentation. Built for high-performance WordPress tracking.</p>
        </div>
    </footer>

    <!-- Sidebar Navigation Highlight Code -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('.nav-link');

            window.addEventListener('scroll', () => {
                let current = '';
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    if (pageYOffset >= sectionTop - 120) {
                        current = section.getAttribute('id');
                    }
                });

                navLinks.forEach(link => {
                    link.classList.remove('active-link');
                    if (link.getAttribute('href').substring(1) === current) {
                        link.classList.add('active-link');
                    }
                });
            });

            // Smooth Scroll links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetEl = document.getElementById(targetId);
                    if (targetEl) {
                        targetEl.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
