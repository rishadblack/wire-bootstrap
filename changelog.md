# Changelog

All notable changes to `rishadblack/wire-bootstrap` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
