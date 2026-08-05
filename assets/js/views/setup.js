import { showToast } from '../components/toaster.js';

export function renderSetup(container, state) {
  const header = document.createElement('div');
  header.className = 'pp-view-header';
  header.style.marginBottom = '32px';
  header.innerHTML = `
    <h2 style="font-size: 1.5rem; color: var(--pp-text-main); margin-bottom: 8px;">Setup Wizard</h2>
    <p style="color: var(--pp-text-muted); margin: 0;">Configure your tracking pixels and server-side connections.</p>
  `;

  const wizardContainer = document.createElement('div');
  wizardContainer.className = 'pp-wizard-container';
  wizardContainer.style.maxWidth = '1200px';
  wizardContainer.style.margin = '0 auto';

  const platformsSelected = state.config?.platforms || [];
  const metaConfig = state.config?.meta || { pixel_id: '', capi_token: '', test_code: '', events: {} };
  const tiktokConfig = state.config?.tiktok || { pixel_id: '', access_token: '', test_code: '', events: {} };
  const redditConfig = state.config?.reddit || { pixel_id: '', access_token: '', test_code: '', events: {} };
  const pinterestConfig = state.config?.pinterest || { tag_id: '', ad_account_id: '', access_token: '', enhanced_match: true, first_party_cookies: true, test_mode: false, events: {}, mappings: {} };
  const linkedinConfig = state.config?.linkedin || { partner_id: '', access_token: '', conversion_id: '', purchase_rule_id: '', add_to_cart_rule_id: '', initiate_checkout_rule_id: '', advanced_matching: false, events: {} };
  const googleConfig = state.config?.google || { conversion_id: '', enhanced_conversions: false, events: [] };
  const ga4Config = state.config?.ga4_config || { setup_type: 'basic', measurement_id: '', api_secret: '', test_code: '', events: {}, custom_events: [] };

  const isPlatformActive = (platform) => platformsSelected.includes(platform);
  let activePlatform = null;

  // SVG Icons
  const icons = {
    check: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>`,
    spinner: `<svg class="btn-spinner" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`,
    settings: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>`
  };

  const brandThemes = {
    facebook: {
      titleLine1: 'Meta Pixel',
      titleLine2: '& Conversions API',
      bg: 'linear-gradient(135deg, #1877F2 0%, #0056C6 100%)',
      glowColor: 'rgba(24, 119, 242, 0.45)',
      titleColor: '#FFFFFF',
      subtitleColor: 'rgba(255, 255, 255, 0.85)',
      btnBg: '#FFFFFF',
      btnText: '#1877F2',
      circleBg: '#0F172A',
      circleIconColor: '#FFFFFF',
      activeBtnBg: '#0F172A',
      activeBtnText: '#FFFFFF',
      emblemSvg: `<div class="emblem-pod" style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; filter: drop-shadow(0 15px 30px rgba(0,0,0,0.3)); transform: rotate(-6deg);"><svg width="100" height="100" viewBox="0 0 24 24" fill="#ffffff"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></div>`
    },
    tiktok: {
      titleLine1: 'TikTok',
      titleLine2: 'Events API Engine',
      bg: 'linear-gradient(135deg, #121216 0%, #1E1E24 100%)',
      glowColor: 'rgba(254, 44, 85, 0.45)',
      titleColor: '#FFFFFF',
      subtitleColor: 'rgba(255, 255, 255, 0.85)',
      btnBg: 'linear-gradient(135deg, #25F4EE 0%, #FE2C55 100%)',
      btnText: '#010101',
      circleBg: '#010101',
      circleIconColor: '#FFFFFF',
      activeBtnBg: '#FFFFFF',
      activeBtnText: '#010101',
      emblemSvg: `<div class="emblem-pod" style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; filter: drop-shadow(0 15px 30px rgba(0,0,0,0.4)); transform: rotate(6deg);"><svg width="96" height="96" viewBox="0 0 24 24" fill="none"><path d="M19.589 6.686a4.793 4.793 0 01-3.77-4.245V2h-3.445v13.672a2.896 2.896 0 01-5.201 1.743 2.895 2.895 0 013.313-4.508V9.423a6.33 6.33 0 00-1.109-.1 6.34 6.34 0 106.34 6.34V8.625A8.214 8.214 0 0019.589 10v-3.314z" fill="#ffffff"/><path d="M16.25 2v4.686a4.793 4.793 0 003.339 3.314V7.5a4.793 4.793 0 01-3.339-3.314V2h-.001z" fill="#25F4EE"/><path d="M12.374 15.672V2h3.445v.441a4.793 4.793 0 003.77 4.245V10a8.214 8.214 0 01-4.77-1.375v7.047a6.34 6.34 0 01-6.34 6.34 6.33 6.33 0 01-3.666-1.161A2.895 2.895 0 0012.374 15.672z" fill="#FE2C55"/></svg></div>`
    },
    reddit: {
      titleLine1: 'Reddit Pixel',
      titleLine2: '& Conversions API',
      bg: 'linear-gradient(135deg, #FF4500 0%, #D83A00 100%)',
      glowColor: 'rgba(255, 69, 0, 0.45)',
      titleColor: '#FFFFFF',
      subtitleColor: 'rgba(255, 255, 255, 0.85)',
      btnBg: '#FFFFFF',
      btnText: '#FF4500',
      circleBg: '#0F172A',
      circleIconColor: '#FFFFFF',
      activeBtnBg: '#0F172A',
      activeBtnText: '#FFFFFF',
      emblemSvg: `<div class="emblem-pod" style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; filter: drop-shadow(0 15px 30px rgba(0,0,0,0.3)); transform: rotate(-5deg);"><svg width="100" height="100" viewBox="0 0 24 24" fill="#ffffff"><path d="M12 18c-2.28 0-4.14-.62-4.48-1.42-.08-.18.02-.38.21-.43.19-.05.39.04.47.22.25.6 1.83 1.13 3.8 1.13 1.97 0 3.55-.53 3.8-1.13.08-.18.28-.27.47-.22.19.05.29.25.21.43C16.14 17.38 14.28 18 12 18zm-4.32-5.46a1.28 1.28 0 100-2.56 1.28 1.28 0 000 2.56zm8.64 0a1.28 1.28 0 100-2.56 1.28 1.28 0 000 2.56zm1.9-2.31a1.2 1.2 0 00-1.17-.92c-.17 0-.34.04-.49.12-1.07-.76-2.54-1.25-4.17-1.31l.85-4.01 2.78.59a1.18 1.18 0 10.27-.72l-3.13-.67a.38.38 0 00-.45.29l-.97 4.57c-1.67.04-3.17.54-4.26 1.32-.15-.09-.32-.13-.49-.13A1.2 1.2 0 003.6 11.23c0 .46.26.86.64 1.06-.03.17-.04.34-.04.52 0 2.87 3.49 5.19 7.8 5.19s7.8-2.32 7.8-5.19c0-.18-.01-.35-.04-.52.38-.2.64-.6.64-1.06z" fill="#ffffff"/></svg></div>`
    },
    pinterest: {
      titleLine1: 'Pinterest Tag',
      titleLine2: '& Conversions API',
      bg: 'linear-gradient(135deg, #E60023 0%, #B8001C 100%)',
      glowColor: 'rgba(230, 0, 35, 0.45)',
      titleColor: '#FFFFFF',
      subtitleColor: 'rgba(255, 255, 255, 0.85)',
      btnBg: '#FFFFFF',
      btnText: '#E60023',
      circleBg: '#0F172A',
      circleIconColor: '#FFFFFF',
      activeBtnBg: '#0F172A',
      activeBtnText: '#FFFFFF',
      emblemSvg: `<div class="emblem-pod" style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; filter: drop-shadow(0 15px 30px rgba(0,0,0,0.3)); transform: rotate(6deg);"><svg width="100" height="100" viewBox="0 0 24 24" fill="#ffffff"><path d="M12.017 4C7.59 4 4 7.59 4 12.017c0 3.39 2.108 6.284 5.084 7.449-.07-.633-.133-1.604.028-2.294.145-.625.938-3.977.938-3.977s-.239-.48-.239-1.188c0-1.112.645-1.942 1.447-1.942.682 0 1.012.513 1.012 1.127 0 .686-.437 1.712-.663 2.663-.189.796.4 1.446 1.185 1.446 1.422 0 2.515-1.5 2.515-3.662 0-1.915-1.376-3.255-3.337-3.255-2.273 0-3.608 1.705-3.608 3.47 0 .687.265 1.425.595 1.825.065.079.075.149.055.23-.06.25-.195.799-.223.909-.037.153-.122.185-.282.112-1.053-.49-1.71-2.03-1.71-3.267 0-2.658 1.932-5.1 5.568-5.1 2.923 0 5.198 2.083 5.198 4.871 0 2.905-1.831 5.242-4.373 5.242-.854 0-1.657-.444-1.932-.968l-.526 2.004c-.19.728-.704 1.64-1.05 2.2 1.034.32 2.127.495 3.26.495 4.427 0 8.017-3.59 8.017-8.017C20.034 7.59 16.444 4 12.017 4z" fill="#ffffff"/></svg></div>`
    },
    ga4: {
      titleLine1: 'Analytics and',
      titleLine2: 'Tracking (GA4)',
      bg: '#FFFFFF',
      glowColor: 'rgba(249, 171, 0, 0.45)',
      titleColor: '#0F172A',
      subtitleColor: '#64748B',
      btnBg: '#0F172A',
      btnText: '#FFFFFF',
      circleBg: '#FFFFFF',
      circleIconColor: '#0F172A',
      activeBtnBg: '#10B981',
      activeBtnText: '#FFFFFF',
      cardBorder: '1px solid rgba(0,0,0,0.08)',
      emblemSvg: `<div class="emblem-pod" style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; filter: drop-shadow(0 15px 25px rgba(249,171,0,0.25)); transform: rotate(-4deg);"><svg width="100" height="100" viewBox="0 0 24 24" fill="none"><path d="M22 20a2 2 0 01-2 2H4a2 2 0 01-2-2V4a2 2 0 012-2h16a2 2 0 012 2v16z" fill="#F8FAFC"/><path d="M6 18a2 2 0 100-4 2 2 0 000 4z" fill="#EA4335"/><path d="M12 18a2 2 0 002-2V9a2 2 0 10-4 0v7a2 2 0 002 2z" fill="#F9AB00"/><path d="M18 18a2 2 0 002-2V5a2 2 0 10-4 0v11a2 2 0 002 2z" fill="#4285F4"/></svg></div>`
    },
    google: {
      titleLine1: 'Google Ads',
      titleLine2: 'Conversion Engine',
      bg: 'linear-gradient(135deg, #4285F4 0%, #1A73E8 100%)',
      glowColor: 'rgba(66, 133, 244, 0.45)',
      titleColor: '#FFFFFF',
      subtitleColor: 'rgba(255, 255, 255, 0.85)',
      btnBg: '#FFFFFF',
      btnText: '#4285F4',
      circleBg: '#0F172A',
      circleIconColor: '#FFFFFF',
      activeBtnBg: '#0F172A',
      activeBtnText: '#FFFFFF',
      emblemSvg: `<div class="emblem-pod" style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; filter: drop-shadow(0 15px 30px rgba(0,0,0,0.3)); transform: rotate(5deg);"><svg width="100" height="100" viewBox="0 0 24 24" fill="none"><path d="M3.75 16.5A4.5 4.5 0 008.25 21h7.5a4.5 4.5 0 004.5-4.5v-9A4.5 4.5 0 0015.75 3h-7.5A4.5 4.5 0 003.75 7.5v9z" fill="#FFFFFF"/><path d="M16.5 6L8.25 18H5.25L13.5 6h3z" fill="#4285F4"/><path d="M18.75 18l-5.25-7.5 1.8-2.6 6.45 10.1h-3z" fill="#FBBC04"/><path fill-rule="evenodd" clip-rule="evenodd" d="M14.55 11.95L11.7 16.1 9.4 12.8l2.85-4.15 2.3 3.3z" fill="#34A853"/></svg></div>`
    }
  };

  const platformsGridContainer = document.createElement('div');
  platformsGridContainer.style.display = 'grid';
  platformsGridContainer.style.gridTemplateColumns = 'repeat(auto-fit, minmax(420px, 1fr))';
  platformsGridContainer.style.gap = '28px';
  platformsGridContainer.style.marginBottom = '32px';

  const createCard = (platform, isActive) => {
    const theme = brandThemes[platform];
    const buttonLabel = isActive ? 'EDIT SETUP' : 'LEARN MORE';
    const buttonIcon = isActive ? '✓' : '↗';

    return `
      <div class="pp-card pp-iconly-card ${isActive ? 'saved-active' : ''}" data-platform="${platform}" style="--pp-card-glow: ${theme.glowColor}; background: ${theme.bg}; border: ${theme.cardBorder || 'none'}; border-radius: 24px; padding: 32px 36px; min-height: 185px; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 12px 35px -8px rgba(15, 23, 42, 0.08);">
        
        ${isActive ? `<div style="position: absolute; top: 16px; right: 20px; background: rgba(255,255,255,0.25); backdrop-filter: blur(8px); color: ${theme.titleColor}; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 4px; z-index: 3;">✓ ACTIVE</div>` : ''}

        <!-- Left Typography & Action Button -->
        <div style="z-index: 2; max-width: 62%;">
          <div style="font-family: var(--pp-font-heading); font-size: 26px; font-weight: 800; line-height: 1.2; color: ${theme.titleColor}; letter-spacing: -0.03em; margin-bottom: 2px;">
            ${theme.titleLine1}
          </div>
          <div style="font-family: var(--pp-font-heading); font-size: 18px; font-weight: 600; color: ${theme.subtitleColor}; margin-bottom: 28px;">
            ${theme.titleLine2}
          </div>

          <button class="pp-btn setup-card-btn" data-platform="${platform}" style="background: ${isActive ? theme.activeBtnBg : theme.btnBg}; color: ${isActive ? theme.activeBtnText : theme.btnText} !important; border: none; border-radius: 30px; padding: 6px 20px 6px 6px; display: inline-flex; align-items: center; gap: 10px; cursor: pointer; font-family: var(--pp-font-heading); font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; box-shadow: 0 6px 16px rgba(0,0,0,0.18);">
            <span class="arrow-badge" style="width: 32px; height: 32px; border-radius: 50%; background: ${theme.circleBg}; color: ${theme.circleIconColor}; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 900; flex-shrink: 0;">
              ${buttonIcon}
            </span>
            <span>${buttonLabel}</span>
          </button>
        </div>

        <!-- Right 3D Emblem Graphic -->
        <div style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); z-index: 1; pointer-events: none;">
          ${theme.emblemSvg}
        </div>
      </div>
    `;
  };

  platformsGridContainer.innerHTML = `
    ${createCard('facebook', isPlatformActive('facebook'))}
    ${createCard('tiktok', isPlatformActive('tiktok'))}
    ${createCard('reddit', isPlatformActive('reddit'))}
    ${createCard('pinterest', isPlatformActive('pinterest'))}
    ${createCard('ga4', isPlatformActive('ga4'))}
    ${createCard('google', isPlatformActive('google'))}
  `;

  // Standard Events List
  const redditStandardEvents = [
    { id: 'PageVisit', name: 'PageVisit' },
    { id: 'ViewContent', name: 'ViewContent' },
    { id: 'Search', name: 'Search' },
    { id: 'AddToCart', name: 'AddToCart' },
    { id: 'AddToWishlist', name: 'AddToWishlist' },
    { id: 'Purchase', name: 'Purchase' },
    { id: 'Lead', name: 'Lead' },
    { id: 'SignUp', name: 'SignUp' },
    { id: 'Custom', name: 'Custom' },
    { id: 'DynamicStatus', name: 'DynamicStatus', desc: 'woocommerce status wise purchase controll' }
  ];

  // Standard Events List
  const metaStandardEvents = [
    { id: 'PageView', name: 'PageView' },
    { id: 'ViewContent', name: 'ViewContent' },
    { id: 'Search', name: 'Search' },
    { id: 'AddToCart', name: 'AddToCart' },
    { id: 'AddToWishlist', name: 'AddToWishlist' },
    { id: 'CustomizeProduct', name: 'CustomizeProduct' },
    { id: 'InitiateCheckout', name: 'InitiateCheckout' },
    { id: 'AddPaymentInfo', name: 'AddPaymentInfo' },
    { id: 'Purchase', name: 'Purchase' },
    { id: 'Contact', name: 'Contact' },
    { id: 'SubmitApplication', name: 'SubmitApplication' },
    { id: 'Lead', name: 'Lead' },
    { id: 'FindLocation', name: 'FindLocation' },
    { id: 'CompleteRegistration', name: 'CompleteRegistration' },
    { id: 'StartTrial', name: 'StartTrial' },
    { id: 'Subscribe', name: 'Subscribe' },
    { id: 'Schedule', name: 'Schedule' },
    { id: 'Donate', name: 'Donate' },
    { id: 'DynamicStatus', name: 'DynamicStatus', desc: 'woocommerce status wise purchase controll' }
  ];

  const tiktokStandardEvents = [
    { id: 'Pageview', name: 'Pageview' },
    { id: 'ViewContent', name: 'ViewContent' },
    { id: 'Search', name: 'Search' },
    { id: 'AddToCart', name: 'AddToCart' },
    { id: 'AddToWishlist', name: 'AddToWishlist' },
    { id: 'InitiateCheckout', name: 'InitiateCheckout' },
    { id: 'AddPaymentInfo', name: 'AddPaymentInfo' },
    { id: 'PlaceAnOrder', name: 'PlaceAnOrder' },
    { id: 'Purchase', name: 'Purchase' },
    { id: 'Contact', name: 'Contact' },
    { id: 'Download', name: 'Download' },
    { id: 'SubmitForm', name: 'SubmitForm' },
    { id: 'CompleteRegistration', name: 'CompleteRegistration' },
    { id: 'Subscribe', name: 'Subscribe' },
    { id: 'DynamicStatus', name: 'DynamicStatus', desc: 'woocommerce status wise purchase controll' }
  ];

  const linkedinStandardEvents = [
    { id: 'KEY_PAGE_VIEW', name: 'KEY_PAGE_VIEW' },
    { id: 'VIEW_CONTENT', name: 'VIEW_CONTENT' },
    { id: 'SEARCH', name: 'SEARCH' },
    { id: 'ADD_TO_CART', name: 'ADD_TO_CART' },
    { id: 'START_CHECKOUT', name: 'START_CHECKOUT' },
    { id: 'PURCHASE', name: 'PURCHASE' },
    { id: 'LEAD', name: 'LEAD' },
    { id: 'SIGN_UP', name: 'SIGN_UP' },
    { id: 'DOWNLOAD', name: 'DOWNLOAD' },
    { id: 'INSTALL', name: 'INSTALL' }
  ];

  const ga4StandardEvents = [
    { id: 'view_item_list', name: 'view_item_list' },
    { id: 'select_item', name: 'select_item' },
    { id: 'view_item', name: 'view_item' },
    { id: 'add_to_cart', name: 'add_to_cart' },
    { id: 'remove_from_cart', name: 'remove_from_cart' },
    { id: 'view_cart', name: 'view_cart' },
    { id: 'begin_checkout', name: 'begin_checkout' },
    { id: 'add_shipping_info', name: 'add_shipping_info' },
    { id: 'add_payment_info', name: 'add_payment_info' },
    { id: 'purchase', name: 'purchase' },
    { id: 'refund', name: 'refund' },
    { id: 'view_promotion', name: 'view_promotion' },
    { id: 'select_promotion', name: 'select_promotion' },
    { id: 'begin_trial', name: 'begin_trial' },
    { id: 'subscribe', name: 'subscribe' },
    { id: 'generate_lead', name: 'generate_lead' },
    { id: 'contact', name: 'contact' },
    { id: 'schedule', name: 'schedule' },
    { id: 'search', name: 'search' },
    { id: 'select_content', name: 'select_content' },
    { id: 'share', name: 'share' },
    { id: 'file_download', name: 'file_download' },
    { id: 'video_start', name: 'video_start' },
    { id: 'video_progress', name: 'video_progress' },
    { id: 'video_complete', name: 'video_complete' },
    { id: 'sign_up', name: 'sign_up' },
    { id: 'login', name: 'login' }
  ];

  const eventParamsMap = {
    'Purchase': ['value', 'currency', 'content_name', 'content_ids', 'content_type', 'num_items', 'order_id'],
    'PURCHASE': ['value', 'currency', 'content_name', 'content_ids', 'content_type', 'num_items', 'order_id'],
    'InitiateCheckout': ['value', 'currency', 'content_name', 'content_ids', 'content_type', 'num_items'],
    'START_CHECKOUT': ['value', 'currency', 'content_name', 'content_ids', 'content_type', 'num_items'],
    'AddPaymentInfo': ['value', 'currency', 'content_category'],
    'AddToCart': ['value', 'currency', 'content_name', 'content_ids', 'content_type'],
    'ADD_TO_CART': ['value', 'currency', 'content_name', 'content_ids', 'content_type'],
    'ViewContent': ['value', 'currency', 'content_name', 'content_ids', 'content_type'],
    'VIEW_CONTENT': ['value', 'currency', 'content_name', 'content_ids', 'content_type'],
    'AddToWishlist': ['value', 'currency', 'content_name', 'content_ids'],
    'DynamicStatus': ['status', 'order_id'],
    'Lead': ['lead_id', 'form_name'],
    'LEAD': ['lead_id', 'form_name'],
    'Contact': ['contact_method'],
    'Search': ['search_string'],
    'SEARCH': ['search_string'],
    'Schedule': ['appointment_time'],
    'Subscribe': ['subscription_type'],
    'Download': ['file_name', 'file_type'],
    'DOWNLOAD': ['file_name', 'file_type'],
    'CompleteRegistration': ['method'],
    'SIGN_UP': ['method'],
    'PageView': ['page_title', 'page_location', 'user_id', 'client_user_agent'],
    'Pageview': ['page_title', 'page_location', 'user_id', 'client_user_agent'],
    'KEY_PAGE_VIEW': ['page_title', 'page_location', 'user_id', 'client_user_agent'],
    'PlaceAnOrder': ['value', 'currency', 'content_name', 'content_ids', 'content_type', 'num_items', 'order_id'],
    'SubmitForm': ['lead_id', 'form_name'],
    'view_item_list': ['item_list_id', 'item_list_name', 'items'],
    'select_item': ['item_list_id', 'item_list_name', 'items'],
    'view_item': ['currency', 'value', 'items'],
    'add_to_cart': ['currency', 'value', 'items'],
    'remove_from_cart': ['currency', 'value', 'items'],
    'view_cart': ['currency', 'value', 'items'],
    'begin_checkout': ['currency', 'value', 'coupon', 'items'],
    'add_shipping_info': ['currency', 'value', 'shipping_tier', 'items'],
    'add_payment_info': ['currency', 'value', 'payment_type', 'items'],
    'purchase': ['transaction_id', 'value', 'tax', 'shipping', 'currency', 'coupon', 'items'],
    'refund': ['transaction_id', 'value', 'currency', 'items'],
    'view_promotion': ['promotion_id', 'promotion_name', 'creative_name', 'items'],
    'select_promotion': ['promotion_id', 'promotion_name', 'creative_name', 'items'],
    'begin_trial': ['currency', 'value', 'subscription_plan', 'trial_period_days'],
    'subscribe': ['transaction_id', 'currency', 'value', 'recurring_interval'],
    'generate_lead': ['lead_type', 'value', 'currency'],
    'contact': ['method', 'link_url'],
    'schedule': ['appointment_type', 'date_time'],
    'search': ['search_term'],
    'select_content': ['content_type', 'item_id'],
    'share': ['method', 'content_type', 'item_id'],
    'file_download': ['file_extension', 'file_name', 'link_url'],
    'video_start': ['video_provider', 'video_title', 'video_url'],
    'video_progress': ['video_provider', 'video_title', 'video_url', 'percent'],
    'video_complete': ['video_provider', 'video_title', 'video_url'],
    'sign_up': ['method'],
    'login': ['method']
  };



  const pinterestActionMappings = [
    { id: 'PageView', name: 'Page View', defaultCategory: 'pagevisit' },
    { id: 'ViewContent', name: 'Product View / View Content', defaultCategory: 'pagevisit' },
    { id: 'Search', name: 'Search', defaultCategory: 'pagevisit' },
    { id: 'AddToCart', name: 'Add To Cart', defaultCategory: 'addtocart' },
    { id: 'InitiateCheckout', name: 'Initiate Checkout', defaultCategory: 'initiatecheckout' },
    { id: 'Purchase', name: 'Purchase / Order Completion', defaultCategory: 'checkout' },
    { id: 'Lead', name: 'Lead / Form Submission', defaultCategory: 'lead' },
    { id: 'CompleteRegistration', name: 'Sign Up / Register', defaultCategory: 'signup' },
    { id: 'Download', name: 'Download File', defaultCategory: 'lead' },
    { id: 'Contact', name: 'Contact Us', defaultCategory: 'lead' },
    { id: 'Schedule', name: 'Book Appointment / Schedule', defaultCategory: 'lead' }
  ];

  const renderPinterestEventToggles = (actions, savedConfig) => {
    const savedEvents = savedConfig.events || {};
    const savedMappings = savedConfig.mappings || {};

    const categoriesList = [
      { id: 'checkout', name: 'Checkout (checkout)' },
      { id: 'addtocart', name: 'Add To Cart (addtocart)' },
      { id: 'initiatecheckout', name: 'Initiate Checkout (initiatecheckout)' },
      { id: 'add_payment_info', name: 'Add Payment Info (add_payment_info)' },
      { id: 'add_to_wishlist', name: 'Add To Wishlist (add_to_wishlist)' },
      { id: 'signup', name: 'Signup (signup)' },
      { id: 'subscribe', name: 'Subscribe (subscribe)' },
      { id: 'submit_application', name: 'Submit Application (submit_application)' },
      { id: 'start_trial', name: 'Start Trial (start_trial)' },
      { id: 'lead', name: 'Lead (lead)' },
      { id: 'pagevisit', name: 'Page Visit (pagevisit)' },
      { id: 'watchvideo', name: 'Watch Video (watchvideo)' },
      { id: 'custom', name: 'Custom (custom)' },
      { id: 'CUSTOM', name: 'Custom Event...' }
    ];

    return actions.map(act => {
      const isChecked = savedEvents[act.id] !== false;
      const selectedCategory = savedMappings[act.id] || act.defaultCategory;
      const isCustomSelected = selectedCategory && selectedCategory !== 'CUSTOM' && !categoriesList.some(c => c.id === selectedCategory);
      const displayVal = isCustomSelected ? 'CUSTOM' : (selectedCategory || act.defaultCategory);
      const customTextVal = isCustomSelected ? selectedCategory : '';

      const optionsHtml = categoriesList.map(cat => {
        return `<option value="${cat.id}" ${displayVal === cat.id ? 'selected' : ''}>${cat.name}</option>`;
      }).join('');

      return `
        <div class="pinterest-mapping-row" style="padding: 16px; border-bottom: 1px solid var(--pp-border-light); display: flex; flex-direction: column; gap: 12px;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="font-weight: 600; font-size: 14px; color: var(--pp-text-main);">${act.name}</div>
            <label class="pp-switch" style="margin: 0;">
              <input type="checkbox" class="pinterest-event-toggle" data-action-id="${act.id}" ${isChecked ? 'checked' : ''}>
              <span class="pp-slider"></span>
            </label>
          </div>
          <div style="display: flex; gap: 16px; align-items: center; width: 100%;">
            <div style="flex: 1;">
              <label style="display: block; font-size: 12px; color: var(--pp-text-muted); margin-bottom: 6px;">Pinterest Category</label>
              <select class="pp-input pinterest-category-select" data-action-id="${act.id}" style="width: 100%;">
                ${optionsHtml}
              </select>
            </div>
            <div class="pinterest-custom-field-container" style="flex: 1; display: ${displayVal === 'CUSTOM' ? 'block' : 'none'};">
              <label style="display: block; font-size: 12px; color: var(--pp-text-muted); margin-bottom: 6px;">Custom Event Name</label>
              <input type="text" class="pp-input pinterest-custom-name-input" data-action-id="${act.id}" value="${customTextVal}" placeholder="e.g. custom_event" style="width: 100%;">
            </div>
          </div>
        </div>
      `;
    }).join('');
  };

  const activeParamsConfig = state.config?.active_params || {};

  const renderEventToggles = (events, savedEvents, platformName) => {
    return events.map(evt => {
      const isChecked = savedEvents[evt.id] !== false;
      const params = eventParamsMap[evt.id] || [];
      
      let paramsHtml = '';
      let expandIcon = '';
      
      if (params.length > 0) {
        const evtKeyLower = evt.id.toLowerCase();
        const evtParamsState = activeParamsConfig[evtKeyLower] || {};
        
        const paramRows = params.map(p => {
          const pEnabled = evtParamsState[p] !== '0'; // default true unless explicitly '0'
          return `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
               <span style="font-size: 12px; color: var(--pp-text-main);">${p}</span>
               <label class="pp-switch" style="margin:0; transform: scale(0.8);">
                 <input type="checkbox" class="param-toggle-checkbox" data-platform="${platformName}" data-event-id="${evt.id}" data-param="${p}" ${pEnabled ? 'checked' : ''}>
                 <span class="pp-slider"></span>
               </label>
            </div>
          `;
        }).join('');
        
        paramsHtml = `
          <div class="event-params-panel" id="params-panel-${platformName}-${evt.id}" style="display: none; padding: 12px 16px; background: rgba(0,0,0,0.1); margin-top: 12px; border-radius: 4px; border: 1px solid var(--pp-border-light);">
            <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--pp-text-muted); margin-bottom: 8px;">Event Parameters</div>
            ${paramRows}
          </div>
        `;
        
        expandIcon = `
          <div class="expand-indicator" style="color: var(--pp-text-muted); cursor: pointer; padding: 4px; margin-right: 8px; transition: transform 0.2s;" data-target="params-panel-${platformName}-${evt.id}">
             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
          </div>
        `;
      }
      
      return `
        <div class="event-row-container" style="padding: 12px; border-bottom: 1px solid var(--pp-border-light);">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; flex: 1;">
              ${expandIcon}
              <div class="event-row-header" style="cursor: pointer; flex: 1;" data-target="params-panel-${platformName}-${evt.id}">
                <div style="font-weight: 500; font-size: 14px; color: var(--pp-text-main);">${evt.name}</div>
                ${evt.desc ? `<div style="font-size: 12px; color: var(--pp-text-muted);">${evt.desc}</div>` : ''}
              </div>
            </div>
            <label class="pp-switch" style="margin-left: 16px;">
              <input type="checkbox" class="event-toggle-checkbox" data-platform="${platformName}" data-event-id="${evt.id}" ${isChecked ? 'checked' : ''}>
              <span class="pp-slider"></span>
            </label>
          </div>
          ${paramsHtml}
        </div>
      `;
    }).join('');
  };

  const renderGA4EventToggles = (events, savedEvents, platformName) => {
    return events.map(evt => {
      const evtConfig = savedEvents[evt.id] || { browser: true, server: true };
      const browserChecked = evtConfig.browser !== false;
      const serverChecked = evtConfig.server !== false;
      const params = eventParamsMap[evt.id] || [];
      
      let paramsHtml = '';
      let expandIcon = '';
      
      if (params.length > 0) {
        const evtKeyLower = evt.id.toLowerCase();
        const evtParamsState = activeParamsConfig[evtKeyLower] || {};
        
        const paramRows = params.map(p => {
          const pEnabled = evtParamsState[p] !== '0'; // default true unless explicitly '0'
          return `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
               <span style="font-size: 12px; color: var(--pp-text-main);">${p}</span>
               <label class="pp-switch" style="margin:0; transform: scale(0.8);">
                 <input type="checkbox" class="param-toggle-checkbox" data-platform="${platformName}" data-event-id="${evt.id}" data-param="${p}" ${pEnabled ? 'checked' : ''}>
                 <span class="pp-slider"></span>
               </label>
            </div>
          `;
        }).join('');
        
        paramsHtml = `
          <div class="event-params-panel" id="params-panel-${platformName}-${evt.id}" style="display: none; padding: 12px 16px; background: rgba(0,0,0,0.1); margin-top: 12px; border-radius: 4px; border: 1px solid var(--pp-border-light);">
            <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--pp-text-muted); margin-bottom: 8px;">Event Parameters</div>
            ${paramRows}
          </div>
        `;
        
        expandIcon = `
          <div class="expand-indicator" style="color: var(--pp-text-muted); cursor: pointer; padding: 4px; margin-right: 8px; transition: transform 0.2s;" data-target="params-panel-${platformName}-${evt.id}">
             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
          </div>
        `;
      }

      return `
        <div class="event-row-container" style="padding: 12px 16px; border-bottom: 1px solid var(--pp-border-light);">
          <div style="display: flex; justify-content: space-between; align-items: center; gap: 24px;">
            <div style="display: flex; align-items: center; flex: 1;">
              ${expandIcon}
              <div class="event-row-header" style="cursor: pointer; flex: 1;" data-target="params-panel-${platformName}-${evt.id}">
                <div style="font-weight: 500; font-size: 14px; color: var(--pp-text-main);">${evt.name}</div>
              </div>
            </div>
            <div style="display: flex; gap: 16px; align-items: center;">
              <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; margin: 0; color: var(--pp-text-muted); cursor: pointer;">
                <input type="checkbox" class="ga4-event-channel" data-event-id="${evt.id}" data-channel="browser" ${browserChecked ? 'checked' : ''}> Browser
              </label>
              <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; margin: 0; color: var(--pp-text-muted); cursor: pointer;">
                <input type="checkbox" class="ga4-event-channel" data-event-id="${evt.id}" data-channel="server" ${serverChecked ? 'checked' : ''}> Server
              </label>
            </div>
          </div>
          ${paramsHtml}
        </div>
      `;
    }).join('');
  };

  const modalOverlay = document.createElement('div');
  modalOverlay.id = 'pp-setup-modal';
  modalOverlay.style.display = 'none';
  modalOverlay.style.position = 'fixed';
  modalOverlay.style.top = '0';
  modalOverlay.style.left = '0';
  modalOverlay.style.width = '100%';
  modalOverlay.style.height = '100%';
  modalOverlay.style.background = 'rgba(0, 0, 0, 0.6)';
  modalOverlay.style.backdropFilter = 'blur(4px)';
  modalOverlay.style.zIndex = '999999';
  modalOverlay.style.justifyContent = 'center';
  modalOverlay.style.alignItems = 'center';

  modalOverlay.innerHTML = `
    <div class="pp-modal-content" style="background: var(--pp-bg); width: 100%; max-width: 800px; border-radius: var(--pp-radius-lg); box-shadow: 0 20px 40px rgba(0,0,0,0.3); border: 1px solid var(--pp-border); display: flex; flex-direction: column; max-height: 90vh; animation: fadeInUp 0.3s ease-out;">
      <div style="padding: 24px 32px; border-bottom: 1px solid var(--pp-border); display: flex; justify-content: space-between; align-items: center;">
        <h3 id="modal-title" style="margin: 0; font-size: 20px; font-weight: 600; color: var(--pp-text-main); display: flex; align-items: center; gap: 12px;">
          Setup
        </h3>
        <button id="modal-close-icon" style="background: none; border: none; color: var(--pp-text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 4px; transition: color 0.2s;">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      </div>
      
      <div id="modal-tabs" style="display: none; padding: 0 32px; border-bottom: 1px solid var(--pp-border); background: rgba(0,0,0,0.02);">
        <div style="display: flex; gap: 24px;">
          <button class="modal-tab-btn active" data-tab="configuration" style="background: none; border: none; border-bottom: 2px solid var(--pp-primary); padding: 16px 0; color: var(--pp-primary); font-weight: 600; cursor: pointer; font-size: 14px;">Configuration</button>
          <button class="modal-tab-btn" data-tab="events-control" style="background: none; border: none; border-bottom: 2px solid transparent; padding: 16px 0; color: var(--pp-text-muted); font-weight: 600; cursor: pointer; font-size: 14px;">Events Control</button>
        </div>
      </div>

      <div id="modal-body" style="padding: 32px; overflow-y: auto; flex: 1;">
        <div id="modal-error-msg" style="display:none; color: var(--pp-danger); margin-bottom: 24px; font-size: 14px; background: var(--pp-danger-bg); padding: 12px 16px; border-radius: 4px; border: 1px solid rgba(239, 68, 68, 0.2);"></div>

        <!-- Meta Form -->
        <div class="platform-form" id="form-facebook" style="display: none;">
          <div class="tab-content" id="facebook-configuration">
            <div class="setup-mode-toggle" style="display: flex; gap: 24px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--pp-border);">
              <label class="pp-radio-btn" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="meta_mode" value="basic" ${!metaConfig.capi_token ? 'checked' : ''} style="accent-color: var(--pp-primary); width: 16px; height: 16px;">
                <span style="font-size: 14px; color: var(--pp-text-main); font-weight: 500;">Basic Setup (Pixel Only)</span>
              </label>
              <label class="pp-radio-btn" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="meta_mode" value="advanced" ${metaConfig.capi_token ? 'checked' : ''} style="accent-color: var(--pp-primary); width: 16px; height: 16px;">
                <span style="font-size: 14px; color: var(--pp-text-main); font-weight: 500;">Advanced Setup (Pixel + CAPI)</span>
              </label>
            </div>
            <div class="config-fields">
              <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Meta Pixel ID <span style="color: var(--pp-danger);">*</span></label>
                <input type="text" id="meta-pixel-id" class="pp-input" value="${metaConfig.pixel_id || ''}" placeholder="e.g. 123456789012345">
              </div>
              <div id="meta-advanced-fields" style="display: ${metaConfig.capi_token ? 'block' : 'none'};">
                <div style="margin-bottom: 24px;">
                  <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Conversions API Access Token <span style="color: var(--pp-danger);">*</span></label>
                  <textarea id="meta-capi-token" class="pp-input" rows="4" placeholder="Paste your CAPI token here...">${metaConfig.capi_token || ''}</textarea>
                </div>
                <div style="margin-bottom: 12px;">
                  <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Test Event Code (Optional)</label>
                  <input type="text" id="meta-test-code" class="pp-input" value="${metaConfig.test_code || ''}" placeholder="e.g. TEST12345">
                </div>
              </div>
            </div>
          </div>
          <div class="tab-content" id="facebook-events-control" style="display: none;">
            <p style="font-size: 14px; color: var(--pp-text-muted); margin-bottom: 16px;">Toggle specific Meta Standard Events to control what gets sent to Facebook.</p>
            <div class="events-list-container" style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: var(--pp-radius-md);">
              ${renderEventToggles(metaStandardEvents, metaConfig.events || {}, 'facebook')}
            </div>
          </div>
        </div>

        <!-- TikTok Form -->
        <div class="platform-form" id="form-tiktok" style="display: none;">
          <div class="tab-content" id="tiktok-configuration">
            <div class="setup-mode-toggle" style="display: flex; gap: 24px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--pp-border);">
              <label class="pp-radio-btn" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="tiktok_mode" value="basic" ${!tiktokConfig.access_token ? 'checked' : ''} style="accent-color: var(--pp-primary); width: 16px; height: 16px;">
                <span style="font-size: 14px; color: var(--pp-text-main); font-weight: 500;">Basic Setup (Pixel Only)</span>
              </label>
              <label class="pp-radio-btn" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="tiktok_mode" value="advanced" ${tiktokConfig.access_token ? 'checked' : ''} style="accent-color: var(--pp-primary); width: 16px; height: 16px;">
                <span style="font-size: 14px; color: var(--pp-text-main); font-weight: 500;">Advanced Setup (Pixel + Events API)</span>
              </label>
            </div>
            <div class="config-fields">
              <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">TikTok Pixel ID <span style="color: var(--pp-danger);">*</span></label>
                <input type="text" id="tiktok-pixel-id" class="pp-input" value="${tiktokConfig.pixel_id || ''}" placeholder="e.g. C1234567890">
              </div>
              <div id="tiktok-advanced-fields" style="display: ${tiktokConfig.access_token ? 'block' : 'none'};">
                <div style="margin-bottom: 24px;">
                  <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Events API Access Token <span style="color: var(--pp-danger);">*</span></label>
                  <textarea id="tiktok-access-token" class="pp-input" rows="4" placeholder="Paste your Access Token here...">${tiktokConfig.access_token || ''}</textarea>
                </div>
                <div style="margin-bottom: 12px;">
                  <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Test Event Code (Optional)</label>
                  <input type="text" id="tiktok-test-code" class="pp-input" value="${tiktokConfig.test_code || ''}" placeholder="e.g. TEST12345">
                </div>
              </div>
            </div>
          </div>
          <div class="tab-content" id="tiktok-events-control" style="display: none;">
            <p style="font-size: 14px; color: var(--pp-text-muted); margin-bottom: 16px;">Toggle specific TikTok Standard Events to control what gets sent to TikTok.</p>
            <div class="events-list-container" style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: var(--pp-radius-md);">
              ${renderEventToggles(tiktokStandardEvents, tiktokConfig.events || {}, 'tiktok')}
            </div>
          </div>
        </div>

        <!-- Reddit Form -->
        <div class="platform-form" id="form-reddit" style="display: none;">
          <div class="tab-content" id="reddit-configuration">
            <div class="setup-mode-toggle" style="display: flex; gap: 24px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--pp-border);">
              <label class="pp-radio-btn" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="reddit_mode" value="basic" ${!redditConfig.access_token ? 'checked' : ''} style="accent-color: var(--pp-primary); width: 16px; height: 16px;">
                <span style="font-size: 14px; color: var(--pp-text-main); font-weight: 500;">Basic Setup (Pixel Only)</span>
              </label>
              <label class="pp-radio-btn" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="reddit_mode" value="advanced" ${redditConfig.access_token ? 'checked' : ''} style="accent-color: var(--pp-primary); width: 16px; height: 16px;">
                <span style="font-size: 14px; color: var(--pp-text-main); font-weight: 500;">Advanced Setup (Pixel + Conversions API)</span>
              </label>
            </div>
            <div class="config-fields">
              <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Reddit Pixel ID <span style="color: var(--pp-danger);">*</span></label>
                <input type="text" id="reddit-pixel-id" class="pp-input" value="${redditConfig.pixel_id || ''}" placeholder="e.g. t2_xxxxxx">
              </div>
              <div id="reddit-advanced-fields" style="display: ${redditConfig.access_token ? 'block' : 'none'};">
                <div style="margin-bottom: 24px;">
                  <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Conversions API Access Token <span style="color: var(--pp-danger);">*</span></label>
                  <textarea id="reddit-access-token" class="pp-input" rows="4" placeholder="Paste your Access Token here...">${redditConfig.access_token || ''}</textarea>
                </div>
                <div style="margin-bottom: 12px;">
                  <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Test Event Code (Optional)</label>
                  <input type="text" id="reddit-test-code" class="pp-input" value="${redditConfig.test_code || ''}" placeholder="e.g. TEST12345">
                </div>
              </div>
            </div>
          </div>
          <div class="tab-content" id="reddit-events-control" style="display: none;">
            <p style="font-size: 14px; color: var(--pp-text-muted); margin-bottom: 16px;">Toggle specific Reddit Standard Events to control what gets sent to Reddit.</p>
            <div class="events-list-container" style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: var(--pp-radius-md);">
              ${renderEventToggles(redditStandardEvents, redditConfig.events || {}, 'reddit')}
            </div>
          </div>
        </div>

        <!-- Pinterest Form -->
        <div class="platform-form" id="form-pinterest" style="display: none;">
          <div class="tab-content" id="pinterest-configuration">
            <div class="setup-mode-toggle" style="display: flex; gap: 24px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--pp-border);">
              <label class="pp-radio-btn" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="pinterest_mode" value="basic" ${!pinterestConfig.access_token ? 'checked' : ''} style="accent-color: var(--pp-primary); width: 16px; height: 16px;">
                <span style="font-size: 14px; color: var(--pp-text-main); font-weight: 500;">Basic Setup (Pixel Only)</span>
              </label>
              <label class="pp-radio-btn" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="pinterest_mode" value="advanced" ${pinterestConfig.access_token ? 'checked' : ''} style="accent-color: var(--pp-primary); width: 16px; height: 16px;">
                <span style="font-size: 14px; color: var(--pp-text-main); font-weight: 500;">Advanced Setup (Pixel + Conversions API)</span>
              </label>
            </div>
            <div class="config-fields">
              <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Pinterest Tag ID <span style="color: var(--pp-danger);">*</span></label>
                <input type="text" id="pinterest-tag-id" class="pp-input" value="${pinterestConfig.tag_id || ''}" placeholder="e.g. 13-digit tag ID">
              </div>

              <div style="margin-bottom: 24px; display: flex; align-items: center;">
                <label class="pp-checkbox-lbl" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                  <input type="checkbox" id="pinterest-enhanced-match" style="accent-color: var(--pp-primary); width: 18px; height: 18px;" ${pinterestConfig.enhanced_match ? 'checked' : ''}>
                  <span style="font-size: 14px; font-weight: 500; color: var(--pp-text-main);">Enable Enhanced Match Data</span>
                </label>
              </div>

              <div style="margin-bottom: 24px; display: flex; align-items: center;">
                <label class="pp-checkbox-lbl" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                  <input type="checkbox" id="pinterest-first-party-cookies" style="accent-color: var(--pp-primary); width: 18px; height: 18px;" ${pinterestConfig.first_party_cookies !== false ? 'checked' : ''}>
                  <span style="font-size: 14px; font-weight: 500; color: var(--pp-text-main);">First-Party Cookie Attribution</span>
                </label>
              </div>

              <div id="pinterest-advanced-fields" style="display: ${pinterestConfig.access_token ? 'block' : 'none'};">
                <div style="margin-bottom: 24px;">
                  <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Pinterest Ad Account ID (Optional, defaults to Tag ID)</label>
                  <input type="text" id="pinterest-ad-account-id" class="pp-input" value="${pinterestConfig.ad_account_id || ''}" placeholder="e.g. 549xxxxxxxxxx">
                </div>

                <div style="margin-bottom: 24px;">
                  <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Conversions API Access Token <span style="color: var(--pp-danger);">*</span></label>
                  <textarea id="pinterest-access-token" class="pp-input" rows="4" placeholder="Starts with pna_live_...">${pinterestConfig.access_token || ''}</textarea>
                </div>

                <div style="margin-bottom: 24px; display: flex; align-items: center;">
                  <label class="pp-checkbox-lbl" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="pinterest-test-mode" style="accent-color: var(--pp-primary); width: 18px; height: 18px;" ${pinterestConfig.test_mode ? 'checked' : ''}>
                    <span style="font-size: 14px; font-weight: 500; color: var(--pp-text-main);">Pinterest Test Mode (Send events to '/events?test=true')</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="tab-content" id="pinterest-events-control" style="display: none;">
            <p style="font-size: 14px; color: var(--pp-text-muted); margin-bottom: 16px;">Toggle and map WooCommerce actions to Pinterest Standard Conversion Categories.</p>
            <div class="events-list-container" style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: var(--pp-radius-md);">
              ${renderPinterestEventToggles(pinterestActionMappings, pinterestConfig)}
            </div>
          </div>
        </div>



        <!-- GA4 Form -->
        <div class="platform-form" id="form-ga4" style="display: none;">
          <div class="tab-content" id="ga4-configuration">
            <div class="setup-mode-toggle" style="display: flex; gap: 24px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--pp-border);">
              <label class="pp-radio-btn" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="ga4_mode" value="basic" ${ga4Config.setup_type !== 'advanced' ? 'checked' : ''} style="accent-color: var(--pp-primary); width: 16px; height: 16px;">
                <span style="font-size: 14px; color: var(--pp-text-main); font-weight: 500;">Basic Setup (GA4 ID Only - Browser Tracking)</span>
              </label>
              <label class="pp-radio-btn" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="ga4_mode" value="advanced" ${ga4Config.setup_type === 'advanced' ? 'checked' : ''} style="accent-color: var(--pp-primary); width: 16px; height: 16px;">
                <span style="font-size: 14px; color: var(--pp-text-main); font-weight: 500;">Advanced Setup (GA4 ID + API Secret + Server Control)</span>
              </label>
            </div>
            <div class="config-fields">
              <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">GA4 Measurement ID <span style="color: var(--pp-danger);">*</span></label>
                <input type="text" id="ga4-measurement-id" class="pp-input" value="${ga4Config.measurement_id || ''}" placeholder="e.g. G-XXXXXXX">
              </div>
              <div id="ga4-advanced-fields" style="display: ${ga4Config.setup_type === 'advanced' ? 'block' : 'none'};">
                <div style="margin-bottom: 24px;">
                  <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Measurement Protocol API Secret <span style="color: var(--pp-danger);">*</span></label>
                  <input type="text" id="ga4-api-secret" class="pp-input" value="${ga4Config.api_secret || ''}" placeholder="Paste API Secret here...">
                </div>
                <div style="margin-bottom: 12px;">
                  <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Test Event Code / Debug Mode (Optional)</label>
                  <input type="text" id="ga4-test-code" class="pp-input" value="${ga4Config.test_code || ''}" placeholder="e.g. debug_mode=true or test code">
                </div>
              </div>
            </div>
          </div>
          <div class="tab-content" id="ga4-events-control" style="display: none;">
            <p style="font-size: 14px; color: var(--pp-text-muted); margin-bottom: 16px;">Toggle Browser (gtag.js) and Server-Side execution per event.</p>
            <div class="events-list-container" style="background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: var(--pp-radius-md); margin-bottom: 24px;">
              ${renderGA4EventToggles(ga4StandardEvents, ga4Config.events || {}, 'ga4')}
            </div>
          </div>
        </div>

        <!-- Google Form -->
        <div class="platform-form" id="form-google" style="display: none;">
          <div class="config-fields">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
              <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--pp-text-main);">Global Conversion ID <span style="color: var(--pp-danger);">*</span></label>
                <input type="text" id="google-conversion-id" class="pp-input" value="${googleConfig.conversion_id || ''}" placeholder="AW-123456789">
              </div>
              <div style="margin-bottom: 24px; display: flex; align-items: flex-end;">
                <label class="pp-checkbox-lbl" style="display: flex; align-items: center; gap: 8px; cursor: pointer; height: 42px;">
                  <input type="checkbox" id="google-enhanced-conversions" style="accent-color: var(--pp-primary); width: 18px; height: 18px;" ${googleConfig.enhanced_conversions ? 'checked' : ''}>
                  <span style="font-size: 14px; font-weight: 500; color: var(--pp-text-main);">Enable Enhanced Conversions</span>
                </label>
              </div>
            </div>
            
            <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--pp-border);">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                  <h4 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 600; color: var(--pp-text-main);">Dynamic Event Mapping</h4>
                  <p style="margin: 0; font-size: 12px; color: var(--pp-text-muted);">Assign specific conversion labels to standard events.</p>
                </div>
                <button type="button" class="pp-btn-outline pp-btn-sm" id="google-add-event-btn" style="padding: 6px 12px; font-size: 13px;">+ Add Event</button>
              </div>
              
              <div id="google-events-container" style="display: flex; flex-direction: column; gap: 12px;">
                <!-- Dynamic rows will be injected here -->
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div style="padding: 20px 32px; border-top: 1px solid var(--pp-border); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.1); border-bottom-left-radius: var(--pp-radius-lg); border-bottom-right-radius: var(--pp-radius-lg);">
        <button class="pp-btn-outline" id="modal-remove-btn" style="display: none; color: var(--pp-danger); border-color: var(--pp-danger);">Remove Setup</button>
        <div style="display: flex; gap: 16px; margin-left: auto;">
           <button class="pp-btn-outline" id="modal-close-btn">Close</button>
           <button class="pp-btn" id="modal-save-btn">Save Configuration</button>
        </div>
      </div>
    </div>
  `;

  wizardContainer.appendChild(platformsGridContainer);
  container.appendChild(header);
  container.appendChild(wizardContainer);
  document.body.appendChild(modalOverlay);

  // --- Logic & Event Listeners ---
  
  const modal = modalOverlay;
  const modalTitle = document.getElementById('modal-title');
  const modalErrorMsg = document.getElementById('modal-error-msg');
  const modalSaveBtn = document.getElementById('modal-save-btn');
  const modalRemoveBtn = document.getElementById('modal-remove-btn');
  const modalTabsContainer = document.getElementById('modal-tabs');

  // Dynamic Event Manager for Google
  const googleEventsContainer = document.getElementById('google-events-container');
  const googleAddEventBtn = document.getElementById('google-add-event-btn');

  const createGoogleEventRow = (eventData = { name: 'purchase', label: '' }) => {
    const row = document.createElement('div');
    row.className = 'google-event-row';
    row.style.display = 'grid';
    row.style.gridTemplateColumns = '1fr 1fr auto';
    row.style.gap = '16px';
    row.style.alignItems = 'center';
    row.style.background = 'rgba(0,0,0,0.02)';
    row.style.padding = '12px';
    row.style.borderRadius = '6px';
    row.style.border = '1px solid var(--pp-border)';

    row.innerHTML = `
      <div>
        <select class="pp-input event-name-select" style="width: 100%;">
          <option value="purchase" ${eventData.name === 'purchase' ? 'selected' : ''}>Purchase</option>
          <option value="add_to_cart" ${eventData.name === 'add_to_cart' ? 'selected' : ''}>Add to Cart</option>
          <option value="begin_checkout" ${eventData.name === 'begin_checkout' ? 'selected' : ''}>Begin Checkout</option>
          <option value="generate_lead" ${eventData.name === 'generate_lead' ? 'selected' : ''}>Generate Lead</option>
          <option value="sign_up" ${eventData.name === 'sign_up' ? 'selected' : ''}>Sign Up</option>
          <option value="contact" ${eventData.name === 'contact' ? 'selected' : ''}>Contact</option>
          <option value="book_appointment" ${eventData.name === 'book_appointment' ? 'selected' : ''}>Book Appointment</option>
          <option value="page_view" ${eventData.name === 'page_view' ? 'selected' : ''}>Page View</option>
        </select>
      </div>
      <div>
        <input type="text" class="pp-input event-label-input" value="${eventData.label}" placeholder="Conversion Label (Optional)" style="width: 100%;">
      </div>
      <div>
        <button type="button" class="remove-event-row-btn" style="background: none; border: none; color: var(--pp-danger); cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      </div>
    `;

    row.querySelector('.remove-event-row-btn').addEventListener('click', () => {
      row.remove();
    });

    return row;
  };

  // Initialize existing Google events
  if (googleEventsContainer) {
    if (googleConfig.events && Array.isArray(googleConfig.events)) {
      googleConfig.events.forEach(ev => {
        googleEventsContainer.appendChild(createGoogleEventRow(ev));
      });
      // Fallback for older config with single conversion_label
    } else if (googleConfig.conversion_label) {
       googleEventsContainer.appendChild(createGoogleEventRow({ name: 'purchase', label: googleConfig.conversion_label }));
    }
  }

  if (googleAddEventBtn && googleEventsContainer) {
    googleAddEventBtn.addEventListener('click', () => {
      googleEventsContainer.appendChild(createGoogleEventRow());
    });
  }

  // Tab switching logic
  if (modalTabsContainer) {
    modalTabsContainer.querySelectorAll('.modal-tab-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        // update active state
        modalTabsContainer.querySelectorAll('.modal-tab-btn').forEach(b => {
          b.classList.remove('active');
          b.style.borderBottomColor = 'transparent';
          b.style.color = 'var(--pp-text-muted)';
        });
        e.target.classList.add('active');
        e.target.style.borderBottomColor = 'var(--pp-primary)';
        e.target.style.color = 'var(--pp-primary)';

        const tabName = e.target.dataset.tab; // configuration or events-control
        
        // hide all contents in the active platform
        if (activePlatform) {
          const configDiv = document.getElementById(`${activePlatform}-configuration`);
          if (configDiv) configDiv.style.display = 'none';
          const eventControlDiv = document.getElementById(`${activePlatform}-events-control`);
          if(eventControlDiv) eventControlDiv.style.display = 'none';
          
          // show requested tab
          const requestedDiv = document.getElementById(`${activePlatform}-${tabName}`);
          if(requestedDiv) requestedDiv.style.display = 'block';
        }
      });
    });
  }

  const openModal = (platform) => {
    activePlatform = platform;
    modalErrorMsg.style.display = 'none';
    
    // Hide all forms in modal
    modal.querySelectorAll('.platform-form').forEach(f => f.style.display = 'none');
    
    // Setup modal specific data
    const form = document.getElementById(`form-${platform}`);
    if (form) form.style.display = 'block';

    if (platform === 'facebook') {
      modalTitle.innerHTML = `${icons.meta} Meta Pixel & CAPI Setup`;
      if (modalTabsContainer) modalTabsContainer.style.display = 'block';
    } else if (platform === 'tiktok') {
      modalTitle.innerHTML = `${icons.tiktok} TikTok Events API Setup`;
      if (modalTabsContainer) modalTabsContainer.style.display = 'block';
    } else if (platform === 'reddit') {
      modalTitle.innerHTML = `${icons.reddit} Reddit Pixel & CAPI Setup`;
      if (modalTabsContainer) modalTabsContainer.style.display = 'block';
    } else if (platform === 'pinterest') {
      modalTitle.innerHTML = `${icons.pinterest} Pinterest Tag & CAPI Setup`;
      if (modalTabsContainer) modalTabsContainer.style.display = 'block';
    } else if (platform === 'ga4') {
      modalTitle.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--pp-primary);"><circle cx="12" cy="12" r="10"></circle><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg> Google Analytics 4 Setup`;
      if (modalTabsContainer) modalTabsContainer.style.display = 'block';
    } else if (platform === 'google') {
      modalTitle.innerHTML = `${icons.google} Google Conversion Tracking Setup`;
      if (modalTabsContainer) modalTabsContainer.style.display = 'none'; // No events control tab for google
    }
    
    // Reset tabs to Configuration
    if(platform === 'facebook' || platform === 'tiktok' || platform === 'reddit' || platform === 'pinterest' || platform === 'ga4') {
      if (modalTabsContainer) {
        const configBtn = modalTabsContainer.querySelector('[data-tab="configuration"]');
        if (configBtn) configBtn.click();
      }
    }
    
    if (isPlatformActive(platform)) {
      modalRemoveBtn.style.display = 'inline-flex';
    } else {
      modalRemoveBtn.style.display = 'none';
    }

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent background scroll
  };

  const closeModal = () => {
    modal.style.display = 'none';
    activePlatform = null;
    document.body.style.overflow = '';
  };

  // Close Events
  const closeIcon = document.getElementById('modal-close-icon');
  if (closeIcon) closeIcon.addEventListener('click', closeModal);
  const closeBtn = document.getElementById('modal-close-btn');
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) closeModal();
  });

  // Handle Card Clicks to Open Modal
  wizardContainer.querySelectorAll('.setup-card-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      openModal(btn.dataset.platform);
    });
  });
  
  // Make the whole card clickable
  wizardContainer.querySelectorAll('.pp-setup-card').forEach(card => {
    card.addEventListener('click', () => {
      openModal(card.dataset.platform);
    });
  });

  // Handle Event Params Expansion
  modal.addEventListener('click', (e) => {
    const expandIndicator = e.target.closest('.expand-indicator');
    const rowHeader = e.target.closest('.event-row-header');
    
    if (expandIndicator || rowHeader) {
      const targetId = (expandIndicator || rowHeader).dataset.target;
      if (targetId) {
        const panel = document.getElementById(targetId);
        if (panel) {
          if (panel.style.display === 'none') {
            panel.style.display = 'block';
            if (expandIndicator) expandIndicator.style.transform = 'rotate(180deg)';
          } else {
            panel.style.display = 'none';
            if (expandIndicator) expandIndicator.style.transform = 'rotate(0deg)';
          }
        }
      }
    }
  });

  // Handle Event Params Toggle via AJAX instantly
  modal.addEventListener('change', async (e) => {
    if (e.target.classList.contains('param-toggle-checkbox')) {
      const eventName = e.target.dataset.eventId;
      const paramName = e.target.dataset.param;
      const isChecked = e.target.checked;
      
      const formData = new FormData();
      formData.append('action', 'PixelOnWP_toggle_event_param_state');
      formData.append('nonce', window.pixelonwp_admin_vars.nonce);
      formData.append('event_name', eventName);
      formData.append('param_name', paramName);
      formData.append('state', isChecked ? '1' : '0');
      try {
        await fetch(window.pixelonwp_admin_vars.ajaxurl, { method: 'POST', body: formData });
        
        // Update local state so it persists if modal closes and reopens without refresh
        const evtKeyLower = eventName.toLowerCase();
        let targetConfig = state.config || {};
        if (!targetConfig.active_params) targetConfig.active_params = {};
        if (!targetConfig.active_params[evtKeyLower]) targetConfig.active_params[evtKeyLower] = {};
        targetConfig.active_params[evtKeyLower][paramName] = isChecked ? '1' : '0';
        
      } catch (err) {
        console.warn('Failed to toggle event param state', err);
      }
    }
  });
  modal.addEventListener('change', (e) => {
    if (e.target.classList.contains('pinterest-category-select')) {
      const select = e.target;
      const row = select.closest('.pinterest-mapping-row');
      const customContainer = row.querySelector('.pinterest-custom-field-container');
      if (select.value === 'CUSTOM') {
        customContainer.style.display = 'block';
      } else {
        customContainer.style.display = 'none';
      }
    }
  });
  // Handle Setup Modes (Basic vs Advanced) inside Modal
  modal.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
      const mode = e.target.value;
      const platform = e.target.name.split('_')[0]; // 'meta', 'tiktok' or 'ga4'
      const advancedFields = document.getElementById(`${platform}-advanced-fields`);
      
      if (advancedFields) {
        if (mode === 'advanced') {
          advancedFields.style.display = 'block';
        } else {
          advancedFields.style.display = 'none';
        }
      }
    });
  });

  // Helper to extract toggled events
  const getToggledEvents = (platform) => {
    const events = {};
    const form = document.getElementById(`form-${platform}`);
    if(!form) return events;
    
    const checkboxes = form.querySelectorAll('.event-toggle-checkbox');
    checkboxes.forEach(cb => {
      events[cb.dataset.eventId] = cb.checked;
    });
    return events;
  };

  const getGA4ToggledEvents = () => {
    const events = {};
    const form = document.getElementById('form-ga4');
    if (!form) return events;
    form.querySelectorAll('.ga4-event-channel').forEach(cb => {
      const evtId = cb.dataset.eventId;
      const channel = cb.dataset.channel;
      if (!events[evtId]) {
        events[evtId] = { browser: true, server: true };
      }
      events[evtId][channel] = cb.checked;
    });
    return events;
  };

  // Handle Save AJAX
  modalSaveBtn.addEventListener('click', async () => {
    if (!activePlatform) return;
    
    const platform = activePlatform;
    const form = document.getElementById(`form-${platform}`);
    
    modalErrorMsg.style.display = 'none';
    const originalText = modalSaveBtn.innerText;
    modalSaveBtn.innerHTML = `${icons.spinner} Saving...`;
    modalSaveBtn.disabled = true;

    // Gather Data
    let data = {};
    if (platform === 'facebook') {
      const mode = form.querySelector('input[name="meta_mode"]:checked').value;
      data.pixelId = document.getElementById('meta-pixel-id').value.trim();
      data.events = getToggledEvents('facebook');
      if (mode === 'advanced') {
        data.capiToken = document.getElementById('meta-capi-token').value.trim();
        data.testCode = document.getElementById('meta-test-code').value.trim();
      } else {
        data.capiToken = '';
        data.testCode = '';
      }
      if (!data.pixelId) {
        showError('Meta Pixel ID is required.');
        return;
      }
      if (mode === 'advanced' && !data.capiToken) {
        showError('CAPI Access Token is required for Advanced Setup.');
        return;
      }
    } else if (platform === 'tiktok') {
      const mode = form.querySelector('input[name="tiktok_mode"]:checked').value;
      data.pixelId = document.getElementById('tiktok-pixel-id').value.trim();
      data.events = getToggledEvents('tiktok');
      if (mode === 'advanced') {
        data.accessToken = document.getElementById('tiktok-access-token').value.trim();
        data.testCode = document.getElementById('tiktok-test-code').value.trim();
      } else {
        data.accessToken = '';
        data.testCode = '';
      }
      if (!data.pixelId) {
        showError('TikTok Pixel ID is required.');
        return;
      }
      if (mode === 'advanced' && !data.accessToken) {
        showError('Access Token is required for Advanced Setup.');
        return;
      }
    } else if (platform === 'reddit') {
      const mode = form.querySelector('input[name="reddit_mode"]:checked').value;
      data.pixelId = document.getElementById('reddit-pixel-id').value.trim();
      data.events = getToggledEvents('reddit');
      if (mode === 'advanced') {
        data.accessToken = document.getElementById('reddit-access-token').value.trim();
        data.testCode = document.getElementById('reddit-test-code').value.trim();
      } else {
        data.accessToken = '';
        data.testCode = '';
      }
      if (!data.pixelId) {
        showError('Reddit Pixel ID is required.');
        return;
      }
      if (mode === 'advanced' && !data.accessToken) {
        showError('Access Token is required for Advanced Setup.');
        return;
      }
    } else if (platform === 'pinterest') {
      const mode = form.querySelector('input[name="pinterest_mode"]:checked').value;
      data.tagId = document.getElementById('pinterest-tag-id').value.trim();
      data.enhancedMatch = document.getElementById('pinterest-enhanced-match').checked;
      data.firstPartyCookies = document.getElementById('pinterest-first-party-cookies').checked;
      
      data.events = {};
      data.mappings = {};

      const mappingRows = form.querySelectorAll('.pinterest-mapping-row');
      mappingRows.forEach(row => {
        const actionToggle = row.querySelector('.pinterest-event-toggle');
        const actionId = actionToggle.dataset.actionId;
        const isEnabled = actionToggle.checked;
        const select = row.querySelector('.pinterest-category-select');
        const categoryVal = select.value;
        const customInput = row.querySelector('.pinterest-custom-name-input');
        const customVal = customInput.value.trim();

        data.events[actionId] = isEnabled;
        if (categoryVal === 'CUSTOM') {
          data.mappings[actionId] = customVal || 'CUSTOM';
        } else {
          data.mappings[actionId] = categoryVal;
        }
      });

      if (mode === 'advanced') {
        data.adAccountId = document.getElementById('pinterest-ad-account-id').value.trim();
        data.accessToken = document.getElementById('pinterest-access-token').value.trim();
        data.testMode = document.getElementById('pinterest-test-mode').checked;
      } else {
        data.adAccountId = '';
        data.accessToken = '';
        data.testMode = false;
      }

      if (!data.tagId) {
        showError('Pinterest Tag ID is required.');
        return;
      }
      if (mode === 'advanced' && !data.accessToken) {
        showError('Conversions API Access Token is required for Advanced Setup.');
        return;
      }

    } else if (platform === 'ga4') {
      const mode = form.querySelector('input[name="ga4_mode"]:checked').value;
      data.setupType = mode;
      data.measurementId = document.getElementById('ga4-measurement-id').value.trim();
      data.events = getGA4ToggledEvents();
      if (mode === 'advanced') {
        data.apiSecret = document.getElementById('ga4-api-secret').value.trim();
        data.testCode = document.getElementById('ga4-test-code').value.trim();
      } else {
        data.apiSecret = '';
        data.testCode = '';
      }
      if (!data.measurementId) {
        showError('GA4 Measurement ID is required.');
        return;
      }
      if (mode === 'advanced' && !data.apiSecret) {
        showError('Measurement Protocol API Secret is required for Advanced Setup.');
        return;
      }
    } else if (platform === 'google') {
      data.conversionId = document.getElementById('google-conversion-id').value.trim();
      data.enhancedConversions = document.getElementById('google-enhanced-conversions').checked;
      
      if (!data.conversionId) {
        showError('Conversion ID is required.');
        return;
      }

      // Gather Dynamic Events
      data.events = [];
      if (googleEventsContainer) {
        const eventRows = googleEventsContainer.querySelectorAll('.google-event-row');
        eventRows.forEach(row => {
          const name = row.querySelector('.event-name-select').value;
          const label = row.querySelector('.event-label-input').value.trim();
          data.events.push({ name: name, label: label });
        });
      }
      // Ensure backwards compatibility format inside php
      data.conversionLabel = data.events.find(e => e.name === 'purchase')?.label || '';
    }

    // Send AJAX
    const nonce = (window.pixelonwp_admin_vars && window.pixelonwp_admin_vars.nonce) || '';
    const ajaxurl = (window.pixelonwp_admin_vars && window.pixelonwp_admin_vars.ajaxurl) || ajaxurl;

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_save_platform_config');
    formData.append('nonce', nonce);
    formData.append('platform', platform);
    formData.append('data', JSON.stringify(data));

    try {
      const response = await fetch(ajaxurl, { method: 'POST', body: formData });
      const result = await response.json();

      if (result.success) {
        // Update Card in background UI
        const card = wizardContainer.querySelector(`.pp-setup-card[data-platform="${platform}"]`);
        card.classList.add('saved-active');
        card.querySelector('.status-badge').style.display = 'inline-flex';
        card.querySelector('.platform-icon').style.color = 'var(--pp-success)';
        card.style.borderColor = 'var(--pp-success)';
        
        const cardBtn = card.querySelector('.setup-card-btn');
        cardBtn.style.background = 'transparent';
        cardBtn.style.color = 'var(--pp-text-main)';
        cardBtn.style.border = '1px solid var(--pp-border)';
        cardBtn.innerHTML = icons.settings + ' Edit Configuration';
        
        // Update arrays safely
        if (!platformsSelected.includes(platform)) platformsSelected.push(platform);

        // Update state
        if (platform === 'facebook') state.config.meta = { pixel_id: data.pixelId, capi_token: data.capiToken, test_code: data.testCode, events: data.events };
        if (platform === 'tiktok') state.config.tiktok = { pixel_id: data.pixelId, access_token: data.accessToken, test_code: data.testCode, events: data.events };
        if (platform === 'reddit') state.config.reddit = { pixel_id: data.pixelId, access_token: data.accessToken, test_code: data.testCode, events: data.events };
        if (platform === 'pinterest') state.config.pinterest = { tag_id: data.tagId, ad_account_id: data.adAccountId, access_token: data.accessToken, enhanced_match: data.enhancedMatch, first_party_cookies: data.firstPartyCookies, test_mode: data.testMode, events: data.events, mappings: data.mappings };
        if (platform === 'ga4') state.config.ga4_config = { setup_type: data.setupType, measurement_id: data.measurementId, api_secret: data.apiSecret, test_code: data.testCode, events: data.events };

        closeModal();
        showToast({ message: `${platform.toUpperCase()} tracking configuration saved successfully!`, type: 'success', title: 'Settings Saved' });
      } else {
        throw new Error(result.data?.message || 'Server error occurred.');
      }
    } catch (error) {
      showError(error.message);
    } finally {
      if (modalSaveBtn) {
        modalSaveBtn.innerHTML = originalText;
        modalSaveBtn.disabled = false;
      }
    }
  });

  // Handle Remove Logic inside modal
  modalRemoveBtn.addEventListener('click', async () => {
    if (!activePlatform) return;
    const platform = activePlatform;
    
    const confirmRemove = confirm(`Are you sure you want to completely remove the ${platform} setup? This will stop tracking immediately.`);
    if (!confirmRemove) return;

    const originalText = modalRemoveBtn.innerText;
    modalRemoveBtn.innerHTML = `${icons.spinner} Removing...`;
    modalRemoveBtn.disabled = true;

    const nonce = (window.pixelonwp_admin_vars && window.pixelonwp_admin_vars.nonce) || '';
    const ajaxurl = (window.pixelonwp_admin_vars && window.pixelonwp_admin_vars.ajaxurl) || ajaxurl;

    const formData = new FormData();
    formData.append('action', 'PixelOnWP_remove_platform_config');
    formData.append('nonce', nonce);
    formData.append('platform', platform);

    try {
      const response = await fetch(ajaxurl, { method: 'POST', body: formData });
      const result = await response.json();

      if (result.success) {
        // Update UI Card
        const card = wizardContainer.querySelector(`.pp-setup-card[data-platform="${platform}"]`);
        card.classList.remove('saved-active');
        card.querySelector('.status-badge').style.display = 'none';
        card.querySelector('.platform-icon').style.color = 'var(--pp-text-muted)';
        card.style.borderColor = 'var(--pp-border)';
        
        const cardBtn = card.querySelector('.setup-card-btn');
        cardBtn.style.background = 'var(--pp-primary)';
        cardBtn.style.color = 'white';
        cardBtn.style.border = 'none';
        cardBtn.innerHTML = 'Setup Now';
        
        // Remove from array safely
        const idx = platformsSelected.indexOf(platform);
        if (idx > -1) platformsSelected.splice(idx, 1);
        
        // Clear inputs in modal
        const form = document.getElementById(`form-${platform}`);
        if (platform === 'facebook') {
          document.getElementById('meta-pixel-id').value = '';
          document.getElementById('meta-capi-token').value = '';
          document.getElementById('meta-test-code').value = '';
          form.querySelector('input[value="basic"]').click();
        } else if (platform === 'tiktok') {
          document.getElementById('tiktok-pixel-id').value = '';
          document.getElementById('tiktok-access-token').value = '';
          document.getElementById('tiktok-test-code').value = '';
          form.querySelector('input[value="basic"]').click();
        } else if (platform === 'pinterest') {
          document.getElementById('pinterest-tag-id').value = '';
          document.getElementById('pinterest-ad-account-id').value = '';
          document.getElementById('pinterest-access-token').value = '';
          document.getElementById('pinterest-enhanced-match').checked = true;
          document.getElementById('pinterest-first-party-cookies').checked = true;
          document.getElementById('pinterest-test-mode').checked = false;
          form.querySelector('input[value="basic"]').click();
        } else if (platform === 'ga4') {
          document.getElementById('ga4-measurement-id').value = '';
          document.getElementById('ga4-api-secret').value = '';
          document.getElementById('ga4-test-code').value = '';
          form.querySelector('input[value="basic"]').click();
        } else if (platform === 'google') {
          document.getElementById('google-conversion-id').value = '';
          document.getElementById('google-enhanced-conversions').checked = false;
          if (googleEventsContainer) {
            googleEventsContainer.innerHTML = '';
          }
        }

        closeModal();
        showToast({ message: `${platform.toUpperCase()} setup removed.`, type: 'info', title: 'Configuration Removed' });
      } else {
        throw new Error(result.data?.message || 'Server error occurred.');
      }
    } catch (error) {
      showToast({ message: error.message, type: 'error', title: 'Removal Failed' });
    } finally {
       modalRemoveBtn.innerHTML = originalText;
       modalRemoveBtn.disabled = false;
    }
  });

  function showError(msg) {
    modalErrorMsg.innerText = msg;
    modalErrorMsg.style.display = 'block';
    modalSaveBtn.innerHTML = 'Save Configuration';
    modalSaveBtn.disabled = false;
  }
}
