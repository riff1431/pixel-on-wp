/**
 * PixelOnWP Animated Toast Notification Component
 *
 * Lightweight, accessible toaster alert system.
 */

let toastContainer = null;

function ensureContainer() {
  if (!toastContainer || !document.body.contains(toastContainer)) {
    toastContainer = document.createElement('div');
    toastContainer.className = 'pp-toast-container';
    toastContainer.id = 'pp-toast-container';
    document.body.appendChild(toastContainer);
  }
  return toastContainer;
}

const icons = {
  success: `<svg class="pp-toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
  error: `<svg class="pp-toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
  warning: `<svg class="pp-toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`,
  info: `<svg class="pp-toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
};

/**
 * Show a toast notification.
 *
 * @param {Object} options
 * @param {string} options.message Toast message text.
 * @param {string} [options.type='success'] success | error | warning | info
 * @param {string} [options.title] Optional toast title.
 * @param {number} [options.duration=4000] Auto-dismiss duration in ms.
 */
export function showToast({ message, type = 'success', title = '', duration = 4000 }) {
  const container = ensureContainer();

  const toast = document.createElement('div');
  toast.className = `pp-toast pp-toast-${type}`;

  const defaultTitles = {
    success: 'Success',
    error: 'Error',
    warning: 'Warning',
    info: 'Information'
  };

  const toastTitle = title || defaultTitles[type] || 'Notice';

  toast.innerHTML = `
    ${icons[type] || icons.info}
    <div class="pp-toast-content">
      <div class="pp-toast-title">${escapeHtml(toastTitle)}</div>
      <div class="pp-toast-message">${escapeHtml(message)}</div>
    </div>
    <button type="button" class="pp-toast-close" aria-label="Close alert">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
  `;

  const closeBtn = toast.querySelector('.pp-toast-close');
  let dismissTimer;

  const dismiss = () => {
    if (dismissTimer) clearTimeout(dismissTimer);
    toast.classList.add('exiting');
    toast.addEventListener('animationend', () => {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    });
    setTimeout(() => {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 300);
  };

  closeBtn.addEventListener('click', dismiss);

  if (duration > 0) {
    dismissTimer = setTimeout(dismiss, duration);
  }

  container.appendChild(toast);
  return toast;
}

function escapeHtml(str) {
  if (typeof str !== 'string') return '';
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
