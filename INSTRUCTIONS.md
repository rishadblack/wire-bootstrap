# wire-bootstrap — build instructions for the implementing agent

You are building a new Composer package, `rishadblack/wire-bootstrap`, inside this folder. It generalises
the form/UI component layer of the Sylloo ERP Theme module (`C:\Office\sylloo-project\sylloo-erp\Modules\Theme`)
into a reusable Laravel + Livewire package, **plus** an installer that sets up Bootstrap 5.3 (npm, SCSS,
Vite, `window.bootstrap`, base layout) in any fresh project with one command. It follows the structure of
the sibling packages in `packages/rishadblack/` (`wire-modals` is the freshest and closest model).

This file is the entry point. Read it fully, then read the three documents in `docs/` in this order:

1. `docs/how-sylloo-does-it.md` — what the Theme module does today: the conventions to keep (wire:model
   drives everything, button loading states, options shapes) and the ten problems the package must fix.
   The Theme code is the behavioural reference, not code to copy.
2. `docs/architecture.md` — the package design: mental model, file tree, the installer spec, the layout
   component, the `Field` base contract, every v1 component with its props, config, JS, test matrix,
   browser scenarios and the later Sylloo adoption notes.
3. `docs/package-conventions.md` — a delta document: conventions are identical to wire-modals, read
   `packages/rishadblack/wire-modals/docs/package-conventions.md` and apply the listed substitutions.

## One-line pitch (use this wording in the readme and the Boost guideline)

> Livewire-native Bootstrap 5.3 UI: one artisan command sets up Bootstrap, Vite and a base layout in a
> fresh Laravel app, and every form field is a single Blade tag that brings its own label, `wire:model`
> binding, ids, validation errors and loading states — restyle it per project with SCSS variables and
> published views, without owning the plumbing.

The differentiator: the serious Livewire component libraries (Flux, WireUI, Mary) are Tailwind-only.
Nothing maintained does this for Bootstrap 5 + Livewire. wire-bootstrap is also the base layer of the
`rishadblack/*` stack — wire-tomselect, wire-modals and i-reports all render Bootstrap 5.3 and the
installer wires them up automatically when they are installed.

## Non-negotiable decisions (already made — do not re-open them)

| Topic | Decision |
|---|---|
| Package name | `rishadblack/wire-bootstrap` (folder `packages/rishadblack/wire-bootstrap`) |
| Namespace | `Rishadblack\WireBootstrap` |
| Service provider | `Rishadblack\WireBootstrap\WireBootstrapServiceProvider` |
| Config | `config/wire-bootstrap.php`, key `wire-bootstrap` |
| Blade namespaces | `<x-wire-bootstrap::*>` plus config alias `<x-wb::*>` (default `wb`, nullable), both via `Blade::anonymousComponentPath()` |
| Component style | **Anonymous components** (a Blaze requirement — Blaze does not optimize class components), each starting with a complete `@props([...])` contract; never read `$attributes['foo']` for a prop. All non-trivial logic lives in `src/Support/*` classes called from the view in one `@php` line; views stay thin |
| Performance | `livewire/blaze` is a first-class target: package views must run under Blaze's function compiler; the provider opts the package view directory into Blaze when the package is installed (`class_exists` guard); Blaze stays a `suggest`, never a `require` — everything must also work without it |
| Base field | `Support\FieldState::resolve($attributes, $props)` (one PHP class) + a shared `field` wrapper anonymous component: label, derived id/name, error display, placeholder default, markers, size, floating, prepend/append, help |
| Installer | `php artisan wire-bootstrap:install` (`--no-npm`, `--skip-build`, `--no-layout`, `--force`), idempotent, never corrupts files it cannot parse |
| Layout | `<x-wire-bootstrap::layout>` skeleton + published `components/layouts/app.blade.php` wrapper; no navbar/sidebar markup in the package |
| CSS strategy | Installer writes `resources/scss/app.scss` importing Bootstrap **source** Sass with override sections; package ships at most a tiny `wire-bootstrap.css` using Bootstrap CSS variables; no `<style>` pushed from views; no CDN references anywhere |
| JS | Bootstrap + Alpine **only**. `resources/js/wire-bootstrap.js` contains nothing but Alpine component registrations on `alpine:init` (Livewire's Alpine); native Bootstrap behaviours go through `data-bs-*` attributes, not JS calls; Bootstrap's JS API only via `window.bootstrap` and only where a `data-bs-*` attribute cannot do it; flatpickr for `input.date` is the single third-party lib; **no jQuery anywhere, no vanilla-JS DOM scripts, no event listeners outside Alpine** |
| v1 inventory | the table in `docs/architecture.md` — fields (text, email, number, hidden, password, textarea, select, select-search, checkbox, checkbox-group, switch, radio, date, file, color, range), button, card, tabs/tab, badge, alert, empty-state, layout, theme-toggle |
| Out of v1 | typeahead, editor, dropzone, text-copy, media/level/item-input (ERP-only), menus, any navbar/sidebar |
| Sibling integration | `input.select-search` uses wire-tomselect when installed; tabs reset on wire-modals open when inside one; **no hard Composer dependency on either** |
| PHP / Laravel / Livewire | `php ^8.3`, `illuminate/* ^11.0|^12.0|^13.0`, `livewire/livewire ^3.0|^4.0` |
| Tests | Pest + Orchestra Testbench in the package; installer unit-tested against temp dirs; browser tests in the sandbox app `tests/Browser` |
| Code style and analysis | Pint (laravel preset) + Larastan level 6 |

## Hard rules while you work

- Follow the project `AGENTS.md` (Laravel Boost guidelines) in the repo root: match sibling package files
  for structure and naming, run `vendor/bin/pint --dirty --format agent` on PHP you changed, write tests
  for behaviour, use `search-docs` before relying on Laravel/Livewire APIs.
- Do NOT modify anything under `C:\Office\sylloo-project\sylloo-erp`. Sylloo adoption is a later, separate
  task (notes at the end of `docs/architecture.md`).
- Do not copy Theme code verbatim; every file listed in `docs/how-sylloo-does-it.md` under "problems" is
  there to be fixed, not ported.
- No ERP or Vuexy vocabulary in the package: no `waves-effect`, `bx-*`, `tf-icon`, `bg-label-*`, `lang()`,
  `ucan()`, `@feature()`. Icons are prop/slot strings; colours are Bootstrap variants.
- Every public prop, config key and installer flag must be exercised by a test.
- CSP-safe: no `eval`, no `new Function`, no inline event handlers; the one candidate exception (the
  pre-paint theme snippet) is decided in Phase 0 and documented.
- Browser behaviour is expressed only as Bootstrap `data-bs-*` attributes and Alpine (`x-data`, `x-on`,
  `x-show`, `$wire`, …) in the views, backed by Alpine components in `wire-bootstrap.js`. Never write a
  loose `<script>`, a `document.querySelector`/`addEventListener` outside an Alpine component, or any
  jQuery — if a behaviour seems to need one, redesign it as an Alpine component or a `data-bs-*` attribute.
- Performance is a feature: views must stay Blaze-compatible (no `$component` variable, no reliance on view
  composers/creators or `View::share()`, no rendering package components via the `view()` helper) and
  allocation-light (one `FieldState` resolve per field, no `@include` chains — compose with anonymous
  components so Blaze optimises the whole tree). Components that touch `$errors`, `request()`, `auth()` or
  time are never marked foldable; folding/memo are opt-in per view and only where Blaze's rules allow.
- The installer must be safe on a dirty real project: idempotent re-runs, marker-based edits, refuse to
  touch files it cannot parse (print manual steps instead), never delete user files.
- Composer dependencies: only those in the decisions table, plus `livewire/blaze` in `suggest` (and in the
  package's `require-dev` so tests can cover the Blaze-installed path). npm dependencies: only written into
  the host app's `package.json` by the installer (`bootstrap`, `@popperjs/core`, `flatpickr`, `sass`) — the
  package itself has no `package.json` build step.
- On this machine `composer` runs on PHP 8.3 while `php` is 8.4 (memory note `composer-php-version`); plain
  `composer` is fine inside the package. Pint on Windows reports CRLF noise; CI on Linux is the real check.
- Start long test runs (especially browser tests) in the background and check after ~30s; do not block.

## Work plan (phases in order; each has an acceptance check)

### Phase 0 — Verify assumptions (no files yet)

Record answers with file references in your working notes:

- `composer show livewire/livewire` in the sandbox; confirm the default full-page layout view location for
  Livewire 3 and 4 (`components.layouts.app`?) and how `Route::livewire()` resolves it, so the installer
  publishes to the right path per version.
- `Blade::anonymousComponentPath()` with a prefix for nested directories
  (`components/input/text.blade.php` → `<x-wire-bootstrap::input.text>`) and whether the same path can be
  registered under a second prefix for the `wb` alias — read the framework source or search-docs; if the
  alias needs a different mechanism, pick one and note it.
- **Blaze** (`composer require livewire/blaze` in the sandbox): its PHP/Laravel/Livewire version lanes (if
  it needs newer lanes than ours, it stays `suggest`-only and the provider guard covers it); whether
  `Blaze::optimize()->in($packageViewsPath)` picks up prefixed anonymous components registered by a package
  provider, or each view needs the `@blaze` directive (and how `@blaze` behaves when Blaze is NOT installed
  — if Blade chokes or prints it, register a no-op directive under a `class_exists` guard); confirm the
  function compiler handles `$errors`, `$attributes` merging, named slots and `@props` defaults the way the
  field wrapper uses them; run `Blaze::debug()` on a throwaway page to see the before/after timings.
- The current Bootstrap 5.3 Sass story under the `sass` npm package and Vite: exact import list for the
  stub, which deprecation warnings appear, and the `css.preprocessorOptions.scss` settings that silence
  them. Build a throwaway scss in the sandbox to prove the stub compiles clean.
- How Livewire's bundled Alpine is extended from app code (`alpine:init` listener timing with the Vite
  bundle) and that `wire:model` works on a flatpickr-managed input behind `wire:ignore` (the wire-tomselect
  package already solves the equivalent problem — read its JS first).
- The CSP decision for the pre-paint dark-mode snippet.

Acceptance: each bullet answered concretely, with the chosen approach stated.

### Phase 1 — Scaffold

Package skeleton per `docs/package-conventions.md` (composer.json, root meta files, CI, pint, phpstan,
phpunit, config, provider, TestCase/Pest, empty resource dirs, Boost guideline + skill skeletons). `git init`
+ first commit once green. Register in the sandbox `composer.json` (path repo + `*@dev` — the one authorised
dependency change) and confirm `php artisan config:show wire-bootstrap` works.

Acceptance: `composer check` passes in the package with one placeholder provider test.

### Phase 2 — Field foundation + core fields

`Support\FieldState` + the shared `field` wrapper component, then `input.text`, `input.email`, `input.number`,
`input.hidden`, `input.password`, `input.textarea`, `input.select`, `input.checkbox`, `input.switch`,
`input.checkbox-group`, `input.radio`, `input.color`, `input.range`, `input.file`. Lang file. Tests from the
matrix in `docs/architecture.md` as you go, foundation first.

Acceptance: foundation + per-component tests pass; rendering a field with a dotted `wire:model` produces
valid, stable, collision-free ids.

### Phase 3 — JS components + the fields that need them

`resources/js/wire-bootstrap.js` (`wbDatepicker`, `wbPasswordToggle`, `wbThemeToggle`, `wbTabs`), then
`input.date` (port the Theme's option/enable/disable behaviour per the architecture doc), the password
toggle wiring, `tabs`/`tab`, `theme-toggle`. PHP-side tests assert the rendered Alpine mount data (option
JSON, converted dates); behaviour is proven in Phase 6 browser tests.

### Phase 4 — UI components + layout

`button` (variant/outline/size/icon/loading), `card`, `badge`, `alert`, `empty-state`, `layout`,
`input.select-search` (wire-tomselect bridge with the no-dependency fallback). Tests per the matrix.

### Phase 5 — Installer

`Support\NodePackages`, `Support\StubWriter`, `Support\Installer`, `Console\InstallCommand`, the four stubs.
Unit tests against temp dirs (fresh, re-run idempotence, `--force`, unparseable vite.config, flag matrix;
process runner injected so npm never runs in tests) + one artisan feature test.

Acceptance: the installer test suite passes, including byte-identical re-runs.

### Phase 6 — Sandbox integration and browser tests

Run the real installer in the sandbox app (it migrates `resources/css/app.css` usage to the SCSS entry;
wire-tomselect and wire-modals must keep working with their imports moved into the generated setup file).
Build `App\Livewire\Pages\FormsPlayground` + `/forms` route + welcome button, feature test, then
`tests/Browser/WireBootstrapTest.php` covering the eight scenarios in `docs/architecture.md`.
`npm run build` after JS changes.

### Phase 7 — Documentation

`readme.md` (wire-tomselect section order, adapted: pitch + install command → requirements → what the
installer does / manual installation → your first form → field reference table → every component section →
configuration reference → theming (scss overrides, publishing views, lang) → extending (writing your own
Field subclass) → sibling packages → testing → troubleshooting → license), Boost guideline, the
`wire-bootstrap-development` skill, `changelog.md` `1.0.0`.

### Phase 8 — Quality gate

`composer check` in the package; sandbox feature + browser suites pass; then report: what was built, test
counts, deviations from this spec (update the status block you add at the top of this file, as wire-modals
did), anything left open, and the Sylloo adoption checklist location.

## When you must stop and ask

- If a Livewire 3 vs 4 or Laravel 11–13 difference makes a non-negotiable decision impossible.
- If the Bootstrap Sass setup cannot be made to compile warning-free with pinned stub versions — propose
  the pin, do not silently ship warnings.
- If safe idempotent editing of `vite.config.js` / `app.js` turns out to be impossible for the standard
  Laravel skeletons — propose falling back to print-only instructions for that file.
- If Blaze's constraints force a change to a component's public prop surface (the API wins — then that view
  simply runs unoptimized, and you note it).
- Anything that would add a Composer dependency, or an npm dependency beyond the four listed.
