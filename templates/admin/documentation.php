<?php
/**
 * PixelOnWP Setup & Operational User Manual
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'user-docs';
?>

<div class="wpt-admin-wrap" id="wpt-admin-app-user-docs">
  <!-- Static Sidebar -->
  <div class="pp-sidebar">
    <div class="pp-sidebar-brand" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <div class="pp-brand-logo" style="background: var(--pp-primary); color: #fff; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);">P</div>
        <span class="pp-brand-name" style="font-weight: 800; font-size: 16px; color: var(--pp-text-heading); font-family: var(--pp-font-heading);">PixelOnWP</span>
        <span class="pp-badge" style="font-size: 10px; padding: 2px 6px; background: rgba(225, 29, 72, 0.1); color: var(--pp-primary); font-weight: 700; border-radius: 4px;">v1.0.3</span>
      </div>
      <button type="button" class="pp-sidebar-toggle-btn" id="pp-sidebar-close" style="display: none; background: none; border: none; color: var(--pp-text-muted); cursor: pointer; padding: 4px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <nav class="pp-sidebar-nav">
      <div class="pp-nav-section-title"><?php esc_html_e('MAIN', 'pixel-on-wp'); ?></div>
      <a href="admin.php?page=pixelonwp-dashboard" class="pp-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        <span><?php esc_html_e('Dashboard', 'pixel-on-wp'); ?></span>
      </a>
      <a href="admin.php?page=pixelonwp-dashboard#setup" class="pp-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L5.6 15.13a2 2 0 01-1.022-.547l-1.07-1.07a2 2 0 010-2.828l1.07-1.07a2 2 0 011.022-.547l2.387-.477a6 6 0 003.86-.517l.318-.158a6 6 0 013.86-.517l2.387.477a2 2 0 011.022.547l1.07 1.07a2 2 0 010 2.828l-1.07 1.07z"/></svg>
        <span><?php esc_html_e('Setup Wizard', 'pixel-on-wp'); ?></span>
      </a>
      <a href="admin.php?page=pixelonwp-dashboard#license" class="pp-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        <span><?php esc_html_e('License Activation', 'pixel-on-wp'); ?></span>
      </a>

      <div class="pp-nav-section-title"><?php esc_html_e('TRACKING & CAPI', 'pixel-on-wp'); ?></div>
      <a href="admin.php?page=pixelonwp-dashboard#server-side" class="pp-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
        <span><?php esc_html_e('Server-Side & ITP', 'pixel-on-wp'); ?></span>
      </a>
      <a href="admin.php?page=pixelonwp-dashboard#events" class="pp-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <span><?php esc_html_e('Event Manager', 'pixel-on-wp'); ?></span>
      </a>
      <a href="admin.php?page=pixelonwp-dashboard#gtm" class="pp-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
        <span><?php esc_html_e('GTM Integration', 'pixel-on-wp'); ?></span>
      </a>
      <a href="admin.php?page=pixelonwp-dashboard#universal" class="pp-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
        <span><?php esc_html_e('Universal Tracker', 'pixel-on-wp'); ?></span>
      </a>

      <div class="pp-nav-section-title"><?php esc_html_e('AI & ANALYTICS', 'pixel-on-wp'); ?></div>
      <a href="admin.php?page=pixelonwp-roas" class="pp-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span><?php esc_html_e('Ad Attribution & ROAS', 'pixel-on-wp'); ?></span>
      </a>

      <div class="pp-nav-section-title"><?php esc_html_e('HELP & MANUAL', 'pixel-on-wp'); ?></div>
      <a href="admin.php?page=pixelonwp-documentation" class="pp-nav-item active">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        <span><?php esc_html_e('User Manual', 'pixel-on-wp'); ?></span>
      </a>
      <a href="admin.php?page=pixel-admin-docs" class="pp-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
        <span><?php esc_html_e('Developer Docs', 'pixel-on-wp'); ?></span>
      </a>
    </nav>
  </div>

  <!-- Main Content Wrapper -->
  <div class="pp-main-wrapper" style="flex: 1; display: flex; flex-direction: column; min-width: 0;">
    
    <!-- Top Header Bar -->
    <div class="pp-top-header" style="background: var(--pp-surface); border-bottom: 1px solid var(--pp-border); padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; gap: 16px; position: sticky; top: 0; z-index: 100;">
      <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
        <button type="button" class="pp-menu-toggle" id="pp-sidebar-open" style="display: none; background: none; border: 1px solid var(--pp-border); border-radius: 8px; padding: 6px; color: var(--pp-text-main); cursor: pointer;">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="pp-badge pp-badge-success" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 20px; font-size: 12px; font-weight: 700;">
          <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
          <?php esc_html_e('Tracking Engine Active', 'pixel-on-wp'); ?>
        </div>
      </div>
      <div class="pp-header-search" onclick="window.location.href='admin.php?page=pixelonwp-dashboard'" style="display: flex; align-items: center; gap: 8px; background: rgba(0,0,0,0.02); border: 1px solid var(--pp-border); padding: 8px 16px; border-radius: 20px; font-size: 13px; color: var(--pp-text-muted); max-width: 320px; width: 100%; cursor: pointer;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span>Search features, pixels...</span>
        <kbd style="margin-left: auto; background: var(--pp-surface); border: 1px solid var(--pp-border); padding: 2px 6px; border-radius: 4px; font-size: 11px;">⌘K</kbd>
      </div>
      <button class="pp-header-btn pp-header-btn-primary" onclick="window.location.href='admin.php?page=pixelonwp-dashboard#setup'" style="display: inline-flex; align-items: center; gap: 8px; background: var(--pp-primary); color: #fff; border: none; padding: 9px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        <span>Add Pixel</span>
      </button>
    </div>

    <!-- Main Content Body -->
    <div class="pp-main-content" style="flex: 1; padding: 32px; overflow-y: auto;">

      <style>
        #wpt-admin-app-user-docs {
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
        }
        #wpt-admin-app-user-docs * {
          box-sizing: border-box;
        }
        #wpt-admin-app-user-docs .pp-sidebar {
          width: 250px;
          background: var(--pp-surface);
          border-right: 1px solid var(--pp-border);
          display: flex;
          flex-direction: column;
          position: sticky;
          top: 0;
          height: 100vh;
          overflow-y: auto;
          z-index: 1000;
          transition: transform 0.3s ease;
        }
        #wpt-admin-app-user-docs .pp-sidebar-brand {
          padding: 24px;
          border-bottom: 1px solid var(--pp-border-light);
        }
        #wpt-admin-app-user-docs .pp-sidebar-nav {
          padding: 16px;
          display: flex;
          flex-direction: column;
          gap: 4px;
        }
        #wpt-admin-app-user-docs .pp-nav-section-title {
          font-size: 10px;
          font-weight: 700;
          color: var(--pp-text-muted);
          letter-spacing: 0.05em;
          padding: 12px 12px 6px 12px;
        }
        #wpt-admin-app-user-docs .pp-nav-item {
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 10px 14px;
          color: var(--pp-text-muted);
          text-decoration: none;
          border-radius: var(--pp-radius);
          font-weight: 500;
          font-size: 13.5px;
          transition: var(--pp-transition);
        }
        #wpt-admin-app-user-docs .pp-nav-item svg {
          width: 18px;
          height: 18px;
          opacity: 0.7;
        }
        #wpt-admin-app-user-docs .pp-nav-item:hover {
          color: var(--pp-text-heading);
          background: rgba(0, 0, 0, 0.03);
        }
        #wpt-admin-app-user-docs .pp-nav-item.active {
          color: var(--pp-primary);
          background: rgba(225, 29, 72, 0.08);
          font-weight: 700;
        }
        #wpt-admin-app-user-docs .pp-nav-item.active svg {
          opacity: 1;
          color: var(--pp-primary);
        }

        /* Hero Header Banner */
        .pp-docs-hero {
          background: linear-gradient(135deg, #059669 0%, #047857 100%);
          border-radius: 16px;
          padding: 36px 32px;
          color: #fff;
          margin-bottom: 24px;
          box-shadow: 0 12px 30px rgba(5, 150, 105, 0.18);
          position: relative;
          overflow: hidden;
          border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .pp-docs-hero::before {
          content: '';
          position: absolute;
          top: -50%;
          right: -20%;
          width: 400px;
          height: 400px;
          background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, rgba(0, 0, 0, 0) 70%);
          pointer-events: none;
        }

        /* Search & Online Documentation Card */
        .pp-docs-online-card {
          background: #ffffff;
          border-radius: 16px;
          border: 1px solid var(--pp-border-light);
          padding: 20px 24px;
          margin-bottom: 28px;
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 16px;
          box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
          flex-wrap: wrap;
        }
        .pp-docs-online-search {
          display: flex;
          align-items: center;
          gap: 10px;
          background: #f8fafc;
          border: 1px solid var(--pp-border-light);
          padding: 10px 16px;
          border-radius: 10px;
          flex: 1;
          min-width: 260px;
          color: var(--pp-text-muted);
          font-size: 13.5px;
        }

        /* Section Navigation Bar */
        .pp-docs-nav-pills {
          display: flex;
          align-items: center;
          gap: 10px;
          margin-bottom: 28px;
          overflow-x: auto;
          padding-bottom: 4px;
          -webkit-overflow-scrolling: touch;
        }
        .pp-docs-nav-pill {
          background: #ffffff;
          border: 1px solid var(--pp-border-light);
          padding: 8px 16px;
          border-radius: 20px;
          font-size: 12.5px;
          font-weight: 600;
          color: var(--pp-text-muted);
          text-decoration: none;
          white-space: nowrap;
          transition: all 0.2s ease;
          display: inline-flex;
          align-items: center;
          gap: 6px;
          box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .pp-docs-nav-pill:hover {
          color: #059669;
          border-color: rgba(5, 150, 105, 0.3);
          background: rgba(5, 150, 105, 0.04);
        }

        /* Category Section Dividers */
        .pp-docs-section-divider {
          display: flex;
          align-items: center;
          gap: 12px;
          margin: 32px 0 18px 0;
        }
        .pp-docs-section-divider h3 {
          margin: 0;
          font-size: 16px;
          font-weight: 800;
          color: var(--pp-text-heading);
          font-family: var(--pp-font-heading);
          letter-spacing: -0.01em;
          white-space: nowrap;
        }
        .pp-docs-section-divider .line {
          flex: 1;
          height: 1px;
          background: var(--pp-border-light);
        }

        .pp-docs-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
          gap: 22px;
          margin-bottom: 10px;
        }
        .pp-docs-card {
          background: #ffffff;
          border-radius: 16px;
          border: 1px solid var(--pp-border-light);
          padding: 24px;
          box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          transition: box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1), transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .pp-docs-card:hover {
          transform: translateY(-3px);
          box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }
        .pp-docs-card-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 16px;
          flex-wrap: wrap;
          gap: 8px;
        }
        .pp-docs-card-title {
          display: flex;
          align-items: center;
          gap: 12px;
          font-size: 15.5px;
          font-weight: 700;
          color: var(--pp-text-heading);
          font-family: var(--pp-font-heading);
        }
        .pp-docs-card code {
          background: rgba(16, 185, 129, 0.06);
          color: #047857;
          border: 1px solid rgba(16, 185, 129, 0.18);
          padding: 2px 7px;
          border-radius: 6px;
          font-family: monospace;
          font-size: 12.5px;
          font-weight: 600;
          word-break: break-word;
        }
        .pp-docs-tag {
          font-size: 10.5px;
          font-weight: 700;
          text-transform: uppercase;
          letter-spacing: 0.05em;
          padding: 3px 8px;
          border-radius: 6px;
        }

        /* Fully Responsive Media Queries */
        @media (max-width: 1024px) {
          .pp-top-header {
            padding: 14px 20px !important;
          }
          .pp-main-content {
            padding: 24px 20px !important;
          }
          .pp-docs-grid {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
          }
        }

        @media (max-width: 768px) {
          #wpt-admin-app-user-docs .pp-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            transform: translateX(-100%);
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
          }
          #wpt-admin-app-user-docs .pp-sidebar.open {
            transform: translateX(0);
          }
          #wpt-admin-app-user-docs #pp-sidebar-close,
          #wpt-admin-app-user-docs #pp-sidebar-open {
            display: inline-flex !important;
          }
          .pp-header-search {
            display: none !important;
          }
          .pp-top-header {
            padding: 12px 16px !important;
          }
          .pp-main-content {
            padding: 18px 14px !important;
          }
          .pp-docs-hero {
            padding: 24px 20px;
            margin-bottom: 20px;
          }
          .pp-docs-hero h1 {
            font-size: 22px !important;
          }
          .pp-docs-hero p {
            font-size: 13.5px !important;
          }
          .pp-docs-grid {
            grid-template-columns: 1fr;
            gap: 16px;
          }
          .pp-docs-card {
            padding: 18px 16px;
          }
          .pp-docs-online-card {
            flex-direction: column;
            align-items: stretch;
          }
        }

        @media (max-width: 480px) {
          .pp-top-header {
            flex-wrap: wrap;
          }
          .pp-docs-hero {
            padding: 20px 16px;
          }
          .pp-docs-hero h1 {
            font-size: 19px !important;
          }
          .pp-docs-card-title {
            font-size: 14.5px;
          }
        }
      </style>

      <!-- Hero Header Banner -->
      <div class="pp-docs-hero">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
          <span class="pp-badge" style="background: rgba(255, 255, 255, 0.2); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.35); font-weight: 700; font-size: 11px; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em;">
            <?php esc_html_e('User Guide & Setup Manual', 'pixel-on-wp'); ?>
          </span>
          <span style="color: #a7f3d0; font-size: 12px; font-weight: 600;">v1.0.3</span>
        </div>
        <h1 style="color: #ffffff; font-size: 28px; margin: 0 0 8px 0; font-weight: 800; font-family: var(--pp-font-heading); letter-spacing: -0.01em;">
          <?php esc_html_e('Setup & Operational User Manual', 'pixel-on-wp'); ?>
        </h1>
        <p style="font-size: 14.5px; margin: 0; color: #d1fae5; max-width: 780px; line-height: 1.6;">
          <?php esc_html_e('Everything you need to master your WordPress tracking, from basic pixel setup to advanced Conversions API integrations and fraud protection.', 'pixel-on-wp'); ?>
        </p>
      </div>

      <!-- Search & Online Documentation Banner -->
      <div class="pp-docs-online-card">
        <div class="pp-docs-online-search">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="text" placeholder="<?php esc_attr_e('Search user manual guides...', 'pixel-on-wp'); ?>" style="border: none; background: transparent; outline: none; width: 100%; font-size: 13.5px; color: var(--pp-text-main);" />
        </div>
        <a href="<?php echo esc_url(home_url('/pixelonwp/docs/user-documents')); ?>" target="_blank" rel="noopener noreferrer" class="pp-header-btn pp-header-btn-primary" style="background: #059669; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);">
          <span><?php esc_html_e('Explore Online Documentation', 'pixel-on-wp'); ?></span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        </a>
      </div>

      <!-- Quick Section Jump Navigation -->
      <div class="pp-docs-nav-pills">
        <a href="#sec-creds" class="pp-docs-nav-pill">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
          <span><?php esc_html_e('Credentials Sourcing', 'pixel-on-wp'); ?></span>
        </a>
        <a href="#sec-events" class="pp-docs-nav-pill">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          <span><?php esc_html_e('Events & eCommerce', 'pixel-on-wp'); ?></span>
        </a>
        <a href="#sec-sec" class="pp-docs-nav-pill">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span><?php esc_html_e('Security & Diagnostics', 'pixel-on-wp'); ?></span>
        </a>
      </div>

      <!-- SECTION 1: CREDENTIALS & INTEGRATIONS SOURCING -->
      <div class="pp-docs-section-divider" id="sec-creds">
        <h3><?php esc_html_e('1. Credentials & Platform Integration Keys', 'pixel-on-wp'); ?></h3>
        <div class="line"></div>
      </div>

      <div class="pp-docs-grid">

        <!-- Card 1: Setup Wizard & Credentials -->
        <div class="pp-docs-card">
          <div>
            <div class="pp-docs-card-header">
              <div class="pp-docs-card-title">
                <div class="pp-card-icon-pod" style="color: #10b981; background: rgba(16, 185, 129, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-bounce" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                </div>
                <span><?php esc_html_e('Credentials Sourcing Guide', 'pixel-on-wp'); ?></span>
              </div>
              <span class="pp-docs-tag" style="background: rgba(16, 185, 129, 0.08); color: #10b981;"><?php esc_html_e('Credentials', 'pixel-on-wp'); ?></span>
            </div>
            <p style="color: var(--pp-text-muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 14px;">
              Retrieve and configure integration keys for all active tracking platforms:
            </p>
            <ul style="padding-left: 18px; color: var(--pp-text-main); font-size: 13px; line-height: 1.7; margin: 0;">
              <li style="margin-bottom: 6px;"><strong>Meta:</strong> Copy the Pixel ID from Meta Events Manager, generate a CAPI Access Token under Settings, and add a Test Event Code to verify dispatches.</li>
              <li style="margin-bottom: 6px;"><strong>TikTok & Reddit:</strong> Retrieve Pixel IDs and Conversions API tokens from their respective developer settings panels.</li>
              <li><strong>GA4 & Google Ads:</strong> Copy Measurement IDs, MP secrets, Conversion IDs, and conversion labels.</li>
            </ul>
          </div>
        </div>

      </div>

      <!-- SECTION 2: ECOMMERCE EVENTS & MAPPINGS -->
      <div class="pp-docs-section-divider" id="sec-events">
        <h3><?php esc_html_e('2. eCommerce Events & Conversion Mapping', 'pixel-on-wp'); ?></h3>
        <div class="line"></div>
      </div>

      <div class="pp-docs-grid">

        <!-- Card 2: Event Manager & Ecommerce -->
        <div class="pp-docs-card">
          <div>
            <div class="pp-docs-card-header">
              <div class="pp-docs-card-title">
                <div class="pp-card-icon-pod" style="color: #2563eb; background: rgba(37, 99, 235, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-pulse" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <span><?php esc_html_e('Events & eCommerce Mappings', 'pixel-on-wp'); ?></span>
              </div>
              <span class="pp-docs-tag" style="background: rgba(37, 99, 235, 0.08); color: #2563eb;"><?php esc_html_e('Event Mapping', 'pixel-on-wp'); ?></span>
            </div>
            <p style="color: var(--pp-text-muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 14px;">
              Control how WooCommerce store interactions map to standard advertising conversions:
            </p>
            <ul style="padding-left: 18px; color: var(--pp-text-main); font-size: 13px; line-height: 1.7; margin: 0;">
              <li style="margin-bottom: 6px;"><strong>Event Toggles:</strong> Toggle tracking variables individually for <code>ViewContent</code>, <code>AddToCart</code>, <code>InitiateCheckout</code>, and <code>Purchase</code> events.</li>
              <li style="margin-bottom: 6px;"><strong>Triggers mapping:</strong> Customize which order states (e.g. <code>Completed</code> or <code>Processing</code>) send a purchase conversion.</li>
              <li><strong>GTM Integration:</strong> Paste your GTM container key to automatically inject required header and body scripts.</li>
            </ul>
          </div>
        </div>

      </div>

      <!-- SECTION 3: SECURITY SHIELDS & DIAGNOSTICS -->
      <div class="pp-docs-section-divider" id="sec-sec">
        <h3><?php esc_html_e('3. Security Shields, Utilities & Diagnostics', 'pixel-on-wp'); ?></h3>
        <div class="line"></div>
      </div>

      <div class="pp-docs-grid">

        <!-- Card 3: Utilities & Security -->
        <div class="pp-docs-card">
          <div>
            <div class="pp-docs-card-header">
              <div class="pp-docs-card-title">
                <div class="pp-card-icon-pod" style="color: #06b6d4; background: rgba(6, 182, 212, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-rotate" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span><?php esc_html_e('Security & Utility Manuals', 'pixel-on-wp'); ?></span>
              </div>
              <span class="pp-docs-tag" style="background: rgba(6, 182, 212, 0.08); color: #06b6d4;"><?php esc_html_e('Security', 'pixel-on-wp'); ?></span>
            </div>
            <p style="color: var(--pp-text-muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 14px;">
              Activate advanced fraud shields, custom redirects, and campaign taggers:
            </p>
            <ul style="padding-left: 18px; color: var(--pp-text-main); font-size: 13px; line-height: 1.7; margin: 0;">
              <li style="margin-bottom: 6px;"><strong>Bot Protection:</strong> Enable the fraud blocker to dynamically drop tracking enqueues for search crawlers and scrapers.</li>
              <li style="margin-bottom: 6px;"><strong>Cookie Consent:</strong> Integrates with active consent banners to block pixels until approval is granted.</li>
              <li style="margin-bottom: 6px;"><strong>UTM Link Builder:</strong> Generate campaign-tracked links directly from your dashboard utilities panel.</li>
              <li><strong>Header & Footer:</strong> Inject custom tracking scripts without modifying theme files.</li>
            </ul>
          </div>
        </div>

        <!-- Card 4: Troubleshooting & Logs -->
        <div class="pp-docs-card">
          <div>
            <div class="pp-docs-card-header">
              <div class="pp-docs-card-title">
                <div class="pp-card-icon-pod" style="color: #8b5cf6; background: rgba(139, 92, 246, 0.08); border-radius: 12px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg class="pp-icon-anim-shake" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
                <span><?php esc_html_e('Troubleshooting & Event Logs', 'pixel-on-wp'); ?></span>
              </div>
              <span class="pp-docs-tag" style="background: rgba(139, 92, 246, 0.08); color: #8b5cf6;"><?php esc_html_e('Diagnostics', 'pixel-on-wp'); ?></span>
            </div>
            <p style="color: var(--pp-text-muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 14px;">
              Verify event dispatches and inspect CAPI response status codes:
            </p>
            <ul style="padding-left: 18px; color: var(--pp-text-main); font-size: 13px; line-height: 1.7; margin: 0;">
              <li style="margin-bottom: 6px;"><strong>Browser extensions:</strong> Install Meta, TikTok, and Reddit Pixel Helper extensions to audit pixel triggers live.</li>
              <li><strong>CAPI Audit Logs:</strong> Check the Diagnostics & Logs table to review server payloads, timestamps, and API response codes.</li>
            </ul>
          </div>
        </div>

      </div> <!-- Close pp-docs-grid -->

    </div> <!-- Close pp-main-content -->
  </div> <!-- Close pp-main-wrapper -->
</div> <!-- Close wpt-admin-wrap -->

<script>
jQuery(document).ready(function($) {
  $('#pp-sidebar-open').on('click', function() {
    $('.pp-sidebar').addClass('open');
  });
  $('#pp-sidebar-close').on('click', function() {
    $('.pp-sidebar').removeClass('open');
  });
});
</script>
