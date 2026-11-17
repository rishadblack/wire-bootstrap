# Changelog

All notable changes to `rishadblack/wire-bootstrap` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.1.0 - 2026-11-17

### Added

- `input.code`: a grouped code field — one box per group (default 4 × 4, `1234-5678-9012-3456`). Props `groups`
  (a count or a list of lengths such as `[3, 3, 4]`), `length`, `separator`, `formatted`, `alphanumeric`,
  `disabled`, `autofocus` plus the common field props. Typing moves on to the next box, Backspace in an empty
  box goes back, arrow keys move between boxes, and a paste with or without separators fills every box.
  Digits only unless `alphanumeric`. `wire:model` receives the plain value (or the separated one with
  `formatted`) through Alpine `x-modelable`. Labelled `role="group"` with a per-box `aria-label`, always
  left to right (RTL-safe).
- `pin-pad`: an on-screen PIN pad — masked dots, digit keys, delete and OK, plus keyboard input (digits,
  Backspace, Enter, Escape) while focused or page-wide with `global-keys`. Props `min`/`max` (4–6 by
  default), `submit` (a Livewire method called on OK / Enter / reaching `max`), `auto-submit`, `disabled`,
  `autofocus`, `size`, `label`, `name`, `id`, `help`. Every submit dispatches a `wb-pin-submit` browser
  event; keys lock while the `submit` call runs; a validation error turns the dots red and is announced.
- Alpine components `wbCode` and `wbPinPad` in `resources/js/wire-bootstrap.js`; their styles in the
  `wire-bootstrap.scss` theme layer (Bootstrap CSS variables, light and dark mode). Apps that imported the
  theme layer and the package JS need only `npm run build`.
- `Support\CodeOptions` (group lengths, code mount data, PIN bounds), config sections `code` (`groups`,
  `length`, `separator`) and `pin_pad` (`min`, `max`), and lang lines `code_label`, `code_group`,
  `pin_label`, `pin_entered`, `pin_erase`, `pin_submit` (a published lang file keeps working: missing keys
  fall back to the package's lines).

## 1.0.2 - 2026-10-28

### Fixed

- `input.email`, `input.number`, `input.switch` and `input.checkbox-group` escaped a bound label, help or placeholder
  twice (`:label="__('…')"` with an apostrophe showed `&#039;`). The wrappers now declare the props of the field they
  wrap and pass them on unescaped.

## 1.0.1 - 2026-10-10

### Added

- `resources/scss/wire-bootstrap.scss` theme layer: restyles the flatpickr calendar (days, ranges, today, month and
  year pickers, week numbers, time picker) with Bootstrap 5.3 CSS variables, so `input.date` follows light and dark
  mode and the app's `$primary`. The installer's `app.scss` stub imports it after the vendor styles; existing apps
  add the import by hand.

### Fixed

- The flatpickr calendar stayed white with dark text in dark mode.

## 1.0.0 - 2026-10-08

### Added

- `php artisan wire-bootstrap:install`: one-command Bootstrap 5.3 setup — npm dependencies (`bootstrap`,
  `@popperjs/core`, `flatpickr`, `sass`), a `resources/scss/app.scss` entry with variable-override
  sections, a managed `wire-bootstrap-setup.js` (with auto-detected wire-tomselect / wire-modals imports),
  a marker-managed import in `app.js`, conservative `vite.config.js` edits (SCSS entry + dart-sass
  deprecation silencing), the Livewire layout and the config. Idempotent; prints manual snippets for files
  it cannot parse. Flags: `--no-npm`, `--skip-build`, `--no-layout`, `--no-blaze`, `--force`.
- Field components (anonymous, Blaze-compilable, available as `<x-wire-bootstrap::*>` and `<x-wb::*>`):
  `input.text`, `input.email`, `input.number`, `input.hidden`, `input.password` (Alpine visibility
  toggle), `input.textarea`, `input.select`, `input.select-search` (field chrome for wire-tomselect),
  `input.checkbox`, `input.switch`, `input.checkbox-group`, `input.radio`, `input.date` (flatpickr),
  `input.editor` (Quill 2 rich text with `basic`/`full`/custom toolbars, bound through a hidden input,
  plain-textarea fallback), `input.file` (Livewire upload progress), `input.color`, `input.range`. Every field derives id/name/error
  key from `wire:model`, renders label + markers, validation error and help text, supports `size`,
  `floating` and `prepend`/`append` input groups, and renders `aria-required` instead of the native
  `required` attribute so Livewire validation always runs.
- UI components: `button` (variant/outline/size/icon + automatic `wire:target` loading spinner), `card`,
  `tabs`/`tab` (Alpine; resets inside a reopening wire-modals modal), `badge`, `alert`, `empty-state`,
  `theme-toggle` and `layout` (skeleton with slots, config-driven `@vite` entries and a pre-paint
  dark-mode snippet).
- Support classes: `FieldState` (field resolution), `Options` (both option shapes), `DateOptions`
  (flatpickr options + date normalisation), `Installer`, `NodePackages`.
- Automatic `livewire/blaze` integration: when installed, the package views are opted into Blaze's
  function compiler (config `wire-bootstrap.blaze`).
- Browser layer: Alpine components only (`wbDatepicker`, `wbTabs`, `wbThemeToggle`) — no jQuery, no
  loose scripts; Bootstrap behaviours via `data-bs-*`.
- Config (`wire-bootstrap`): alias prefix, Vite entries, field defaults, label markers, date display
  formats and flatpickr options, theme defaults, Blaze toggle. Publishable views and lang lines.
- Pest + Testbench suite (92 tests) and a Boost guideline + `wire-bootstrap-development` skill.
