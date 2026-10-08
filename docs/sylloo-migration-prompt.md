# Adopt rishadblack/wire-bootstrap in Sylloo ERP (wrapper strategy, zero consumer edits)

You are working in the Sylloo ERP repository (`C:\Office\sylloo-project\sylloo-erp`, Laravel 13, PHP 8.4,
Livewire 4.4, nwidart modules, Bootstrap 5 via the Vuexy-based `Modules/Theme`). Follow the repository's
`AGENTS.md`, `.ai/guidelines/sylloo-erp.md` and the module skills. Two ERP rules override everything else:

1. **Never write, edit, create or run tests on your own.** Finish the code, then ask: "Want me to write/run
   tests for this?" and wait for a yes.
2. Match sibling files before writing new code; use `lang()`, `ucan()`, `$this->alert()`; never introduce
   alternatives.

## Goal

Replace the hand-rolled form/UI component layer of `Modules/Theme` with the package
`rishadblack/wire-bootstrap` 1.0.0 (https://github.com/rishadblack/wire-bootstrap — the readme and the
`wire-bootstrap-development` Boost skill are the API reference), by rewriting each Theme component view as a
**thin wrapper** around the package component. Every consumer blade keeps using `x-theme::input.*` /
`x-theme::backend.*` names **without edits** in phases 1–2, exactly like the `WithDynamicComponent` alias
strategy used for the wire-modals migration.

Two things are explicitly **out of scope**:

- **Do NOT run `php artisan wire-bootstrap:install`.** The installer is for fresh apps; Vuexy owns the
  ERP's asset pipeline, layouts and SCSS. Only the component library and its JS are adopted.
- The template layer stays ERP-owned: layouts, menus, `item-input/*` (POS rows), `input/media`,
  `input/level`, PowerGrid views, mail themes. Do not rewrite them onto the package.

## Component mapping (build this into the wrappers)

| Theme component | Becomes a wrapper around | Notes |
|---|---|---|
| `input/text`, `text-rb`, `text_read`, `text-login` | `x-wb::input.text` | `text_read` → `readonly` attribute; `text-login` keeps its own styling classes |
| `input/email`, `number`, `password`, `textarea` | matching `x-wb::input.*` | password keeps the package toggle |
| `input/price` | `x-wb::input.number` | keep the currency prepend via `prepend` (use `currencySymbol()` from larahelpers) |
| `input/select`, `select_light` | `x-wb::input.select` | both Theme option shapes are supported natively |
| `input/select-tom`, `select2` | **wire-tomselect**, chrome via `x-wb::input.select-search` | do not port Choices.js/Select2 |
| `input/checkbox`, `checkbox2` | `x-wb::input.switch` / `x-wb::input.checkbox` | the Theme "checkbox" is actually a switch — keep each wrapper rendering what it renders today |
| `input/radio` | `x-wb::input.radio` | |
| `input/date`, `time`, `date-range` | `x-wb::input.date` (`time`, `range` props) | removes the per-page CDN pushes |
| `input/editor` | `x-wb::input.editor` | Quill 2; sanitize before `{!! !!}` output |
| `input/file`, `file-multiple` | `x-wb::input.file` | `profile_file` stays ERP (avatar cropper) |
| `input/color-picker`, `range`, `spacing` | `x-wb::input.color` / `input.range` | `spacing` stays ERP if it is more than a styled number input — check first |
| `backend/button` + `button/{danger,info,success,warning,save,print,language}` | one `x-wb::button` | wrappers map each variant file to `variant`/`outline` props and re-add `waves-effect waves-light`; `print`/`language` keep their extra behaviour |
| `backend/card` | `x-wb::card` | map `title`/`button`/`card_header`/`card_footer`/`video` slots onto `title`/`toolbar`/`footer`; keep extra slots in the wrapper if used |
| `backend/badge` | `x-wb::badge` | keep `bg-label-*` (Vuexy) in the wrapper via a `class` override |
| `backend/tab`, `tab-button`, `tab-content` | `x-wb::tabs` / `x-wb::tab` | the package already resets tabs when a wire-modals modal reopens |
| `backend/text`, `container` | check contents; wrap or leave | |
| `datatable/filters`, `input/typeahead`, `text-copy`, `text-map` | leave as-is | revisit after 1.x |

## Phase 0 — Inventory (no edits)

Run and keep the output; it decides every wrapper's contract:

```bash
git checkout -b feature/wire-bootstrap
# usage count per component (which wrappers matter most, which are dead)
grep -rhoE "x-theme::(input|backend)\.[a-z0-9_-]+" Modules --include=*.blade.php | sort | uniq -c | sort -rn
# legacy attribute spellings the wrappers must keep accepting
grep -rn "read-only\|valid-status\|require=\|required=\|datalist=\|column=\|row=" Modules --include=*.blade.php | head -50
grep -rhoE ":options=\"[^\"]{0,60}" Modules --include=*.blade.php | head -30
# how labels/placeholders are produced (lang() stays in the wrappers, never in the package)
grep -rn "label=\"{{ lang(" Modules --include=*.blade.php | head -10
# confirm the browser globals the package JS needs
grep -rn "window.bootstrap" resources/js Modules/Theme/resources
grep -rn "flatpickr\|Quill\|quill" resources/js package.json Modules/Theme/resources/assets/libs -r | head -20
```

Acceptance: a table of Theme components × usage count × attribute spellings seen, and a confirmed answer
for how flatpickr/quill will be provided (npm, replacing the Vuexy static libs for these two).

## Phase 1 — Install the package and its browser layer

1. Until the package is on Packagist, add the VCS repository
   (`{"type": "vcs", "url": "https://github.com/rishadblack/wire-bootstrap"}`) and run
   `composer require rishadblack/wire-bootstrap:^1.0`. Publish nothing yet except the config:
   `php artisan vendor:publish --tag=wire-bootstrap-config`.
2. In the ERP's Vite bundle (`resources/js/app.js`, after whatever sets `window.bootstrap`):
   `npm i flatpickr quill`, then set `window.flatpickr` and `window.Quill` and import
   `../../vendor/rishadblack/wire-bootstrap/resources/js/wire-bootstrap.js`. Add
   `flatpickr/dist/flatpickr.css` and `quill/dist/quill.snow.css` to the bundled styles and remove the
   Theme's CDN pushes / static lib links for flatpickr and quill only. `npm run build`.
3. Config: set `alias_prefix => 'wb'`; align `date.display_format` with what the ERP shows today
   (check `input/date.blade.php` — `d-M-Y` family); set `defaults.show_placeholder` to match current
   placeholder behaviour (`Type {label}`); leave `blaze => true` (inert until Phase 4).
4. Boost: add `rishadblack/wire-bootstrap` to `boost.json` packages and `wire-bootstrap-development` to
   skills, run `php artisan boost:update`.

Acceptance: `php artisan config:show wire-bootstrap` works; a scratch page rendering
`<x-wb::input.text label="Test" wire:model="x" />` and `<x-wb::input.date wire:model="d" />` displays and
binds with no console errors; nothing else in the ERP has changed.

## Phase 2 — Rewrite Theme views as wrappers (zero consumer edits)

One commit per component (or small group), highest Phase 0 usage first. Each wrapper:

- Lives in the same file (`Modules/Theme/resources/views/components/input/text.blade.php` etc.) so every
  `x-theme::input.text` keeps resolving.
- Translates the legacy contract to package props and **keeps accepting the old spellings** found in
  Phase 0: `read-only` → `readonly` attribute, `require` → `required`, `valid-status` dropped (note it),
  `column`/`row` → `data-column`/`data-row` pass-through attributes, `datalist`/`options` forwarded,
  missing-placeholder behaviour unchanged.
- Keeps ERP-only concerns in the wrapper: `lang()` label defaults, `waves-effect` on buttons, Vuexy
  `bg-label-*` badge classes, the `@feature()` checks where a wrapper replaces an item-input-adjacent view.
- Deletes the logic the package now owns (option-shape handling, error markup, flatpickr `@php` blocks,
  pushed `<style>`/CDN blocks). The pushed `.form-label` / `.card-body` style overrides from
  `input/common/label.blade.php` and `backend/card.blade.php` move once into the theme SCSS.
- `input/common/label.blade.php` and `error-label.blade.php` stay until the last wrapper lands, then grep
  for leftover includes and delete them.

Acceptance per commit (manual, in the browser — ask the user to click through, do not write tests): the
module pages with the highest usage of that component look and behave as before — label, required marker,
placeholder, validation error display, value binding, and for date fields the same display format and
enable/disable behaviour. `storage/logs` stays clean while clicking through.

## Phase 3 — Modernise, module by module

- New modal/page code uses `x-wb::*` directly (update `.ai/skills` templates and the module skills to show
  the package components instead of the theme ones).
- Collapse trivial wrappers where a module-wide search-replace is safe and reviewed
  (`x-theme::backend.button.danger` → `x-wb::button variant="danger"`), one module per commit.
- Replace `item-input/*` internals with package fields where the behaviour matches (keep the
  `@feature('inventory')` prepend logic in the ERP views).

## Phase 4 — Blaze (optional but recommended)

`composer require livewire/blaze`. The package opts its own views in automatically. Then, in a Theme
service provider, opt the **wrapper** views in with `Blaze::optimize()->in(<theme components path>)` —
function compiler only; never enable `fold`/`memo` there (wrappers render `lang()`, `ucan()` and `$errors`
state). Verify with `Blaze::debug()` on a list-heavy page and record the before/after timings.

## Rules while you work

- Phases 1–2 must not edit any consumer blade outside `Modules/Theme/resources/views/components/`.
- Never copy package markup into the ERP; if a wrapper needs different markup, publish the package view
  (`wire-bootstrap-views`) and note why.
- The package knows nothing about the ERP: `lang()`, `ucan()`, `@feature()`, Vuexy classes live only in
  wrappers.
- Commit at the end of each phase step with a message listing the replaced files.
- Stop and ask if: a Theme component's behaviour cannot be reproduced through package props + wrapper
  markup; Phase 0 shows an attribute spelling the wrapper cannot translate safely; the flatpickr/quill
  bundle swap conflicts with a Vuexy page that uses the static libs directly.
