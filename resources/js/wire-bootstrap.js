/**
 * wire-bootstrap browser layer.
 *
 * Nothing here but Alpine component registrations (the package rule: Bootstrap
 * data-bs-* attributes + Alpine only — no jQuery, no loose DOM scripts).
 * Livewire bundles Alpine, so we register on alpine:init.
 */

const THEME_KEY = 'wb-theme';

const readStoredTheme = () => {
    try {
        return window.localStorage.getItem(THEME_KEY);
    } catch {
        return null;
    }
};

const storeTheme = (theme) => {
    try {
        window.localStorage.setItem(THEME_KEY, theme);
    } catch {
        // Storage may be unavailable (private mode); the toggle still works for the page.
    }
};

const applyTheme = (theme) => {
    const resolved =
        theme === 'auto'
            ? window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'dark'
                : 'light'
            : theme;

    document.documentElement.setAttribute('data-bs-theme', resolved);
};

/**
 * flatpickr parses string options (minDate, enable, ...) with the DISPLAY
 * dateFormat, so server-normalised Y-m-d strings must become Date objects.
 */
const toDate = (value) => {
    if (typeof value !== 'string' || ! /^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return value;
    }

    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
};

document.addEventListener('alpine:init', () => {
    /**
     * Date field. Mounts flatpickr on $refs.input and keeps Livewire in sync
     * by re-dispatching an input event after every change, so wire:model works
     * unchanged. Runtime option updates arrive via the Livewire-dispatched
     * `wire-bootstrap:date-options` event ({ model, enable, disable }).
     */
    window.Alpine.data('wbDatepicker', (config) => ({
        picker: null,

        init() {
            if (typeof window.flatpickr !== 'function') {
                console.warn('[wire-bootstrap] flatpickr is not loaded; the date field falls back to a plain input.');

                return;
            }

            const options = { ...config.options };

            ['minDate', 'maxDate', 'defaultDate'].forEach((key) => {
                if (options[key] !== undefined) {
                    options[key] = toDate(options[key]);
                }
            });

            if (config.disableAll) {
                options.disable = [() => true];
            } else {
                if (config.enable.length > 0) {
                    options.enable = config.enable.map(toDate);
                }

                if (config.disable.length > 0) {
                    options.disable = config.disable.map(toDate);
                }
            }

            options.onChange = () => {
                this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
                this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
            };

            this.picker = window.flatpickr(this.$refs.input, options);

            if (typeof this.$wire !== 'undefined') {
                this.$wire.on('wire-bootstrap:date-options', (event) => {
                    const payload = Array.isArray(event) ? event[0] : event;

                    if (! payload || payload.model !== config.model) {
                        return;
                    }

                    this.updateDates(payload.enable ?? [], payload.disable ?? []);
                });
            }
        },

        updateDates(enable, disable) {
            if (! this.picker) {
                return;
            }

            if (enable.length === 1 && enable[0] === 'disable_all') {
                this.picker.set('enable', []);
                this.picker.set('disable', [() => true]);
            } else if (enable.length > 0) {
                this.picker.set('disable', disable.length > 0 ? disable.map(toDate) : []);
                this.picker.set('enable', enable.map(toDate));
            } else {
                this.picker.set('enable', []);
                this.picker.set('disable', disable.length > 0 ? disable.map(toDate) : [() => true]);
            }

            this.picker.redraw();
            this.picker.clear();
        },

        destroy() {
            this.picker?.destroy();
        },
    }));

    /**
     * Rich text editor (Quill). The wire:model binding lives on a hidden
     * input; every edit writes the HTML there and re-dispatches an input
     * event, so Livewire sees an ordinary field.
     */
    window.Alpine.data('wbEditor', (config) => ({
        quill: null,

        init() {
            if (typeof window.Quill !== 'function') {
                console.warn('[wire-bootstrap] Quill is not loaded; the editor field falls back to a plain textarea.');
                this.$refs.fallback?.classList.remove('d-none');

                return;
            }

            this.$refs.editor.classList.remove('d-none');

            this.quill = new window.Quill(this.$refs.editor, {
                theme: 'snow',
                placeholder: config.placeholder ?? '',
                readOnly: Boolean(config.readOnly),
                modules: { toolbar: config.toolbar },
            });

            const initial = this.$refs.input.value;

            if (initial) {
                this.quill.clipboard.dangerouslyPasteHTML(initial);
            }

            this.quill.on('text-change', (delta, oldDelta, source) => {
                if (source !== 'user') {
                    return;
                }

                this.$refs.input.value = this.quill.getLength() <= 1 ? '' : this.quill.root.innerHTML;
                this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        },

        destroy() {
            this.quill = null;
        },
    }));

    /**
     * Tabs. Pure Alpine state driving Bootstrap's nav-tabs markup; optionally
     * resets to the default tab when the surrounding wire-modals modal reopens.
     */
    window.Alpine.data('wbTabs', (config) => ({
        activeTab: config.default ?? null,
        modalId: null,

        init() {
            this.modalId = this.$el.closest('.modal')?.id ?? null;
        },

        select(key) {
            this.activeTab = key;
        },

        isActive(key) {
            return this.activeTab === key;
        },

        onModalOpened(event) {
            if (config.resetOnModalOpen && this.modalId && event.detail?.modal === this.modalId) {
                this.activeTab = config.default ?? null;
            }
        },
    }));

    /**
     * Grouped code field (input.code). One box per group; `value` is the
     * x-modelable value wire:model binds to: the plain characters, or the
     * separator-joined groups when config.formatted is on. Typing past the end
     * of a box carries over into the next one, Backspace in an empty box goes
     * back, arrows move between boxes and a paste fills every box.
     */
    window.Alpine.data('wbCode', (config) => ({
        value: '',
        emitted: null,

        init() {
            this.fill(this.value);

            this.$watch('value', (value) => {
                if (value !== this.emitted) {
                    this.fill(value);
                }
            });
        },

        box(index) {
            return this.$refs['group' + index] ?? null;
        },

        total() {
            return config.groups.reduce((sum, length) => sum + length, 0);
        },

        clean(text) {
            const value = String(text ?? '');

            return config.alphanumeric
                ? value.toUpperCase().replace(/[^A-Z0-9]/g, '')
                : value.replace(/\D/g, '');
        },

        /** Spread a value (plain or separated) over the boxes. */
        fill(value) {
            const chars = this.clean(value).slice(0, this.total());
            let offset = 0;

            config.groups.forEach((length, index) => {
                const box = this.box(index);

                if (box) {
                    box.value = chars.slice(offset, offset + length);
                }

                offset += length;
            });
        },

        emit() {
            const parts = config.groups.map((length, index) => this.clean(this.box(index)?.value).slice(0, length));
            let result = parts.join('');

            if (config.formatted) {
                let last = parts.length - 1;

                while (last >= 0 && parts[last] === '') {
                    last--;
                }

                result = parts.slice(0, last + 1).join(config.separator);
            }

            this.emitted = result;
            this.value = result;
        },

        focusBox(index, caret = null) {
            const box = this.box(index);

            if (! box) {
                return;
            }

            box.focus();

            if (caret !== null) {
                const position = caret === 'end' ? box.value.length : Math.min(caret, box.value.length);

                box.setSelectionRange(position, position);
            }
        },

        typed(index, event) {
            const box = event.target;
            const length = config.groups[index];
            const chars = this.clean(box.value);
            const typedAtEnd = box.selectionStart === null || box.selectionStart >= box.value.length;

            box.value = chars.slice(0, length);

            // Carry anything past the end of this box into the following boxes.
            let carry = chars.slice(length);
            let next = index + 1;
            let landed = null;

            while (carry !== '' && next < config.groups.length) {
                const target = this.box(next);
                const merged = carry + this.clean(target.value);

                target.value = merged.slice(0, config.groups[next]);
                landed = landed ?? { index: next, caret: Math.min(carry.length, config.groups[next]) };
                carry = merged.slice(config.groups[next]);
                next++;
            }

            if (landed) {
                this.focusBox(landed.index, landed.caret);
            } else if (chars.length >= length && typedAtEnd && index < config.groups.length - 1) {
                this.focusBox(index + 1);
            }

            this.emit();
        },

        pasted(index, event) {
            const chars = this.clean(event.clipboardData?.getData('text') ?? '');

            if (chars === '') {
                return;
            }

            // A whole code always starts at the first box; a part fills from here on.
            let current = chars.length >= this.total() ? 0 : index;
            let rest = chars;

            while (true) {
                this.box(current).value = rest.slice(0, config.groups[current]);
                rest = rest.slice(config.groups[current]);

                if (rest === '' || current === config.groups.length - 1) {
                    break;
                }

                current++;
            }

            const full = this.box(current).value.length >= config.groups[current];

            this.focusBox(full && current < config.groups.length - 1 ? current + 1 : current, 'end');
            this.emit();
        },

        keydown(index, event) {
            const box = event.target;
            const atStart = box.selectionStart === 0 && box.selectionEnd === 0;
            const atEnd = box.selectionStart === box.value.length && box.selectionEnd === box.value.length;

            if (event.key === 'Backspace' && index > 0 && atStart) {
                event.preventDefault();

                const previous = this.box(index - 1);

                previous.value = previous.value.slice(0, -1);
                this.focusBox(index - 1, 'end');
                this.emit();
            } else if (event.key === 'ArrowLeft' && index > 0 && atStart) {
                event.preventDefault();
                this.focusBox(index - 1, 'end');
            } else if (event.key === 'ArrowRight' && index < config.groups.length - 1 && atEnd) {
                event.preventDefault();
                this.focusBox(index + 1, 0);
            }
        },
    }));

    /**
     * PIN pad. `value` is the x-modelable digits. Submitting (OK, Enter, or
     * reaching config.max with autoSubmit) dispatches `wb-pin-submit` and, when
     * config.submit names a Livewire method, calls it once the bound value has
     * reached Livewire. Keys are ignored while disabled or a submit runs.
     */
    window.Alpine.data('wbPinPad', (config) => ({
        value: '',
        busy: false,

        init() {
            this.$watch('value', (value) => {
                const digits = String(value ?? '').replace(/\D/g, '').slice(0, config.max);

                if (value !== null && value !== digits) {
                    this.value = digits;
                }
            });

            if (config.autofocus && ! config.disabled) {
                this.$nextTick(() => this.$root.focus());
            }
        },

        digits() {
            return String(this.value ?? '');
        },

        length() {
            return this.digits().length;
        },

        shown() {
            return Math.min(config.max, Math.max(config.min, this.length()));
        },

        status() {
            return config.status.replace(':count', this.length()).replace(':max', config.max);
        },

        locked() {
            return config.disabled || this.busy;
        },

        press(digit) {
            if (this.locked() || this.length() >= config.max) {
                return;
            }

            this.value = this.digits() + String(digit);

            if (config.autoSubmit && this.length() === config.max) {
                this.done();
            }
        },

        erase() {
            if (! this.locked()) {
                this.value = this.digits().slice(0, -1);
            }
        },

        clear() {
            if (! this.locked()) {
                this.value = '';
            }
        },

        done() {
            if (this.locked() || this.length() < config.min) {
                return;
            }

            const value = this.digits();

            // Wait a tick so x-modelable has handed the value to wire:model first.
            this.$nextTick(() => {
                this.$dispatch('wb-pin-submit', { value });

                if (config.submit && typeof this.$wire !== 'undefined') {
                    this.busy = true;

                    Promise.resolve(this.$wire.$call(config.submit)).finally(() => {
                        this.busy = false;
                    });
                }
            });
        },

        keydown(event) {
            if (config.disabled || event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }

            const target = event.target;
            const insidePad = this.$root.contains(target);
            const typing = target instanceof HTMLElement
                && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));

            // With global keys: leave other fields alone and stay quiet while hidden (e.g. a closed modal).
            if ((typing && ! insidePad) || this.$root.getClientRects().length === 0) {
                return;
            }

            // Enter / Space on a focused key button press that button natively.
            if ((event.key === 'Enter' || event.key === ' ') && target instanceof HTMLButtonElement && insidePad) {
                return;
            }

            if (/^\d$/.test(event.key)) {
                event.preventDefault();
                this.press(event.key);
            } else if (event.key === 'Backspace' || event.key === 'Delete') {
                event.preventDefault();
                this.erase();
            } else if (event.key === 'Escape' && this.length() > 0) {
                event.preventDefault();
                this.clear();
            } else if (event.key === 'Enter') {
                event.preventDefault();
                this.done();
            }
        },
    }));

    /**
     * Theme toggle. Cycles light -> dark (-> auto when enabled), persists the
     * choice and applies data-bs-theme on <html>. The layout's pre-paint
     * snippet applies the stored theme before first paint.
     */
    window.Alpine.data('wbThemeToggle', (config) => ({
        theme: 'light',

        init() {
            this.theme = readStoredTheme() ?? config.default ?? 'light';
            applyTheme(this.theme);
        },

        toggle() {
            const order = config.withAuto ? ['light', 'dark', 'auto'] : ['light', 'dark'];
            const next = order[(order.indexOf(this.theme) + 1) % order.length];

            this.theme = next;
            storeTheme(next);
            applyTheme(next);
        },

        isDark() {
            return this.theme === 'dark'
                || (this.theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        },
    }));
});
