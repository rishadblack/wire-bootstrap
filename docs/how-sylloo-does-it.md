# How the Sylloo Theme module does it today (behavioural reference)

The source being extracted is `C:\Office\sylloo-project\sylloo-erp\Modules\Theme` (a nwidart module inside
the Sylloo ERP, built on the purchased Vuexy Bootstrap 5 template). Read the files named here before
designing anything, but remember the rule from `INSTRUCTIONS.md`: the Theme code is the behavioural
reference, **not** code to copy. It is full of patterns the package must fix.

## What the module contains (three layers)

1. **Template layer — stays in the ERP, out of scope.**
   `resources/assets/` (the whole Vuexy asset tree: `libs/*` with ~60 vendored JS libraries, sass, fonts,
   images), `resources/views/layouts/backend.blade.php` and `developer.blade.php`, every menu component
   (`sidemenu`, `topmenu`, `reportmenu`, `dashboardmenu`, `profile-menu`, `setting-menu`,
   `customer-main-menu`, `developer-sidemenu` — all backed by classes in `app/View/Components/Backend/*`
   that read ERP permissions via `ucan()`), the PowerGrid view overrides under
   `resources/views/vendor/livewire-powergrid`, and the mail themes.

2. **Form & UI primitives — what wire-bootstrap generalises.**
   `resources/views/components/input/*` (~30 anonymous components: text, textarea, number, password, email,
   select, select2, select-tom, select_light, checkbox, checkbox2, radio, date, date-range, time, file,
   file-multiple, color-picker, range, editor, typeahead, text-copy, text-map, text-login, text_read,
   spacing, level, media, profile_file), the shared partials `input/common/label.blade.php` and
   `input/common/error-label.blade.php`, `backend/button.blade.php` plus seven variant files under
   `backend/button/` (danger, info, success, warning, save, print, language), `backend/card.blade.php`,
   `backend/badge.blade.php`, `backend/tab.blade.php` + `tab-button` + `tab-content`,
   `backend/container.blade.php`, `backend/text.blade.php`, `backend/modal.blade.php` (already replaced by
   wire-modals), `datatable/filters.blade.php`.

3. **ERP glue — stays in the ERP.**
   `item-input/*` (POS/invoice row inputs; `quantity.blade.php` wraps its input in `@feature('inventory')`),
   `input/media.blade.php` (opens the ERP media-manager modal via the dynamic-component system),
   `input/level.blade.php`, anything calling `lang()`, `ucan()`, `@feature()`.

## The conventions worth keeping (these are the package's inherited API ideas)

- **Everything hangs off `wire:model`.** Every input derives its `id`, `name`, datalist id and `@error` key
  from `$attributes->wire('model')->value` (`input/text.blade.php:6-9`). A field is one tag:
  `<x-theme::input.text label="Name" wire:model="name" />`. This is the single best idea in the module and
  the package keeps it.
- **Label + error are shared partials** included by every field (`input/common/label.blade.php`,
  `error-label.blade.php`): label text, bold style, `(*)` required marker, `(Optional)` marker, a
  "suggestion" lightbulb, then `invalid-feedback` under the input.
- **Placeholder defaults to the label** (`'Type ' . $attributes['label']`).
- **`options` array or slot** on selects: pass `:options="[...]"` (either `key => label` or
  `['value' => ..., 'name' => ...]` items) or write `<option>` tags in the slot
  (`input/select.blade.php:12-32`).
- **Button loading state bound to `wire:target`** (`backend/button.blade.php`): when the trigger carries
  `wire:target`, the label and icon get `wire:loading.remove` and a spinner with `wire:loading` appears.
  Buttons never double-submit visually. Keep this behaviour exactly.
- **Card with header slots** (`backend/card.blade.php`): optional `title` slot rendered as `card-header`
  with an optional `button` slot pushed right, plus `card_header` / `card_footer` / `video` extra slots.
- **Tabs are Alpine-driven and modal-aware** (`backend/tab.blade.php`): `x-data` holds `activeTab`, and an
  `x-on:wire-modals:opened.window` listener resets to the default tab when the surrounding wire-modals modal
  reopens. Keep the reset-on-modal-open behaviour as an opt-in.
- **Date field = flatpickr behind `wire:ignore`** (`input/date.blade.php`): props `date`, `time`, `format`,
  `minDate`, `maxDate`, `inline`, `defaultDate`, `enableDates`, `disableDates`; an Alpine component builds
  the flatpickr options, and a Livewire event `enable-dates-updated` re-feeds the enabled dates at runtime.
  The behaviour (including the d-M-Y display format family) is good; the implementation is not (see below).
- **Input groups on row inputs** (`item-input/quantity.blade.php`): `input-group input-group-merge` with a
  prepend showing available stock. Generalise as `prepend` / `append` slots on every field.

## The problems the package MUST fix (each one is a requirement)

1. **Undeclared attribute magic.** Fields read `$attributes['label']`, `$attributes['column']`,
   `$attributes['row']`, `$attributes['read-only']`, `$attributes['valid-status']`, `$attributes['options']`,
   `$attributes['datalist']` ad hoc and then also splat `{{ $attributes }}` onto the element — so `label`,
   `options` etc. leak into the rendered HTML as attributes, nothing is documented, and
   `$attributes->merge()` semantics are broken. The package declares every prop and passes only genuine HTML
   attributes through.
2. **Duplication with drift.** The label/required markup exists three times with different spellings:
   `label.blade.php` uses `required`, `checkbox.blade.php` and `password.blade.php` use `require` and inline
   their own label. `item-input/quantity.blade.php` renders its own error div. Select option rendering is
   copy-pasted into four select variants. The package has one base field; nothing is written twice.
3. **Inline `<style>` pushed per component.** `label.blade.php:1-9` and `card.blade.php:1-9` `@push` style
   blocks with hardcoded hex colours on global selectors (`.form-label`, `.card-body`) — pushed once per
   page but overriding Bootstrap globally from a component file. The package ships no `<style>` in views;
   whatever CSS it needs lives in one published/importable stylesheet using Bootstrap CSS variables.
4. **CDN pushes.** `date.blade.php:144-149` pushes flatpickr from jsdelivr. All JS/CSS comes from the app
   bundle via the installer; package views never reference a CDN.
5. **Theme and app coupling.** `waves-effect waves-light` on buttons, `bx`/`fa`/`tf-icon` icon classes,
   `bg-label-*` Vuexy badge classes (`badge.blade.php`), `lang()`, `ucan()`, `@feature()`, the ERP media
   modal. None of it may appear in the package. Icons arrive via props/slots; colours are Bootstrap
   variants; translations use Laravel's `__()` with publishable lang files only where the package itself
   needs text (marker words like "Optional").
6. **Fragile JS ids.** `date.blade.php:78` builds `$jsId` by stripping `.` and `_` from the wire:model name,
   so `user.name` and `username` collide. Ids must be deterministic and collision-free (hash suffix or
   `md5`), and dotted wire:model names must produce valid HTML ids.
7. **Boolean props as strings.** `date.blade.php:13-16` has to `filter_var()` its own props because callers
   pass `time="false"`. Class-based components with typed constructor properties fix this at the contract
   level.
8. **No tests.** Nothing in the Theme module is tested. Every package component gets render/binding/error
   tests.
9. **Seven files for one button.** `button/danger.blade.php` etc. differ only in a CSS class. The package
   has one button component with a `variant` prop (and `outline`/`size` props).
10. **`checkbox.blade.php` renders a switch** (`form-switch`) while calling itself checkbox, and
    `wire:ignore.self` on a checkbox input is a workaround, not a design. The package has distinct
    `checkbox`, `switch` and `radio`/`radio-group` components with correct Livewire bindings and no
    `wire:ignore` unless a JS library owns the DOM.

## What "setup" means in Sylloo (reference for the installer)

Sylloo never solved setup as code — the Vuexy template was hand-wired once (`layouts/backend.blade.php`
links ~20 static css/js files from `Modules/Theme/resources/assets`) and every new project repeats that by
copying. The sandbox app in this repository shows the modern baseline the installer must produce
automatically: Vite + `laravel-vite-plugin`, `resources/js/app.js` importing `bootstrap` and exposing
`window.bootstrap`, `resources/css/app.css` importing the Bootstrap CSS, a minimal component layout
(`resources/views/layouts/app.blade.php`) with `@vite`, and sibling package imports
(wire-tomselect/wire-modals JS). The installer turns that hand-made setup into one command — with SCSS
instead of the prebuilt CSS so projects can override Bootstrap variables.
