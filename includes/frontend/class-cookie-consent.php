<?php
/**
 * Advanced Cookie Consent & Geo-Compliance Class.
 *
 * @package PixelOnWP\Includes\Frontend
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Frontend;

if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Cookie_Consent
{
  private $config;
  private $detected_country;
  private $applied_rule;

  public function __construct()
  {
    $this->config = get_option('PixelOnWP_cookie_consent', []);
    $this->detect_geo();
    $this->determine_rule();
  }

  private function detect_geo(): void
  {
    $engine = $this->config['geo_engine'] ?? 'cloudflare';
    
    if ($engine === 'cloudflare' && isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
      $this->detected_country = strtoupper(sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']));
    } else {
      // Fallback or Native (Not a full GeoIP, simplified for V1 without MaxMind DB)
      // Usually would query WooCommerce if available: WC_Geolocation::geolocate_ip()
      $this->detected_country = 'UNKNOWN'; 
    }
  }

  private function determine_rule(): void
  {
    $this->applied_rule = null;
    $rules = $this->config['geo_rules'] ?? [];
    
    foreach ($rules as $rule) {
      if (in_array($this->detected_country, $rule['countries'] ?? [])) {
        $this->applied_rule = $rule;
        break;
      }
    }
  }

  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
  {
    if (empty($this->config['enabled']) || $this->config['enabled'] === 'false') {
      return;
    }

    $loader->add_action('wp_head', $this, 'inject_consent_mode_v2', 0);
    $loader->add_action('wp_head', $this, 'inject_custom_scripts_head', 1);
    $loader->add_action('wp_body_open', $this, 'inject_custom_scripts_body', 1);
    $loader->add_action('wp_footer', $this, 'inject_custom_scripts_footer', 98);
    $loader->add_action('wp_footer', $this, 'render_cookie_banner', 99);
  }

  private function get_banner_behavior(): string
  {
    if ($this->config['mode'] === 'custom' && $this->applied_rule) {
      return $this->applied_rule['banner_behavior'] ?? 'opt_in';
    }
    if ($this->config['mode'] === 'custom' && !$this->applied_rule) {
      return $this->config['fallback_behavior'] === 'optout' ? 'opt_out' : 'opt_in';
    }
    
    if ($this->config['mode'] === 'optout') return 'opt_out';
    if ($this->config['mode'] === 'notice') return 'notice'; 
    
    return 'opt_in'; // Default strict
  }

  public function inject_consent_mode_v2(): void
  {
    if (empty($this->config['cm_v2']) || $this->config['cm_v2'] === 'false') {
      return;
    }

    $behavior = $this->get_banner_behavior();
    $default_state = ($behavior === 'opt_out' || $behavior === 'hidden') ? 'granted' : 'denied';
    
    echo "
    <!-- PixelOnWP: Google Advanced Consent Mode V2 -->
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      
      var defaultState = '{$default_state}';
      var storedConsent = null;
      var cm = document.cookie.match(new RegExp('(^| )pixelonwp_consent=([^;]+)'));
      var ls = null;
      try { ls = window.localStorage.getItem('pixelonwp_consent'); } catch(e) {}
      
      if (cm) {
        try { storedConsent = JSON.parse(decodeURIComponent(cm[2])); } catch(e) {}
      } else if (ls) {
        try { storedConsent = JSON.parse(ls); } catch(e) {}
      }
      
      if (!storedConsent && cm && cm[2] === 'granted') {
         storedConsent = { analytics: true, marketing: true, functional: true };
      }
      
      gtag('consent', 'default', {
        'ad_storage': storedConsent && storedConsent.marketing ? 'granted' : defaultState,
        'ad_user_data': storedConsent && storedConsent.marketing ? 'granted' : defaultState,
        'ad_personalization': storedConsent && storedConsent.marketing ? 'granted' : defaultState,
        'analytics_storage': storedConsent && storedConsent.analytics ? 'granted' : defaultState,
        'personalization_storage': storedConsent && storedConsent.functional ? 'granted' : defaultState,
        'wait_for_update': 500
      });
      gtag('set', 'ads_data_redaction', true);
    </script>
    <!-- End PixelOnWP Consent Mode V2 -->
    ";
  }

  private function inject_scripts(string $location): void
  {
    $scripts = $this->config['scripts'] ?? [];
    foreach ($scripts as $script) {
      if (($script['location'] ?? 'head') === $location && !empty($script['content'])) {
        $cat = $script['category'] ?? 'marketing';
        
        // Wrap the script so it only executes when consent is granted
        echo "\n<!-- PixelOnWP Script Manager: " . esc_html($script['name']) . " -->\n";
        echo "<script>
          (function() {
            function runScript() {
              var el = document.createElement('div');
              el.innerHTML = " . wp_json_encode($script['content']) . ";
              var s = el.getElementsByTagName('script');
              for(var i=0; i<s.length; i++) {
                var newS = document.createElement('script');
                if (s[i].src) newS.src = s[i].src;
                if (s[i].innerHTML) newS.innerHTML = s[i].innerHTML;
                document.head.appendChild(newS);
              }
            }
            if ('{$cat}' === 'necessary') {
              runScript();
            } else {
              var hasConsent = false;
              var m = document.cookie.match(new RegExp('(^| )pixelonwp_consent=([^;]+)'));
              if (m) {
                try {
                  var state = JSON.parse(decodeURIComponent(m[2]));
                  if (state['{$cat}']) hasConsent = true;
                } catch(e) {
                  if (m[2] === 'granted') hasConsent = true;
                }
              }
              if (hasConsent) {
                runScript();
              } else {
                window.addEventListener('pixelonwp_consent_{$cat}', runScript);
                // Backwards compat with legacy global accept
                window.addEventListener('pixelonwp_consent_granted', runScript);
              }
            }
          })();
        </script>\n";
      }
    }
  }

  public function inject_custom_scripts_head(): void { $this->inject_scripts('head'); }
  public function inject_custom_scripts_body(): void { $this->inject_scripts('body'); }
  public function inject_custom_scripts_footer(): void { $this->inject_scripts('footer'); }

  public function render_cookie_banner(): void
  {
    $behavior = $this->get_banner_behavior();
    if ($behavior === 'hidden') return;

    $banner = $this->config['banner'] ?? [];
    $layout = $banner['layout'] ?? 'floating_bottom';
    $title = esc_html($banner['title'] ?? 'Privacy Preferences');
    $desc = esc_html($banner['description'] ?? '');
    $policy = esc_url($banner['policy_url'] ?? '/privacy-policy');
    $btn_accept = esc_html($banner['btn_accept'] ?? 'Accept All');
    $btn_reject = esc_html($banner['btn_reject'] ?? 'Reject All');
    $btn_prefs = esc_html($banner['btn_prefs'] ?? 'Cookie Settings');
    $btn_save = esc_html($banner['btn_save'] ?? 'Save My Preferences');
    
    // Categories
    $c_n_t = esc_html($banner['cat_necessary_title'] ?? 'Strictly Necessary');
    $c_n_d = esc_html($banner['cat_necessary_desc'] ?? 'These cookies are essential for the website to function properly.');
    $c_a_t = esc_html($banner['cat_analytics_title'] ?? 'Analytics & Performance');
    $c_a_d = esc_html($banner['cat_analytics_desc'] ?? 'Cookies to measure and improve performance.');
    $c_m_t = esc_html($banner['cat_marketing_title'] ?? 'Marketing & Targeting');
    $c_m_d = esc_html($banner['cat_marketing_desc'] ?? 'Cookies set by advertising partners to show relevant adverts.');
    $c_f_t = esc_html($banner['cat_functional_title'] ?? 'Functional & Preferences');
    $c_f_d = esc_html($banner['cat_functional_desc'] ?? 'Cookies for enhanced functionality and personalization.');

    $bg = esc_attr($banner['color_bg'] ?? '#1e293b');
    $text = esc_attr($banner['color_text'] ?? '#f8fafc');
    $btn_bg = esc_attr($banner['color_btn'] ?? '#3b82f6');
    $custom_css = $banner['custom_css'] ?? '';
    $expiry_days = intval($banner['expiry_days'] ?? 365);
    if ($expiry_days <= 0) $expiry_days = 365;
    $expiry_seconds = $expiry_days * 24 * 60 * 60;

    echo "
    <style>
      :root {
        --pp-cc-bg: {$bg};
        --pp-cc-text: {$text};
        --pp-cc-primary: {$btn_bg};
      }
      
      /* Layer 1: Banner */
      #pp-cookie-banner {
        position: fixed; z-index: 999998;
        background: var(--pp-cc-bg); color: var(--pp-cc-text);
        box-shadow: 0 -4px 15px rgba(0,0,0,0.1);
        font-family: inherit; display: none !important;
      }
      #pp-cookie-banner.layout-floating_bottom {
        bottom: 20px; left: 20px; right: 20px; border-radius: 12px;
        padding: 24px; display: flex; flex-direction: column; gap: 16px;
      }
      @media (min-width: 768px) {
        #pp-cookie-banner.layout-floating_bottom { flex-direction: row; align-items: center; justify-content: space-between; }
      }
      #pp-cookie-banner.layout-corner_badge {
        bottom: 20px; left: 20px; width: 350px; border-radius: 12px;
        padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      }
      #pp-cookie-banner.layout-center_modal {
        top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 90%; max-width: 500px; border-radius: 16px;
        padding: 32px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);
      }
      .pp-cb-title { font-weight: bold; font-size: 18px; margin-bottom: 8px; }
      .pp-cb-desc { font-size: 14px; opacity: 0.9; margin-bottom: 12px; line-height: 1.5; }
      .pp-cb-desc a { color: var(--pp-cc-primary); text-decoration: underline; }
      .pp-cb-btns { display: flex; gap: 12px; flex-wrap: wrap; }
      .pp-cb-btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: opacity 0.2s; }
      .pp-cb-btn:hover { opacity: 0.8; }
      .pp-cb-accept { background: var(--pp-cc-primary); color: #fff; }
      .pp-cb-reject { background: transparent; border: 1px solid currentColor; color: inherit; }
      .pp-cb-prefs { background: transparent; text-decoration: underline; color: inherit; padding: 10px 0; border: none; cursor: pointer; }

      /* Layer 2: Preference Center */
      #pp-preference-center {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5); z-index: 999999;
        display: none; align-items: center; justify-content: center;
        backdrop-filter: blur(4px);
      }
      .pp-pc-modal {
        background: var(--pp-cc-bg); color: var(--pp-cc-text);
        width: 90%; max-width: 800px; max-height: 90vh;
        border-radius: 12px; display: flex; flex-direction: column;
        overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
      }
      .pp-pc-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; }
      .pp-pc-header h2 { margin: 0; font-size: 20px; }
      .pp-pc-close { background: transparent; border: none; color: inherit; font-size: 24px; cursor: pointer; opacity: 0.7; }
      
      .pp-pc-body { display: flex; flex: 1; overflow: hidden; flex-direction: column; }
      @media (min-width: 768px) { .pp-pc-body { flex-direction: row; } }
      
      .pp-pc-sidebar { width: 100%; border-bottom: 1px solid rgba(255,255,255,0.1); overflow-y: auto; }
      @media (min-width: 768px) { .pp-pc-sidebar { width: 250px; border-right: 1px solid rgba(255,255,255,0.1); border-bottom: none; } }
      .pp-pc-tab { padding: 16px 24px; cursor: pointer; transition: background 0.2s; font-weight: 500; border-bottom: 1px solid rgba(255,255,255,0.05); }
      .pp-pc-tab:hover { background: rgba(255,255,255,0.05); }
      .pp-pc-tab.active { background: rgba(255,255,255,0.1); border-left: 4px solid var(--pp-cc-primary); }
      
      .pp-pc-content { flex: 1; padding: 24px; overflow-y: auto; }
      .pp-pc-panel { display: none; }
      .pp-pc-panel.active { display: block; animation: fadeIn 0.3s; }
      .pp-pc-panel h3 { margin-top: 0; margin-bottom: 12px; font-size: 18px; display: flex; justify-content: space-between; align-items: center; }
      .pp-pc-panel p { font-size: 14px; opacity: 0.8; line-height: 1.6; }
      
      .pp-pc-footer { padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
      
      /* iOS Toggle */
      .pp-toggle-switch { position: relative; display: inline-block; width: 46px; height: 24px; }
      .pp-toggle-switch input { opacity: 0; width: 0; height: 0; }
      .pp-toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.2); transition: .4s; border-radius: 24px; }
      .pp-toggle-slider:before { position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
      .pp-toggle-switch input:checked + .pp-toggle-slider { background-color: var(--pp-cc-primary); }
      .pp-toggle-switch input:checked + .pp-toggle-slider:before { transform: translateX(22px); }
      .pp-toggle-always-on { font-size: 13px; color: var(--pp-cc-primary); font-weight: bold; }

      {$custom_css}
    </style>
    ";

    echo "
    <!-- Layer 1: Banner -->
    <div id='pp-cookie-banner' class='layout-{$layout}'>
      <div class='pp-cb-content'>
        <div class='pp-cb-title'>{$title}</div>
        <div class='pp-cb-desc'>{$desc} <a href='{$policy}'>Privacy Policy</a></div>
      </div>
      <div class='pp-cb-btns'>
        <button id='pp-cb-prefs-btn' class='pp-cb-prefs'>{$btn_prefs}</button>
        <button id='pp-cb-reject-btn' class='pp-cb-btn pp-cb-reject'>{$btn_reject}</button>
        <button id='pp-cb-accept-btn' class='pp-cb-btn pp-cb-accept'>{$btn_accept}</button>
      </div>
    </div>

    <!-- Layer 2: Preference Center Modal -->
    <div id='pp-preference-center'>
      <div class='pp-pc-modal'>
        <div class='pp-pc-header'>
          <h2>{$btn_prefs}</h2>
          <button id='pp-pc-close-btn' class='pp-pc-close'>&times;</button>
        </div>
        <div class='pp-pc-body'>
          <div class='pp-pc-sidebar'>
            <div class='pp-pc-tab active' data-target='pc-cat-necessary'>{$c_n_t}</div>
            <div class='pp-pc-tab' data-target='pc-cat-analytics'>{$c_a_t}</div>
            <div class='pp-pc-tab' data-target='pc-cat-marketing'>{$c_m_t}</div>
            <div class='pp-pc-tab' data-target='pc-cat-functional'>{$c_f_t}</div>
          </div>
          <div class='pp-pc-content'>
            <div id='pc-cat-necessary' class='pp-pc-panel active'>
              <h3>{$c_n_t} <span class='pp-toggle-always-on'>Always Active</span></h3>
              <p>{$c_n_d}</p>
            </div>
            <div id='pc-cat-analytics' class='pp-pc-panel'>
              <h3>{$c_a_t} <label class='pp-toggle-switch'><input type='checkbox' id='chk-cat-analytics'><span class='pp-toggle-slider'></span></label></h3>
              <p>{$c_a_d}</p>
            </div>
            <div id='pc-cat-marketing' class='pp-pc-panel'>
              <h3>{$c_m_t} <label class='pp-toggle-switch'><input type='checkbox' id='chk-cat-marketing'><span class='pp-toggle-slider'></span></label></h3>
              <p>{$c_m_d}</p>
            </div>
            <div id='pc-cat-functional' class='pp-pc-panel'>
              <h3>{$c_f_t} <label class='pp-toggle-switch'><input type='checkbox' id='chk-cat-functional'><span class='pp-toggle-slider'></span></label></h3>
              <p>{$c_f_d}</p>
            </div>
          </div>
        </div>
        <div class='pp-pc-footer'>
          <button id='pp-pc-save-btn' class='pp-cb-btn pp-cb-reject'>{$btn_save}</button>
          <div style='display:flex; gap:12px;'>
            <button id='pp-pc-reject-btn' class='pp-cb-btn pp-cb-reject'>{$btn_reject}</button>
            <button id='pp-pc-accept-btn' class='pp-cb-btn pp-cb-accept'>{$btn_accept}</button>
          </div>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('pp-cookie-banner');
        const prefCenter = document.getElementById('pp-preference-center');
        
        let currentConsent = null;
        
        // Check localStorage first as requested
        try {
            let lsMatch = window.localStorage.getItem('pixelonwp_consent');
            if (lsMatch) currentConsent = JSON.parse(lsMatch);
        } catch(e) {}

        // Fallback to cookie if localStorage is empty
        if (!currentConsent) {
            const cookieMatch = document.cookie.match(new RegExp('(^| )pixelonwp_consent=([^;]+)'));
            if (cookieMatch) {
              try {
                currentConsent = JSON.parse(decodeURIComponent(cookieMatch[2]));
              } catch(e) {
                 if (cookieMatch[2] === 'granted') {
                   currentConsent = { analytics: true, marketing: true, functional: true };
                 } else {
                   currentConsent = { analytics: false, marketing: false, functional: false };
                 }
              }
            }
        }
        
        if (currentConsent) {
          if (banner) banner.style.setProperty('display', 'none', 'important');
        } else {
          if (banner) banner.style.setProperty('display', banner.classList.contains('layout-center_modal') ? 'block' : 'flex', 'important');
        }

        // Preference Center Tabs
        const tabs = document.querySelectorAll('.pp-pc-tab');
        const panels = document.querySelectorAll('.pp-pc-panel');
        tabs.forEach(tab => {
          tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(tab.getAttribute('data-target')).classList.add('active');
          });
        });

        // Open/Close Pref Center
        document.getElementById('pp-cb-prefs-btn').addEventListener('click', () => {
          if (currentConsent) {
            document.getElementById('chk-cat-analytics').checked = !!currentConsent.analytics;
            document.getElementById('chk-cat-marketing').checked = !!currentConsent.marketing;
            document.getElementById('chk-cat-functional').checked = !!currentConsent.functional;
          }
          prefCenter.style.display = 'flex';
        });
        document.getElementById('pp-pc-close-btn').addEventListener('click', () => {
          prefCenter.style.display = 'none';
        });

        const setConsent = (stateObj) => {
          const jsonStr = encodeURIComponent(JSON.stringify(stateObj));
          const d = new Date();
          let expSecs = {$expiry_seconds};
          if (!expSecs || expSecs <= 0) expSecs = 31536000; // 365 days fallback
          
          d.setTime(d.getTime() + (expSecs * 1000));
          document.cookie = 'pixelonwp_consent=' + jsonStr + '; path=/; expires=' + d.toUTCString() + '; max-age=' + expSecs + '; SameSite=Lax';
          try { window.localStorage.setItem('pixelonwp_consent', JSON.stringify(stateObj)); } catch(e) {}
          console.log('PixelOnWP Consent Saved:', stateObj);
          banner.style.setProperty('display', 'none', 'important');
          prefCenter.style.setProperty('display', 'none', 'important');
          
          if (typeof gtag === 'function') {
            gtag('consent', 'update', {
              'ad_storage': stateObj.marketing ? 'granted' : 'denied',
              'ad_user_data': stateObj.marketing ? 'granted' : 'denied',
              'ad_personalization': stateObj.marketing ? 'granted' : 'denied',
              'analytics_storage': stateObj.analytics ? 'granted' : 'denied',
              'personalization_storage': stateObj.functional ? 'granted' : 'denied'
            });
          }

          // Dispatch granular events
          window.dispatchEvent(new CustomEvent('pixelonwp_consent_updated', { detail: stateObj }));
          if (stateObj.analytics) window.dispatchEvent(new Event('pixelonwp_consent_analytics'));
          if (stateObj.marketing) window.dispatchEvent(new Event('pixelonwp_consent_marketing'));
          if (stateObj.functional) window.dispatchEvent(new Event('pixelonwp_consent_functional'));

          // Global legacy event for back-compat
          if (stateObj.analytics || stateObj.marketing || stateObj.functional) {
            window.dispatchEvent(new Event('pixelonwp_consent_granted'));
          }

          // Send AJAX log
          const globalStatus = (stateObj.marketing && stateObj.analytics) ? 'granted' : (stateObj.marketing || stateObj.analytics ? 'partial' : 'denied');
          const fd = new FormData();
          fd.append('action', 'pixelonwp_log_consent_proof');
          fd.append('status', globalStatus);
          fetch('" . admin_url('admin-ajax.php') . "', { method: 'POST', body: fd });
        };

        const acceptAll = () => setConsent({ analytics: true, marketing: true, functional: true });
        const rejectAll = () => setConsent({ analytics: false, marketing: false, functional: false });
        const savePrefs = () => setConsent({
          analytics: document.getElementById('chk-cat-analytics').checked,
          marketing: document.getElementById('chk-cat-marketing').checked,
          functional: document.getElementById('chk-cat-functional').checked
        });

        // Buttons Layer 1
        document.getElementById('pp-cb-accept-btn').addEventListener('click', acceptAll);
        document.getElementById('pp-cb-reject-btn').addEventListener('click', rejectAll);
        
        // Buttons Layer 2
        document.getElementById('pp-pc-accept-btn').addEventListener('click', acceptAll);
        document.getElementById('pp-pc-reject-btn').addEventListener('click', rejectAll);
        document.getElementById('pp-pc-save-btn').addEventListener('click', savePrefs);
      });
    </script>
    ";
  }
}
