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
