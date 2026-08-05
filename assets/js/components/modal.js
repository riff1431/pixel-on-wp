/**
 * PixelOnWP Modal Dialog Component
 *
 * Lightweight backdrop-blur Apple Liquid Glass dialog framework.
 */

export function showModal({ title = '', body = '', footer = '', onClose = null }) {
  const backdrop = document.createElement('div');
  backdrop.className = 'pp-modal-backdrop';

  const dialog = document.createElement('div');
  dialog.className = 'pp-modal-dialog';

  dialog.innerHTML = `
    <div class="pp-modal-header">
      <h3>${title}</h3>
      <button type="button" class="pp-toast-close pp-modal-close-btn" aria-label="Close modal">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="pp-modal-body">
      ${typeof body === 'string' ? body : ''}
    </div>
    ${footer ? `<div class="pp-modal-footer">${typeof footer === 'string' ? footer : ''}</div>` : ''}
  `;

  if (typeof body === 'object' && body instanceof HTMLElement) {
    dialog.querySelector('.pp-modal-body').appendChild(body);
  }
  if (typeof footer === 'object' && footer instanceof HTMLElement) {
    const footerContainer = dialog.querySelector('.pp-modal-footer');
    if (footerContainer) footerContainer.appendChild(footer);
  }

  backdrop.appendChild(dialog);

  const closeModal = () => {
    backdrop.style.opacity = '0';
    dialog.style.transform = 'scale(0.95)';
    setTimeout(() => {
      if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
      if (typeof onClose === 'function') onClose();
    }, 200);
  };

  const closeBtn = dialog.querySelector('.pp-modal-close-btn');
  if (closeBtn) closeBtn.addEventListener('click', closeModal);

  backdrop.addEventListener('click', (e) => {
    if (e.target === backdrop) closeModal();
  });

  const handleEscKey = (e) => {
    if (e.key === 'Escape') {
      closeModal();
      document.removeEventListener('keydown', handleEscKey);
    }
  };
  document.addEventListener('keydown', handleEscKey);

  document.body.appendChild(backdrop);

  return { backdrop, dialog, close: closeModal };
}

/**
 * Modern Apple Liquid Glass Confirmation Modal
 * Replaces native browser confirm() dialogs.
 */
export function showConfirmModal({
  title = 'Are you sure?',
  message = 'This action cannot be undone.',
  confirmText = 'Confirm',
  cancelText = 'Cancel',
  type = 'danger'
}) {
  return new Promise((resolve) => {
    const backdrop = document.createElement('div');
    backdrop.className = 'pp-modal-backdrop';

    const dialog = document.createElement('div');
    dialog.className = 'pp-modal-dialog pp-confirm-modal-dialog';
    dialog.style.maxWidth = '440px';

    const iconBg = type === 'danger' ? '#fef2f2' : '#eff6ff';
    const iconColor = type === 'danger' ? '#ef4444' : '#2563eb';
    const iconSvg = type === 'danger'
      ? `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`
      : `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;

    const btnClass = type === 'danger' ? 'pp-btn pp-btn-danger' : 'pp-btn pp-btn-primary';

    dialog.innerHTML = `
      <div style="padding: 28px 24px 20px 24px; text-align: center;">
        <div style="width: 58px; height: 58px; border-radius: 50%; background: ${iconBg}; color: ${iconColor}; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 4px 14px rgba(0,0,0,0.06);">
          ${iconSvg}
        </div>
        <h3 style="margin: 0 0 8px 0; font-family: var(--pp-font-heading); font-size: 20px; font-weight: 800; color: var(--pp-text-heading);">${title}</h3>
        <p style="margin: 0; color: var(--pp-text-muted); font-size: 14px; line-height: 1.5;">${message}</p>
      </div>
      <div class="pp-modal-footer" style="padding: 16px 24px; justify-content: center; gap: 12px; border-top: 1px solid var(--pp-border-light);">
        <button type="button" class="pp-btn-outline" id="pp-confirm-cancel-btn" style="flex: 1; max-width: 160px; justify-content: center;">${cancelText}</button>
        <button type="button" class="${btnClass}" id="pp-confirm-ok-btn" style="flex: 1; max-width: 160px; justify-content: center;">${confirmText}</button>
      </div>
    `;

    backdrop.appendChild(dialog);
    document.body.appendChild(backdrop);

    const closeAndResolve = (result) => {
      backdrop.style.opacity = '0';
      dialog.style.transform = 'scale(0.95)';
      setTimeout(() => {
        if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
        resolve(result);
      }, 150);
    };

    dialog.querySelector('#pp-confirm-ok-btn').addEventListener('click', () => closeAndResolve(true));
    dialog.querySelector('#pp-confirm-cancel-btn').addEventListener('click', () => closeAndResolve(false));
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) closeAndResolve(false);
    });

    window.addEventListener('keydown', function escHandler(e) {
      if (e.key === 'Escape' && backdrop.parentNode) {
        closeAndResolve(false);
        window.removeEventListener('keydown', escHandler);
      }
    });
  });
}

/**
 * Modern Apple Liquid Glass Alert Modal
 * Replaces native browser alert() dialogs.
 */
export function showAlertModal({
  title = 'Notice',
  message = '',
  buttonText = 'OK',
  type = 'info'
}) {
  return new Promise((resolve) => {
    const backdrop = document.createElement('div');
    backdrop.className = 'pp-modal-backdrop';

    const dialog = document.createElement('div');
    dialog.className = 'pp-modal-dialog pp-alert-modal-dialog';
    dialog.style.maxWidth = '420px';

    const iconBg = type === 'error' ? '#fef2f2' : (type === 'success' ? '#ecfdf5' : '#eff6ff');
    const iconColor = type === 'error' ? '#ef4444' : (type === 'success' ? '#10b981' : '#2563eb');
    const iconSvg = type === 'error'
      ? `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`
      : (type === 'success'
          ? `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>`
          : `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`
        );

    dialog.innerHTML = `
      <div style="padding: 28px 24px 20px 24px; text-align: center;">
        <div style="width: 54px; height: 54px; border-radius: 50%; background: ${iconBg}; color: ${iconColor}; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 4px 14px rgba(0,0,0,0.05);">
          ${iconSvg}
        </div>
        <h3 style="margin: 0 0 8px 0; font-family: var(--pp-font-heading); font-size: 20px; font-weight: 800; color: var(--pp-text-heading);">${title}</h3>
        <p style="margin: 0; color: var(--pp-text-muted); font-size: 14px; line-height: 1.5;">${message}</p>
      </div>
      <div class="pp-modal-footer" style="padding: 16px 24px; justify-content: center; border-top: 1px solid var(--pp-border-light);">
        <button type="button" class="pp-btn pp-btn-primary" id="pp-alert-ok-btn" style="min-width: 140px; justify-content: center;">${buttonText}</button>
      </div>
    `;

    backdrop.appendChild(dialog);
    document.body.appendChild(backdrop);

    const closeAndResolve = () => {
      backdrop.style.opacity = '0';
      dialog.style.transform = 'scale(0.95)';
      setTimeout(() => {
        if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
        resolve();
      }, 150);
    };

    dialog.querySelector('#pp-alert-ok-btn').addEventListener('click', closeAndResolve);
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) closeAndResolve();
    });

    window.addEventListener('keydown', function escHandler(e) {
      if (e.key === 'Escape' && backdrop.parentNode) {
        closeAndResolve();
        window.removeEventListener('keydown', escHandler);
      }
    });
  });
}

// Make confirm and alert modals globally available for convenience
window.PixelOnWP_Confirm = showConfirmModal;
window.PixelOnWP_Alert = showAlertModal;
