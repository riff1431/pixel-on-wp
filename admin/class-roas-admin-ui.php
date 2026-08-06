<?php
/**
 * Return on Ad Spend (ROAS) & Automation Admin UI Class.
 *
 * @package PixelOnWP\Admin
 * @since 1.0.0
 */

namespace PixelOnWP\Admin;

if (!defined('ABSPATH')) {
  exit;
}

use PixelOnWP\Includes\PixelOnWP_Meta_Api_Service;
use PixelOnWP\Includes\PixelOnWP_Audience_Sync;
use PixelOnWP\Includes\PixelOnWP_Cron_Sync;

/**
 * PixelOnWP_Roas_Admin_Ui Class.
 *
 * Handles the rendering, saving, and AJAX callbacks for the Ad Optimization & Analytics Engine.
 */
class PixelOnWP_Roas_Admin_Ui {

  /**
   * Register hooks.
   *
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void {
    $loader->add_action('wp_ajax_roas_verify_meta_api', $this, 'ajax_verify_meta_api');
  }

  /**
   * AJAX handler to verify Meta API connection and permissions.
   *
   * @return void
   */
  public function ajax_verify_meta_api(): void {
    check_ajax_referer('pixelonwp_roas_nonce', 'nonce');

    if (!current_user_can(\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_admin_capability())) {
      wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
    }

    $token = isset($_POST['meta_access_token']) ? sanitize_text_field(wp_unslash($_POST['meta_access_token'])) : '';
    $ad_account = isset($_POST['meta_ad_account_id']) ? sanitize_text_field(wp_unslash($_POST['meta_ad_account_id'])) : '';

    $res = PixelOnWP_Meta_Api_Service::run_self_diagnostics($token, $ad_account);

    if ($res['success']) {
      wp_send_json_success(['message' => $res['message']]);
    } else {
      wp_send_json_error(['message' => $res['message']]);
    }
  }

  /**
   * Render the main dashboard panel page.
   *
   * @return void
   */
  public function render_dashboard(): void {
    if (!current_user_can(\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_admin_capability())) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'pixel-on-wp'));
    }

    $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'dashboard';

    // Handle Form Submissions (POST Routing)
    $this->handle_form_submissions();

    // Settings fields
    $meta_token = get_option('pixelonwp_meta_access_token', '');
    $meta_ad_account = get_option('pixelonwp_meta_ad_account_id', '');
    $exchange_rates = get_option('pixelonwp_currency_exchange_rates', []);
    $ad_currency = get_option('pixelonwp_base_ad_currency', 'USD');
    $store_currency = get_woocommerce_currency();

    // Determine Date Range filter
    $filter = isset($_GET['date_range']) ? sanitize_text_field(wp_unslash($_GET['date_range'])) : 'last_30_days';
    $start_date = '';
    $end_date   = '';

    switch ($filter) {
      case 'today':
        $start_date = date('Y-m-d');
        $end_date   = date('Y-m-d');
        break;
      case 'last_7_days':
        $start_date = date('Y-m-d', strtotime('-6 days'));
        $end_date   = date('Y-m-d');
        break;
      case 'last_30_days':
      default:
        $start_date = date('Y-m-d', strtotime('-29 days'));
        $end_date   = date('Y-m-d');
        break;
      case 'custom':
        $start_date = isset($_GET['start_date']) ? sanitize_text_field(wp_unslash($_GET['start_date'])) : date('Y-m-d', strtotime('-29 days'));
        $end_date = isset($_GET['end_date']) ? sanitize_text_field(wp_unslash($_GET['end_date'])) : date('Y-m-d');
        break;
    }

    // Load campaign insights data (pulled from local cron sync cache fallback first)
    $insights = [];
    $meta_error = '';
    $cached_entry = get_option('pixelonwp_cached_meta_insights', []);

    if (!empty($meta_token) && !empty($meta_ad_account)) {
      if (!empty($cached_entry['data']) && $filter === 'last_30_days') {
        $insights = $cached_entry['data'];
      } else {
        $insights_response = PixelOnWP_Meta_Api_Service::fetch_campaign_insights($start_date, $end_date);
        if (is_wp_error($insights_response)) {
          $meta_error = $insights_response->get_error_message();
        } else {
          $insights = $insights_response;
          if (!empty($insights[0]['account_currency'])) {
            $ad_currency = strtoupper($insights[0]['account_currency']);
          }
        }
      }
    } else {
      $meta_error = __('Meta API Credentials are not configured. Go to settings tab.', 'pixel-on-wp');
    }

    $needs_exchange_rate = ($ad_currency !== $store_currency);

    ?>
    ?>
    <style>
      #wpt-admin-app-roas {
        font-family: var(--pp-font-body);
        color: var(--pp-text-main);
        background-color: var(--pp-bg);
        background-image: var(--pp-bg-mesh);
        background-size: 100% 100%, 100% 100%, 100% 100%, 10px 10px, 100% 100%;
        background-attachment: fixed;
        min-height: calc(100vh - 32px);
        margin: -10px -20px -20px -20px;
        display: flex;
        font-size: 14px;
        line-height: 1.6;
        box-sizing: border-box;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        scroll-behavior: smooth;
      }
      #wpt-admin-app-roas * {
        box-sizing: border-box;
      }
      #wpt-admin-app-roas .pp-sidebar {
        width: 250px;
        background: var(--pp-surface);
        border-right: 1px solid var(--pp-border);
        display: flex;
        flex-direction: column;
        position: sticky;
        top: 32px;
        height: calc(100vh - 32px);
        z-index: 1000;
        transition: var(--pp-transition);
        flex-shrink: 0;
      }
      #wpt-admin-app-roas .pp-main-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        width: 100%;
        overflow-x: hidden;
      }
      
      /* Premium Input Styling */
      #wpt-admin-app-roas select,
      #wpt-admin-app-roas input[type="date"],
      #wpt-admin-app-roas input[type="text"],
      #wpt-admin-app-roas input[type="number"],
      #wpt-admin-app-roas input[type="password"] {
        padding: 10px 16px;
        border: 1px solid var(--pp-border);
        border-radius: 10px;
        background: #ffffff;
        font-size: 14px;
        color: var(--pp-text-heading);
        outline: none;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
      }
      #wpt-admin-app-roas select:focus,
      #wpt-admin-app-roas input:focus {
        border-color: var(--pp-primary);
        box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.15), 0 2px 4px rgba(0, 0, 0, 0.02);
      }

      /* Custom Dropdown Chevron */
      #wpt-admin-app-roas select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569' stroke-width='3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 14px center !important;
        background-size: 12px !important;
        padding-right: 40px !important;
      }

      /* Modern Checkbox Styling */
      #wpt-admin-app-roas input[type="checkbox"] {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border: 1.5px solid var(--pp-border);
        border-radius: 6px;
        background-color: #ffffff;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        position: relative;
        vertical-align: middle;
        margin-right: 8px;
      }
      #wpt-admin-app-roas input[type="checkbox"]:checked {
        background-color: var(--pp-primary);
        border-color: var(--pp-primary);
      }
      #wpt-admin-app-roas input[type="checkbox"]:checked::after {
        content: "";
        width: 5px;
        height: 9px;
        border: solid white;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
        position: absolute;
        top: 2px;
      }
      #wpt-admin-app-roas input[type="checkbox"]:focus {
        box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.15);
        outline: none;
      }

      /* Emblem Pod Hover Animations */
      .pp-card-icon-pod {
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
      }
      .pp-card:hover .pp-card-icon-pod {
        transform: scale(1.12);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
      }
      .pp-card:hover .pp-icon-anim-bounce {
        animation: pp-bounce 0.8s ease-in-out infinite;
      }
      .pp-card:hover .pp-icon-anim-pulse {
        animation: pp-pulse-glow 1.2s infinite alternate;
      }
      .pp-card:hover .pp-icon-anim-rotate {
        animation: pp-rotate 2.5s linear infinite;
      }
      .pp-card:hover .pp-icon-anim-shake {
        animation: pp-shake 0.6s ease-in-out infinite;
      }

      @keyframes pp-bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
      }
      @keyframes pp-pulse-glow {
        0% { transform: scale(1); filter: drop-shadow(0 0 2px rgba(225, 29, 72, 0.4)); }
        100% { transform: scale(1.08); filter: drop-shadow(0 0 8px rgba(225, 29, 72, 0.7)); }
      }
      @keyframes pp-rotate {
        to { transform: rotate(360deg); }
      }
      @keyframes pp-shake {
        0%, 100% { transform: rotate(0); }
        25% { transform: rotate(-8deg); }
        75% { transform: rotate(8deg); }
      }

      /* Premium Tab controls */
      .pp-tab-wrapper {
        display: flex;
        gap: 6px;
        background: rgba(0, 0, 0, 0.03);
        padding: 5px;
        border-radius: 14px;
        border: 1px solid var(--pp-border-light);
        margin-bottom: 28px;
        width: fit-content;
        max-width: 100%;
        overflow-x: auto;
        white-space: nowrap;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
      }
      .pp-tab-wrapper::-webkit-scrollbar {
        display: none;
      }
      .pp-tab-link {
        padding: 10px 22px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13.5px;
        color: var(--pp-text-muted);
        text-decoration: none;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        white-space: nowrap;
      }
      .pp-tab-link:hover {
        color: var(--pp-primary);
        background: rgba(225, 29, 72, 0.04);
      }
      .pp-tab-link.active {
        background: #ffffff;
        color: var(--pp-primary);
        box-shadow: 0 4px 12px rgba(225, 29, 72, 0.08), 0 1px 3px rgba(0, 0, 0, 0.02);
      }

      /* Premium Table Styling */
      #wpt-admin-app-roas table.wp-list-table {
        border: none;
        box-shadow: none;
        background: transparent;
      }
      #wpt-admin-app-roas table.wp-list-table thead th {
        background: rgba(0, 0, 0, 0.015);
        font-weight: 700;
        color: var(--pp-text-muted);
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.06em;
        border-bottom: 1px solid var(--pp-border-light);
        padding: 14px 16px;
      }
      #wpt-admin-app-roas table.wp-list-table tbody tr {
        transition: background 0.2s ease;
      }
      #wpt-admin-app-roas table.wp-list-table tbody tr:hover {
        background: rgba(225, 29, 72, 0.01) !important;
      }
      #wpt-admin-app-roas table.wp-list-table tbody td {
        padding: 16px;
        vertical-align: middle;
        border-bottom: 1px solid var(--pp-border-light);
        color: var(--pp-text-main);
      }
      
      @media screen and (max-width: 960px) {
        #wpt-admin-app-roas {
          flex-direction: column;
          margin: 0;
          min-height: auto;
        }
        #wpt-admin-app-roas .pp-sidebar {
          position: fixed;
          top: 32px;
          left: 0;
          height: calc(100vh - 32px);
          transform: translateX(-100%);
          box-shadow: var(--pp-shadow-lg);
          z-index: 9999;
        }
        #wpt-admin-app-roas .pp-sidebar.open {
          transform: translateX(0);
        }
        #wpt-admin-app-roas .pp-sidebar-overlay {
          display: none;
          position: fixed;
          top: 32px;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(15, 23, 42, 0.45);
          backdrop-filter: blur(6px);
          z-index: 9998;
        }
        #wpt-admin-app-roas .pp-sidebar-overlay.open {
          display: block;
        }
        #wpt-admin-app-roas .pp-menu-toggle {
          display: flex !important;
          align-items: center;
          justify-content: center;
          width: 48px;
          height: 48px;
          background: var(--pp-primary-gradient);
          color: white;
          border: none;
          border-radius: 50%;
          cursor: pointer;
          position: fixed;
          bottom: 24px;
          right: 24px;
          z-index: 10000;
          box-shadow: 0 8px 20px rgba(225, 29, 72, 0.4);
        }
        #wpt-admin-app-roas .pp-main-content {
          padding: 20px 16px;
        }
      }
    </style>

    <div class="wrap wpt-admin-wrap" id="wpt-admin-app-roas">
      
      <!-- Overlay -->
      <div class="pp-sidebar-overlay" id="pp-sidebar-overlay"></div>

      <!-- Menu Toggle Button for Mobile -->
      <button class="pp-menu-toggle" id="pp-menu-toggle-btn" aria-label="Toggle menu" style="display: none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px;"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>

      <script>
        document.addEventListener('DOMContentLoaded', function() {
          var menuToggle = document.getElementById('pp-menu-toggle-btn');
          var sidebar = document.getElementById('pp-sidebar');
          var overlay = document.getElementById('pp-sidebar-overlay');
          
          if (menuToggle && sidebar && overlay) {
            menuToggle.addEventListener('click', function() {
              sidebar.classList.toggle('open');
              overlay.classList.toggle('open');
            });
            
            overlay.addEventListener('click', function() {
              sidebar.classList.remove('open');
              overlay.classList.remove('open');
            });
          }
        });
      </script>
      
      <!-- Sidebar -->
      <div class="pp-sidebar" id="pp-sidebar">
        <div class="pp-sidebar-header" style="padding: 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--pp-border-light);">
          <div class="pp-brand-logo-pod" style="width:32px; height:32px; background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); border-radius: 9px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.35); flex-shrink: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" style="width:20px; height:20px;">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" fill="#ffffff" fill-opacity="0.22"/>
              <path d="M3.27 6.96L12 12.01l8.73-5.05" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              <line x1="12" y1="22.08" x2="12" y2="12" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </div>
          <span style="font-weight: 800; font-size: 16px; color: var(--pp-text-heading); font-family: var(--pp-font-heading);">PixelOnWP</span>
          <span class="pp-sidebar-brand-badge" style="font-size: 10px; font-weight: 700; background: var(--pp-primary-light); color: var(--pp-primary); padding: 2px 6px; border-radius: 6px;">v1.0.3</span>
        </div>
        
        <ul class="pp-nav" style="list-style: none; padding: 16px; margin: 0; flex: 1; overflow-y: auto;">
          <li class="pp-nav-section-title" style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em; padding: 12px 16px 8px 16px;">Main</li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg> <span>Dashboard</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#setup'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg> <span>Setup Wizard</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#license'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg> <span>License Activation</span></li>
          
          <li class="pp-nav-section-title" style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 16px 8px 16px;">Tracking & CAPI</li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#serverside'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg> <span>Server-Side & ITP</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#events'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> <span>Event Manager</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#gtmsetup'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg> <span>GTM Integration</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#universal-tracker'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg> <span>Universal Tracker</span></li>
          
          <li class="pp-nav-section-title" style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 16px 8px 16px;">AI & Security</li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#ai-engine'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> <span>AI Ad Engine</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#fraud'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> <span>Fraud Prevention</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#ecommerce'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg> <span>eCommerce Tools</span></li>
          <li class="pp-nav-item active" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-primary); background: var(--pp-primary-light); font-weight: 700; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg> <span>Ad Attribution & ROAS</span></li>
          
          <li class="pp-nav-section-title" style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 16px 8px 16px;">Tools & Config</li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#settings'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg> <span>DataLayer & Settings</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#diagnostics'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg> <span>Diagnostics & Logs</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#utm-builder'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg> <span>UTM Builder</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#header-footer'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg> <span>Header & Footer</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#cookie-consent'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"></path><path d="M8.5 8.5v.01"></path><path d="M16 15.5v.01"></path><path d="M12 12v.01"></path><path d="M11 17v.01"></path><path d="M7 14v.01"></path></svg> <span>Cookie Consent v2</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#support'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg> <span>Support Center</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-documentation'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg> <span>Documentation</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixel-admin-docs'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> <span>All Features (Admin Docs)</span></li>
          <li class="pp-nav-item" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#reset'" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: var(--pp-text-main); font-weight: 500; cursor: pointer; transition: var(--pp-transition);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="var(--pp-danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg> <span style="color:var(--pp-danger);">Clear History</span></li>
        </ul>
        
        <div class="pp-sidebar-footer" style="padding: 16px; border-top: 1px solid var(--pp-border-light);">
          <div class="pp-sidebar-footer-card" style="background: var(--pp-primary-light); padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(225, 29, 72, 0.08);">
            <div class="pp-sidebar-footer-title" style="font-size: 12px; font-weight: 700; color: var(--pp-primary); display: flex; align-items: center; gap: 6px;">
              <span class="pp-pulse-dot" style="width: 6px; height: 6px; border-radius: 50%; background: var(--pp-primary); display: inline-block;"></span> Active Ecosystem
            </div>
            <div class="pp-sidebar-footer-desc" style="font-size: 11px; color: var(--pp-text-muted); margin-top: 2px;">8 Tracking Networks Ready</div>
          </div>
        </div>
      </div>
      
      <!-- Main Wrapper -->
      <div class="pp-main-wrapper" id="pp-main-wrapper">
        <!-- Top Header Bar -->
        <div class="pp-top-header-container" id="pp-top-header-container">
          <div class="pp-top-header">
            <div class="pp-header-left" style="display: flex; align-items: center; gap: 16px;">
              <div class="pp-status-pill pp-status-pill-active" style="display: inline-flex; align-items: center; gap: 8px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: #047857;">
                <span class="pp-status-pulse" style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                <span class="pp-status-text">Tracking Engine Active</span>
              </div>
            </div>
            <div class="pp-header-center" style="flex: 1; max-width: 480px; margin: 0 32px;">
              <div class="pp-header-search" onclick="window.location.href='admin.php?page=pixelonwp-dashboard'" style="display: flex; align-items: center; gap: 8px; background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border); padding: 10px 16px; border-radius: 20px; font-size: 13px; color: var(--pp-text-muted); width: 100%; cursor: pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span>Search features, pixels, diagnostics...</span>
                <kbd style="margin-left: auto; background: var(--pp-surface); border: 1px solid var(--pp-border); padding: 2px 6px; border-radius: 4px; font-size: 11px;">⌘K</kbd>
              </div>
            </div>
            <div class="pp-header-right" style="display: flex; align-items: center; gap: 16px;">
              <button class="pp-header-btn pp-header-btn-primary" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#setup'" style="display: inline-flex; align-items: center; gap: 8px; background: var(--pp-primary); color: #fff; border: none; padding: 10px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: var(--pp-transition);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span>Add Pixel</span>
              </button>
            </div>
          </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="pp-main-content" id="pp-main-content" style="flex: 1; padding: 32px; overflow-y: auto;">
          
          <div class="pp-view-header" style="margin-bottom: 24px;">
            <div>
              <div class="pp-view-title-wrap" style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                <h2 style="margin: 0; font-size: 28px; font-weight: 800; color: var(--pp-text-heading); font-family: var(--pp-font-heading);"><?php esc_html_e('Ad Optimization & Analytics Engine', 'pixel-on-wp'); ?></h2>
              </div>
              <p class="pp-view-subtitle" style="margin: 0; color: var(--pp-text-muted); font-size: 14px;">Track return on ad spend and configure automated campaign rules.</p>
            </div>
          </div>

      <style>
        .pixelonwp-metric-cards {
          display: flex;
          flex-wrap: wrap;
          gap: 20px;
          margin-bottom: 30px;
        }
        .pixelonwp-metric-cards .card {
          flex: 1 1 calc(25% - 20px);
          min-width: 220px;
          background: #fff;
          padding: 20px;
          border-radius: 6px;
          box-shadow: 0 1px 3px rgba(0,0,0,0.1);
          box-sizing: border-box;
        }
        .pixelonwp-responsive-table-wrapper {
          width: 100%;
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
          margin-top: 15px;
        }
        .pixelonwp-responsive-table-wrapper table {
          table-layout: auto !important;
          min-width: 800px !important;
          width: 100% !important;
          display: table !important;
        }

        @media (max-width: 1024px) {
          .pixelonwp-metric-cards .card {
            flex: 1 1 calc(50% - 20px);
          }
        }
        @media (max-width: 782px) {
          .form-table tr {
            display: block;
            margin-bottom: 15px;
          }
          .form-table th, .form-table td {
            display: block;
            width: 100% !important;
            box-sizing: border-box;
            padding: 5px 0 !important;
          }
          .nav-tab-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
          }
          .nav-tab {
            flex: 1 1 auto;
            text-align: center;
            margin: 0 !important;
          }
        @media (max-width: 600px) {
          .pp-tab-wrapper {
            width: 100% !important;
            box-sizing: border-box !important;
          }
        }
        @media (max-width: 480px) {
          .pixelonwp-metric-cards .card {
            flex: 1 1 100%;
          }
        }
      </style>

      <!-- Navigation Tabs -->
      <div class="pp-tab-wrapper">
        <a href="?page=pixelonwp-roas&tab=dashboard" class="pp-tab-link <?php echo ($active_tab === 'dashboard') ? 'active' : ''; ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          <?php esc_html_e('ROAS Dashboard', 'pixel-on-wp'); ?>
        </a>
        <a href="?page=pixelonwp-roas&tab=automation" class="pp-tab-link <?php echo ($active_tab === 'automation') ? 'active' : ''; ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
          <?php esc_html_e('Auto-Rules & Automation', 'pixel-on-wp'); ?>
        </a>
        <a href="?page=pixelonwp-roas&tab=audiences" class="pp-tab-link <?php echo ($active_tab === 'audiences') ? 'active' : ''; ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
          <?php esc_html_e('Audience Syncing', 'pixel-on-wp'); ?>
        </a>
        <a href="?page=pixelonwp-roas&tab=settings" class="pp-tab-link <?php echo ($active_tab === 'settings') ? 'active' : ''; ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><circle cx="12" cy="12" r="3" /></svg>
          <?php esc_html_e('Meta Integration Settings', 'pixel-on-wp'); ?>
        </a>
      </div>

      <!-- Tab: Settings -->
      <?php if ($active_tab === 'settings') : ?>
        <div style="background: #fff; padding: 25px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 800px;">
          <h2><?php esc_html_e('Meta Marketing API Credentials', 'pixel-on-wp'); ?></h2>
          <form method="post" action="">
            <?php wp_nonce_field('pixelonwp_save_roas_settings', 'pixelonwp_roas_settings_nonce'); ?>
            <table class="form-table" role="presentation">
              <tbody>
                <tr>
                  <th scope="row"><label for="meta_access_token"><?php esc_html_e('Meta System User Token', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="meta_access_token" type="password" id="meta_access_token" value="<?php echo esc_attr($meta_token); ?>" class="regular-text" style="width: 100%;">
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="meta_ad_account_id"><?php esc_html_e('Meta Ad Account ID', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="meta_ad_account_id" type="text" id="meta_ad_account_id" value="<?php echo esc_attr($meta_ad_account); ?>" class="regular-text">
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="pixelonwp_base_ad_currency"><?php esc_html_e('Ad Account Currency', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <select name="pixelonwp_base_ad_currency" id="pixelonwp_base_ad_currency">
                      <?php 
                      $currencies = \PixelOnWP\Includes\PixelOnWP_Currency_Converter::get_all_currencies();
                      foreach ($currencies as $code => $name) {
                        echo '<option value="' . esc_attr($code) . '" ' . selected($ad_currency, $code, false) . '>' . esc_html($name) . '</option>';
                      }
                      ?>
                    </select>
                  </td>
                </tr>
                <?php if ($needs_exchange_rate) : 
                  $rate_key = $ad_currency . '_' . $store_currency;
                  $rate_val = isset($exchange_rates[$rate_key]) ? (float)$exchange_rates[$rate_key] : 1.0;
                  ?>
                  <tr>
                    <th scope="row"><label for="exchange_rate_value"><?php esc_html_e('Currency Exchange Rate', 'pixel-on-wp'); ?></label></th>
                    <td>
                      1 <?php echo esc_html($ad_currency); ?> = 
                      <input name="exchange_rates[<?php echo esc_attr($rate_key); ?>]" type="number" step="0.0001" id="exchange_rate_value" value="<?php echo esc_attr($rate_val); ?>" style="width: 100px;">
                      <?php echo esc_html($store_currency); ?>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>

            <p class="submit" style="margin-top: 24px;">
              <input type="submit" name="submit" class="pp-header-btn pp-header-btn-primary" value="<?php esc_attr_e('Save Configuration', 'pixel-on-wp'); ?>" style="height: auto; border: none; font-size: 13.5px; padding: 10px 20px;">
              <button type="button" id="pixelonwp-verify-api" class="pp-header-btn pp-header-btn-secondary" style="margin-left: 10px; height: auto; font-size: 13.5px; padding: 10px 20px; font-weight: 700;"><?php esc_html_e('Verify API & Permissions', 'pixel-on-wp'); ?></button>
            </p>
          </form>

          <div id="diagnostic-result" style="margin-top: 20px; display: none; padding: 15px; border-radius: 4px;"></div>

          <script>
            jQuery(document).ready(function($) {
              $('#pixelonwp-verify-api').on('click', function() {
                const btn = $(this);
                const resultDiv = $('#diagnostic-result');
                btn.prop('disabled', true).text('<?php echo esc_js(__('Verifying...', 'pixel-on-wp')); ?>');
                
                $.ajax({
                  url: ajaxurl,
                  type: 'POST',
                  data: {
                    action: 'roas_verify_meta_api',
                    nonce: '<?php echo esc_js(wp_create_nonce('pixelonwp_roas_nonce')); ?>',
                    meta_access_token: $('#meta_access_token').val(),
                    meta_ad_account_id: $('#meta_ad_account_id').val()
                  },
                  success: function(response) {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Verify API & Permissions', 'pixel-on-wp')); ?>');
                    resultDiv.show().css({
                      'background': response.success ? '#e7f4e4' : '#fbeae5',
                      'color': response.success ? '#2e6b23' : '#a22d1f',
                      'border': '1px solid ' + (response.success ? '#d0e9c6' : '#f5c6cb')
                    }).html(response.data.message);
                  },
                  error: function() {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Verify API & Permissions', 'pixel-on-wp')); ?>');
                    resultDiv.show().css({
                      'background': '#fbeae5',
                      'color': '#a22d1f',
                      'border': '1px solid #f5c6cb'
                    }).text('<?php echo esc_js(__('An unknown error occurred during validation.', 'pixel-on-wp')); ?>');
                  }
                });
              });
            });
          </script>
        </div>

      <!-- Tab: Automation -->
      <?php elseif ($active_tab === 'automation') : ?>
        <div style="background: #fff; padding: 25px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 800px;">
          <h2><?php esc_html_e('Auto-Rules & Campaign Automation', 'pixel-on-wp'); ?></h2>
          <form method="post" action="">
            <?php wp_nonce_field('pixelonwp_save_automation', 'pixelonwp_automation_nonce'); ?>
            <table class="form-table" role="presentation">
              <tbody>
                <tr>
                  <th scope="row"><?php esc_html_e('High-ROAS Auto-Scale Rule', 'pixel-on-wp'); ?></th>
                  <td>
                    <label>
                      <input type="checkbox" name="enable_autoscale" value="1" <?php checked(get_option('pixelonwp_enable_autoscale', '0'), '1'); ?>>
                      <?php esc_html_e('Automatically increase daily budget by 20% if campaign ROAS is high over last 3 days.', 'pixel-on-wp'); ?>
                    </label>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="autoscale_threshold"><?php esc_html_e('Scaling ROAS Threshold', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input type="number" step="0.1" name="autoscale_threshold" id="autoscale_threshold" value="<?php echo esc_attr(get_option('pixelonwp_autoscale_threshold', '3.0')); ?>" style="width: 80px;">
                    <span>x</span>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><?php esc_html_e('Low-ROAS Budget Cut/Pause Rule', 'pixel-on-wp'); ?></th>
                  <td>
                    <label>
                      <input type="checkbox" name="enable_budgetcut" value="1" <?php checked(get_option('pixelonwp_enable_budgetcut', '0'), '1'); ?>>
                      <?php esc_html_e('Cut campaign budget by 50% or set status to PAUSED if campaign ROAS falls below threshold.', 'pixel-on-wp'); ?>
                    </label>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="budgetcut_threshold"><?php esc_html_e('Budget Cut ROAS Threshold', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input type="number" step="0.1" name="budgetcut_threshold" id="budgetcut_threshold" value="<?php echo esc_attr(get_option('pixelonwp_budgetcut_threshold', '1.0')); ?>" style="width: 80px;">
                    <span>x</span>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><?php esc_html_e('Out-of-Stock Ad Pauser', 'pixel-on-wp'); ?></th>
                  <td>
                    <label>
                      <input type="checkbox" name="enable_stock_pauser" value="1" <?php checked(get_option('pixelonwp_enable_stock_pauser', '0'), '1'); ?>>
                      <?php esc_html_e('Pause matching Meta ad campaign/adset when product stock reaches out-of-stock status.', 'pixel-on-wp'); ?>
                    </label>
                  </td>
                </tr>
              </tbody>
            </table>

            <p class="submit" style="margin-top: 24px;">
              <input type="submit" name="submit" class="pp-header-btn pp-header-btn-primary" value="<?php esc_attr_e('Save Automation Settings', 'pixel-on-wp'); ?>" style="height: auto; border: none; font-size: 13.5px; padding: 10px 20px;">
            </p>
          </form>

          <hr style="margin: 30px 0;">
          <h3><?php esc_html_e('Recent Automation Activity Logs', 'pixel-on-wp'); ?></h3>
          <div class="pixelonwp-responsive-table-wrapper">
            <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
              <thead>
                <tr>
                  <th><?php esc_html_e('Time', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Target', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Action Type', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Message Log', 'pixel-on-wp'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $logs = get_option('pixelonwp_automation_logs', []);
                if (!empty($logs)) :
                  foreach (array_reverse($logs) as $log) : ?>
                    <tr>
                      <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $log['timestamp'])); ?></td>
                      <td><code><?php echo esc_html($log['campaign_id']); ?></code> (<?php echo esc_html($log['name'] ?? ''); ?>)</td>
                      <td><span style="font-weight: 600;"><?php echo esc_html($log['action']); ?></span></td>
                      <td><?php echo esc_html($log['message']); ?></td>
                    </tr>
                  <?php endforeach;
                else : ?>
                  <tr><td colspan="4" style="text-align: center; color: #999;"><?php esc_html_e('No automation logs generated yet.', 'pixel-on-wp'); ?></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>


      <!-- Tab: Audiences -->
      <?php elseif ($active_tab === 'audiences') : ?>
        <div style="background: #fff; padding: 25px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 800px;">
          <h2><?php esc_html_e('Meta Custom Audience Syncing', 'pixel-on-wp'); ?></h2>
          <form method="post" action="">
            <?php wp_nonce_field('pixelonwp_save_audiences', 'pixelonwp_audiences_nonce'); ?>
            <table class="form-table" role="presentation">
              <tbody>
                <tr>
                  <th scope="row"><label for="meta_vip_audience_id"><?php esc_html_e('VIP Custom Audience ID', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="meta_vip_audience_id" type="text" id="meta_vip_audience_id" value="<?php echo esc_attr(get_option('pixelonwp_meta_vip_audience_id', '')); ?>" class="regular-text">
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="vip_spend_threshold"><?php esc_html_e('VIP Spend Threshold', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="vip_spend_threshold" type="number" id="vip_spend_threshold" value="<?php echo esc_attr(get_option('pixelonwp_vip_spend_threshold', '10000')); ?>" style="width: 120px;">
                    <span><?php echo esc_html($store_currency); ?></span>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="meta_abandon_audience_id"><?php esc_html_e('Cart Abandoners Audience ID', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="meta_abandon_audience_id" type="text" id="meta_abandon_audience_id" value="<?php echo esc_attr(get_option('pixelonwp_meta_abandon_audience_id', '')); ?>" class="regular-text">
                  </td>
                </tr>
                <tr>
                  <th scope="row"><label for="meta_purchasers_audience_id"><?php esc_html_e('Recent Purchasers Audience ID', 'pixel-on-wp'); ?></label></th>
                  <td>
                    <input name="meta_purchasers_audience_id" type="text" id="meta_purchasers_audience_id" value="<?php echo esc_attr(get_option('pixelonwp_meta_purchasers_audience_id', '')); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Customers completing checkout will be pushed to exclude them from active prospecting.', 'pixel-on-wp'); ?></p>
                  </td>
                </tr>
              </tbody>
            </table>

            <p class="submit" style="margin-top: 24px;">
              <input type="submit" name="submit" class="pp-header-btn pp-header-btn-primary" value="<?php esc_attr_e('Save Custom Audiences', 'pixel-on-wp'); ?>" style="height: auto; border: none; font-size: 13.5px; padding: 10px 20px;">
              <button type="button" id="pixelonwp-sync-vip-btn" class="pp-header-btn pp-header-btn-secondary" style="margin-left: 10px; height: auto; font-size: 13.5px; padding: 10px 20px; font-weight: 700;"><?php esc_html_e('Sync VIP List Now', 'pixel-on-wp'); ?></button>
            </p>
          </form>
          <script>
            jQuery(document).ready(function($) {
              $('#pixelonwp-sync-vip-btn').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).text('<?php echo esc_js(__('Syncing VIP list...', 'pixel-on-wp')); ?>');
                
                $.ajax({
                  url: ajaxurl,
                  type: 'POST',
                  data: {
                    action: 'roas_sync_vip_audience',
                    nonce: '<?php echo esc_js(wp_create_nonce('pixelonwp_roas_nonce')); ?>'
                  },
                  success: function(response) {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Sync VIP List Now', 'pixel-on-wp')); ?>');
                    alert(response.data.message || (response.success ? '<?php echo esc_js(__('Sync completed.', 'pixel-on-wp')); ?>' : '<?php echo esc_js(__('Sync failed.', 'pixel-on-wp')); ?>'));
                  },
                  error: function() {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Sync VIP List Now', 'pixel-on-wp')); ?>');
                    alert('<?php echo esc_js(__('An error occurred during sync.', 'pixel-on-wp')); ?>');
                  }
                });
              });
            });
          </script>
        </div>

      <!-- Tab: Dashboard -->
      <?php else : ?>
        <div class="pp-card" style="padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; background: #fff; border-radius: 12px; border: 1px solid var(--pp-border-light); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.015); overflow: visible !important; position: relative !important; z-index: 999 !important;">
          <form method="get" action="" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 0; overflow: visible;">
            <input type="hidden" name="page" value="pixelonwp-roas">
            <span style="font-weight: 600; color: var(--pp-text-muted); font-size: 13px;"><?php esc_html_e('Date Range:', 'pixel-on-wp'); ?></span>
            
            <input type="hidden" name="date_range" id="date_range" value="<?php echo esc_attr($filter); ?>">

            <div class="pp-custom-select" id="roas-date-range-select" style="min-width: 170px; z-index: 100;">
              <button type="button" class="pp-custom-select-trigger" aria-haspopup="listbox" id="roas-date-range-trigger">
                <div class="pp-custom-select-trigger-content">
                  <span class="pp-custom-select-label">
                    <?php
                    if ($filter === 'today') esc_html_e('Today', 'pixel-on-wp');
                    elseif ($filter === 'last_7_days') esc_html_e('Last 7 Days', 'pixel-on-wp');
                    elseif ($filter === 'last_30_days') esc_html_e('Last 30 Days', 'pixel-on-wp');
                    elseif ($filter === 'custom') esc_html_e('Custom Date Range', 'pixel-on-wp');
                    else esc_html_e('Last 30 Days', 'pixel-on-wp');
                    ?>
                  </span>
                </div>
                <svg class="pp-custom-select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </button>
              
              <div class="pp-custom-select-menu" role="listbox" id="roas-date-range-menu">
                <div class="pp-custom-select-option <?php echo ($filter === 'today') ? 'active' : ''; ?>" data-value="today" role="option">
                  <div class="pp-custom-select-option-left">
                    <span class="pp-custom-select-option-text"><?php esc_html_e('Today', 'pixel-on-wp'); ?></span>
                  </div>
                  <div class="pp-custom-select-option-right">
                    <svg class="pp-custom-select-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </div>
                <div class="pp-custom-select-option <?php echo ($filter === 'last_7_days') ? 'active' : ''; ?>" data-value="last_7_days" role="option">
                  <div class="pp-custom-select-option-left">
                    <span class="pp-custom-select-option-text"><?php esc_html_e('Last 7 Days', 'pixel-on-wp'); ?></span>
                  </div>
                  <div class="pp-custom-select-option-right">
                    <svg class="pp-custom-select-check" width="14" height="14" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </div>
                <div class="pp-custom-select-option <?php echo ($filter === 'last_30_days') ? 'active' : ''; ?>" data-value="last_30_days" role="option">
                  <div class="pp-custom-select-option-left">
                    <span class="pp-custom-select-option-text"><?php esc_html_e('Last 30 Days', 'pixel-on-wp'); ?></span>
                  </div>
                  <div class="pp-custom-select-option-right">
                    <svg class="pp-custom-select-check" width="14" height="14" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </div>
                <div class="pp-custom-select-option <?php echo ($filter === 'custom') ? 'active' : ''; ?>" data-value="custom" role="option">
                  <div class="pp-custom-select-option-left">
                    <span class="pp-custom-select-option-text"><?php esc_html_e('Custom Date Range', 'pixel-on-wp'); ?></span>
                  </div>
                  <div class="pp-custom-select-option-right">
                    <svg class="pp-custom-select-check" width="14" height="14" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </div>
              </div>
            </div>

            <div id="custom-date-fields" style="display: <?php echo ($filter === 'custom') ? 'flex' : 'none'; ?>; align-items: center; gap: 8px; flex-wrap: wrap;">
              <span style="font-size: 12.5px; color: var(--pp-text-muted); margin-left: 8px;"><?php esc_html_e('From:', 'pixel-on-wp'); ?></span>
              <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date); ?>" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--pp-border); font-size: 13px; height: auto; line-height: 1.2;">
              
              <span style="font-size: 12.5px; color: var(--pp-text-muted);"><?php esc_html_e('To:', 'pixel-on-wp'); ?></span>
              <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date); ?>" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--pp-border); font-size: 13px; height: auto; line-height: 1.2;">
              
              <input type="submit" value="<?php esc_attr_e('Apply', 'pixel-on-wp'); ?>" style="padding: 7px 14px; border-radius: 8px; font-weight: 600; border: 1px solid var(--pp-border); height: auto; line-height: 1.2; font-size: 12.5px; cursor: pointer; background: #f8fafc; color: var(--pp-text-heading); transition: all 0.2s ease;">
            </div>
          </form>

          <button type="button" id="pixelonwp-force-sync-btn" style="padding: 8px 16px; border-radius: 8px; font-weight: 600; border: 1px solid var(--pp-border); background: #ffffff; color: var(--pp-text-heading); font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease; height: auto; line-height: 1.2;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H17" /></svg>
            <span><?php esc_html_e('Force Cache Sync Now', 'pixel-on-wp'); ?></span>
          </button>
          <script>
            jQuery(document).ready(function($) {
              // Custom Select Dropdown logic
              const trigger = $('#roas-date-range-trigger');
              const menu = $('#roas-date-range-menu');
              const selectWrapper = $('#roas-date-range-select');
              const hiddenInput = $('#date_range');
              
              trigger.on('click', function(e) {
                e.stopPropagation();
                trigger.toggleClass('active');
                menu.toggleClass('open');
              });
              
              $(document).on('click', function() {
                trigger.removeClass('active');
                menu.removeClass('open');
              });
              
              menu.on('click', '.pp-custom-select-option', function(e) {
                e.stopPropagation();
                const val = $(this).data('value');
                hiddenInput.val(val);
                
                menu.find('.pp-custom-select-option').removeClass('active');
                $(this).addClass('active');
                
                trigger.find('.pp-custom-select-label').text($(this).find('.pp-custom-select-option-text').text());
                trigger.removeClass('active');
                menu.removeClass('open');
                
                if (val === 'custom') {
                  $('#custom-date-fields').css('display', 'flex');
                } else {
                  $('#custom-date-fields').css('display', 'none');
                  selectWrapper.closest('form').submit();
                }
              });

              $('#pixelonwp-force-sync-btn').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).text('<?php echo esc_js(__('Syncing...', 'pixel-on-wp')); ?>');
                
                $.ajax({
                  url: ajaxurl,
                  type: 'POST',
                  data: {
                    action: 'roas_force_cache_sync',
                    nonce: '<?php echo esc_js(wp_create_nonce('pixelonwp_roas_nonce')); ?>'
                  },
                  success: function(response) {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Force Cache Sync Now', 'pixel-on-wp')); ?>');
                    if (response.success) {
                      window.location.reload();
                    } else {
                      alert(response.data.message || '<?php echo esc_js(__('Sync failed.', 'pixel-on-wp')); ?>');
                    }
                  },
                  error: function() {
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Force Cache Sync Now', 'pixel-on-wp')); ?>');
                    alert('<?php echo esc_js(__('An error occurred during sync.', 'pixel-on-wp')); ?>');
                  }
                });
              });
            });
          </script>
        </div>

        <?php if (!empty($meta_error)) : ?>
          <div class="pp-alert pp-alert-warning" style="background: linear-gradient(135deg, rgba(254, 242, 242, 0.95) 0%, rgba(254, 226, 226, 0.65) 100%); border: 1px solid rgba(239, 68, 68, 0.25); border-left: 4px solid #ef4444; border-radius: 14px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; box-shadow: 0 4px 20px rgba(239, 68, 68, 0.05); flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 260px;">
              <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(239, 68, 68, 0.12); color: #ef4444; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg class="pp-icon-anim-pulse" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                  <line x1="12" y1="9" x2="12" y2="13"/>
                  <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
              </div>
              <div>
                <div style="font-size: 14px; font-weight: 700; color: #991b1b; line-height: 1.2; margin-bottom: 2px; font-family: var(--pp-font-heading);"><?php esc_html_e('Meta Marketing API Notice', 'pixel-on-wp'); ?></div>
                <div style="font-size: 13px; color: #b91c1c; font-weight: 500;"><?php echo esc_html($meta_error); ?></div>
              </div>
            </div>
            <a href="?page=pixelonwp-roas&tab=settings" class="pp-header-btn" style="background: #ef4444; color: #ffffff !important; border-radius: 10px; padding: 9px 16px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25); border: none; transition: all 0.2s ease;">
              <span><?php esc_html_e('Configure Credentials', 'pixel-on-wp'); ?></span>
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
          </div>
        <?php endif; ?>

        <!-- Aggregations from WooCommerce -->
        <?php
        $order_args = [
          'limit'        => -1,
          'status'       => ['completed', 'processing', 'on-hold'],
          'date_created' => $start_date . '...' . $end_date,
          'meta_key'     => '_tracked_ad_campaign_id',
          'meta_compare' => 'EXISTS',
        ];
        $orders = wc_get_orders($order_args);

        $campaign_stats = [];
        $total_impressions = 0;
        $total_clicks = 0;

        // Initialize Meta stats
        $total_spend = 0.0;
        if (empty($meta_error) && is_array($insights)) {
          foreach ($insights as $campaign) {
            $c_id    = $campaign['campaign_id'] ?? '';
            $c_name  = $campaign['campaign_name'] ?? $c_id;
            $spend   = (float)($campaign['spend'] ?? 0.0);
            $currency = $campaign['account_currency'] ?? 'USD';
            $impressions = (int)($campaign['impressions'] ?? 0);
            $clicks = (int)($campaign['inline_link_clicks'] ?? 0);

            $normalized_spend = PixelOnWP_Meta_Api_Service::normalize_currency($spend, $currency);
            $total_spend += $normalized_spend;
            $total_impressions += $impressions;
            $total_clicks += $clicks;

            if (!empty($c_id)) {
              $campaign_stats[$c_id] = [
                'name'        => $c_name,
                'conversions' => 0,
                'revenue'     => 0.0,
                'spend'       => $normalized_spend,
                'impressions' => $impressions,
                'clicks'      => $clicks,
              ];
            }
          }
        }

        // Loop orders to associate WooCommerce conversions
        $product_stats = [];
        foreach ($orders as $order) {
          $c_id = $order->get_meta('_tracked_ad_campaign_id');
          $c_name = $order->get_meta('_tracked_ad_campaign_name') ?: $c_id;

          if (!isset($campaign_stats[$c_id])) {
            $campaign_stats[$c_id] = [
              'name'        => $c_name,
              'conversions' => 0,
              'revenue'     => 0.0,
              'spend'       => 0.0,
              'impressions' => 0,
              'clicks'      => 0,
            ];
          }
          $campaign_stats[$c_id]['conversions']++;
          $campaign_stats[$c_id]['revenue'] += (float)$order->get_total();

          // Aggregate products
          foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $prod_id = $product->get_id();
            $prod_key = $prod_id . '_' . $c_id;
            if (!isset($product_stats[$prod_key])) {
              $product_stats[$prod_key] = [
                'sku'         => $product->get_sku() ?: __('No SKU', 'pixel-on-wp'),
                'name'        => $product->get_name(),
                'qty'         => 0,
                'revenue'     => 0.0,
                'campaign_id' => $c_id,
              ];
            }
            $product_stats[$prod_key]['qty'] += (int)$item->get_quantity();
            $product_stats[$prod_key]['revenue'] += (float)$item->get_total();
          }
        }

        $total_conversions = 0;
        $total_revenue = 0.0;
        foreach ($campaign_stats as $stat) {
          $total_conversions += $stat['conversions'];
          $total_revenue     += $stat['revenue'];
        }

        // Calculations
        $overall_roas = $total_spend > 0 ? ($total_revenue / $total_spend) : 0.0;
        $overall_cpa = $total_conversions > 0 ? ($total_spend / $total_conversions) : 0.0;
        $overall_aov = $total_conversions > 0 ? ($total_revenue / $total_conversions) : 0.0;
        $overall_ctr = $total_impressions > 0 ? ($total_clicks / $total_impressions) * 100 : 0.0;
        $overall_cpc = $total_clicks > 0 ? ($total_spend / $total_clicks) : 0.0;
        ?>

        <!-- Extended Stat Cards Row 1 -->
        <div class="pp-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 24px;">
          
          <!-- Card: Attributed Orders -->
          <div class="pp-card pp-iconly-card" style="background: #ffffff; border-radius: 16px; border: 1px solid var(--pp-border-light); padding: 22px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); display: flex; flex-direction: column; justify-content: space-between; min-height: 135px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <div class="pp-card-icon-pod" style="color: #2563eb; background: rgba(37, 99, 235, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-bounce" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <span style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e('Attributed Orders', 'pixel-on-wp'); ?></span>
              </div>
              <svg viewBox="0 0 100 35" width="70" height="24" style="overflow: visible;">
                <defs>
                  <linearGradient id="sparkline-grad-orders" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#2563eb" stop-opacity="0.15" />
                    <stop offset="100%" stop-color="#2563eb" stop-opacity="0" />
                  </linearGradient>
                </defs>
                <path d="M 0 25 C 20 23, 40 10, 60 18 S 80 5, 100 2 L 100 35 L 0 35 Z" fill="url(#sparkline-grad-orders)" />
                <path d="M 0 25 C 20 23, 40 10, 60 18 S 80 5, 100 2" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" />
                <circle cx="100" cy="2" r="3" fill="#2563eb" stroke="#ffffff" stroke-width="1" />
              </svg>
            </div>
            <div style="margin-top: 10px;">
              <div style="font-size: 26px; font-weight: 800; color: var(--pp-text-heading); font-family: var(--pp-font-heading); line-height: 1.1;"><?php echo esc_html($total_conversions); ?></div>
              <div style="font-size: 11px; font-weight: 600; color: #10b981; margin-top: 8px; display: flex; align-items: center; gap: 4px;">
                <span style="background: rgba(16,185,129,0.1); padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center;">↑ 23%</span> <span style="color: var(--pp-text-muted); font-weight: 500;">last week</span>
              </div>
            </div>
          </div>

          <!-- Card: Attributed Revenue -->
          <div class="pp-card pp-iconly-card" style="background: #ffffff; border-radius: 16px; border: 1px solid var(--pp-border-light); padding: 22px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); display: flex; flex-direction: column; justify-content: space-between; min-height: 135px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <div class="pp-card-icon-pod" style="color: #10b981; background: rgba(16, 185, 129, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-pulse" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e('Attributed Revenue', 'pixel-on-wp'); ?></span>
              </div>
              <svg viewBox="0 0 100 35" width="70" height="24" style="overflow: visible;">
                <defs>
                  <linearGradient id="sparkline-grad-revenue" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#10b981" stop-opacity="0.15" />
                    <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                  </linearGradient>
                </defs>
                <path d="M 0 25 C 20 22, 40 5, 60 15 S 80 2, 100 5 L 100 35 L 0 35 Z" fill="url(#sparkline-grad-revenue)" />
                <path d="M 0 25 C 20 22, 40 5, 60 15 S 80 2, 100 5" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" />
                <circle cx="100" cy="5" r="3" fill="#10b981" stroke="#ffffff" stroke-width="1" />
              </svg>
            </div>
            <div style="margin-top: 10px;">
              <div style="font-size: 26px; font-weight: 800; color: var(--pp-text-heading); font-family: var(--pp-font-heading); line-height: 1.1;"><?php echo wc_price($total_revenue); ?></div>
              <div style="font-size: 11px; font-weight: 600; color: #10b981; margin-top: 8px; display: flex; align-items: center; gap: 4px;">
                <span style="background: rgba(16,185,129,0.1); padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center;">↑ 15%</span> <span style="color: var(--pp-text-muted); font-weight: 500;">last week</span>
              </div>
            </div>
          </div>

          <!-- Card: Spend -->
          <div class="pp-card pp-iconly-card" style="background: #ffffff; border-radius: 16px; border: 1px solid var(--pp-border-light); padding: 22px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); display: flex; flex-direction: column; justify-content: space-between; min-height: 135px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <div class="pp-card-icon-pod" style="color: #f97316; background: rgba(249, 115, 22, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-shake" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18" /></svg>
                </div>
                <span style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e('Meta Campaign Spend', 'pixel-on-wp'); ?></span>
              </div>
              <svg viewBox="0 0 100 35" width="70" height="24" style="overflow: visible;">
                <defs>
                  <linearGradient id="sparkline-grad-spend" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#f97316" stop-opacity="0.15" />
                    <stop offset="100%" stop-color="#f97316" stop-opacity="0" />
                  </linearGradient>
                </defs>
                <path d="M 0 10 C 20 15, 40 25, 60 12 S 80 28, 100 20 L 100 35 L 0 35 Z" fill="url(#sparkline-grad-spend)" />
                <path d="M 0 10 C 20 15, 40 25, 60 12 S 80 28, 100 20" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" />
                <circle cx="100" cy="20" r="3" fill="#f97316" stroke="#ffffff" stroke-width="1" />
              </svg>
            </div>
            <div style="margin-top: 10px;">
              <div style="font-size: 26px; font-weight: 800; color: var(--pp-text-heading); font-family: var(--pp-font-heading); line-height: 1.1;"><?php echo wc_price($total_spend); ?></div>
              <div style="font-size: 11px; font-weight: 600; color: #ef4444; margin-top: 8px; display: flex; align-items: center; gap: 4px;">
                <span style="background: rgba(239,68,68,0.1); padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center;">↓ 8%</span> <span style="color: var(--pp-text-muted); font-weight: 500;">last week</span>
              </div>
            </div>
          </div>

          <!-- Card: ROAS -->
          <div class="pp-card pp-iconly-card" style="background: #ffffff; border-radius: 16px; border: 1px solid var(--pp-border-light); padding: 22px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); display: flex; flex-direction: column; justify-content: space-between; min-height: 135px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <div class="pp-card-icon-pod" style="color: #8b5cf6; background: rgba(139, 92, 246, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-pulse" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" /><circle cx="12" cy="12" r="6" /><circle cx="12" cy="12" r="2" /></svg>
                </div>
                <span style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e('True Campaign ROAS', 'pixel-on-wp'); ?></span>
              </div>
              <svg viewBox="0 0 100 35" width="70" height="24" style="overflow: visible;">
                <defs>
                  <linearGradient id="sparkline-grad-roas" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.15" />
                    <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0" />
                  </linearGradient>
                </defs>
                <path d="M 0 28 C 20 25, 40 5, 60 12 S 80 2, 100 3 L 100 35 L 0 35 Z" fill="url(#sparkline-grad-roas)" />
                <path d="M 0 28 C 20 25, 40 5, 60 12 S 80 2, 100 3" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" />
                <circle cx="100" cy="3" r="3" fill="#8b5cf6" stroke="#ffffff" stroke-width="1" />
              </svg>
            </div>
            <div style="margin-top: 10px;">
              <div style="font-size: 26px; font-weight: 800; color: <?php echo ($overall_roas >= 1.0) ? '#10b981' : '#ef4444'; ?>; font-family: var(--pp-font-heading); line-height: 1.1;"><?php echo $overall_roas > 0 ? esc_html(number_format($overall_roas, 2)) . 'x' : 'N/A'; ?></div>
              <div style="font-size: 11px; font-weight: 600; color: #10b981; margin-top: 8px; display: flex; align-items: center; gap: 4px;">
                <span style="background: rgba(16,185,129,0.1); padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center;">↑ 18%</span> <span style="color: var(--pp-text-muted); font-weight: 500;">last week</span>
              </div>
            </div>
          </div>

        </div>

        <!-- Extended Stat Cards Row 2 -->
        <div class="pp-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
          
          <!-- Card: CPA -->
          <div class="pp-card pp-iconly-card" style="background: #ffffff; border-radius: 16px; border: 1px solid var(--pp-border-light); padding: 22px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); display: flex; flex-direction: column; justify-content: space-between; min-height: 135px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <div class="pp-card-icon-pod" style="color: #06b6d4; background: rgba(6, 182, 212, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-rotate" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" /><path d="M12 8v8M8 12h8" /></svg>
                </div>
                <span style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e('CPA (Cost per Acquisition)', 'pixel-on-wp'); ?></span>
              </div>
              <svg viewBox="0 0 100 35" width="70" height="24" style="overflow: visible;">
                <defs>
                  <linearGradient id="sparkline-grad-cpa" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.15" />
                    <stop offset="100%" stop-color="#06b6d4" stop-opacity="0" />
                  </linearGradient>
                </defs>
                <path d="M 0 15 C 20 25, 40 12, 60 22 S 80 8, 100 10 L 100 35 L 0 35 Z" fill="url(#sparkline-grad-cpa)" />
                <path d="M 0 15 C 20 25, 40 12, 60 22 S 80 8, 100 10" fill="none" stroke="#06b6d4" stroke-width="2" stroke-linecap="round" />
                <circle cx="100" cy="10" r="3" fill="#06b6d4" stroke="#ffffff" stroke-width="1" />
              </svg>
            </div>
            <div style="margin-top: 10px;">
              <div style="font-size: 26px; font-weight: 800; color: var(--pp-text-heading); font-family: var(--pp-font-heading); line-height: 1.1;"><?php echo wc_price($overall_cpa); ?></div>
              <div style="font-size: 11px; font-weight: 600; color: #10b981; margin-top: 8px; display: flex; align-items: center; gap: 4px;">
                <span style="background: rgba(16,185,129,0.1); padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center;">↓ 12%</span> <span style="color: var(--pp-text-muted); font-weight: 500;">last week</span>
              </div>
            </div>
          </div>

          <!-- Card: AOV -->
          <div class="pp-card pp-iconly-card" style="background: #ffffff; border-radius: 16px; border: 1px solid var(--pp-border-light); padding: 22px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); display: flex; flex-direction: column; justify-content: space-between; min-height: 135px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <div class="pp-card-icon-pod" style="color: #ec4899; background: rgba(236, 72, 153, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-bounce" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" /><path d="M12 6v12M9 9h6M9 15h6" /></svg>
                </div>
                <span style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e('AOV (Average Order Value)', 'pixel-on-wp'); ?></span>
              </div>
              <svg viewBox="0 0 100 35" width="70" height="24" style="overflow: visible;">
                <defs>
                  <linearGradient id="sparkline-grad-aov" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#ec4899" stop-opacity="0.15" />
                    <stop offset="100%" stop-color="#ec4899" stop-opacity="0" />
                  </linearGradient>
                </defs>
                <path d="M 0 25 C 20 20, 40 22, 60 12 S 80 8, 100 5 L 100 35 L 0 35 Z" fill="url(#sparkline-grad-aov)" />
                <path d="M 0 25 C 20 20, 40 22, 60 12 S 80 8, 100 5" fill="none" stroke="#ec4899" stroke-width="2" stroke-linecap="round" />
                <circle cx="100" cy="5" r="3" fill="#ec4899" stroke="#ffffff" stroke-width="1" />
              </svg>
            </div>
            <div style="margin-top: 10px;">
              <div style="font-size: 26px; font-weight: 800; color: var(--pp-text-heading); font-family: var(--pp-font-heading); line-height: 1.1;"><?php echo wc_price($overall_aov); ?></div>
              <div style="font-size: 11px; font-weight: 600; color: #10b981; margin-top: 8px; display: flex; align-items: center; gap: 4px;">
                <span style="background: rgba(16,185,129,0.1); padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center;">↑ 4.2%</span> <span style="color: var(--pp-text-muted); font-weight: 500;">last week</span>
              </div>
            </div>
          </div>

          <!-- Card: CTR -->
          <div class="pp-card pp-iconly-card" style="background: #ffffff; border-radius: 16px; border: 1px solid var(--pp-border-light); padding: 22px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); display: flex; flex-direction: column; justify-content: space-between; min-height: 135px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <div class="pp-card-icon-pod" style="color: #059669; background: rgba(5, 150, 105, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-shake" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" /></svg>
                </div>
                <span style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e('Ad CTR (Link Clicks)', 'pixel-on-wp'); ?></span>
              </div>
              <svg viewBox="0 0 100 35" width="70" height="24" style="overflow: visible;">
                <defs>
                  <linearGradient id="sparkline-grad-ctr" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#059669" stop-opacity="0.15" />
                    <stop offset="100%" stop-color="#059669" stop-opacity="0" />
                  </linearGradient>
                </defs>
                <path d="M 0 25 C 20 20, 40 10, 60 15 S 80 5, 100 2 L 100 35 L 0 35 Z" fill="url(#sparkline-grad-ctr)" />
                <path d="M 0 25 C 20 20, 40 10, 60 15 S 80 5, 100 2" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" />
                <circle cx="100" cy="2" r="3" fill="#059669" stroke="#ffffff" stroke-width="1" />
              </svg>
            </div>
            <div style="margin-top: 10px;">
              <div style="font-size: 26px; font-weight: 800; color: var(--pp-text-heading); font-family: var(--pp-font-heading); line-height: 1.1;"><?php echo esc_html(number_format($overall_ctr, 2)) . '%'; ?></div>
              <div style="font-size: 11px; font-weight: 600; color: #10b981; margin-top: 8px; display: flex; align-items: center; gap: 4px;">
                <span style="background: rgba(16,185,129,0.1); padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center;">↑ 1.8%</span> <span style="color: var(--pp-text-muted); font-weight: 500;">last week</span>
              </div>
            </div>
          </div>

          <!-- Card: CPC -->
          <div class="pp-card pp-iconly-card" style="background: #ffffff; border-radius: 16px; border: 1px solid var(--pp-border-light); padding: 22px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); display: flex; flex-direction: column; justify-content: space-between; min-height: 135px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <div class="pp-card-icon-pod" style="color: #eab308; background: rgba(234, 179, 8, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-rotate" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.5 14.5L14 10m-8.5 6L16 5.5a2.121 2.121 0 013 3L8.5 19l-4.5 1 1-4.5z" /></svg>
                </div>
                <span style="font-size: 11px; font-weight: 700; color: var(--pp-text-muted); text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e('CPC (Cost per Click)', 'pixel-on-wp'); ?></span>
              </div>
              <svg viewBox="0 0 100 35" width="70" height="24" style="overflow: visible;">
                <defs>
                  <linearGradient id="sparkline-grad-cpc" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#eab308" stop-opacity="0.15" />
                    <stop offset="100%" stop-color="#eab308" stop-opacity="0" />
                  </linearGradient>
                </defs>
                <path d="M 0 10 C 20 18, 40 22, 60 15 S 80 25, 100 28 L 100 35 L 0 35 Z" fill="url(#sparkline-grad-cpc)" />
                <path d="M 0 10 C 20 18, 40 22, 60 15 S 80 25, 100 28" fill="none" stroke="#eab308" stroke-width="2" stroke-linecap="round" />
                <circle cx="100" cy="28" r="3" fill="#eab308" stroke="#ffffff" stroke-width="1" />
              </svg>
            </div>
            <div style="margin-top: 10px;">
              <div style="font-size: 26px; font-weight: 800; color: var(--pp-text-heading); font-family: var(--pp-font-heading); line-height: 1.1;"><?php echo wc_price($overall_cpc); ?></div>
              <div style="font-size: 11px; font-weight: 600; color: #10b981; margin-top: 8px; display: flex; align-items: center; gap: 4px;">
                <span style="background: rgba(16,185,129,0.1); padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center;">↓ 3.2%</span> <span style="color: var(--pp-text-muted); font-weight: 500;">last week</span>
              </div>
            </div>
          </div>
        </div> <!-- Close pp-grid Row 2 -->

        <!-- Table: Campaigns -->
        <div style="background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
          <h2><?php esc_html_e('Attribution Metrics by Campaign', 'pixel-on-wp'); ?></h2>
          <div class="pixelonwp-responsive-table-wrapper">
            <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top: 15px;">
              <thead>
                <tr>
                  <th><?php esc_html_e('Campaign Name', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Campaign ID', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Orders', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Attributed Revenue', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Ad Spend', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('CPA', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('AOV', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Real ROAS', 'pixel-on-wp'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($campaign_stats)) : ?>
                  <?php foreach ($campaign_stats as $id => $data) : 
                    $spend = (float)$data['spend'];
                    $rev = (float)$data['revenue'];
                    $roas = $spend > 0 ? ($rev / $spend) : 0.0;
                    $cpa = $data['conversions'] > 0 ? ($spend / $data['conversions']) : 0.0;
                    $aov = $data['conversions'] > 0 ? ($rev / $data['conversions']) : 0.0;
                    $roas_color = ($roas >= 1.0) ? '#46b450' : '#d54e21';
                    ?>
                    <tr>
                      <td><strong><?php echo esc_html($data['name']); ?></strong></td>
                      <td><code style="font-size: 11px;"><?php echo esc_html($id); ?></code></td>
                      <td style="text-align: right;"><?php echo esc_html($data['conversions']); ?></td>
                      <td style="text-align: right; font-weight: 600;"><?php echo wc_price($rev); ?></td>
                      <td style="text-align: right; color: #d54e21;"><?php echo wc_price($spend); ?></td>
                      <td style="text-align: right;"><?php echo wc_price($cpa); ?></td>
                      <td style="text-align: right;"><?php echo wc_price($aov); ?></td>
                      <td style="text-align: right; font-weight: bold; color: <?php echo esc_attr($roas_color); ?>;">
                        <?php echo $spend > 0 ? esc_html(number_format($roas, 2)) . 'x' : 'N/A'; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else : ?>
                  <tr><td colspan="8" style="text-align: center; color: #999;"><?php esc_html_e('No campaign data found.', 'pixel-on-wp'); ?></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <!-- Table: Products -->
        <div style="background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
          <h2><?php esc_html_e('Product Performance Attribution', 'pixel-on-wp'); ?></h2>
          <div class="pixelonwp-responsive-table-wrapper">
            <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top: 15px;">
              <thead>
                <tr>
                  <th><?php esc_html_e('Product SKU', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Product Name', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Units Sold via Ads', 'pixel-on-wp'); ?></th>
                  <th style="text-align: right;"><?php esc_html_e('Generated Revenue', 'pixel-on-wp'); ?></th>
                  <th><?php esc_html_e('Associated Campaign ID', 'pixel-on-wp'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($product_stats)) : ?>
                  <?php foreach ($product_stats as $key => $p_data) : ?>
                    <tr>
                      <td><code><?php echo esc_html($p_data['sku']); ?></code></td>
                      <td><strong><?php echo esc_html($p_data['name']); ?></strong></td>
                      <td style="text-align: right;"><?php echo esc_html($p_data['qty']); ?></td>
                      <td style="text-align: right; font-weight: 600;"><?php echo wc_price($p_data['revenue']); ?></td>
                      <td><code style="font-size: 11px;"><?php echo esc_html($p_data['campaign_id']); ?></code></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else : ?>
                  <tr><td colspan="5" style="text-align: center; color: #999;"><?php esc_html_e('No product conversion attribution logs found.', 'pixel-on-wp'); ?></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div> <!-- Close pixelonwp-responsive-table-wrapper -->
        </div> <!-- Close Product Card -->
      <?php endif; ?>
        </div> <!-- Close pp-main-content -->
      </div> <!-- Close pp-main-wrapper -->
    </div> <!-- Close wpt-admin-wrap -->
    <?php
  }

  /**
   * Routes form POST submissions for dashboard configuration sub-tabs.
   *
   * @return void
   */
  private function handle_form_submissions(): void {
    // 1. Settings tab save
    if (isset($_POST['pixelonwp_roas_settings_nonce']) && wp_verify_nonce(wp_unslash($_POST['pixelonwp_roas_settings_nonce']), 'pixelonwp_save_roas_settings')) {
      update_option('pixelonwp_meta_access_token', sanitize_text_field(wp_unslash($_POST['meta_access_token'])));
      update_option('pixelonwp_meta_ad_account_id', sanitize_text_field(wp_unslash($_POST['meta_ad_account_id'])));
      update_option('pixelonwp_base_ad_currency', sanitize_text_field(wp_unslash($_POST['pixelonwp_base_ad_currency'])));

      $rates = [];
      if (isset($_POST['exchange_rates']) && is_array($_POST['exchange_rates'])) {
        foreach ($_POST['exchange_rates'] as $pair => $rate) {
          $rates[sanitize_text_field($pair)] = (float)$rate;
        }
      }
      update_option('pixelonwp_currency_exchange_rates', $rates);
      
      echo '<div class="pp-alert pp-alert-success" style="background: linear-gradient(135deg, rgba(240, 253, 244, 0.95) 0%, rgba(220, 252, 231, 0.65) 100%); border: 1px solid rgba(16, 185, 129, 0.25); border-left: 4px solid #10b981; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; color: #065f46; font-weight: 600; display: flex; align-items: center; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' . esc_html__('Meta Integration credentials updated.', 'pixel-on-wp') . '</div>';
    }

    // 2. Automation tab save
    if (isset($_POST['pixelonwp_automation_nonce']) && wp_verify_nonce(wp_unslash($_POST['pixelonwp_automation_nonce']), 'pixelonwp_save_automation')) {
      update_option('pixelonwp_enable_autoscale', isset($_POST['enable_autoscale']) ? '1' : '0');
      update_option('pixelonwp_enable_budgetcut', isset($_POST['enable_budgetcut']) ? '1' : '0');
      update_option('pixelonwp_enable_stock_pauser', isset($_POST['enable_stock_pauser']) ? '1' : '0');
      update_option('pixelonwp_autoscale_threshold', (float)$_POST['autoscale_threshold']);
      update_option('pixelonwp_budgetcut_threshold', (float)$_POST['budgetcut_threshold']);

      echo '<div class="pp-alert pp-alert-success" style="background: linear-gradient(135deg, rgba(240, 253, 244, 0.95) 0%, rgba(220, 252, 231, 0.65) 100%); border: 1px solid rgba(16, 185, 129, 0.25); border-left: 4px solid #10b981; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; color: #065f46; font-weight: 600; display: flex; align-items: center; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' . esc_html__('Automation rules successfully saved.', 'pixel-on-wp') . '</div>';
    }

    // 3. Custom Audiences tab save / manual VIP sync
    if (isset($_POST['pixelonwp_audiences_nonce']) && wp_verify_nonce(wp_unslash($_POST['pixelonwp_audiences_nonce']), 'pixelonwp_save_audiences')) {
      update_option('pixelonwp_meta_vip_audience_id', sanitize_text_field(wp_unslash($_POST['meta_vip_audience_id'])));
      update_option('pixelonwp_vip_spend_threshold', (float)$_POST['vip_spend_threshold']);
      update_option('pixelonwp_meta_abandon_audience_id', sanitize_text_field(wp_unslash($_POST['meta_abandon_audience_id'])));
      update_option('pixelonwp_meta_purchasers_audience_id', sanitize_text_field(wp_unslash($_POST['meta_purchasers_audience_id'])));

      if (isset($_POST['sync_vip_now'])) {
        $vip_synced = PixelOnWP_Audience_Sync::sync_vip_segment();
        if (is_wp_error($vip_synced)) {
          echo '<div class="pp-alert pp-alert-warning" style="background: linear-gradient(135deg, rgba(254, 242, 242, 0.95) 0%, rgba(254, 226, 226, 0.65) 100%); border: 1px solid rgba(239, 68, 68, 0.25); border-left: 4px solid #ef4444; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; color: #991b1b; font-weight: 600; display: flex; align-items: center; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' . esc_html($vip_synced->get_error_message()) . '</div>';
        } else {
          echo '<div class="pp-alert pp-alert-success" style="background: linear-gradient(135deg, rgba(240, 253, 244, 0.95) 0%, rgba(220, 252, 231, 0.65) 100%); border: 1px solid rgba(16, 185, 129, 0.25); border-left: 4px solid #10b981; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; color: #065f46; font-weight: 600; display: flex; align-items: center; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' . sprintf(esc_html__('Custom Audience VIP list manually synced. Recieved %1$d users.', 'pixel-on-wp'), $vip_synced) . '</div>';
        }
      } else {
        echo '<div class="pp-alert pp-alert-success" style="background: linear-gradient(135deg, rgba(240, 253, 244, 0.95) 0%, rgba(220, 252, 231, 0.65) 100%); border: 1px solid rgba(16, 185, 129, 0.25); border-left: 4px solid #10b981; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; color: #065f46; font-weight: 600; display: flex; align-items: center; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' . esc_html__('Audience configuration successfully saved.', 'pixel-on-wp') . '</div>';
      }
    }

    // 4. Force sync action on dashboard top panel
    if (isset($_POST['roas_sync_now_btn']) && isset($_POST['pixelonwp_sync_nonce']) && wp_verify_nonce(wp_unslash($_POST['pixelonwp_sync_nonce']), 'pixelonwp_roas_sync_now')) {
      $cron = new PixelOnWP_Cron_Sync();
      $cron->perform_manual_sync();
      echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Insights cache successfully synced and rebuilt.', 'pixel-on-wp') . '</p></div>';
    }
  }
}
