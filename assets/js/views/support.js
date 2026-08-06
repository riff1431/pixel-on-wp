/**
 * PixelOnWP - Support Center View
 * 
 * Provides full client-side Support Ticket System interface inside plugin's admin UI.
 * Features: Ticket creation form, single-ticket thread interface, automated diagnostics
 * accordion, live indicators & polling, image uploader, and local state persistence.
 * 
 * @package PixelOnWP
 */

import { showToast } from '../components/toaster.js?v=12';

let pollingInterval = null;

export function renderSupport(container, state) {
  // Determine if specific ticket URL query or hash passed (e.g., #support?ticket=102)
  let activeTicketId = state.queryParams?.ticket || null;
  let viewMode = activeTicketId ? 'detail' : 'list';
  let activeFilter = 'all';

  function init() {
    container.innerHTML = '';
    if (pollingInterval) {
      clearInterval(pollingInterval);
      pollingInterval = null;
    }

    if (viewMode === 'detail' && activeTicketId) {
      loadSingleTicketView(activeTicketId);
    } else {
      loadTicketListView();
    }
  }

  /**
   * 1. Ticket List View
   */
  async function loadTicketListView() {
    container.innerHTML = `
      <div class="pp-view-header" style="display: flex; justify-content: flex-end;">
        <span class="pp-badge pp-badge-success" style="display:inline-flex; align-items:center; gap:6px;">
          <span class="pp-live-dot"></span> Active Support
        </span>
      </div>

      <div class="pp-grid-unequal-2-1" style="max-width: 1000px;">
        <!-- Left Column: contact form card -->
        <div class="pp-card">
          <div class="pp-card-header" style="border-bottom: 1px solid var(--pp-border-light); padding: 16px 24px;">
            <div class="pp-card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--pp-text-heading);">Quick Support Request</div>
          </div>
          
          <div style="padding: 24px;">
            <form id="pp-page-contact-form">
              <div style="margin-bottom:16px;">
                <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--pp-text-heading);">Subject *</label>
                <input type="text" id="pp-page-subject" class="pp-input" placeholder="e.g. GA4 Purchase Event Issue" required style="width:100%;" />
              </div>

              <div class="pp-grid-2col" style="gap:16px; margin-bottom:16px;">
                <div>
                  <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--pp-text-heading);">Category</label>
                  <select id="pp-page-category" class="pp-select" style="width:100%; height: 42px;">
                    <option value="E-commerce">E-commerce</option>
                    <option value="Lead-Gen">Lead-Gen</option>
                    <option value="General Bug" selected>General Bug</option>
                    <option value="Feature Request">Feature Request</option>
                  </select>
                </div>

                <div>
                  <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--pp-text-heading);">Priority</label>
                  <select id="pp-page-priority" class="pp-select" style="width:100%; height: 42px;">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Urgent">Urgent</option>
                  </select>
                </div>
              </div>

              <div style="margin-bottom:16px;">
                <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--pp-text-heading);">Description *</label>
                <textarea id="pp-page-description" class="pp-textarea" rows="5" placeholder="Describe the issue you are experiencing in detail..." required style="width:100%; font-family:var(--pp-font-body);"></textarea>
              </div>

              <div style="margin-bottom:18px;">
                <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--pp-text-heading);">Image Attachment (Optional)</label>
                <input type="file" id="pp-page-attachment" accept="image/*" style="font-size:13px; color:var(--pp-text-muted); width: 100%;" />
              </div>

              <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px 14px; border-radius:8px; font-size:12px; color:#475569; margin-bottom:24px; display:flex; align-items:start; gap:8px; line-height: 1.5;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" style="margin-top:2px; flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span>Automated system diagnostics will be automatically attached.</span>
              </div>

              <button type="submit" id="pp-page-submit" class="pp-btn pp-btn-primary" style="width:100%; justify-content:center; padding: 12px; font-size: 15px;">Submit Ticket</button>
            </form>
          </div>
        </div>

        <!-- Right Column: status / documentation box -->
        <div class="pp-card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 32px;">
          <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--pp-success-bg); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; border: 2px solid rgba(16, 185, 129, 0.2); box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="var(--pp-success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-help-circle"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          </div>
          <h3 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 8px 0; color: var(--pp-text-main);">Huipper Support Hub</h3>
          <p style="font-size: 13px; color: var(--pp-text-muted); margin-bottom: 24px;">Need immediate assistance? Access our complete documentation, FAQs, and developer tools on our website.</p>
          
          <a href="https://huipper.com/support" target="_blank" rel="noopener noreferrer" class="pp-btn" style="width: 100%; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            Visit Website Support
          </a>

          <div style="width: 100%; text-align: left; background: rgba(0,0,0,0.03); padding: 16px; border-radius: var(--pp-radius-sm); font-size: 13px; margin-top: 24px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
              <span style="color: var(--pp-text-muted);">Response Time</span>
              <span style="font-weight: 600; color: var(--pp-success);">&lt; 24 Hours</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
              <span style="color: var(--pp-text-muted);">Support Plan</span>
              <span style="font-weight: 600;">Active Developer Support</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--pp-text-muted);">Diagnostics</span>
              <span style="font-weight: 600; color: var(--pp-accent-emerald);">Auto-Attached</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Create Ticket Modal Container -->
      <div id="pp-create-ticket-modal-root"></div>
    `;

    // In-page contact form listener
    const pageForm = container.querySelector('#pp-page-contact-form');
    if (pageForm) {
      pageForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const subject = pageForm.querySelector('#pp-page-subject').value.trim();
        const category = pageForm.querySelector('#pp-page-category').value;
        const priority = pageForm.querySelector('#pp-page-priority').value;
        const description = pageForm.querySelector('#pp-page-description').value.trim();
        const fileInput = pageForm.querySelector('#pp-page-attachment');
        const submitBtn = pageForm.querySelector('#pp-page-submit');

        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        try {
          const restUrl = window.pixelonwp_admin_vars?.rest_url || '/wp-json/pixelonwp/v1';
          const nonce = window.pixelonwp_admin_vars?.nonce || '';

          const formData = new FormData();
          formData.append('subject', subject);
          formData.append('category', category);
          formData.append('priority', priority);
          formData.append('description', description);
          if (fileInput?.files[0]) {
            formData.append('attachment', fileInput.files[0]);
          }

          const res = await fetch(`${restUrl}/support/tickets`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce },
            body: formData
          });

          const data = await res.json();
          if (data.success && data.ticket) {
            showToast('Support ticket created successfully.', 'success');
            activeTicketId = data.ticket.id;
            viewMode = 'detail';
            loadSingleTicketView(data.ticket.id);
          } else {
            showToast(data.message || 'Failed to create ticket.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Ticket';
          }
        } catch (err) {
          showToast('Network error creating ticket.', 'error');
          submitBtn.disabled = false;
          submitBtn.textContent = 'Submit Ticket';
        }
      });
    }
  }

  async function fetchTickets() {
    try {
      const restUrl = window.pixelonwp_admin_vars?.rest_url || '/wp-json/pixelonwp/v1';
      const nonce = window.pixelonwp_admin_vars?.nonce || '';
      
      const res = await fetch(`${restUrl}/support/tickets`, {
        headers: { 'X-WP-Nonce': nonce }
      });
      const data = await res.json();
      if (data.success && Array.isArray(data.tickets)) {
        return data.tickets;
      }
    } catch (err) {
      console.warn('REST API fetch error, falling back to local simulation:', err);
    }
    return [];
  }

  async function renderTicketsList() {
    const listContainer = container.querySelector('#pp-ticket-list-container');
    if (!listContainer) return;

    const tickets = await fetchTickets();

    const filtered = tickets.filter(t => {
      if (activeFilter === 'open') return t.status.toLowerCase() === 'open';
      if (activeFilter === 'closed') return t.status.toLowerCase() === 'closed' || t.status.toLowerCase() === 'resolved';
      return true;
    });

    if (filtered.length === 0) {
      listContainer.innerHTML = `
        <div class="pp-card" style="text-align:center; padding:60px 20px;">
          <div style="width:60px; height:60px; background:rgba(225,29,72,0.08); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px auto; color:var(--pp-primary);">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          </div>
          <h3 style="margin:0 0 8px 0; color:var(--pp-text-heading);">No Support Tickets Found</h3>
          <p style="color:var(--pp-text-muted); max-width:400px; margin:0 auto 20px auto;">You haven't created any support requests matching this filter yet.</p>
          <button class="pp-btn pp-btn-primary" id="pp-btn-create-empty">Create Support Ticket</button>
        </div>
      `;
      const btnEmpty = listContainer.querySelector('#pp-btn-create-empty');
      if (btnEmpty) btnEmpty.addEventListener('click', () => openCreateTicketModal());
      return;
    }

    let html = `<div class="pp-ticket-cards-grid" style="display:grid; grid-template-columns:1fr; gap:14px;">`;

    filtered.forEach(ticket => {
      const isOpen = ticket.status.toLowerCase() === 'open';
      const statusBadge = isOpen
        ? `<span class="pp-badge pp-badge-success" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;">Open</span>`
        : `<span class="pp-badge" style="background:#f3f4f6; color:#4b5563; border:1px solid #e5e7eb;">Closed</span>`;

      const priorityColor = {
        'Urgent': '#ef4444',
        'High': '#f97316',
        'Medium': '#eab308',
        'Low': '#3b82f6'
      }[ticket.priority] || '#6b7280';

      const messageCount = ticket.messages?.length || 0;

      html += `
        <div class="pp-card pp-ticket-card-item" data-id="${ticket.id}" style="padding:20px; transition:var(--pp-transition); cursor:pointer; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
          <div style="flex:1; min-width:280px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
              <span style="font-weight:700; font-family:var(--pp-font-heading); color:var(--pp-primary); font-size:14px;">Ticket #${ticket.id}</span>
              ${statusBadge}
              <span class="pp-badge" style="background:rgba(243,244,246,0.8); color:var(--pp-text-main); font-size:11px;">Category: ${escapeHtml(ticket.category || 'General')}</span>
              <span class="pp-badge" style="background:rgba(255,255,255,0.9); color:${priorityColor}; border:1px solid ${priorityColor}40; font-size:11px;">${escapeHtml(ticket.priority || 'Medium')}</span>
            </div>
            <h3 style="margin:0 0 6px 0; font-size:16px; color:var(--pp-text-heading); font-weight:600;">${escapeHtml(ticket.subject)}</h3>
            <div style="font-size:12px; color:var(--pp-text-muted); display:flex; gap:16px;">
              <span>Updated: <strong>${escapeHtml(ticket.last_updated || 'Just Now')}</strong></span>
              <span>Messages: <strong>${messageCount}</strong></span>
              <span>Diagnostics: <strong style="color:var(--pp-accent-emerald);">Attached ✓</strong></span>
            </div>
          </div>
          <div>
            <button class="pp-btn pp-btn-secondary btn-view-ticket" data-id="${ticket.id}" style="display:inline-flex; align-items:center; gap:6px;">
              View Thread
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>
          </div>
        </div>
      `;
    });

    html += `</div>`;
    listContainer.innerHTML = html;

    // Attach click listeners to cards and buttons
    listContainer.querySelectorAll('.pp-ticket-card-item').forEach(card => {
      card.addEventListener('click', (e) => {
        const id = card.dataset.id;
        activeTicketId = id;
        viewMode = 'detail';
        loadSingleTicketView(id);
      });
    });
  }

  /**
   * 2. Single Ticket / Thread View (Matching exact user UI reference)
   */
  async function loadSingleTicketView(ticketId) {
    container.innerHTML = `
      <div style="padding: 20px 0; text-align:center; color:var(--pp-text-muted);">
        <div class="pp-spinner" style="margin: 0 auto 12px auto;"></div>
        Loading Support Thread #${ticketId}...
      </div>
    `;

    let ticket = null;
    try {
      const restUrl = window.pixelonwp_admin_vars?.rest_url || '/wp-json/pixelonwp/v1';
      const nonce = window.pixelonwp_admin_vars?.nonce || '';
      const res = await fetch(`${restUrl}/support/tickets/${ticketId}`, {
        headers: { 'X-WP-Nonce': nonce }
      });
      const data = await res.json();
      if (data.success && data.ticket) {
        ticket = data.ticket;
      }
    } catch (err) {
      console.warn('Error fetching ticket detail:', err);
    }

    if (!ticket) {
      container.innerHTML = `
        <div class="pp-card" style="padding:40px; text-align:center;">
          <h3 style="color:var(--pp-danger);">Ticket #${ticketId} Not Found</h3>
          <p style="color:var(--pp-text-muted); margin-bottom:20px;">The requested ticket could not be loaded.</p>
          <button class="pp-btn pp-btn-secondary" id="pp-btn-back-error">&lt; Back to All Tickets</button>
        </div>
      `;
      container.querySelector('#pp-btn-back-error')?.addEventListener('click', () => {
        viewMode = 'list';
        activeTicketId = null;
        loadTicketListView();
      });
      return;
    }

    const isOpen = ticket.status.toLowerCase() === 'open';
    const isOnline = ticket.developer_online !== false;
    const isTyping = ticket.developer_typing === true;

    const diagJson = JSON.stringify(ticket.system_diagnostics || {}, null, 2);

    container.innerHTML = `
      <!-- UI REFERENCE HEADER BANNER -->
      <div class="pp-card pp-support-header-bar" style="margin-bottom:20px; border-top:4px solid var(--pp-primary);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:12px;">
          <div style="font-size:18px; font-weight:700; font-family:var(--pp-font-heading); color:var(--pp-text-heading); display:flex; align-items:center; gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--pp-primary)" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Tracker Pro Panel - Support Center
          </div>
          <div>
            <span class="pp-badge pp-badge-success" style="padding:6px 14px; font-size:12px;">[ Active Support ]</span>
          </div>
        </div>

        <div class="pp-support-status-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; background:rgba(247,246,249,0.7); padding:12px 16px; border-radius:var(--pp-radius-sm); border:1px solid var(--pp-border);">
          <button class="pp-btn-link" id="pp-btn-back-tickets" style="background:none; border:none; color:var(--pp-primary); font-weight:600; cursor:pointer; font-size:13px; padding:0; display:flex; align-items:center; gap:4px;">
            &lt; Back to All Tickets
          </button>
          
          <div class="pp-support-status-bar-title" style="font-weight:700; font-size:15px; color:var(--pp-text-heading);">
            Ticket #${escapeHtml(ticket.id)}: ${escapeHtml(ticket.subject)}
          </div>

          <div class="pp-support-status-bar-meta" style="font-size:13px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
            <span>Status: <strong id="pp-ticket-status-tag" class="${isOpen ? 'pp-status-open' : 'pp-status-closed'}">[ ${escapeHtml(ticket.status)} ]</strong></span>
            <span class="pp-separator" style="color:var(--pp-text-muted);">|</span>
            <span>Last Updated: <strong id="pp-ticket-updated-tag">${escapeHtml(ticket.last_updated || 'Just Now')}</strong></span>
            <span class="pp-separator" style="color:var(--pp-text-muted);">|</span>
            <span>Developer Status: <strong id="pp-dev-status-tag" style="color:#10b981;">[ ${isOnline ? '🟢 Developer is Online' : '⚪ Developer Offline'} ]</strong></span>
          </div>
        </div>
      </div>

      <!-- AUTOMATED DIAGNOSTICS ACCORDION CONTAINER -->
      <div class="pp-card pp-diagnostics-card" style="margin-bottom:20px; background:linear-gradient(135deg, rgba(240,249,255,0.95) 0%, rgba(224,242,254,0.6) 100%); border:1px solid #bae6fd;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <div>
            <div style="font-size:12px; font-weight:800; letter-spacing:0.05em; color:#0369a1; text-transform:uppercase; margin-bottom:4px; display:flex; align-items:center; gap:6px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
              AUTOMATED DIAGNOSTICS ATTACHED
            </div>
            <div style="font-size:13px; color:#0c4a6e;">
              ℹ️ System details (WP ${escapeHtml(ticket.system_diagnostics?.wp_version || '6.5')}, PHP ${escapeHtml(ticket.system_diagnostics?.php_version || '8.1')}, Active Scripts) were sent to help resolve this faster.
            </div>
          </div>
          <button class="pp-btn pp-btn-secondary" id="pp-toggle-diagnostics-accordion" style="background:#ffffff; border-color:#93c5fd; color:#0284c7; font-size:12px; font-weight:600;">
            [ View Attached System Specs (Toggle Accordion) ]
          </button>
        </div>

        <div id="pp-diagnostics-accordion-body" style="display:none; margin-top:14px; padding-top:14px; border-top:1px dashed #93c5fd;">
          <pre style="background:#0f172a; color:#38bdf8; padding:14px; border-radius:10px; font-size:12px; font-family:monospace; max-height:220px; overflow-y:auto; margin:0;">${escapeHtml(diagJson)}</pre>
        </div>
      </div>

      <!-- CONVERSATION HISTORY -->
      <div class="pp-card pp-conversation-card" style="margin-bottom:20px;">
        <div style="font-size:12px; font-weight:800; letter-spacing:0.05em; color:var(--pp-text-muted); text-transform:uppercase; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          CONVERSATION HISTORY
        </div>

        <div id="pp-conversation-thread" style="display:flex; flex-direction:column; gap:16px;">
          ${renderMessages(ticket.messages || [])}
        </div>

        <!-- LIVE TYPING INDICATOR -->
        <div id="pp-live-typing-container" style="margin-top:16px; display:${isTyping ? 'block' : 'none'};">
          <div style="background:#fff1f2; border:1px solid #fecdd3; padding:10px 14px; border-radius:12px; display:inline-flex; align-items:center; gap:10px; font-size:13px; color:#be123c;">
            <span style="font-weight:700; color:#e11d48; display:flex; align-items:center; gap:6px;">
              <span class="pp-live-dot-red"></span> [🔴 LIVE UPDATE / TYPING...]
            </span>
            <span>Developer is typing...</span>
            <span class="pp-typing-dots"><span>.</span><span>.</span><span>.</span></span>
          </div>
        </div>
      </div>

      <!-- REPLY TO DEVELOPER FORM -->
      <div class="pp-card pp-reply-card" style="background:#ffffff;">
        <div style="font-size:12px; font-weight:800; letter-spacing:0.05em; color:var(--pp-text-muted); text-transform:uppercase; margin-bottom:12px;">
          REPLY TO DEVELOPER
        </div>

        <form id="pp-reply-form">
          <div style="margin-bottom:14px;">
            <textarea id="pp-reply-textarea" class="pp-textarea" rows="4" placeholder="Type your reply here..." style="width:100%; border-radius:var(--pp-radius-sm); padding:12px; font-family:var(--pp-font-body); font-size:14px; border:1px solid var(--pp-border);" ${!isOpen ? 'disabled' : ''}></textarea>
          </div>

          <!-- Attachment Section -->
          <div style="margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
              <label for="pp-reply-attachment-file" class="pp-btn pp-btn-secondary" style="font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                [ Attach Screenshot / Image Payload ]
              </label>
              <input type="file" id="pp-reply-attachment-file" accept="image/*" style="display:none;" />
              <span id="pp-reply-file-name" style="font-size:12px; color:var(--pp-text-muted);">No image attached</span>
            </div>
            <div style="font-size:11px; color:var(--pp-text-muted);">
              (System Specs payload auto-attached on submission)
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="pp-reply-actions-row" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; pt-2; border-top:1px solid var(--pp-border-light); padding-top:16px;">
            <button type="button" class="pp-btn" id="pp-btn-close-ticket" style="border:1px solid var(--pp-danger); color:var(--pp-danger); background:transparent; font-weight:600;" ${!isOpen ? 'disabled' : ''}>
              [ Close &amp; Resolve Ticket ]
            </button>

            <button type="submit" class="pp-btn pp-btn-primary" id="pp-btn-submit-reply" style="min-width:140px;" ${!isOpen ? 'disabled' : ''}>
              [ Send Reply ]
            </button>
          </div>
        </form>
      </div>
    `;

    // Event Listeners for Single Ticket View
    container.querySelector('#pp-btn-back-tickets')?.addEventListener('click', () => {
      viewMode = 'list';
      activeTicketId = null;
      loadTicketListView();
    });

    const accordionToggle = container.querySelector('#pp-toggle-diagnostics-accordion');
    const accordionBody = container.querySelector('#pp-diagnostics-accordion-body');
    if (accordionToggle && accordionBody) {
      accordionToggle.addEventListener('click', () => {
        const isHidden = accordionBody.style.display === 'none';
        accordionBody.style.display = isHidden ? 'block' : 'none';
      });
    }

    // Attachment file change listener
    const fileInput = container.querySelector('#pp-reply-attachment-file');
    const fileNameSpan = container.querySelector('#pp-reply-file-name');
    if (fileInput && fileNameSpan) {
      fileInput.addEventListener('change', (e) => {
        if (fileInput.files && fileInput.files[0]) {
          fileNameSpan.textContent = `Attached: ${fileInput.files[0].name}`;
          fileNameSpan.style.color = 'var(--pp-accent-emerald)';
          fileNameSpan.style.fontWeight = '600';
        } else {
          fileNameSpan.textContent = 'No image attached';
          fileNameSpan.style.color = 'var(--pp-text-muted)';
        }
      });
    }

    // Close Ticket listener
    const closeBtn = container.querySelector('#pp-btn-close-ticket');
    if (closeBtn) {
      closeBtn.addEventListener('click', async () => {
        if (!confirm('Are you sure you want to close and resolve this ticket?')) return;
        closeBtn.disabled = true;
        closeBtn.textContent = 'Closing...';

        try {
          const restUrl = window.pixelonwp_admin_vars?.rest_url || '/wp-json/pixelonwp/v1';
          const nonce = window.pixelonwp_admin_vars?.nonce || '';
          const res = await fetch(`${restUrl}/support/tickets/${ticketId}/close`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce }
          });
          const data = await res.json();

          if (data.success) {
            showToast('Ticket resolved and closed.', 'success');
            loadSingleTicketView(ticketId);
          } else {
            showToast(data.message || 'Failed to close ticket.', 'error');
            closeBtn.disabled = false;
            closeBtn.textContent = '[ Close & Resolve Ticket ]';
          }
        } catch (err) {
          showToast('Network error while closing ticket.', 'error');
          closeBtn.disabled = false;
          closeBtn.textContent = '[ Close & Resolve Ticket ]';
        }
      });
    }

    // Submit Reply listener
    const replyForm = container.querySelector('#pp-reply-form');
    if (replyForm) {
      replyForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const textarea = container.querySelector('#pp-reply-textarea');
        const submitBtn = container.querySelector('#pp-btn-submit-reply');
        const messageText = textarea ? textarea.value.trim() : '';
        const attachmentFile = fileInput?.files[0];

        if (!messageText && !attachmentFile) {
          showToast('Please type a message or select an image attachment.', 'warning');
          return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';

        try {
          const restUrl = window.pixelonwp_admin_vars?.rest_url || '/wp-json/pixelonwp/v1';
          const nonce = window.pixelonwp_admin_vars?.nonce || '';

          const formData = new FormData();
          formData.append('message', messageText);
          if (attachmentFile) {
            formData.append('attachment', attachmentFile);
          }

          const res = await fetch(`${restUrl}/support/tickets/${ticketId}/reply`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce },
            body: formData
          });

          const data = await res.json();
          if (data.success) {
            showToast('Reply submitted to support team.', 'success');
            loadSingleTicketView(ticketId);
          } else {
            showToast(data.message || 'Error submitting reply.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = '[ Send Reply ]';
          }
        } catch (err) {
          showToast('Network error submitting reply.', 'error');
          submitBtn.disabled = false;
          submitBtn.textContent = '[ Send Reply ]';
        }
      });
    }

    // Start Live Polling for Developer Online / Typing Status
    startPolling(ticketId);
  }

  function renderMessages(messages) {
    if (!messages || messages.length === 0) {
      return `<div style="color:var(--pp-text-muted); font-style:italic;">No messages in this thread yet.</div>`;
    }

    return messages.map(msg => {
      const isUser = msg.sender === 'user';
      const label = isUser ? '[YOU / USER]' : '[DEVELOPER / SUPPORT TEAM]';
      const bg = isUser ? '#f8fafc' : '#f0fdf4';
      const border = isUser ? '#e2e8f0' : '#bbf7d0';
      const headerColor = isUser ? 'var(--pp-text-heading)' : '#15803d';

      return `
        <div class="pp-message-bubble" style="background:${bg}; border:1px solid ${border}; border-radius:14px; padding:16px;">
          <div class="pp-message-bubble-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <div style="font-weight:700; font-size:13px; color:${headerColor};">
              ${escapeHtml(label)}
            </div>
            <div style="font-size:12px; color:var(--pp-text-muted);">
              (${escapeHtml(msg.timestamp || 'Just now')})
            </div>
          </div>
          <div style="font-size:14px; line-height:1.6; color:var(--pp-text-main); white-space:pre-wrap;">${escapeHtml(msg.text || '')}</div>
          ${msg.attachment ? `
            <div style="margin-top:12px; padding-top:10px; border-top:1px dashed ${border};">
              <span style="font-size:11px; font-weight:700; color:var(--pp-text-muted);">ATTACHMENT:</span><br/>
              <a href="${escapeHtml(msg.attachment)}" target="_blank" rel="noopener noreferrer">
                <img src="${escapeHtml(msg.attachment)}" alt="Attachment" style="max-width:260px; max-height:180px; border-radius:8px; border:1px solid var(--pp-border); margin-top:6px; object-fit:cover;" />
              </a>
            </div>
          ` : ''}
        </div>
      `;
    }).join('');
  }

  /**
   * Live Polling Mechanism
   */
  function startPolling(ticketId) {
    if (pollingInterval) clearInterval(pollingInterval);

    pollingInterval = setInterval(async () => {
      try {
        const restUrl = window.pixelonwp_admin_vars?.rest_url || '/wp-json/pixelonwp/v1';
        const nonce = window.pixelonwp_admin_vars?.nonce || '';
        const res = await fetch(`${restUrl}/support/status?id=${ticketId}`, {
          headers: { 'X-WP-Nonce': nonce }
        });
        const data = await res.json();
        if (data.success) {
          const devStatusTag = container.querySelector('#pp-dev-status-tag');
          if (devStatusTag) {
            devStatusTag.innerHTML = `[ ${data.developer_online ? '🟢 Developer is Online' : '⚪ Developer Offline'} ]`;
            devStatusTag.style.color = data.developer_online ? '#10b981' : '#9ca3af';
          }

          const typingContainer = container.querySelector('#pp-live-typing-container');
          if (typingContainer) {
            typingContainer.style.display = data.developer_typing ? 'block' : 'none';
          }
        }
      } catch (err) {
        // Silent catch for background polling
      }
    }, 10000);
  }

  /**
   * 3. Create Ticket Modal Handler
   */
  function openCreateTicketModal() {
    const modalRoot = container.querySelector('#pp-create-ticket-modal-root');
    if (!modalRoot) return;

    modalRoot.innerHTML = `
      <div class="pp-modal-backdrop" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:99999; display:flex; align-items:center; justify-content:center; padding:20px;">
        <div class="pp-modal-content" style="background:#ffffff; border-radius:var(--pp-radius); max-width:600px; width:100%; padding:28px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid var(--pp-border);">
          
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0; font-size:20px; font-weight:700; color:var(--pp-text-heading); display:flex; align-items:center; gap:8px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--pp-primary)" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
              Create Support Ticket
            </h3>
            <button type="button" id="pp-modal-close-x" style="background:none; border:none; font-size:20px; color:var(--pp-text-muted); cursor:pointer;">&times;</button>
          </div>

          <form id="pp-create-ticket-form">
            <div style="margin-bottom:14px;">
              <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--pp-text-heading);">Subject *</label>
              <input type="text" id="pp-create-subject" class="pp-input" placeholder="e.g. GA4 Purchase Event Issue" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--pp-border);" />
            </div>

            <div class="pp-grid-2col" style="gap:14px; margin-bottom:14px;">
              <div>
                <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--pp-text-heading);">Category</label>
                <select id="pp-create-category" class="pp-select" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--pp-border);">
                  <option value="E-commerce">E-commerce</option>
                  <option value="Lead-Gen">Lead-Gen</option>
                  <option value="General Bug" selected>General Bug</option>
                  <option value="Feature Request">Feature Request</option>
                </select>
              </div>

              <div>
                <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--pp-text-heading);">Priority</label>
                <select id="pp-create-priority" class="pp-select" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--pp-border);">
                  <option value="Low">Low</option>
                  <option value="Medium" selected>Medium</option>
                  <option value="High">High</option>
                  <option value="Urgent">Urgent</option>
                </select>
              </div>
            </div>

            <div style="margin-bottom:14px;">
              <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--pp-text-heading);">Description *</label>
              <textarea id="pp-create-description" class="pp-textarea" rows="4" placeholder="Describe the issue you are experiencing in detail..." required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--pp-border); font-family:var(--pp-font-body);"></textarea>
            </div>

            <div style="margin-bottom:16px;">
              <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--pp-text-heading);">Image Attachment (Optional)</label>
              <input type="file" id="pp-create-attachment" accept="image/*" style="font-size:13px; color:var(--pp-text-muted);" />
              <div style="font-size:11px; color:var(--pp-text-muted); margin-top:4px;">Only image files (.jpg, .png, .gif, .webp) allowed.</div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px; font-size:12px; color:#475569; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
              <span>Automated System Diagnostics payload (WP version, PHP version, Active Plugins &amp; Config JSON) will be automatically attached.</span>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
              <button type="button" id="pp-modal-cancel" class="pp-btn pp-btn-secondary">Cancel</button>
              <button type="submit" id="pp-modal-submit" class="pp-btn pp-btn-primary">Submit Ticket</button>
            </div>
          </form>

        </div>
      </div>
    `;

    const closeModal = () => { modalRoot.innerHTML = ''; };

    modalRoot.querySelector('#pp-modal-close-x')?.addEventListener('click', closeModal);
    modalRoot.querySelector('#pp-modal-cancel')?.addEventListener('click', closeModal);

    modalRoot.querySelector('#pp-create-ticket-form')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const subject = modalRoot.querySelector('#pp-create-subject').value.trim();
      const category = modalRoot.querySelector('#pp-create-category').value;
      const priority = modalRoot.querySelector('#pp-create-priority').value;
      const description = modalRoot.querySelector('#pp-create-description').value.trim();
      const fileInput = modalRoot.querySelector('#pp-create-attachment');
      const submitBtn = modalRoot.querySelector('#pp-modal-submit');

      submitBtn.disabled = true;
      submitBtn.textContent = 'Submitting...';

      try {
        const restUrl = window.pixelonwp_admin_vars?.rest_url || '/wp-json/pixelonwp/v1';
        const nonce = window.pixelonwp_admin_vars?.nonce || '';

        const formData = new FormData();
        formData.append('subject', subject);
        formData.append('category', category);
        formData.append('priority', priority);
        formData.append('description', description);
        if (fileInput?.files[0]) {
          formData.append('attachment', fileInput.files[0]);
        }

        const res = await fetch(`${restUrl}/support/tickets`, {
          method: 'POST',
          headers: { 'X-WP-Nonce': nonce },
          body: formData
        });

        const data = await res.json();
        if (data.success && data.ticket) {
          showToast('Support ticket created successfully.', 'success');
          closeModal();
          activeTicketId = data.ticket.id;
          viewMode = 'detail';
          loadSingleTicketView(data.ticket.id);
        } else {
          showToast(data.message || 'Failed to create ticket.', 'error');
          submitBtn.disabled = false;
          submitBtn.textContent = 'Submit Ticket';
        }
      } catch (err) {
        showToast('Network error creating ticket.', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Ticket';
      }
    });
  }

  function escapeHtml(str) {
    if (typeof str !== 'string') return str;
    return str.replace(/[&<>"']/g, function(m) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[m];
    });
  }

  // Initialize view
  init();
}
