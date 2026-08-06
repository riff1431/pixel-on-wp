(function () {
  const config = window.pixelonwp_universal_tracker_vars || { rules: [], ga4_custom_events: [], platforms: {} };
  const rules = config.rules || [];
  const ga4Events = config.ga4_custom_events || [];
  const platforms = config.platforms || {};

  if (rules.length === 0 && ga4Events.length === 0) return;

  // Auto-Healing Element Matcher
  function elementMatches(element, selector) {
    if (!selector) return false;
    if (element.matches(selector)) return true;

    // Split selector into parts (e.g., tag, class names)
    // Check if the element contains key parts of the selector to automatically self-heal path changes
    try {
      const isClassSelector = selector.startsWith('.');
      const isIdSelector = selector.startsWith('#');

      if (isClassSelector) {
        const classes = selector.replace(/^\./, '').split('.');
        const matchesCount = classes.filter(c => element.classList.contains(c)).length;
        if (classes.length > 0 && (matchesCount / classes.length) >= 0.7) {
          return true;
        }
      } else if (isIdSelector) {
        const cleanId = selector.replace('#', '');
        if (element.id && element.id.includes(cleanId)) {
          return true;
        }
      }
    } catch (e) {
      return false;
    }

    return false;
  }

  // URL Match Checker
  function isUrlMatch(rule) {
    if (!rule.url_match_type || rule.url_match_type === 'all') return true;

    const pagePath = window.location.pathname;
    const matchVal = rule.url_match_value || '';

    if (rule.url_match_type === 'specific') {
      if (pagePath === matchVal || pagePath.includes(matchVal)) return true;
      try {
        const regex = new RegExp(matchVal);
        return regex.test(pagePath);
      } catch (e) {
        return false;
      }
    } else if (rule.url_match_type === 'exclude') {
      if (pagePath === matchVal || pagePath.includes(matchVal)) return false;
      try {
        const regex = new RegExp(matchVal);
        return !regex.test(pagePath);
      } catch (e) {
        return true;
      }
    }

    return true;
  }

  // Advanced Parameter Extractor
  function extractParams(element, ruleParams) {
    const params = {};
    if (!ruleParams || !Array.isArray(ruleParams)) return params;

    ruleParams.forEach(p => {
      let value = '';
      const source = p.value_source || '';

      switch (p.value_type) {
        case 'static':
          value = source;
          break;
        case 'innerText':
          if (source) {
            const targetEl = document.querySelector(source);
            value = targetEl ? (targetEl.innerText || targetEl.textContent || '') : '';
          } else {
            value = element.innerText || element.textContent || '';
          }
          break;
        case 'attribute':
          value = element.getAttribute(source) || '';
          break;
        case 'input':
          if (source) {
            const inputEl = document.querySelector(source);
            if (inputEl) {
              if (inputEl.type === 'checkbox' || inputEl.type === 'radio') {
                value = inputEl.checked ? inputEl.value : '';
              } else {
                value = inputEl.value || '';
              }
            }
          }
          break;
        case 'query_param':
          if (source) {
            const searchParams = new URLSearchParams(window.location.search);
            value = searchParams.get(source) || '';
          }
          break;
        case 'js_var':
          if (source) {
            try {
              const segments = source.replace(/^window\./, '').split('.');
              let currentObj = window;
              for (const s of segments) {
                if (currentObj && s in currentObj) {
                  currentObj = currentObj[s];
                } else {
                  currentObj = undefined;
                  break;
                }
              }
              value = currentObj !== undefined ? currentObj : '';
            } catch (e) {
              value = '';
            }
          }
          break;
      }

      params[p.key] = typeof value === 'string' ? value.trim() : value;
    });

    return params;
  }

  // Multi-platform Dispatcher
  function dispatchEvent(eventName, params, selectedPlatforms, rule) {
    const firedPlatforms = [];
    const executionDetails = {};

    if (!isUrlMatch(rule)) return;

    // Default to all enabled platforms if selectedPlatforms is empty
    if (!selectedPlatforms || selectedPlatforms.length === 0) {
      selectedPlatforms = [];
      if (platforms.fb_pixel_id) selectedPlatforms.push('facebook');
      if (platforms.tt_pixel_id) selectedPlatforms.push('tiktok');
      if (platforms.reddit_pixel_id) selectedPlatforms.push('reddit');
      if (platforms.pinterest_tag_id) selectedPlatforms.push('pinterest');
      if (platforms.google_ads_id) selectedPlatforms.push('google_ads');
      if (platforms.ga4_measurement_id) selectedPlatforms.push('ga4');
    }

    // Generate unique Event ID for deduplication (CAPI & Pixel Match)
    const eventId = 'ev_' + Date.now() + '_' + Math.floor(Math.random() * 100000);

    // Helper to extract Whitelisted Facebook Standard Parameters
    function getFbStandardParams(ev, d) {
      d = d || {};
      const raw_data = {};

      // Basic normalization helper for items
      let content_ids = [];
      let contents = [];
      let num_items = 0;

      if (d.items && Array.isArray(d.items)) {
        d.items.forEach(item => {
          const id = String(item.item_id || item.id || item.product_id || item.productId || '');
          if (id) {
            content_ids.push(id);
            const qty = parseInt(item.quantity || 1, 10);
            const price = parseFloat(item.price || 0);
            contents.push({ id, quantity: qty, item_price: price });
            num_items += qty;
          }
        });
      } else if (d.content_ids && Array.isArray(d.content_ids)) {
        d.content_ids.forEach(id => {
          content_ids.push(String(id));
          contents.push({ id: String(id), quantity: 1 });
          num_items += 1;
        });
      }

      switch (ev) {
        case 'AddPaymentInfo':
          if (d.value !== undefined) raw_data.value = parseFloat(d.value);
          if (d.currency) raw_data.currency = String(d.currency);
          if (content_ids.length > 0) {
            raw_data.content_ids = content_ids;
            raw_data.content_type = 'product';
            raw_data.contents = contents;
          }
          break;
        case 'AddToCart':
        case 'AddToWishlist':
        case 'ViewContent':
          if (d.value !== undefined) raw_data.value = parseFloat(d.value);
          if (d.currency) raw_data.currency = String(d.currency);
          if (content_ids.length > 0) {
            raw_data.content_ids = content_ids;
            raw_data.content_type = 'product';
            raw_data.contents = contents;
          }
          if (d.items && Array.isArray(d.items) && d.items.length === 1) {
            raw_data.content_name = String(d.items[0].item_name || d.items[0].name || '');
            if (d.items[0].item_category) raw_data.content_category = String(d.items[0].item_category);
          } else if (d.content_name) {
            raw_data.content_name = String(d.content_name);
            if (d.content_category) raw_data.content_category = String(d.content_category);
          }
          if (ev === 'AddToCart') {
            raw_data.num_items = num_items;
          }
          break;
        case 'CompleteRegistration':
          if (d.value !== undefined) raw_data.value = parseFloat(d.value);
          if (d.currency) raw_data.currency = String(d.currency);
          if (d.content_name) raw_data.content_name = String(d.content_name);
          if (d.status) raw_data.status = String(d.status);
          break;
        case 'Contact':
        case 'FindLocation':
        case 'Schedule':
        case 'SubmitApplication':
          if (d.content_category) raw_data.content_category = String(d.content_category);
          if (d.content_name) raw_data.content_name = String(d.content_name);
          break;
        case 'CustomizeProduct':
          if (content_ids.length > 0) {
            raw_data.content_ids = content_ids;
            raw_data.content_type = 'product';
            raw_data.contents = contents;
          }
          break;
        case 'Donate':
          if (d.value !== undefined) raw_data.value = parseFloat(d.value);
          if (d.currency) raw_data.currency = String(d.currency);
          if (d.content_name) raw_data.content_name = String(d.content_name);
          break;
        case 'InitiateCheckout':
          if (d.value !== undefined) raw_data.value = parseFloat(d.value);
          if (d.currency) raw_data.currency = String(d.currency);
          if (content_ids.length > 0) {
            raw_data.content_ids = content_ids;
            raw_data.content_type = 'product';
            raw_data.contents = contents;
          }
          raw_data.num_items = num_items;
          break;
        case 'Lead':
          if (d.value !== undefined) raw_data.value = parseFloat(d.value);
          if (d.currency) raw_data.currency = String(d.currency);
          if (d.content_name) raw_data.content_name = String(d.content_name);
          if (d.content_category) raw_data.content_category = String(d.content_category);
          break;
        case 'PageView':
          break;
        case 'Purchase':
          if (d.value !== undefined) raw_data.value = parseFloat(d.value);
          if (d.currency) raw_data.currency = String(d.currency);
          if (content_ids.length > 0) {
            raw_data.content_ids = content_ids;
            raw_data.content_type = 'product';
            raw_data.contents = contents;
          }
          raw_data.num_items = num_items;
          if (d.items && Array.isArray(d.items) && d.items.length === 1) {
            raw_data.content_name = String(d.items[0].item_name || d.items[0].name || '');
          } else if (d.content_name) {
            raw_data.content_name = String(d.content_name);
          }
          break;
        case 'Search':
          if (d.search_term || d.search_string) raw_data.search_string = String(d.search_term || d.search_string);
          if (content_ids.length > 0) raw_data.content_ids = content_ids;
          if (d.content_category) raw_data.content_category = String(d.content_category);
          if (d.value !== undefined) raw_data.value = parseFloat(d.value);
          if (d.currency) raw_data.currency = String(d.currency);
          break;
        case 'StartTrial':
        case 'Subscribe':
          if (d.value !== undefined) raw_data.value = parseFloat(d.value);
          if (d.currency) raw_data.currency = String(d.currency);
          if (d.predicted_ltv) raw_data.predicted_ltv = parseFloat(d.predicted_ltv);
          break;
        default:
          Object.assign(raw_data, d);
          break;
      }
      return raw_data;
    }

    // 1. Meta (Facebook)
    if (selectedPlatforms.includes('facebook') && platforms.fb_pixel_id) {
      if (typeof window.fbq === 'function') {
        const standardEvents = ['ViewContent', 'Search', 'AddToCart', 'AddToWishlist', 'InitiateCheckout', 'AddPaymentInfo', 'Purchase', 'Lead', 'CompleteRegistration', 'Contact', 'CustomizeProduct', 'Donate', 'FindLocation', 'PageView', 'Schedule', 'StartTrial', 'SubmitApplication', 'Subscribe'];

        // Map common variations or lowercase events to standard PascalCase name
        let targetEventName = eventName;
        const normalized = String(eventName || '').toLowerCase().replace(/_/g, '');
        const conversionMap = {
          'pageview': 'PageView', 'viewitem': 'ViewContent', 'addtocart': 'AddToCart',
          'addtowishlist': 'AddToWishlist', 'begincheckout': 'InitiateCheckout',
          'addpaymentinfo': 'AddPaymentInfo', 'purchase': 'Purchase',
          'generatelead': 'Lead', 'contact': 'Contact', 'submitform': 'Lead',
          'schedule': 'Schedule', 'signup': 'CompleteRegistration', 'search': 'Search',
          'customizeproduct': 'CustomizeProduct', 'donate': 'Donate',
          'findlocation': 'FindLocation', 'starttrial': 'StartTrial',
          'submitapplication': 'SubmitApplication', 'subscribe': 'Subscribe'
        };
        if (conversionMap[normalized]) {
          targetEventName = conversionMap[normalized];
        }

        const fbData = getFbStandardParams(targetEventName, params);
        const metaPixels = window.PixelOnWP_events?.meta_pixels || [];
        const pixelIds = metaPixels.length > 0 
          ? metaPixels.map(p => p.pixel_id || p.pixelId).filter(Boolean)
          : (platforms.fb_pixel_id ? [platforms.fb_pixel_id] : []);

        const isStandard = standardEvents.includes(targetEventName);
        if (pixelIds.length > 0) {
          const singleType = isStandard ? 'trackSingle' : 'trackSingleCustom';
          pixelIds.forEach(pId => {
            window.fbq(singleType, pId, targetEventName, fbData, { eventID: eventId });
          });
        } else {
          if (isStandard) {
            window.fbq('track', targetEventName, fbData, { eventID: eventId });
          } else {
            window.fbq('trackCustom', targetEventName, fbData, { eventID: eventId });
          }
        }
        firedPlatforms.push('Facebook');
        executionDetails['Facebook'] = { status: 'Sent', id: platforms.fb_pixel_id };
      } else {
        executionDetails['Facebook'] = { status: 'Failed (fbq not found)', id: platforms.fb_pixel_id };
      }
    }

    // 2. TikTok
    if (selectedPlatforms.includes('tiktok') && platforms.tt_pixel_id) {
      if (typeof window.ttq !== 'undefined') {
        let ttEventName = eventName;
        if (eventName === 'PageView') ttEventName = 'Pageview';
        else if (eventName === 'ViewContent') ttEventName = 'ViewContent';
        else if (eventName === 'AddToCart') ttEventName = 'AddToCart';
        else if (eventName === 'Purchase') ttEventName = 'CompletePayment';
        else if (eventName === 'InitiateCheckout') ttEventName = 'InitiateCheckout';
        else if (eventName === 'Search') ttEventName = 'Search';
        else if (eventName === 'AddPaymentInfo') ttEventName = 'AddPaymentInfo';
        else if (eventName === 'AddToWishlist') ttEventName = 'AddToWishlist';
        else if (eventName === 'PlaceAnOrder') ttEventName = 'PlaceAnOrder';
        else if (eventName === 'Contact') ttEventName = 'Contact';
        else if (eventName === 'Download') ttEventName = 'Download';
        else if (eventName === 'SubmitForm') ttEventName = 'SubmitForm';
        else if (eventName === 'CompleteRegistration') ttEventName = 'CompleteRegistration';
        else if (eventName === 'Subscribe') ttEventName = 'Subscribe';

        let ttData = {};
        if (params.currency) ttData.currency = params.currency;
        if (params.value !== undefined) ttData.value = params.value;
        if (params.query) ttData.query = params.query;
        if (params.search_term) ttData.query = params.search_term;
        if (params.items && Array.isArray(params.items) && params.items.length > 0) {
          ttData.content_type = 'product';
          ttData.contents = params.items.map(i => ({
            content_id: String(i.item_id || i.id || i.product_id || i.productId),
            content_name: i.item_name || i.name || i.product_name,
            price: i.price || i.item_price || 0,
            quantity: i.quantity || 1
          }));
          if (params.items.length === 1) {
            const itm = ttData.contents[0];
            ttData.content_id = itm.content_id;
            ttData.content_ids = [itm.content_id];
            ttData.content_name = itm.content_name;
            ttData.price = itm.price;
            ttData.quantity = itm.quantity;
            if (params.items[0].item_category || params.items[0].category) {
              ttData.content_category = params.items[0].item_category || params.items[0].category;
            }
          } else {
            ttData.content_ids = ttData.contents.map(itm => itm.content_id);
          }
        } else {
          ttData = Object.assign({}, params, ttData);
        }

        if (ttEventName === 'Pageview') {
          window.ttq.page();
        } else {
          window.ttq.track(ttEventName, ttData, { event_id: eventId });
        }
        firedPlatforms.push('TikTok');
        executionDetails['TikTok'] = { status: 'Sent', id: platforms.tt_pixel_id };
      } else {
        executionDetails['TikTok'] = { status: 'Failed (ttq not found)', id: platforms.tt_pixel_id };
      }
    }

    // 3. Google Ads Conversion
    if (selectedPlatforms.includes('google_ads') && platforms.google_ads_id) {
      if (typeof window.gtag === 'function') {
        const conversionLabel = rule.google_ads_label || '';
        const sendTo = platforms.google_ads_id + (conversionLabel ? '/' + conversionLabel : '');
        window.gtag('event', 'conversion', {
          'send_to': sendTo,
          'event_id': eventId,
          ...params
        });
        firedPlatforms.push('Google Ads');
        executionDetails['Google Ads'] = { status: 'Sent', send_to: sendTo };
      } else {
        executionDetails['Google Ads'] = { status: 'Failed (gtag not found)', id: platforms.google_ads_id };
      }
    }

    // 4. GA4 (Google Analytics 4)
    let ga4BrowserEnabled = true;
    let ga4ServerEnabled = true;

    if (config.ga4_config && config.ga4_config.events && config.ga4_config.events[eventName]) {
      ga4BrowserEnabled = config.ga4_config.events[eventName].browser !== false;
      ga4ServerEnabled = config.ga4_config.events[eventName].server !== false;
    }

    if (selectedPlatforms.includes('ga4') && platforms.ga4_measurement_id && ga4BrowserEnabled) {
      if (typeof window.gtag === 'function') {
        const gaParams = {
          'send_to': platforms.ga4_measurement_id,
          'event_id': eventId,
          ...params
        };
        if (config.ga4_debug_mode) {
          gaParams.debug_mode = true;
        }
        window.gtag('event', eventName, gaParams);
        firedPlatforms.push('GA4 Browser');
        executionDetails['GA4 Browser'] = { status: 'Sent', id: platforms.ga4_measurement_id };
      } else {
        executionDetails['GA4 Browser'] = { status: 'Failed (gtag not found)', id: platforms.ga4_measurement_id };
      }
    }

    // 5. Reddit Pixel
    if (selectedPlatforms.includes('reddit') && platforms.reddit_pixel_id) {
      if (typeof window.rdt !== 'undefined') {
        const normalizedKey = String(eventName || '').toLowerCase().replace(/[^a-z0-9]/g, '');
        const rdtMap = {
          'page_view': 'PageVisit', 'pageview': 'PageVisit', 'pagevisit': 'PageVisit',
          'view_item': 'ViewContent', 'viewitem': 'ViewContent', 'viewcontent': 'ViewContent',
          'addtowishlist': 'AddToWishlist',
          'begincheckout': 'InitiateCheckout', 'initiatecheckout': 'InitiateCheckout',
          'purchase': 'Purchase', 'completepayment': 'Purchase',
          'generatelead': 'Lead', 'lead': 'Lead', 'contact': 'Contact',
          'submitform': 'Lead', 'signup': 'SignUp', 'search': 'Search'
        };
        const rdtEventName = rdtMap[normalizedKey] || 'Custom';

        if (normalizedKey === 'addtocart' || rdtEventName === 'AddToCart') {
          return;
        }

        // Check active toggle from events control configuration
        let redditActive = true;
        const redditConfig = config.reddit_config || (window.PixelOnWP_events && window.PixelOnWP_events.reddit_config) || {};
        if (redditConfig.events) {
          const cfg = redditConfig.events;
          const normalized = String(rdtEventName || '').toLowerCase().replace(/_/g, '');
          let val = cfg[rdtEventName] !== undefined ? cfg[rdtEventName] : cfg[normalized];
          if (val === '0' || val === 0 || val === 'false' || val === false) {
            redditActive = false;
          }
        }



        if (redditActive) {
          let rdtData = {
            conversionId: eventId
          };

          if (rdtEventName === 'Custom') {
            rdtData.customEventName = eventName;
          }

          const supportsProducts = ['ViewContent', 'AddToCart', 'AddToWishlist', 'Purchase', 'Custom'].includes(rdtEventName);
          const supportsValueCurrency = ['Purchase', 'Custom'].includes(rdtEventName);

          if (rdtEventName === 'Search') {
            if (params.search_term || params.query) rdtData.searchQuery = params.search_term || params.query;
          }

          if (supportsProducts) {
            let products = [];
            let itemCount = 0;
            if (params.items && Array.isArray(params.items)) {
              params.items.forEach(item => {
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
            if (params.currency) rdtData.currency = params.currency;
            if (params.value !== undefined) rdtData.value = parseFloat(params.value);
            if (!rdtData.itemCount) {
              rdtData.itemCount = 1;
            }
          }

          if (rdtEventName === 'PageVisit') {
            window.rdt('track', 'PageVisit');
          } else {
            window.rdt('track', rdtEventName, rdtData);
          }
          firedPlatforms.push('Reddit');
          executionDetails['Reddit'] = { status: 'Sent', id: platforms.reddit_pixel_id };
        } else {
          executionDetails['Reddit'] = { status: 'Disabled', id: platforms.reddit_pixel_id };
        }
      } else {
        executionDetails['Reddit'] = { status: 'Failed (rdt not found)', id: platforms.reddit_pixel_id };
      }
    }

    // 6. Pinterest Pixel (Pinterest Tag)
    const pinterestConfig = config.pinterest_config || (window.PixelOnWP_events && window.PixelOnWP_events.pinterest_config) || {};
    const pinterestTagId = platforms.pinterest_tag_id || pinterestConfig.tag_id;
    if (selectedPlatforms.includes('pinterest') && pinterestTagId) {
      if (typeof window.pintrk !== 'undefined') {
        const normalized = String(eventName || '').toLowerCase().replace(/[^a-z0-9]/g, '');
        const cleanToMappingKey = {
          'pageview': 'PageView', 'page_view': 'PageView', 'pagevisit': 'PageView',
          'viewitem': 'ViewContent', 'view_item': 'ViewContent', 'viewcontent': 'ViewContent',
          'search': 'Search',
          'addtocart': 'AddToCart', 'add_to_cart': 'AddToCart',
          'begincheckout': 'InitiateCheckout', 'initiatecheckout': 'InitiateCheckout', 'startcheckout': 'InitiateCheckout',
          'purchase': 'Purchase', 'completepayment': 'Purchase',
          'lead': 'Lead', 'submitform': 'Lead',
          'signup': 'CompleteRegistration', 'completeregistration': 'CompleteRegistration',
          'download': 'Download',
          'contact': 'Contact',
          'schedule': 'Schedule'
        };
        const actionKey = cleanToMappingKey[normalized];
        const mappings = pinterestConfig.mappings || {};

        let pinEventName = '';
        if (actionKey && mappings[actionKey]) {
          pinEventName = mappings[actionKey];
        } else {
          const pinMap = {
            'page_view': 'pagevisit', 'pageview': 'pagevisit', 'pagevisit': 'pagevisit',
            'view_item': 'pagevisit', 'viewitem': 'pagevisit', 'viewcontent': 'pagevisit',
            'search': 'search',
            'addtocart': 'addtocart', 'add_to_cart': 'addtocart',
            'begincheckout': 'initiatecheckout', 'initiatecheckout': 'initiatecheckout', 'startcheckout': 'initiatecheckout',
            'purchase': 'checkout', 'completepayment': 'checkout',
            'lead': 'lead', 'submitform': 'lead',
            'signup': 'signup', 'completeregistration': 'signup',
            'download': 'lead', 'contact': 'lead', 'schedule': 'lead',
            'watchvideo': 'watchvideo', 'watch_video': 'watchvideo',
            'viewcategory': 'viewcategory', 'view_category': 'viewcategory'
          };
          pinEventName = pinMap[normalized] || 'pagevisit';
        }

        let pinterestActive = true;
        if (pinterestConfig.events && actionKey) {
          const isEnabled = pinterestConfig.events[actionKey];
          if (isEnabled === false || isEnabled === '0' || isEnabled === 'false') {
            pinterestActive = false;
          }
        }

        if (pinterestActive) {
          let pinData = {};
          if (eventId) {
            pinData.event_id = eventId;
          }
          if (params.value !== undefined) {
            pinData.value = parseFloat(params.value);
          }
          if (params.currency) {
            pinData.currency = String(params.currency);
          }

          // Map official Pinterest event-specific parameters
          if (pinEventName === 'checkout' || pinEventName === 'addtocart') {
            if (params.num_items !== undefined) {
              pinData.order_quantity = parseInt(params.num_items, 10);
            } else if (params.quantity !== undefined) {
              pinData.order_quantity = parseInt(params.quantity, 10);
            }
          }
          if (pinEventName === 'pagevisit' && params.property !== undefined) {
            pinData.property = String(params.property);
          }
          if ((pinEventName === 'signup' || pinEventName === 'lead') && params.lead_type !== undefined) {
            pinData.lead_type = String(params.lead_type);
          }
          if (pinEventName === 'watchvideo' && params.video_title !== undefined) {
            pinData.video_title = String(params.video_title);
          }
          if (pinEventName === 'search') {
            pinData.search_query = String(params.search_query || params.query || params.search_term || '');
          }
          if (pinEventName === 'viewcategory') {
            pinData.product_category = String(params.product_category || params.category || '');
          }

          window.pintrk('track', pinEventName, pinData);
          firedPlatforms.push('Pinterest');
          executionDetails['Pinterest'] = { status: 'Sent', id: pinterestTagId };
        } else {
          executionDetails['Pinterest'] = { status: 'Disabled', id: pinterestTagId };
        }
      } else {
        executionDetails['Pinterest'] = { status: 'Failed (pintrk not found)', id: pinterestTagId };
      }
    }

    const fbTrackingMode = config.facebook_tracking_mode || (window.PixelOnWP_events && window.PixelOnWP_events.facebook_tracking_mode) || 'hybrid';
    const ttTrackingMode = config.tiktok_tracking_mode || (window.PixelOnWP_events && window.PixelOnWP_events.tiktok_tracking_mode) || 'hybrid';
    const redditTrackingMode = config.reddit_tracking_mode || (window.PixelOnWP_events && window.PixelOnWP_events.reddit_tracking_mode) || 'hybrid';

    const shouldSendToServer = (fbTrackingMode !== 'browser') || (ttTrackingMode !== 'browser') || (redditTrackingMode !== 'browser') || (pinterestConfig.access_token && pinterestConfig.tag_id) || (platforms.ga4_measurement_id && ga4ServerEnabled);

    if (shouldSendToServer) {
      const apiRoute = config.custom_route || (window.PixelOnWP_events && window.PixelOnWP_events.custom_route) || '/wp-json/pixelonwp/v1/collect';
      const payload = {
        event: eventName,
        event_id: eventId,
        custom_data: params
      };

      fetch(apiRoute, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            firedPlatforms.push('Server CAPI');
            executionDetails['Server CAPI'] = { status: 'Sent' };
          } else {
            executionDetails['Server CAPI'] = { status: 'Failed: ' + (res.message || 'Unknown Error') };
          }
        })
        .catch(e => {
          console.warn('PixelOnWP CAPI Forwarding Failed for Custom Event:', e);
          executionDetails['Server CAPI'] = { status: 'Network Error' };
        });
    }

    const debugDetail = {
      event_name: eventName,
      trigger_type: rule.trigger_type,
      selector: rule.selector,
      params: params,
      platforms: firedPlatforms,
      execution_details: executionDetails,
      timestamp: Date.now()
    };

    window.dispatchEvent(new CustomEvent('plugin_live_event_tracked', {
      detail: debugDetail
    }));
  }

  // Set up event listeners
  function initTracker() {
    rules.forEach(rule => {
      if (!rule.active) return;

      const trigger = rule.trigger_type;
      const selector = rule.selector;

      if (trigger === 'page_view') {
        dispatchEvent(rule.event_name, extractParams(document.body, rule.parameters), rule.platforms || [], rule);

      } else if (trigger === 'click') {
        document.addEventListener('click', function (e) {
          // Attempt fallback matches if primary fails
          let target = e.target.closest(selector);
          if (!target) {
            // Traverse path to find if any element is close to healing classes
            let el = e.target;
            while (el && el !== document) {
              if (elementMatches(el, selector)) {
                target = el;
                break;
              }
              el = el.parentNode;
            }
          }

          if (target) {
            const params = extractParams(target, rule.parameters);
            dispatchEvent(rule.event_name, params, rule.platforms || [], rule);
          }
        }, true);

      } else if (trigger === 'submit') {
        document.addEventListener('submit', function (e) {
          let target = e.target.closest(selector);
          if (!target && elementMatches(e.target, selector)) {
            target = e.target;
          }

          if (target) {
            const params = extractParams(target, rule.parameters);
            dispatchEvent(rule.event_name, params, rule.platforms || [], rule);
          }
        }, true);

      } else if (trigger === 'visibility') {
        if ('IntersectionObserver' in window) {
          const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                const params = extractParams(entry.target, rule.parameters);
                dispatchEvent(rule.event_name, params, rule.platforms || [], rule);
                obs.unobserve(entry.target);
              }
            });
          }, { threshold: 0.1 });

          const bindObserver = () => {
            // Collect elements matching selector directly or via auto-healing
            document.querySelectorAll('*').forEach(el => {
              if (elementMatches(el, selector)) {
                observer.observe(el);
              }
            });
          };

          bindObserver();
          const mutObs = new MutationObserver(bindObserver);
          mutObs.observe(document.body, { childList: true, subtree: true });
        }
      }
    });

    // 2. Initialize GA4 Custom Events Builders
    const getGaClientId = () => {
      const match = document.cookie.match(/_ga=([^;]+)/);
      if (match) {
        return match[1].replace(/^GA\d\.\d\./, '');
      }
      return '';
    };

    const getGaSessionId = () => {
      const measurementIdClean = (platforms.ga4_measurement_id || '').replace('G-', '');
      const match = document.cookie.match(new RegExp('_ga_' + measurementIdClean + '=([^;]+)'));
      if (match) {
        const parts = match[1].split('.');
        if (parts.length > 2) {
          return parts[2];
        }
      }
      return '';
    };

    ga4Events.forEach(evt => {
      const trigger = evt.trigger_type;
      const selector = evt.selector;

      const triggerGA4Event = (element) => {
        // Build dynamic parameters
        const params = extractParams(element, evt.parameters);

        const sendGA4 = (cId, sId) => {
          const finalParams = { ...params };
          if (cId) {
            finalParams.client_id = cId;
          }
          if (sId) {
            finalParams.session_id = sId;
          }
          if (config.ga4_debug_mode) {
            finalParams.debug_mode = true;
          }

          // 1. Client-Side (gtag.js)
          if (evt.client_enabled && platforms.ga4_measurement_id && typeof window.gtag === 'function') {
            window.gtag('event', evt.name, {
              'send_to': platforms.ga4_measurement_id,
              ...finalParams
            });
          }

          // 2. Server-Side (Measurement Protocol/CAPI Route forwarding)
          if (evt.server_enabled) {
            const apiRoute = config.custom_route || (window.PixelOnWP_events && window.PixelOnWP_events.custom_route) || '/wp-json/pixelonwp/v1/collect';
            const eventId = 'ev_' + Date.now() + '_' + Math.floor(Math.random() * 100000);
            const payload = {
              event: evt.name,
              event_id: eventId,
              custom_data: finalParams
            };
            fetch(apiRoute, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(payload)
            }).catch(e => console.warn('GA4 custom event server-side forward failed:', e));
          }
        };

        // Enforce client_id and session_id attachment with race timeout
        let resolved = false;
        const cookieClientId = getGaClientId();
        const cookieSessionId = getGaSessionId();

        if (typeof window.gtag === 'function' && platforms.ga4_measurement_id) {
          const timeoutId = setTimeout(() => {
            if (!resolved) {
              resolved = true;
              sendGA4(cookieClientId, cookieSessionId);
            }
          }, 350);

          window.gtag('get', platforms.ga4_measurement_id, 'client_id', (clientId) => {
            if (!resolved) {
              resolved = true;
              clearTimeout(timeoutId);
              sendGA4(clientId || cookieClientId, cookieSessionId);
            }
          });
        } else {
          sendGA4(cookieClientId, cookieSessionId);
        }
      };

      if (trigger === 'page_view') {
        triggerGA4Event(document.body);
      } else if (trigger === 'click') {
        document.addEventListener('click', function (e) {
          let target = e.target.closest(selector);
          if (!target) {
            let el = e.target;
            while (el && el !== document) {
              if (elementMatches(el, selector)) {
                target = el;
                break;
              }
              el = el.parentNode;
            }
          }
          if (target) {
            triggerGA4Event(target);
          }
        }, true);
      } else if (trigger === 'submit') {
        document.addEventListener('submit', function (e) {
          let target = e.target.closest(selector);
          if (!target && elementMatches(e.target, selector)) {
            target = e.target;
          }
          if (target) {
            triggerGA4Event(target);
          }
        }, true);
      } else if (trigger === 'visibility') {
        if ('IntersectionObserver' in window) {
          const observedElements = new WeakSet();
          const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                triggerGA4Event(entry.target);
                obs.unobserve(entry.target);
              }
            });
          }, { threshold: 0.1 });
          const bindObserver = () => {
            document.querySelectorAll('*').forEach(el => {
              if (!observedElements.has(el) && elementMatches(el, selector)) {
                observedElements.add(el);
                observer.observe(el);
              }
            });
          };
          bindObserver();
          const mutObs = new MutationObserver(bindObserver);
          mutObs.observe(document.body, { childList: true, subtree: true });
        }
      }
    });
  }

  // Expose Global API
  window.PixelOnWP = window.PixelOnWP || {};
  window.PixelOnWP.track = (eventName, params = {}, ruleObj = {}) => {
    const allPlatforms = [];
    if (platforms.fb_pixel_id) allPlatforms.push('facebook');
    if (platforms.tt_pixel_id) allPlatforms.push('tiktok');
    if (platforms.reddit_pixel_id) allPlatforms.push('reddit');
    if (platforms.pinterest_tag_id) allPlatforms.push('pinterest');
    if (platforms.google_ads_id) allPlatforms.push('google_ads');
    if (platforms.ga4_measurement_id) allPlatforms.push('ga4');

    // Merge defaults
    const rule = Object.assign({ url_match_type: 'all' }, ruleObj);
    dispatchEvent(eventName, params, rule.platforms || allPlatforms, rule);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTracker);
  } else {
    initTracker();
  }
})();
