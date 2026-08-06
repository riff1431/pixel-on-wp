/**
 * PixelOnWP DataLayer & Forms Listener
 * Handles engagement events (scroll, clicks, media) and form submissions.
 */

// Expose a global track function for custom buttons/events
window.PixelOnWP = window.PixelOnWP || {};
window.PixelOnWP.track = function (eventName, params = {}) {
  // Deduplicate at the entry point of JS tracking
  window.PixelOnWP_Fired = window.PixelOnWP_Fired || {};
  let dedupeKey = eventName + '_' + (params.event_id || '');
  if (params.event_id && window.PixelOnWP_Fired[dedupeKey]) {
    console.warn('PixelOnWP JS: Duplicate Event Prevented ->', eventName);
    return;
  }
  if (params.event_id) {
    window.PixelOnWP_Fired[dedupeKey] = true;
  }

  window.dataLayer = window.dataLayer || [];

  // Standard GA4 Base Payload
  let payload = { event: eventName };

  if (params.event_id) {
    payload.event_id = params.event_id;
    delete params.event_id;
  }

  // GA4-Wise Schema Formatting
  const ecommerceEvents = ['view_promotion', 'select_promotion', 'add_to_wishlist', 'remove_from_wishlist', 'refund', 'view_item', 'view_item_list', 'select_item', 'add_to_cart', 'remove_from_cart', 'view_cart', 'begin_checkout', 'add_shipping_info', 'add_payment_info', 'purchase'];

  if (ecommerceEvents.includes(eventName)) {
    payload.ecommerce = {
      ...params
    };
  } else if (eventName === 'generate_lead') {
    payload.value = params.value || 0;
    payload.currency = params.currency || 'USD';
    payload.form_name = params.form_name || '';
  } else if (eventName === 'search') {
    payload.search_term = params.search_term || '';
  } else if (eventName === 'login' || eventName === 'sign_up' || eventName === 'share_product') {
    payload.method = params.method || 'Website';
  } else if (eventName === 'video_play' || eventName === 'video_complete') {
    payload.video_title = params.video_title || '';
    payload.video_provider = params.video_provider || 'HTML5';
  } else if (eventName === 'file_download') {
    payload.file_name = params.file_name || '';
    payload.file_extension = params.file_extension || '';
  } else {
    // Attach any other params dynamically for custom events
    payload = { ...payload, ...params };
  }

  window.dataLayer.push(payload);

  // Generate unique event ID for deduplication
  const eventId = payload.event_id || ('evt_' + Math.random().toString(36).substr(2, 9) + Date.now().toString(36));
  payload.event_id = eventId;

  // Track the generated eventId in deduplication registry now that it's finalized
  if (!window.PixelOnWP_Fired[eventName + '_' + eventId]) {
    window.PixelOnWP_Fired[eventName + '_' + eventId] = true;
  }

  // Bridge to GA4 (gtag.js) explicitly if present
  let gtagStatus = 'pending';
  if (typeof gtag === 'function') {
    if (isPlatformEventActive('ga4', eventName)) {
      try {
        let gData = Object.assign({}, payload.ecommerce || params);

        // Dynamic event param filtering for GA4
        if (window.PixelOnWP_events && window.PixelOnWP_events.active_params) {
          const evtKeyLower = eventName.toLowerCase();
          const evtParamsState = window.PixelOnWP_events.active_params[evtKeyLower];
          if (evtParamsState) {
            for (let paramKey in gData) {
              if (evtParamsState[paramKey] === '0') {
                delete gData[paramKey];
              }
            }
          }
        }

        // Look up conversion label for Google Ads
        if (window.PixelOnWP_events && window.PixelOnWP_events.google_config) {
          const googleConfig = window.PixelOnWP_events.google_config;
          const conversionId = googleConfig.conversion_id ? googleConfig.conversion_id.trim() : '';
          if (conversionId) {
            const gEvents = Array.isArray(googleConfig.events) ? googleConfig.events : [];
            let label = '';
            const normalizedEvent = eventName.toLowerCase();
            for (let ev of gEvents) {
              if (ev.name && ev.name.toLowerCase() === normalizedEvent && ev.label) {
                label = ev.label.trim();
                break;
              }
            }
            if (!label && normalizedEvent === 'purchase' && googleConfig.conversion_label) {
              label = googleConfig.conversion_label.trim();
            }

            if (label) {
              gData.send_to = conversionId + '/' + label;
            } else {
              gData.send_to = conversionId;
            }
          }
        }

        gtag('event', eventName, gData);
        gtagStatus = 'success';
      } catch (e) {
        console.warn('PixelOnWP: gtag event execution failed', e);
        gtagStatus = 'failed';
      }
    } else {
      gtagStatus = 'disabled';
    }
  }

  // Send Event to Backend Diagnostics & Logs Live
  if (typeof jQuery !== 'undefined' && window.pixelonwp_frontend_vars && window.pixelonwp_frontend_vars.ajax_url) {
    jQuery.post(window.pixelonwp_frontend_vars.ajax_url, {
      action: 'PixelOnWP_log_frontend_event',
      event_name: eventName,
      event_id: eventId,
      status: gtagStatus,
      payload: JSON.stringify(payload)
    });
  }

  // Helper to check granular toggles
  function isPlatformEventActive(platform, eventName) {
    if (!window.PixelOnWP_events) return false;
    const normalized = String(eventName || '').toLowerCase().replace(/_/g, '');
    if (platform === 'meta') {
      if (window.PixelOnWP_events.meta_config && window.PixelOnWP_events.meta_config.events) {
        // Map common variations for config options
        const cfg = window.PixelOnWP_events.meta_config.events;
        let val = cfg[eventName] !== undefined ? cfg[eventName] : cfg[normalized];
        if (normalized === 'addtocart' && cfg['AddToCart'] !== undefined) val = cfg['AddToCart'];
        if (normalized === 'viewcontent' && cfg['ViewContent'] !== undefined) val = cfg['ViewContent'];
        if (normalized === 'initiatecheckout' && cfg['InitiateCheckout'] !== undefined) val = cfg['InitiateCheckout'];
        if (normalized === 'addpaymentinfo' && cfg['AddPaymentInfo'] !== undefined) val = cfg['AddPaymentInfo'];
        if (normalized === 'completeregistration' && cfg['CompleteRegistration'] !== undefined) val = cfg['CompleteRegistration'];

        if (val === undefined && (normalized === 'placeanorder' || eventName === 'PlaceAnOrder')) {
          return false;
        }
        if (val === '0' || val === 0 || val === 'false' || val === false) return false;
      }
      return true;
    }
    if (platform === 'tiktok') {
      if (window.PixelOnWP_events.tiktok_config && window.PixelOnWP_events.tiktok_config.events) {
        const cfg = window.PixelOnWP_events.tiktok_config.events;
        let val = cfg[eventName] !== undefined ? cfg[eventName] : cfg[normalized];
        if (val === undefined && (normalized === 'placeanorder' || eventName === 'PlaceAnOrder')) {
          return false;
        }
        if (val === '0' || val === 0 || val === 'false' || val === false) return false;
      }
      return true;
    }

    if (platform === 'reddit') {
      if (window.PixelOnWP_events.reddit_config && window.PixelOnWP_events.reddit_config.events) {
        const cfg = window.PixelOnWP_events.reddit_config.events;
        let val = cfg[eventName] !== undefined ? cfg[eventName] : cfg[normalized];
        if (val === '0' || val === 0 || val === 'false' || val === false) return false;
      }
      return true;
    }
    if (platform === 'ga4') {
      if (window.PixelOnWP_events.ga4_config && window.PixelOnWP_events.ga4_config.events) {
        const cfg = window.PixelOnWP_events.ga4_config.events;
        const val = cfg[eventName];
        if (val !== undefined) {
          if (val === false || val === 'false' || val === 0 || val === '0' || (typeof val === 'object' && val.browser === false)) {
            return false;
          }
        }
      }
      return true;
    }
    return false;
  }

  // Bridge to Meta Pixel (fbq) if available
  if (typeof fbq !== 'undefined' && (!window.PixelOnWP_events || window.PixelOnWP_events.facebook_tracking_mode !== 'server')) {
    const fbqMap = {
      'page_view': 'PageView', 'view_item': 'ViewContent', 'add_to_cart': 'AddToCart',
      'add_to_wishlist': 'AddToWishlist', 'begin_checkout': 'InitiateCheckout',
      'add_payment_info': 'AddPaymentInfo', 'purchase': 'Purchase',
      'generate_lead': 'Lead', 'contact': 'Contact', 'submit_form': 'Lead',
      'schedule': 'Schedule', 'sign_up': 'CompleteRegistration', 'search': 'Search',
      'customize_product': 'CustomizeProduct', 'donate': 'Donate',
      'find_location': 'FindLocation', 'start_trial': 'StartTrial',
      'submit_application': 'SubmitApplication', 'subscribe': 'Subscribe',
      'pageview': 'PageView', 'viewcontent': 'ViewContent', 'addtocart': 'AddToCart',
      'addtowishlist': 'AddToWishlist', 'initiatecheckout': 'InitiateCheckout',
      'addpaymentinfo': 'AddPaymentInfo', 'completeregistration': 'CompleteRegistration',
      'customizeproduct': 'CustomizeProduct', 'findlocation': 'FindLocation',
      'starttrial': 'StartTrial', 'submitapplication': 'SubmitApplication'
    };
    const normalizedName = String(eventName || '').toLowerCase().replace(/_/g, '');
    const fbEvent = fbqMap[normalizedName] || eventName;
    const isStandard = !!fbqMap[normalizedName];
    const fbType = isStandard ? 'track' : 'trackCustom';

    if (isPlatformEventActive('meta', fbEvent)) {
      let fbData = {};
      const ecom = payload.ecommerce || payload;

      // Basic normalization helper for items
      let content_ids = [];
      let contents = [];
      let num_items = 0;

      if (ecom.items && Array.isArray(ecom.items)) {
        ecom.items.forEach(item => {
          const id = String(item.item_id || item.id || item.product_id || item.productId || '');
          if (id) {
            content_ids.push(id);
            const qty = parseInt(item.quantity || 1, 10);
            const price = parseFloat(item.price || 0);
            contents.push({ id, quantity: qty, item_price: price });
            num_items += qty;
          }
        });
      } else if (ecom.content_ids && Array.isArray(ecom.content_ids)) {
        ecom.content_ids.forEach(id => {
          content_ids.push(String(id));
          contents.push({ id: String(id), quantity: 1 });
          num_items += 1;
        });
      }

      switch (fbEvent) {
        case 'AddPaymentInfo':
          if (ecom.value !== undefined) fbData.value = parseFloat(ecom.value);
          if (ecom.currency) fbData.currency = String(ecom.currency);
          if (content_ids.length > 0) {
            fbData.content_ids = content_ids;
            fbData.content_type = 'product';
            fbData.contents = contents;
          }
          break;
        case 'AddToCart':
        case 'AddToWishlist':
        case 'ViewContent':
          if (ecom.value !== undefined) fbData.value = parseFloat(ecom.value);
          if (ecom.currency) fbData.currency = String(ecom.currency);
          if (content_ids.length > 0) {
            fbData.content_ids = content_ids;
            fbData.content_type = 'product';
            fbData.contents = contents;
          }
          if (ecom.items && Array.isArray(ecom.items) && ecom.items.length === 1) {
            fbData.content_name = String(ecom.items[0].item_name || ecom.items[0].name || '');
            if (ecom.items[0].item_category) fbData.content_category = String(ecom.items[0].item_category);
          } else if (ecom.content_name) {
            fbData.content_name = String(ecom.content_name);
            if (ecom.content_category) fbData.content_category = String(ecom.content_category);
          }
          if (fbEvent === 'AddToCart') {
            fbData.num_items = num_items;
          }
          break;
        case 'CompleteRegistration':
          if (ecom.value !== undefined) fbData.value = parseFloat(ecom.value);
          if (ecom.currency) fbData.currency = String(ecom.currency);
          if (ecom.content_name) fbData.content_name = String(ecom.content_name);
          if (ecom.status) fbData.status = String(ecom.status);
          break;
        case 'Contact':
        case 'FindLocation':
        case 'Schedule':
        case 'SubmitApplication':
          if (ecom.content_category) fbData.content_category = String(ecom.content_category);
          if (ecom.content_name) fbData.content_name = String(ecom.content_name);
          break;
        case 'CustomizeProduct':
          if (content_ids.length > 0) {
            fbData.content_ids = content_ids;
            fbData.content_type = 'product';
            fbData.contents = contents;
          }
          break;
        case 'Donate':
          if (ecom.value !== undefined) fbData.value = parseFloat(ecom.value);
          if (ecom.currency) fbData.currency = String(ecom.currency);
          if (ecom.content_name) fbData.content_name = String(ecom.content_name);
          break;
        case 'InitiateCheckout':
          if (ecom.value !== undefined) fbData.value = parseFloat(ecom.value);
          if (ecom.currency) fbData.currency = String(ecom.currency);
          if (content_ids.length > 0) {
            fbData.content_ids = content_ids;
            fbData.content_type = 'product';
            fbData.contents = contents;
          }
          fbData.num_items = num_items;
          break;
        case 'Lead':
          if (ecom.value !== undefined) fbData.value = parseFloat(ecom.value);
          if (ecom.currency) fbData.currency = String(ecom.currency);
          if (ecom.content_name) fbData.content_name = String(ecom.content_name);
          if (ecom.content_category) fbData.content_category = String(ecom.content_category);
          break;
        case 'PageView':
          break;
        case 'Purchase':
          if (ecom.value !== undefined) fbData.value = parseFloat(ecom.value);
          if (ecom.currency) fbData.currency = String(ecom.currency);
          if (content_ids.length > 0) {
            fbData.content_ids = content_ids;
            fbData.content_type = 'product';
            fbData.contents = contents;
          }
          fbData.num_items = num_items;
          if (ecom.items && Array.isArray(ecom.items) && ecom.items.length === 1) {
            fbData.content_name = String(ecom.items[0].item_name || ecom.items[0].name || '');
          } else if (ecom.content_name) {
            fbData.content_name = String(ecom.content_name);
          }
          break;
        case 'Search':
          if (payload.search_term || ecom.search_term || ecom.search_string) {
            fbData.search_string = String(payload.search_term || ecom.search_term || ecom.search_string);
          }
          if (content_ids.length > 0) fbData.content_ids = content_ids;
          if (ecom.content_category) fbData.content_category = String(ecom.content_category);
          if (ecom.value !== undefined) fbData.value = parseFloat(ecom.value);
          if (ecom.currency) fbData.currency = String(ecom.currency);
          break;
        case 'StartTrial':
        case 'Subscribe':
          if (ecom.value !== undefined) fbData.value = parseFloat(ecom.value);
          if (ecom.currency) fbData.currency = String(ecom.currency);
          if (ecom.predicted_ltv) fbData.predicted_ltv = parseFloat(ecom.predicted_ltv);
          break;
        default:
          fbData = Object.assign({}, ecom, fbData);
          break;
      }

      delete fbData.event;
      delete fbData.event_id;

      const metaPixels = window.PixelOnWP_events?.meta_pixels || [];
      const pixelIds = metaPixels.length > 0 
        ? metaPixels.map(p => p.pixel_id || p.pixelId).filter(Boolean)
        : [];

      if (pixelIds.length > 0) {
        const singleType = isStandard ? 'trackSingle' : 'trackSingleCustom';
        pixelIds.forEach(pId => {
          fbq(singleType, pId, fbEvent, fbData, { eventID: eventId });
        });
      } else {
        fbq(fbType, fbEvent, fbData, { eventID: eventId });
      }
    }
  }

  // Bridge to TikTok Pixel (ttq) if available
  if (typeof ttq !== 'undefined') {
    const ttqMap = {
      'page_view': 'Pageview', 'view_item': 'ViewContent', 'add_to_cart': 'AddToCart',
      'add_to_wishlist': 'AddToWishlist', 'begin_checkout': 'InitiateCheckout',
      'add_payment_info': 'AddPaymentInfo', 'purchase': 'CompletePayment',
      'generate_lead': 'SubmitForm', 'contact': 'Contact', 'sign_up': 'CompleteRegistration',
      'search': 'Search', 'subscribe': 'Subscribe', 'place_an_order': 'PlaceAnOrder',
      'PlaceAnOrder': 'PlaceAnOrder', 'file_download': 'Download', 'download': 'Download',
      'submit_form': 'SubmitForm', 'SubmitForm': 'SubmitForm'
    };
    const ttEvent = ttqMap[eventName] || eventName;

    if (isPlatformEventActive('tiktok', ttEvent)) {
      let ttData = {};
      const ecom = payload.ecommerce || payload;
      if (ecom.currency) ttData.currency = ecom.currency;
      if (ecom.value !== undefined) ttData.value = ecom.value;
      if (payload.search_term || ecom.search_term) ttData.query = payload.search_term || ecom.search_term;
      if (ecom.items && ecom.items.length > 0) {
        ttData.content_type = 'product';
        ttData.contents = ecom.items.map(i => ({
          content_id: String(i.item_id || i.id || i.product_id || i.productId),
          content_name: i.item_name || i.name || i.product_name,
          price: i.price || i.item_price || 0,
          quantity: i.quantity || 1
        }));
        if (ecom.items.length === 1) {
          const itm = ttData.contents[0];
          ttData.content_id = itm.content_id;
          ttData.content_ids = [itm.content_id];
          ttData.content_name = itm.content_name;
          ttData.price = itm.price;
          ttData.quantity = itm.quantity;
          if (ecom.items[0].item_category || ecom.items[0].category) {
            ttData.content_category = ecom.items[0].item_category || ecom.items[0].category;
          }
        } else {
          ttData.content_ids = ttData.contents.map(itm => itm.content_id);
        }
      } else {
        ttData = Object.assign({}, ecom, ttData);
      }

      delete ttData.event;
      delete ttData.event_id;

      if (ttEvent === 'Pageview') {
        console.log('PixelOnWP System: Firing TikTok Browser Pageview');
        ttq.page();
      } else {
        console.log('PixelOnWP System: Firing TikTok Browser Event ->', ttEvent, ttData);
        ttq.track(ttEvent, ttData, { event_id: eventId });
      }
    }
  }

  // Bridge to Reddit Pixel (rdt) if available
  if (typeof rdt !== 'undefined' && (!window.PixelOnWP_events || window.PixelOnWP_events.reddit_tracking_mode !== 'server')) {
    const rdtMap = {
      'page_view': 'PageVisit', 'view_item': 'ViewContent', 'add_to_cart': 'AddToCart',
      'add_to_wishlist': 'AddToWishlist', 'begin_checkout': 'InitiateCheckout',
      'purchase': 'Purchase', 'generate_lead': 'Lead', 'contact': 'Contact',
      'submit_form': 'Lead', 'sign_up': 'SignUp', 'search': 'Search'
    };
    const rdtEvent = rdtMap[eventName] || 'Custom';

    if (isPlatformEventActive('reddit', rdtEvent)) {
      let rdtData = {
        conversionId: eventId
      };

      if (rdtEvent === 'Custom') {
        rdtData.customEventName = eventName;
      }

      const ecom = payload.ecommerce || payload;

      // Check event metadata limits per type
      const supportsProducts = ['ViewContent', 'AddToCart', 'AddToWishlist', 'Purchase', 'Custom'].includes(rdtEvent);
      const supportsValueCurrency = ['Purchase', 'Custom'].includes(rdtEvent);

      if (rdtEvent === 'Search') {
        if (payload.search_term || ecom.search_term) rdtData.searchQuery = payload.search_term || ecom.search_term;
      }

      if (supportsProducts) {
        let products = [];
        let itemCount = 0;

        if (ecom.items && Array.isArray(ecom.items)) {
          ecom.items.forEach(item => {
            const id = String(item.item_id || item.id || item.product_id || item.productId || '');
            if (id) {
              const qty = parseInt(item.quantity || 1, 10);
              itemCount += qty;
              products.push({
                id: id,
                name: item.item_name || item.name || '',
                category: item.item_category || item.category || '',
                itemPrice: parseFloat(item.price || item.item_price || 0),
                quantity: qty
              });
            }
          });
        }

        if (products.length > 0) {
          rdtData.products = products;
          if (supportsValueCurrency) {
            rdtData.itemCount = itemCount;
          }
        }
      }

      if (supportsValueCurrency) {
        if (ecom.currency) rdtData.currency = ecom.currency;
        if (ecom.value !== undefined) rdtData.value = parseFloat(ecom.value);
        if (!rdtData.itemCount) {
          rdtData.itemCount = 1;
        }
      }

      if (rdtEvent === 'PageVisit') {
        rdt('track', 'PageVisit');
      } else {
        console.log('PixelOnWP System: Firing Reddit Browser Event ->', rdtEvent, rdtData);
        rdt('track', rdtEvent, rdtData);
      }
    }
  }
  // Bridge to Pinterest Tag (pintrk) if available
  if (typeof window.pintrk !== 'undefined' && window.PixelOnWP_events && window.PixelOnWP_events.pinterest_tracking_mode !== 'server') {
    const pinMap = {
      'page_view': 'pagevisit', 'view_item': 'pagevisit', 'add_to_cart': 'addtocart',
      'begin_checkout': 'initiatecheckout', 'purchase': 'checkout',
      'generate_lead': 'lead', 'contact': 'lead', 'submit_form': 'lead',
      'sign_up': 'signup', 'search': 'search', 'schedule': 'lead',
      'add_to_wishlist': 'pagevisit', 'add_payment_info': 'pagevisit',
      'file_download': 'lead', 'video_play': 'watchvideo'
    };
    const pinEventName = pinMap[eventName] || 'custom';

    // Only skip the generic page_view (base code already fires pagevisit via pintrk('page')).
    // view_item, add_to_wishlist etc. should still fire pagevisit WITH product data.
    const isGenericPageView = (eventName === 'page_view' || eventName === 'pageview');

    if (!isGenericPageView) {
      let pinData = {};
      const ecom = payload.ecommerce || payload;

      // Always attach event_id for deduplication
      pinData.event_id = eventId;

      // Value and currency
      if (ecom.value !== undefined) pinData.value = parseFloat(ecom.value);
      if (ecom.currency) pinData.currency = String(ecom.currency);

      // Order quantity for checkout/addtocart
      if (pinEventName === 'checkout' || pinEventName === 'addtocart') {
        let itemCount = 0;
        if (ecom.items && Array.isArray(ecom.items)) {
          ecom.items.forEach(function(item) {
            itemCount += parseInt(item.quantity || 1, 10);
          });
        }
        if (itemCount > 0) pinData.order_quantity = itemCount;

        // Line items for enhanced data
        if (ecom.items && Array.isArray(ecom.items)) {
          pinData.line_items = ecom.items.map(function(item) {
            return {
              product_name: item.item_name || item.name || '',
              product_id: String(item.item_id || item.id || item.product_id || ''),
              product_price: parseFloat(item.price || item.item_price || 0),
              product_quantity: parseInt(item.quantity || 1, 10)
            };
          });
        }
      }

      // Product data for pagevisit (view_item)
      if (pinEventName === 'pagevisit' && ecom.items && Array.isArray(ecom.items)) {
        pinData.line_items = ecom.items.map(function(item) {
          return {
            product_name: item.item_name || item.name || '',
            product_id: String(item.item_id || item.id || item.product_id || ''),
            product_price: parseFloat(item.price || item.item_price || 0),
            product_quantity: parseInt(item.quantity || 1, 10)
          };
        });
      }

      // Search query
      if (pinEventName === 'search') {
        pinData.search_query = String(payload.search_term || ecom.search_term || ecom.search_string || '');
      }

      // Lead type
      if (pinEventName === 'lead' || pinEventName === 'signup') {
        pinData.lead_type = String(ecom.form_name || ecom.method || eventName);
      }

      // Watch video
      if (pinEventName === 'watchvideo') {
        pinData.video_title = String(ecom.video_title || ecom.video_url || '');
      }

      console.log('PixelOnWP System: Firing Pinterest Browser Event ->', pinEventName, pinData);
      window.pintrk('track', pinEventName, pinData);
    }
  }

  // Bridge to Server-Side (First-Party Endpoint)
  if (window.PixelOnWP_events && window.PixelOnWP_events.tracking_mode !== 'browser') {
    const apiRoute = window.PixelOnWP_events.custom_route || '/wp-json/pixelonwp/v1/collect';
    fetch(apiRoute, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).catch(e => console.warn('PixelOnWP CAPI Forwarding Failed:', e));
  }
};

document.addEventListener('DOMContentLoaded', () => {
  if (typeof window.PixelOnWP_events === 'undefined') return;

  const events = window.PixelOnWP_events;
  window.dataLayer = window.dataLayer || [];

  // --- Scroll Tracking ---
  if (events.scroll) {
    let scrollDepths = [25, 50, 75, 90];
    let scrolled = [];

    window.addEventListener('scroll', () => {
      const docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const scrollPos = window.scrollY;
      const scrollPercent = (scrollPos / docHeight) * 100;

      scrollDepths.forEach((depth) => {
        if (scrollPercent >= depth && !scrolled.includes(depth)) {
          scrolled.push(depth);
          window.PixelOnWP.track('scroll', { percent_scrolled: depth });
        }
      });
    });
  }

  // --- Universal Event DOM Parser (Maps ALL 70+ Events) ---
  // This automatically binds GA4 pushes to any element with a data-pp-event attribute or matching class
  const parseUniversalEvents = (target) => {
    if (!target) return;

    // Check data attribute first: data-pp-event="enroll_course"
    const datasetEvent = target.getAttribute('data-pp-event');
    if (datasetEvent && events[datasetEvent]) {
      const params = {};
      // Auto-extract any data-pp-param-* attributes (e.g., data-pp-param-course_name="SEO Basics")
      Array.from(target.attributes).forEach(attr => {
        if (attr.name.startsWith('data-pp-param-')) {
          const paramName = attr.name.replace('data-pp-param-', '');
          params[paramName] = attr.value;
        }
      });
      window.PixelOnWP.track(datasetEvent, params);
      return true;
    }

    // Check Class/ID matching for fallback (Excluding WooCommerce core ecommerce events to prevent double tracking)
    const allEvents = [
      'session_start', 'first_visit', 'user_engagement', 'view_home', 'view_page',
      'select_item', 'view_category', 'search', 'filter_products',
      'sort_products', 'quick_view', 'add_to_wishlist', 'remove_from_wishlist', 'add_to_compare',
      'remove_from_compare', 'share_product', 'product_review', 'rate_product', 'notify_me',
      'update_cart',
      'add_shipping_info', 'add_payment_info', 'apply_coupon', 'remove_coupon',
      'refund', 'cancel_order', 'reorder', 'subscription_purchase', 'subscription_renewal',
      'subscription_cancel', 'view_promotion', 'select_promotion', 'generate_lead', 'contact',
      'submit_form', 'form_start', 'form_submit', 'form_success', 'form_error', 'multi_step_form_next',
      'multi_step_form_previous', 'form_abandon', 'quote_request', 'consultation_booking',
      'appointment_booking', 'schedule', 'book_demo', 'request_callback', 'subscribe', 'unsubscribe',
      'sign_up', 'complete_registration', 'login', 'logout', 'verify_email', 'forgot_password',
      'reset_password', 'profile_update', 'account_delete', 'upgrade_plan', 'downgrade_plan',
      'trial_started', 'trial_ended', 'purchase_membership', 'donate', 'volunteer_signup',
      'join_waitlist', 'affiliate_signup', 'franchise_inquiry', 'job_application', 'phone_click',
      'email_click', 'whatsapp_click', 'messenger_click', 'telegram_click', 'sms_click',
      'skype_click', 'live_chat_open', 'live_chat_message', 'call_now', 'video_start', 'video_play',
      'video_pause', 'video_resume', 'video_progress', 'video_complete', 'audio_play', 'audio_complete',
      'podcast_play', 'podcast_complete', 'scroll', 'scroll_depth', 'click', 'button_click',
      'cta_click', 'link_click', 'internal_link_click', 'outbound_click', 'file_download', 'download',
      'copy_text', 'print_page', 'page_exit', 'time_on_page', 'view_content', 'customize_product',
      'find_location', 'start_trial', 'add_to_favourites', 'remove_from_favourites', 'invite_friend',
      'course_view', 'enroll_course', 'lesson_start', 'lesson_complete', 'quiz_start', 'quiz_complete',
      'certificate_download', 'workspace_created', 'project_created', 'invite_member', 'api_key_created',
      'integration_connected', 'feature_used', 'export_data', 'import_data', 'billing_updated',
      'property_view', 'property_saved', 'property_shared', 'schedule_visit', 'mortgage_calculator',
      'request_brochure', 'vehicle_view', 'test_drive_booking', 'finance_request', 'trade_in_request',
      'reserve_table', 'order_food', 'menu_view', 'add_to_order', 'doctor_selected', 'symptom_checker',
      'prescription_download', 'ad_view', 'ad_click', 'campaign_click', 'remarketing_trigger',
      'custom_event', 'custom_conversion', 'custom_goal'
    ];

    for (const evtName of allEvents) {
      if (events[evtName]) {
        if (target.classList?.contains(`pp-track-${evtName}`) || target.id === `pp-track-${evtName}`) {
          window.PixelOnWP.track(evtName);
          return true;
        }
      }
    }
    return false;
  };

  // --- Session & Engagement Tracking (GA4 Standards) ---
  if (events.first_visit && !localStorage.getItem('PixelOnWP_first_visit')) {
    localStorage.setItem('PixelOnWP_first_visit', '1');
    window.PixelOnWP.track('first_visit');
  }

  if (events.session_start && !sessionStorage.getItem('PixelOnWP_session_start')) {
    sessionStorage.setItem('PixelOnWP_session_start', '1');
    window.PixelOnWP.track('session_start');
  }

  if (events.user_engagement) {
    let engaged = false;
    const triggerEngagement = () => {
      if (!engaged) {
        engaged = true;
        window.PixelOnWP.track('user_engagement', { engagement_time_msec: 10000 });
      }
    };
    // GA4 defines engagement as 10s on page, or a conversion event
    setTimeout(triggerEngagement, 10000);
  }

  // --- Advanced Global Click Listeners ---
  document.addEventListener('click', (e) => {
    // 0. Universal Event DOM Parser
    if (parseUniversalEvents(e.target)) return;

    // 1. Button / CTA Tracking
    const btn = e.target.closest('button, .btn, .button, input[type="submit"]');
    if (btn) {
      const btnText = btn.innerText?.trim() || btn.value?.trim() || 'Button';
      if (events.cta_click && btnText.match(/buy|now|get|started|submit|join|sign|subscribe/i)) {
        window.PixelOnWP.track('cta_click', { click_text: btnText });
      } else if (events.button_click) {
        window.PixelOnWP.track('button_click', { click_text: btnText });
      }
    }

    const link = e.target.closest('a');
    if (!link) {
      if (events.click && !btn) {
        // Generic click fallback if enabled
        window.PixelOnWP.track('click', { element_classes: e.target.className });
      }
      return;
    }

    const href = link.getAttribute('href') || '';
    const linkText = link.innerText.trim();

    // 1. Email Click
    if (events.email_click && href.startsWith('mailto:')) {
      window.PixelOnWP.track('email_click', { contact_method: 'email', target_url: href });
    }

    // 2. Phone Click
    else if (events.phone_click && href.startsWith('tel:')) {
      window.PixelOnWP.track('phone_click', { contact_method: 'phone', target_url: href });
    }

    // 3. File Download
    else if (events.file_download && href.match(/\.(pdf|doc|docx|xls|xlsx|ppt|pptx|zip|rar|txt|csv)$/i)) {
      const ext = href.split('.').pop();
      const fileName = href.split('/').pop();
      window.PixelOnWP.track('file_download', { file_name: fileName, file_extension: ext, link_url: href });
    }

    // 4. Outbound Click
    else if (events.outbound_click && href.startsWith('http') && !href.includes(window.location.hostname)) {
      window.PixelOnWP.track('outbound_click', { link_url: href, link_text: linkText });
    }

    // 5. Internal Link Click
    else if (events.internal_link_click && href.startsWith('http') && href.includes(window.location.hostname)) {
      window.PixelOnWP.track('internal_link_click', { link_url: href, link_text: linkText });
    }

    // 6. Generic Link Click
    else if (events.link_click) {
      window.PixelOnWP.track('link_click', { link_url: href, link_text: linkText });
    }
  });

  // --- Copy Text Tracking ---
  if (events.copy_text) {
    document.addEventListener('copy', () => {
      const selectedText = window.getSelection().toString();
      if (selectedText.length > 0) {
        window.PixelOnWP.track('copy_text', { text: selectedText });
      }
    });
  }

  // --- HTML5 Video Tracking ---
  if (events.video_play) {
    const videos = document.querySelectorAll('video');
    videos.forEach(video => {
      video.addEventListener('play', () => {
        window.PixelOnWP.track('video_play', { video_provider: 'html5', video_url: video.currentSrc });
      });
    });
  }

  // --- Form Integrations ---
  const pushFormEvent = (formName, formId) => {
    window.PixelOnWP.track('generate_lead', {
      form_name: formName,
      form_id: formId,
      form_destination: window.location.href
    });
  };

  // 1. Contact Form 7
  if (events.form_cf7) {
    document.addEventListener('wpcf7mailsent', function (event) {
      pushFormEvent('Contact Form 7', event.detail.contactFormId);
    }, false);
  }

  // 2. WPForms
  if (events.form_wpforms) {
    if (typeof jQuery !== 'undefined') {
      jQuery(document).on('wpformsAjaxSubmitSuccess', function (e, response) {
        const formId = response?.form_id || 'wpforms';
        pushFormEvent('WPForms', formId);
      });
    }
  }

  // 3. Elementor Forms
  if (events.form_elementor) {
    if (typeof jQuery !== 'undefined') {
      jQuery(document).on('submit_success', function (e, response) {
        if (e.target && e.target.classList && e.target.classList.contains('elementor-form')) {
          const formName = e.target.getAttribute('name') || 'elementor_form';
          pushFormEvent('Elementor Form', formName);
        }
      });
    }
  }

  // 4. Gravity Forms
  if (events.form_gravity) {
    if (typeof jQuery !== 'undefined') {
      jQuery(document).on('gform_confirmation_loaded', function (event, formId) {
        pushFormEvent('Gravity Forms', formId);
      });
    }
  }

  // 5. Formidable Forms
  if (events.form_formidable) {
    document.addEventListener('frmFormComplete', function (event) {
      const formId = event.detail?.formId || 'formidable';
      pushFormEvent('Formidable Forms', formId);
    }, false);
  }

  // 6. Fluent Forms
  if (events.form_fluent) {
    if (typeof jQuery !== 'undefined') {
      jQuery(document).on('fluentform_submission_success', function (event, response, id) {
        pushFormEvent('Fluent Forms', id);
      });
    }
  }

  // 7. Ninja Forms
  if (events.form_ninja) {
    if (typeof jQuery !== 'undefined') {
      jQuery(document).on('nfFormSubmitResponse', function (event, response, id) {
        pushFormEvent('Ninja Forms', id);
      });
    }
  }

  // 8. Calendly (Iframe PostMessage)
  if (events.form_calendly || events.schedule) {
    window.addEventListener('message', function (e) {
      if (e.data && e.data.event === 'calendly.event_scheduled') {
        window.PixelOnWP.track('schedule', {
          method: 'Calendly',
          form_name: 'Calendly Schedule'
        });
      }
    });
  }

  // 9. WooCommerce AJAX Add To Cart (JSON Payload Listener to bypass theme script stripping)
  function processWooCommerceFragments() {
    const scriptDiv = document.querySelector('div.PixelOnWP-ajax-scripts[data-pixelonwp-events]');
    if (scriptDiv) {
      try {
        let payloads = scriptDiv.getAttribute('data-pixelonwp-events');
        if (typeof payloads === 'string') {
          payloads = JSON.parse(payloads);
        }
        if (Array.isArray(payloads)) {
          payloads.forEach(function (evt) {
            let eventName = evt.event_name;
            if (eventName === 'AddToCart') eventName = 'add_to_cart';
            else if (eventName === 'RemoveFromCart') eventName = 'remove_from_cart';
            else if (eventName === 'PageView') eventName = 'page_view';
            else if (eventName === 'ViewContent') eventName = 'view_item';
            else if (eventName === 'InitiateCheckout') eventName = 'begin_checkout';
            else if (eventName === 'Purchase') eventName = 'purchase';

            let params = evt.custom_data || {};
            if (evt.event_id) {
              params.event_id = evt.event_id;
            }
            window.PixelOnWP.track(eventName, params);
          });
        }
        // Remove attribute to prevent double firing on subsequent DOM mutations
        scriptDiv.removeAttribute('data-pixelonwp-events');
      } catch (e) {
        console.error('PixelOnWP: Failed to parse AJAX cart events', e);
      }
    }
  }

  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart updated_cart_totals', function () {
      processWooCommerceFragments();
    });

    jQuery(document.body).on('added_to_cart', function (event, fragments, cart_hash, button) {
      if (typeof rdt === 'function') {
        const $button = jQuery(button);
        if ($button.length) {
          const productId = String($button.data('product_id') || $button.attr('data-product_id') || '');
          const quantity = parseInt($button.data('quantity') || $button.attr('data-quantity') || 1, 10);

          let price = 0;
          let currency = 'USD';

          if (window.PixelOnWP_events) {
            const ecom = window.PixelOnWP_events.ga4_config || {};
            currency = ecom.currency || 'USD';
          }

          const priceVal = $button.data('price') || $button.attr('data-price');
          if (priceVal) {
            price = parseFloat(priceVal);
          } else {
            const $priceAmount = jQuery('.summary .price .amount, .product-page-price .amount').first();
            if ($priceAmount.length) {
              const cleanedPrice = $priceAmount.text().replace(/[^\d.]/g, '');
              if (cleanedPrice) {
                price = parseFloat(cleanedPrice);
              }
            }
          }

          const total_value = price * quantity;
          const eventId = 'rdt_atc_' + Date.now() + '_' + Math.floor(Math.random() * 100000);

          // Set deduplication flag to block the universal-tracker from double-firing this AJAX Cart event
          window.PixelOnWP_Reddit_ATC_Fired = true;
          setTimeout(function () {
            window.PixelOnWP_Reddit_ATC_Fired = false;
          }, 1000);

          const rdtData = {
            conversionId: eventId,
            itemCount: quantity,
            value: total_value,
            currency: currency,
            contentIds: [productId],
            contentType: 'product'
          };

          console.log('PixelOnWP: Reddit AJAX AddToCart fired ->', rdtData);
          rdt('track', 'AddToCart', rdtData);
        }
      }
    });
  }
  // Fallback for non-jQuery triggers or late injections
  const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      if (mutation.addedNodes.length) {
        processWooCommerceFragments();
      }
    });
  });
  observer.observe(document.body, { childList: true, subtree: true });

});
