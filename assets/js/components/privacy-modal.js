export async function openPrivacyModal() {
  // Create modal overlay
  const overlay = document.createElement('div');
  overlay.className = 'pp-modal-overlay';
  overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(2px);';

  // Create modal container
  const modal = document.createElement('div');
  modal.className = 'pp-modal-content pp-card';
  modal.style.cssText = 'background: #fff; width: 600px; max-width: 90%; border-radius: 12px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); display: flex; flex-direction: column; max-height: 85vh;';

  // Header
  const header = document.createElement('div');
  header.style.cssText = 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;';
  header.innerHTML = '<h2 style="margin: 0; font-size: 18px; color: var(--pp-text-main);">📋 Dynamic Privacy Policy</h2>';
  
  const closeBtn = document.createElement('button');
  closeBtn.innerHTML = '&times;';
  closeBtn.style.cssText = 'background: transparent; border: none; font-size: 24px; cursor: pointer; color: var(--pp-text-muted); line-height: 1;';
  closeBtn.onclick = () => overlay.remove();
  header.appendChild(closeBtn);

  // Instruction
  const instruction = document.createElement('p');
  instruction.style.cssText = 'font-size: 14px; color: var(--pp-text-muted); margin-top: 0; margin-bottom: 16px;';
  instruction.innerText = 'Copy and paste this dynamic Privacy Policy clause onto your website\'s Privacy Policy page. It has been generated based on the active modules in PixelOnWP.';

  // Textarea container
  const body = document.createElement('div');
  body.style.cssText = 'flex: 1; overflow-y: auto; margin-bottom: 20px;';
  
  const textarea = document.createElement('textarea');
  textarea.className = 'pp-input';
  textarea.style.cssText = 'width: 100%; height: 250px; resize: vertical; font-family: monospace; font-size: 13px; line-height: 1.5; padding: 12px; border: 1px solid var(--pp-border); border-radius: 6px; box-sizing: border-box;';
  textarea.value = 'Generating policy based on active features...';
  textarea.readOnly = true;

  body.appendChild(textarea);

  // Footer / Actions
  const footer = document.createElement('div');
  footer.style.cssText = 'display: flex; justify-content: flex-end; gap: 12px;';

  const copyBtn = document.createElement('button');
  copyBtn.className = 'pp-btn';
  copyBtn.innerHTML = '📋 Copy Policy to Clipboard';
  copyBtn.disabled = true;

  copyBtn.onclick = async () => {
    try {
      await navigator.clipboard.writeText(textarea.value);
      const originalText = copyBtn.innerHTML;
      copyBtn.innerHTML = '✓ Copied!';
      copyBtn.style.background = 'var(--pp-success)';
      setTimeout(() => {
        copyBtn.innerHTML = originalText;
        copyBtn.style.background = 'var(--pp-primary)';
      }, 2000);
    } catch (err) {
      alert('Failed to copy text. Please select and copy manually.');
    }
  };

  const cancelBtn = document.createElement('button');
  cancelBtn.className = 'pp-btn-outline';
  cancelBtn.innerText = 'Close';
  cancelBtn.onclick = () => overlay.remove();

  footer.appendChild(cancelBtn);
  footer.appendChild(copyBtn);

  modal.appendChild(header);
  modal.appendChild(instruction);
  modal.appendChild(body);
  modal.appendChild(footer);
  overlay.appendChild(modal);
  document.body.appendChild(overlay);

  // Fetch dynamic policy
  const formData = new FormData();
  formData.append('action', 'PixelOnWP_generate_privacy_policy');
  formData.append('nonce', window.pixelonwp_admin_vars.nonce);

  try {
    const res = await fetch(window.pixelonwp_admin_vars.ajaxurl, {
      method: 'POST',
      body: formData
    });
    const result = await res.json();
    if (result.success && result.data && result.data.policy) {
      textarea.value = result.data.policy;
      copyBtn.disabled = false;
    } else {
      textarea.value = 'Failed to generate policy: ' + (result.data && result.data.message ? result.data.message : 'Please try again.');
    }
  } catch (error) {
    textarea.value = 'Network error while generating policy.';
  }
}
