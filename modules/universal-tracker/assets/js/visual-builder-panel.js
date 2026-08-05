/**
 * Live Visual Event Setup Panel (Tracker Pro Visual Panel) JS
 */
(function () {
    if (typeof window.PixelOnWPVisualBuilderData === 'undefined') {
        return;
    }

    let selectionMode = false;
    let hoveredEl = null;

    // Create shadow DOM container for isolated stylesheet
    const host = document.createElement('div');
    host.id = 'pixelonwp-visual-builder-panel-root';
    document.body.appendChild(host);

    const shadow = host.attachShadow({ mode: 'open' });

    // Stylesheet rules scoped entirely inside the Shadow DOM
    const style = document.createElement('style');
    style.textContent = `
        .panel-wrapper {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 360px;
            background: #1e293b;
            color: #f8fafc;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            font-family: system-ui, -apple-system, sans-serif;
            z-index: 100000;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid #334155;
        }
        .panel-header {
            padding: 16px;
            background: #0f172a;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
        }
        .panel-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .btn-select {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-align: center;
        }
        .input-field {
            width: 100%;
            background: #334155;
            border: 1px solid #475569;
            color: #fff;
            padding: 8px;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-save {
            background: #10b981;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-align: center;
        }
    `;
    shadow.appendChild(style);

    const wrapper = document.createElement('div');
    wrapper.className = 'panel-wrapper';
    wrapper.innerHTML = `
        <div class="panel-header">
            <span>Visual Event Setup</span>
        </div>
        <div class="panel-body">
            <button class="btn-select" id="btn-picker">+ Visual Target Selector</button>
            <input type="text" id="target-selector" class="input-field" placeholder="CSS Selector" readonly>
            <input type="text" id="rule-name" class="input-field" placeholder="Rule Name (e.g., Shop Button Click)">
            <input type="text" id="event-name" class="input-field" placeholder="GA4/Meta Event Name (e.g., shopBtn)">
            <button class="btn-save" id="btn-save-rule">Save Rule</button>
        </div>
    `;
    shadow.appendChild(wrapper);

    // Click handler for picker mode toggles
    const btnPicker = wrapper.querySelector('#btn-picker');
    const selectorInput = wrapper.querySelector('#target-selector');

    btnPicker.addEventListener('click', (e) => {
        e.preventDefault();
        selectionMode = !selectionMode;
        if (selectionMode) {
            btnPicker.textContent = '[Click Target Element on Page]';
            btnPicker.style.background = '#e11d48';
        } else {
            btnPicker.textContent = '+ Visual Target Selector';
            btnPicker.style.background = '#2563eb';
        }
    });

    // Prevent default actions, clicks, and navigations during Element Picker Mode
    ['mousedown', 'mouseup', 'click'].forEach(evt => {
        document.addEventListener(evt, (e) => {
            if (!selectionMode) return;
            const target = e.composedPath ? e.composedPath()[0] : e.target;
            if (host.contains(target) || target === host) return;

            e.preventDefault();
            e.stopPropagation();

            if (evt === 'click') {
                const selector = generateSelector(target);
                selectorInput.value = selector;
                selectionMode = false;
                btnPicker.textContent = '+ Visual Target Selector';
                btnPicker.style.background = '#2563eb';
                if (hoveredEl) {
                    hoveredEl.style.outline = '';
                    hoveredEl = null;
                }
            }
        }, true);
    });

    // Element hover state outline highlight
    document.addEventListener('mouseover', (e) => {
        if (!selectionMode) return;
        const target = e.composedPath ? e.composedPath()[0] : e.target;
        if (host.contains(target) || target === host) return;

        if (hoveredEl) {
            hoveredEl.style.outline = '';
        }
        hoveredEl = target;
        hoveredEl.style.outline = '2px solid #2563EB';
    }, true);

    document.addEventListener('mouseout', (e) => {
        if (hoveredEl) {
            hoveredEl.style.outline = '';
            hoveredEl = null;
        }
    }, true);

    // Unique selector generator helper prioritizing id, class and parent chains
    function generateSelector(el) {
        if (el.id) {
            return '#' + el.id;
        }
        const dataAttr = Array.from(el.attributes).find(a => a.name.startsWith('data-'));
        if (dataAttr) {
            return `[${dataAttr.name}="${dataAttr.value}"]`;
        }
        const path = [];
        while (el && el.nodeType === Node.ELEMENT_NODE) {
            let selector = el.nodeName.toLowerCase();
            if (el.className) {
                const classes = Array.from(el.classList).filter(c => !c.includes('pp-'));
                if (classes.length > 0) {
                    selector += '.' + classes.join('.');
                }
            }
            path.unshift(selector);
            el = el.parentNode;
        }
        return path.join(' > ');
    }

    // Save configuration Rule call
    wrapper.querySelector('#btn-save-rule').addEventListener('click', () => {
        const ruleName = wrapper.querySelector('#rule-name').value.trim();
        const selector = selectorInput.value.trim();
        const eventName = wrapper.querySelector('#event-name').value.trim();

        if (!ruleName || !selector || !eventName) {
            alert('Please fill out all fields.');
            return;
        }

        jQuery.post(window.PixelOnWPVisualBuilderData.ajax_url, {
            action: 'pixelonwp_save_visual_tracker_rule',
            nonce: window.PixelOnWPVisualBuilderData.nonce,
            rule_name: ruleName,
            selector: selector,
            event_name: eventName,
            destinations: ['facebook', 'tiktok', 'ga4'] // Multi-destination bypass
        }, function (res) {
            if (res.success) {
                alert('Visual setup rule saved successfully.');
                wrapper.querySelector('#rule-name').value = '';
                selectorInput.value = '';
                wrapper.querySelector('#event-name').value = '';
            } else {
                alert('Error: ' + res.data.message);
            }
        });
    });
})();
