export function renderDocumentation(container, state) {
  container.innerHTML = '';

  const content = document.createElement('div');
  content.className = 'pp-card';
  content.style.padding = '32px';

  content.innerHTML = `
    <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(6, 182, 212, 0.08) 100%); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 16px; padding: 24px 28px; margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
      <div>
        <h3 style="margin: 0 0 6px 0; color: var(--pp-text-heading); font-size: 18px; font-weight: 700;">Setup & Operational User Guide</h3>
        <p style="margin: 0; color: var(--pp-text-muted); font-size: 13px;">Everything you need to master your WordPress tracking, from basic pixel setup to advanced integrations.</p>
      </div>
      <a href="https://huipper.com" target="_blank" class="pp-btn pp-btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
        <span>Online Docs Hub</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
      </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
      
      <div style="background: rgba(255,255,255,0.7); border: 1.5px solid var(--pp-border-strong); border-radius: 16px; padding: 22px;">
        <h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 700; color: var(--pp-text-heading); display: flex; align-items: center; gap: 10px;">
          <span style="display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: rgba(225, 29, 72, 0.1); border-radius: 8px; color: var(--pp-primary); font-size: 14px;">1</span>
          Credentials Sourcing Guide
        </h4>
        <ul style="margin: 0; padding-left: 20px; color: var(--pp-text-muted); font-size: 13px; line-height: 1.6;">
          <li><strong>Meta:</strong> Copy Pixel ID from Meta Events Manager, generate CAPI Access Token under Settings, and add a Test Event Code.</li>
          <li><strong>TikTok & Reddit:</strong> Retrieve Pixel IDs and Conversions API access tokens from developer portals.</li>
          <li><strong>GA4 & Google Ads:</strong> Copy Measurement IDs, API Secrets, and Conversion Labels.</li>
        </ul>
      </div>

      <div style="background: rgba(255,255,255,0.7); border: 1.5px solid var(--pp-border-strong); border-radius: 16px; padding: 22px;">
        <h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 700; color: var(--pp-text-heading); display: flex; align-items: center; gap: 10px;">
          <span style="display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: rgba(14, 165, 233, 0.1); border-radius: 8px; color: var(--pp-info); font-size: 14px;">2</span>
          Event Manager & eCommerce Tools
        </h4>
        <ul style="margin: 0; padding-left: 20px; color: var(--pp-text-muted); font-size: 13px; line-height: 1.6;">
          <li><strong>Standard Events:</strong> Automatically map WooCommerce Checkout, Add to Cart, View Content, and Purchase events.</li>
          <li><strong>Event Deduplication:</strong> Unique Event ID generators ensure browser and server events pair seamlessly without double counting.</li>
          <li><strong>Custom Parameters:</strong> Pass user data hash (email, phone, city) for maximum Event Quality Match Score.</li>
        </ul>
      </div>

      <div style="background: rgba(255,255,255,0.7); border: 1.5px solid var(--pp-border-strong); border-radius: 16px; padding: 22px;">
        <h4 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 700; color: var(--pp-text-heading); display: flex; align-items: center; gap: 10px;">
          <span style="display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: rgba(245, 158, 11, 0.1); border-radius: 8px; color: var(--pp-warning); font-size: 14px;">3</span>
          Fraud Prevention & ITP Bypass
        </h4>
        <ul style="margin: 0; padding-left: 20px; color: var(--pp-text-muted); font-size: 13px; line-height: 1.6;">
          <li><strong>Courier Sync:</strong> Connect Steadfast, Pathao, and RedX Merchant API credentials to automatically block high-return parcels.</li>
          <li><strong>First-Party Proxy:</strong> Route tracking requests through custom domain endpoints to bypass ad-blockers and iOS ITP limits.</li>
        </ul>
      </div>

    </div>
  `;

  container.appendChild(content);
}
