export function renderUTMBuilder(container, state) {

  const dashboardGrid = document.createElement('div');
  dashboardGrid.className = 'pp-grid-sidebar-right';
  dashboardGrid.style.marginBottom = '40px';

  // Form Card (Left)
  const formCard = document.createElement('div');
  formCard.className = 'pp-card';
  formCard.innerHTML = `
    <div class="pp-card-header">
      <div class="pp-card-title">Campaign Parameters</div>
    </div>
    <div style="display: flex; flex-direction: column; gap: 16px;">
      
      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-main);">Website URL <span style="color:var(--pp-danger)">*</span></label>
        <input type="url" id="utm_url" class="pp-input" placeholder="https://yoursite.com/product" value="${window.location.origin}" required>
        <p style="font-size: 11px; color: var(--pp-text-muted); margin-top: 4px;">The full website URL (e.g. https://www.example.com)</p>
      </div>

      <div class="pp-grid-2col" style="gap: 16px;">
        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-main);">Campaign Source (utm_source) <span style="color:var(--pp-danger)">*</span></label>
          <input type="text" id="utm_source" class="pp-input" placeholder="e.g. facebook, google, newsletter">
          <p style="font-size: 11px; color: var(--pp-text-muted); margin-top: 4px;">The referrer (e.g. facebook, google)</p>
        </div>
        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-main);">Campaign Medium (utm_medium)</label>
          <input type="text" id="utm_medium" class="pp-input" placeholder="e.g. cpc, email, social">
          <p style="font-size: 11px; color: var(--pp-text-muted); margin-top: 4px;">Marketing medium (e.g. cpc, banner, email)</p>
        </div>
      </div>

      <div>
        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-main);">Campaign Name (utm_campaign)</label>
        <input type="text" id="utm_campaign" class="pp-input" placeholder="e.g. summer_sale, black_friday">
        <p style="font-size: 11px; color: var(--pp-text-muted); margin-top: 4px;">Product, promo code, or slogan</p>
      </div>

      <div class="pp-grid-2col" style="gap: 16px;">
        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-main);">Campaign Term (utm_term)</label>
          <input type="text" id="utm_term" class="pp-input" placeholder="e.g. running_shoes">
          <p style="font-size: 11px; color: var(--pp-text-muted); margin-top: 4px;">Identify the paid keywords</p>
        </div>
        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: var(--pp-text-main);">Campaign Content (utm_content)</label>
          <input type="text" id="utm_content" class="pp-input" placeholder="e.g. logolink, textlink">
          <p style="font-size: 11px; color: var(--pp-text-muted); margin-top: 4px;">Use to differentiate ads</p>
        </div>
      </div>

    </div>
  `;

  // Right Column (Templates & Output)
  const rightCol = document.createElement('div');
  rightCol.style.display = 'flex';
  rightCol.style.flexDirection = 'column';
  rightCol.style.gap = '24px';

  const templatesCard = document.createElement('div');
  templatesCard.className = 'pp-card';
  templatesCard.innerHTML = `
    <div class="pp-card-header">
      <div class="pp-card-title">Quick Templates</div>
    </div>
    <div style="display: flex; flex-direction: column; gap: 8px;">
      <button class="pp-btn pp-btn-secondary utm-tpl-btn" data-tpl="meta" style="width: 100%; justify-content: flex-start;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        Meta (Facebook) Ads
      </button>
      <button class="pp-btn pp-btn-secondary utm-tpl-btn" data-tpl="google" style="width: 100%; justify-content: flex-start;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>
        Google Ads
      </button>
      <button class="pp-btn pp-btn-secondary utm-tpl-btn" data-tpl="tiktok" style="width: 100%; justify-content: flex-start;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 8px;"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93v7.2c0 1.96-.5 3.92-1.85 5.38-1.79 1.92-4.57 2.68-7.07 1.99-2.39-.64-4.33-2.61-4.88-5.06-.52-2.34.12-4.86 1.72-6.57 1.6-1.72 4.09-2.52 6.36-2.06v4.06c-1.15-.22-2.39.06-3.23.87-.78.77-1.14 1.96-.89 3.04.28 1.12 1.25 1.99 2.41 2.12 1.25.13 2.51-.55 3.02-1.65.26-.57.37-1.2.37-1.83V.02z"/></svg>
        TikTok Ads
      </button>
      <button class="pp-btn pp-btn-secondary utm-tpl-btn" data-tpl="email" style="width: 100%; justify-content: flex-start;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        Email Newsletter
      </button>
    </div>
  `;

  const outputCard = document.createElement('div');
  outputCard.className = 'pp-card';
  outputCard.innerHTML = `
    <div class="pp-card-header">
      <div class="pp-card-title">Generated URL</div>
    </div>
    <div style="display: flex; flex-direction: column; gap: 12px;">
      <textarea id="utm_result" class="pp-input" style="min-height: 120px; font-family: monospace; font-size: 12px; resize: none; background: #020617; border-color: var(--pp-primary); color: #38bdf8;" readonly></textarea>
      <button id="btn-copy-utm" class="pp-btn" style="width: 100%; justify-content: center;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
        Copy to Clipboard
      </button>
    </div>
  `;

  rightCol.appendChild(templatesCard);
  rightCol.appendChild(outputCard);

  dashboardGrid.appendChild(formCard);
  dashboardGrid.appendChild(rightCol);

  container.appendChild(dashboardGrid);

  // Logic
  const inputs = {
    url: document.getElementById('utm_url'),
    source: document.getElementById('utm_source'),
    medium: document.getElementById('utm_medium'),
    campaign: document.getElementById('utm_campaign'),
    term: document.getElementById('utm_term'),
    content: document.getElementById('utm_content')
  };
  const resultObj = document.getElementById('utm_result');
  const copyBtn = document.getElementById('btn-copy-utm');

  const updateUrl = () => {
    try {
      if (!inputs.url.value) {
        resultObj.value = '';
        return;
      }
      
      let baseUrl = inputs.url.value;
      if (!baseUrl.startsWith('http://') && !baseUrl.startsWith('https://')) {
        baseUrl = 'https://' + baseUrl;
      }
      
      const url = new URL(baseUrl);
      
      if (inputs.source.value) url.searchParams.set('utm_source', inputs.source.value);
      else url.searchParams.delete('utm_source');
      
      if (inputs.medium.value) url.searchParams.set('utm_medium', inputs.medium.value);
      else url.searchParams.delete('utm_medium');
      
      if (inputs.campaign.value) url.searchParams.set('utm_campaign', inputs.campaign.value);
      else url.searchParams.delete('utm_campaign');
      
      if (inputs.term.value) url.searchParams.set('utm_term', inputs.term.value);
      else url.searchParams.delete('utm_term');
      
      if (inputs.content.value) url.searchParams.set('utm_content', inputs.content.value);
      else url.searchParams.delete('utm_content');
      
      resultObj.value = url.toString();
    } catch (e) {
      resultObj.value = 'Invalid URL...';
    }
  };

  // Attach event listeners to all inputs
  Object.values(inputs).forEach(input => {
    input.addEventListener('input', updateUrl);
  });

  // Templates
  const templates = {
    meta: {
      source: '{{site_source_name}}',
      medium: 'paid_social',
      campaign: '{{campaign.name}}',
      content: '{{ad.name}}',
      term: '{{adset.name}}'
    },
    google: {
      source: 'google',
      medium: 'cpc',
      campaign: '{campaignid}',
      content: '{creative}',
      term: '{keyword}'
    },
    tiktok: {
      source: 'tiktok',
      medium: 'cpc',
      campaign: '__CAMPAIGN_NAME__',
      content: '__AD_NAME__',
      term: '__AID_NAME__'
    },
    email: {
      source: 'newsletter',
      medium: 'email',
      campaign: 'monthly_promo',
      content: 'hero_link',
      term: ''
    }
  };

  container.querySelectorAll('.utm-tpl-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const tpl = e.currentTarget.getAttribute('data-tpl');
      if (templates[tpl]) {
        inputs.source.value = templates[tpl].source;
        inputs.medium.value = templates[tpl].medium;
        inputs.campaign.value = templates[tpl].campaign;
        inputs.content.value = templates[tpl].content;
        inputs.term.value = templates[tpl].term;
        updateUrl();
      }
    });
  });

  // Copy
  copyBtn.addEventListener('click', () => {
    if (!resultObj.value || resultObj.value === 'Invalid URL...') return;
    
    resultObj.select();
    document.execCommand('copy');
    
    const originalText = copyBtn.innerHTML;
    copyBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><polyline points="20 6 9 17 4 12"/></svg> Copied!`;
    copyBtn.style.background = 'var(--pp-success)';
    copyBtn.style.color = 'white';
    copyBtn.style.borderColor = 'var(--pp-success)';
    
    setTimeout(() => {
      copyBtn.innerHTML = originalText;
      copyBtn.style = "width: 100%; justify-content: center;";
    }, 2000);
  });

  // Initial render
  updateUrl();
}
