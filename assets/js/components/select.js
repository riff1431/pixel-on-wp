/**
 * PixelOnWP Apple Liquid Glass Custom Select Dropdown Component & Auto-Enhancer
 */

export function createCustomSelect({ options = [], value = '', onChange = null, className = '', id = '' }) {
  const wrapper = document.createElement('div');
  wrapper.className = `pp-custom-select ${className}`.trim();
  if (id) wrapper.id = id;

  let currentValue = value !== undefined ? value : (options[0] ? options[0].value : '');
  let isOpen = false;

  const getOption = () => options.find(o => String(o.value) === String(currentValue)) || options[0];

  const trigger = document.createElement('button');
  trigger.type = 'button';
  trigger.className = 'pp-custom-select-trigger';
  trigger.setAttribute('aria-haspopup', 'listbox');

  const updateTrigger = () => {
    const opt = getOption();
    const label = opt ? opt.label : (options[0] ? options[0].label : '');
    const iconHtml = opt && opt.icon ? `<span class="pp-custom-select-icon">${opt.icon}</span>` : '';
    trigger.innerHTML = `
      <div class="pp-custom-select-trigger-content">
        ${iconHtml}
        <span class="pp-custom-select-label">${label}</span>
      </div>
      <svg class="pp-custom-select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    `;
  };
  updateTrigger();

  const menu = document.createElement('div');
  menu.className = 'pp-custom-select-menu';
  menu.setAttribute('role', 'listbox');

  const renderMenu = () => {
    menu.innerHTML = '';
    options.forEach(opt => {
      const item = document.createElement('div');
      const isActive = String(opt.value) === String(currentValue);
      item.className = `pp-custom-select-option ${isActive ? 'active' : ''}`;
      item.setAttribute('role', 'option');
      item.setAttribute('aria-selected', isActive ? 'true' : 'false');
      
      const iconHtml = opt.icon ? `<span class="pp-custom-select-option-icon">${opt.icon}</span>` : '';
      const badgeHtml = opt.badge ? `<span class="pp-custom-select-option-badge">${opt.badge}</span>` : '';
      
      item.innerHTML = `
        <div class="pp-custom-select-option-left">
          ${iconHtml}
          <span class="pp-custom-select-option-text">${opt.label}</span>
        </div>
        <div class="pp-custom-select-option-right">
          ${badgeHtml}
          <svg class="pp-custom-select-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
      `;

      item.addEventListener('click', (e) => {
        e.stopPropagation();
        currentValue = opt.value;
        updateTrigger();
        closeMenu();
        if (typeof onChange === 'function') onChange(currentValue);
      });

      menu.appendChild(item);
    });
  };

  const openMenu = () => {
    // Close any other open custom select menus
    document.querySelectorAll('.pp-custom-select-menu.open').forEach(m => {
      if (m !== menu) {
        m.classList.remove('open');
        m.parentElement?.querySelector('.pp-custom-select-trigger')?.classList.remove('active');
      }
    });

    renderMenu();

    // Check positioning near window bottom
    const rect = wrapper.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    if (spaceBelow < 220 && rect.top > 220) {
      menu.classList.add('drop-up');
    } else {
      menu.classList.remove('drop-up');
    }

    menu.classList.add('open');
    trigger.classList.add('active');
    trigger.setAttribute('aria-expanded', 'true');
    isOpen = true;
  };

  const closeMenu = () => {
    menu.classList.remove('open');
    trigger.classList.remove('active');
    trigger.setAttribute('aria-expanded', 'false');
    isOpen = false;
  };

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    if (isOpen) closeMenu();
    else openMenu();
  });

  // Keyboard navigation
  trigger.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      if (!isOpen) openMenu();
    } else if (e.key === 'Escape') {
      closeMenu();
    }
  });

  const onOutsideClick = (e) => {
    if (!wrapper.contains(e.target)) {
      closeMenu();
    }
  };

  window.addEventListener('click', onOutsideClick);

  wrapper.appendChild(trigger);
  wrapper.appendChild(menu);

  Object.defineProperty(wrapper, 'value', {
    get: () => currentValue,
    set: (val) => {
      currentValue = val;
      updateTrigger();
    }
  });

  return wrapper;
}

/**
 * Transforms a native <select> element into a custom liquid glass select component
 * while keeping 2-way value and event synchronization completely intact.
 */
export function enhanceSelectElement(selectEl) {
  if (!selectEl || selectEl.dataset.ppEnhanced === 'true') return;

  // Mark as enhanced
  selectEl.dataset.ppEnhanced = 'true';

  // Read options from native select
  const readOptions = () => {
    return Array.from(selectEl.options).map(opt => ({
      value: opt.value,
      label: opt.text || opt.value,
      disabled: opt.disabled
    }));
  };

  let options = readOptions();
  let currentValue = selectEl.value;

  // Create custom select wrapper
  const customSelectWrapper = createCustomSelect({
    options,
    value: currentValue,
    onChange: (newValue) => {
      if (selectEl.value !== newValue) {
        selectEl.value = newValue;
        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        selectEl.dispatchEvent(new Event('input', { bubbles: true }));
      }
    }
  });

  // Copy style layout attributes if specified (e.g. width, flex, max-width)
  const inlineWidth = selectEl.style.width;
  const inlineMaxWidth = selectEl.style.maxWidth;
  const inlineMinWidth = selectEl.style.minWidth;
  const inlineFlex = selectEl.style.flex;
  const inlineMargin = selectEl.style.margin;

  if (inlineWidth) customSelectWrapper.style.width = inlineWidth;
  else customSelectWrapper.style.width = '100%';

  if (inlineMaxWidth) customSelectWrapper.style.maxWidth = inlineMaxWidth;
  if (inlineMinWidth) customSelectWrapper.style.minWidth = inlineMinWidth;
  if (inlineFlex) customSelectWrapper.style.flex = inlineFlex;
  if (inlineMargin) customSelectWrapper.style.margin = inlineMargin;

  // Hide native select completely
  selectEl.style.display = 'none';
  selectEl.style.visibility = 'hidden';
  selectEl.style.position = 'absolute';
  selectEl.ariaHidden = 'true';
  selectEl.tabIndex = -1;

  // Sync external changes to native select back to custom select
  selectEl.addEventListener('change', () => {
    if (customSelectWrapper.value !== selectEl.value) {
      customSelectWrapper.value = selectEl.value;
    }
  });

  // Observe DOM changes in <select> options
  const observer = new MutationObserver(() => {
    const updatedOptions = readOptions();
    const newCustomSelect = createCustomSelect({
      options: updatedOptions,
      value: selectEl.value,
      onChange: (newValue) => {
        selectEl.value = newValue;
        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        selectEl.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });

    if (inlineWidth) newCustomSelect.style.width = inlineWidth;
    else newCustomSelect.style.width = '100%';
    if (inlineMaxWidth) newCustomSelect.style.maxWidth = inlineMaxWidth;
    if (inlineMinWidth) newCustomSelect.style.minWidth = inlineMinWidth;
    if (inlineFlex) newCustomSelect.style.flex = inlineFlex;
    if (inlineMargin) newCustomSelect.style.margin = inlineMargin;

    customSelectWrapper.replaceWith(newCustomSelect);
  });

  observer.observe(selectEl, { childList: true, subtree: true, characterData: true });

  // Insert custom wrapper right after native select
  if (selectEl.parentNode) {
    selectEl.parentNode.insertBefore(customSelectWrapper, selectEl.nextSibling);
  }
}

/**
 * Scans a container (or document) and enhances all native <select> elements
 */
export function enhanceAllSelects(container = document) {
  if (!container) return;
  const selects = container.querySelectorAll('select:not([data-pp-enhanced])');
  selects.forEach(selectEl => {
    enhanceSelectElement(selectEl);
  });
}
