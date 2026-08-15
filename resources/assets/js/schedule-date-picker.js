(function (window, $) {
    if (!window || !window.document || !$ || !$.fn || !$.fn.datepicker) {
        return;
    }

    const FIELD_SELECTOR = '.js-schedule-date-field';
    const DISPLAY_SELECTOR = '[data-date-display]';
    const NATIVE_SELECTOR = '[data-date-native]';
    const BUTTON_SELECTOR = '[data-date-picker]';
    const STYLE_ID = 'schedule-date-picker-runtime-style';

    const pad = (value) => String(value).padStart(2, '0');

    const isValidDate = (date) => date instanceof Date && !Number.isNaN(date.getTime());

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const injectRuntimeStyles = () => {
        if (document.getElementById(STYLE_ID)) {
            return;
        }

        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            .schedule-date-field .schedule-date-display {
                background: #fff !important;
                color: #1f2937 !important;
            }

            .datepicker.dropdown-menu,
            .datepicker.datepicker-dropdown {
                background: #fff !important;
                border: 1px solid #d9e3ef !important;
                border-radius: 14px !important;
                box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18) !important;
                color: #1f2937 !important;
                padding: 12px !important;
                z-index: 3000 !important;
            }

            .datepicker.dropdown-menu table,
            .datepicker.datepicker-dropdown table {
                width: 100%;
                margin: 0;
            }

            .datepicker.dropdown-menu table thead th,
            .datepicker.datepicker-dropdown table thead th {
                color: #64748b !important;
                font-weight: 700;
                border: 0 !important;
                background: transparent !important;
            }

            .datepicker.dropdown-menu table tbody td,
            .datepicker.datepicker-dropdown table tbody td {
                border: 0 !important;
                border-radius: 10px !important;
                color: #0f172a !important;
            }

            .datepicker.dropdown-menu table tbody td.day:hover,
            .datepicker.datepicker-dropdown table tbody td.day:hover,
            .datepicker.dropdown-menu table tbody td.active,
            .datepicker.datepicker-dropdown table tbody td.active,
            .datepicker.dropdown-menu table tbody td.active:hover,
            .datepicker.datepicker-dropdown table tbody td.active:hover {
                background: #1d4ed8 !important;
                color: #fff !important;
            }

            .datepicker.dropdown-menu table tbody td.disabled,
            .datepicker.datepicker-dropdown table tbody td.disabled {
                color: #cbd5e1 !important;
            }

            .datepicker.dropdown-menu .datepicker-switch,
            .datepicker.datepicker-dropdown .datepicker-switch,
            .datepicker.dropdown-menu .prev,
            .datepicker.datepicker-dropdown .prev,
            .datepicker.dropdown-menu .next,
            .datepicker.datepicker-dropdown .next {
                color: #0f172a !important;
                font-weight: 700;
                background: transparent !important;
            }

            .datepicker.dropdown-menu .datepicker-switch:hover,
            .datepicker.datepicker-dropdown .datepicker-switch:hover,
            .datepicker.dropdown-menu .prev:hover,
            .datepicker.datepicker-dropdown .prev:hover,
            .datepicker.dropdown-menu .next:hover,
            .datepicker.datepicker-dropdown .next:hover {
                background: #eff6ff !important;
                color: #1d4ed8 !important;
            }
        `;
        document.head.appendChild(style);
    };

    const parseIsoDate = (value) => {
        if (!value) return null;
        const date = new Date(`${value}T00:00:00`);
        return isValidDate(date) ? date : null;
    };

    const parseDisplayDate = (value) => {
        if (!value) return null;
        const parts = String(value).split('/');
        if (parts.length !== 3) return null;

        const day = Number(parts[0]);
        const month = Number(parts[1]);
        const year = Number(parts[2].length === 2 ? `20${parts[2]}` : parts[2]);
        if (!day || !month || !year) return null;

        const date = new Date(year, month - 1, day);
        if (!isValidDate(date)) return null;
        if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
            return null;
        }

        return date;
    };

    const formatDisplay = (value) => {
        const date = parseIsoDate(value) || parseDisplayDate(value);
        if (!date) return '';
        return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}`;
    };

    const formatIso = (date) => {
        if (!isValidDate(date)) return '';
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    };

    const resolveBoundValue = (wrapper, sourceName, fallbackAttr) => {
        if (!sourceName) {
            return wrapper.getAttribute(fallbackAttr) || '';
        }

        const form = wrapper.closest('form');
        const source = form?.querySelector(`input[name="${sourceName}"]`) || document.querySelector(`input[name="${sourceName}"]`);
        if (!source) {
            return wrapper.getAttribute(fallbackAttr) || '';
        }

        return source.value || '';
    };

    const parseBoundDate = (value) => {
        if (!value) return null;
        if (value instanceof Date) return isValidDate(value) ? value : null;
        const parsed = parseIsoDate(value) || parseDisplayDate(value);
        if (!parsed) return null;

        return new Date(Date.UTC(parsed.getFullYear(), parsed.getMonth(), parsed.getDate()));
    };

    const applyPicker = (wrapper) => {
        const displayInput = wrapper.querySelector(DISPLAY_SELECTOR);
        const hiddenInput = wrapper.querySelector(NATIVE_SELECTOR);
        if (!displayInput || !hiddenInput) return;
        if ($(displayInput).data('datepicker')) return;
        const initialDisplayValue = displayInput.value || displayInput.getAttribute('value') || '';

        const syncFromDisplay = () => {
            const sourceValue = displayInput.value || initialDisplayValue;
            const parsed = parseDisplayDate(sourceValue);
            const previousValue = hiddenInput.value;
            hiddenInput.value = parsed ? formatIso(parsed) : '';
            if (hiddenInput.value !== previousValue) {
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (parsed && !displayInput.value) {
                displayInput.value = formatDisplay(formatIso(parsed));
            }
            displayInput.setCustomValidity(hiddenInput.value ? '' : 'Vui lòng chọn ngày.');
        };

        const minValue = parseBoundDate(resolveBoundValue(wrapper, wrapper.dataset.minSource || '', 'data-min'));
        const maxValue = parseBoundDate(resolveBoundValue(wrapper, wrapper.dataset.maxSource || '', 'data-max'));
        const options = {
            autoclose: true,
            clearBtn: false,
            container: 'body',
            format: 'dd/mm/yyyy',
            language: 'vi',
            todayHighlight: true,
            orientation: 'bottom auto',
        };

        if (minValue) {
            options.startDate = minValue;
        }

        if (maxValue) {
            options.endDate = maxValue;
        }

        $(displayInput)
            .datepicker(options)
            .on('changeDate clearDate change input', syncFromDisplay);

        wrapper.querySelector(BUTTON_SELECTOR)?.addEventListener('click', () => {
            $(displayInput).datepicker('show');
        });

        displayInput.addEventListener('click', () => {
            $(displayInput).datepicker('show');
        });

        if (hiddenInput.value) {
            displayInput.value = formatDisplay(hiddenInput.value);
            $(displayInput).datepicker('setDate', parseIsoDate(hiddenInput.value));
        } else if (initialDisplayValue) {
            displayInput.value = initialDisplayValue;
            syncFromDisplay();
            const parsed = parseDisplayDate(initialDisplayValue);
            if (parsed) {
                $(displayInput).datepicker('setDate', parsed);
            }
        }

        syncFromDisplay();
        setTimeout(syncFromDisplay, 0);
        wrapper.dataset.datepickerReady = '1';
    };

    const getFieldWrapper = (fieldName, root = document) => {
        if (!fieldName || !root.querySelector) {
            return null;
        }

        return root.querySelector(`${FIELD_SELECTOR}[data-field="${fieldName}"]`);
    };

    const setValue = (fieldName, value, root = document) => {
        const wrapper = getFieldWrapper(fieldName, root);
        if (!wrapper) return false;

        const displayInput = wrapper.querySelector(DISPLAY_SELECTOR);
        const hiddenInput = wrapper.querySelector(NATIVE_SELECTOR);
        if (!displayInput || !hiddenInput) return false;

        const parsed = parseIsoDate(value) || parseDisplayDate(value);
        const isoValue = parsed ? formatIso(parsed) : '';
        const displayValue = parsed ? formatDisplay(isoValue) : '';

        displayInput.value = displayValue;
        hiddenInput.value = isoValue;
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        displayInput.dispatchEvent(new Event('change', { bubbles: true }));

        if ($(displayInput).data('datepicker')) {
            $(displayInput).datepicker('setDate', parsed);
        }

        return true;
    };

    const refreshBounds = (root = document) => {
        root.querySelectorAll(FIELD_SELECTOR).forEach((wrapper) => {
            const displayInput = wrapper.querySelector(DISPLAY_SELECTOR);
            if (!displayInput || !$(displayInput).data('datepicker')) return;

            const minValue = parseBoundDate(resolveBoundValue(wrapper, wrapper.dataset.minSource || '', 'data-min'));
            const maxValue = parseBoundDate(resolveBoundValue(wrapper, wrapper.dataset.maxSource || '', 'data-max'));

            $(displayInput).datepicker('setStartDate', minValue || null);
            $(displayInput).datepicker('setEndDate', maxValue || null);
        });
    };

    const init = (root = document) => {
        if (!root.querySelectorAll) return;
        injectRuntimeStyles();
        root.querySelectorAll(FIELD_SELECTOR).forEach((wrapper) => applyPicker(wrapper));
        refreshBounds(root);
    };

    const fieldHtml = (options = {}) => {
        const label = options.label || '';
        const name = options.name || '';
        const fieldName = options.fieldName || name;
        const value = formatDisplay(options.value || '');
        const nativeValue = options.nativeValue || options.value || '';
        const placeholder = options.placeholder || 'DD/MM/YYYY';
        const buttonLabel = options.buttonLabel || 'Lich';
        const help = options.help || '';
        const wrapperClass = options.wrapperClass || '';
        const inputClass = options.inputClass || '';
        const displayId = options.displayId || '';
        const nativeId = options.nativeId || '';
        const formAttr = options.form ? ` form="${escapeHtml(options.form)}"` : '';
        const required = options.required ? ' required' : '';
        const min = options.min ? ` data-min="${escapeHtml(options.min)}"` : '';
        const max = options.max ? ` data-max="${escapeHtml(options.max)}"` : '';
        const minSource = options.minSource ? ` data-min-source="${escapeHtml(options.minSource)}"` : '';
        const maxSource = options.maxSource ? ` data-max-source="${escapeHtml(options.maxSource)}"` : '';

        return `
            <div class="schedule-date-field js-schedule-date-field ${escapeHtml(wrapperClass)}"
                data-field="${escapeHtml(fieldName)}"${min}${max}${minSource}${maxSource}>
                ${label ? `<label class="form-label">${escapeHtml(label)}</label>` : ''}
                <div class="schedule-date-input-group input-group">
                    <input type="text" class="form-control schedule-date-display js-schedule-date-display ${escapeHtml(inputClass)}"
                        data-date-display="${escapeHtml(fieldName)}"${displayId ? ` id="${escapeHtml(displayId)}"` : ''} placeholder="${escapeHtml(placeholder)}" value="${escapeHtml(value)}"${options.readonly ? ' readonly' : ''}${formAttr}${required}>
                    <input type="hidden" name="${escapeHtml(name)}" data-date-native="${escapeHtml(fieldName)}"
                        ${nativeId ? `id="${escapeHtml(nativeId)}"` : ''} value="${escapeHtml(nativeValue)}"${formAttr}>
                    <button type="button" class="btn btn-outline-secondary schedule-date-toggle"
                        data-date-picker="${escapeHtml(fieldName)}">${escapeHtml(buttonLabel)}</button>
                </div>
                ${help ? `<small class="text-muted">${escapeHtml(help)}</small>` : ''}
            </div>
        `;
    };

    window.ScheduleDatePicker = {
        fieldHtml,
        formatDisplay,
        formatIso,
        init,
        setValue,
        refreshBounds,
    };

    document.addEventListener('DOMContentLoaded', () => init(document));
})(window, window.jQuery);
