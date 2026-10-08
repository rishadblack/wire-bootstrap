# Package conventions — deltas from wire-modals

The scaffolding, testing, CI, readme, Boost and sandbox-integration conventions are **identical to
wire-modals**. Read `packages/rishadblack/wire-modals/docs/package-conventions.md` in full and follow it,
with the substitutions and deltas below. When in doubt, the freshest built example is wire-modals itself —
copy its file shapes, not wire-tomselect's, where the two differ.

## Substitutions

| wire-modals | wire-bootstrap |
|---|---|
| `rishadblack/wire-modals` | `rishadblack/wire-bootstrap` |
| `Rishadblack\WireModals` | `Rishadblack\WireBootstrap` |
| `WireModalsServiceProvider` | `WireBootstrapServiceProvider` |
| config `wire-modals` | config `wire-bootstrap` |
| tags `wire-modals-config`, `-views` | `wire-bootstrap-config`, `-views`, `-lang` |
| skill `wire-modals-development` | `wire-bootstrap-development` |
| keywords | `laravel, livewire, bootstrap, blade-components, forms, ui, alpine` |

## composer.json deltas

- Same `require` lanes as wire-modals (`php ^8.3`, `illuminate/support` + `illuminate/view`
  `^11.0|^12.0|^13.0`, `livewire/livewire ^3.0|^4.0`). Add `illuminate/console` and
  `illuminate/filesystem` (same version lanes) for the install command. Still no `illuminate/database`.
- Same `require-dev` and scripts (`test`, `lint`, `lint:check`, `analyse`, `check`), plus
  `livewire/blaze` in `require-dev` (version lane from Phase 0) and in `suggest` with the line
  `"livewire/blaze": "Compiles the wire-bootstrap components to plain PHP functions (auto-detected)"`.
  If Blaze's lanes are narrower than the CI matrix, gate its tests with
  `class_exists` skips so the older lanes stay green.
- Description from the pitch in `INSTRUCTIONS.md`.

## Deltas and additions

- **Translations**: this package ships `resources/lang/en/wire-bootstrap.php` (marker words, default
  placeholder pattern, loading text). Load with `loadTranslationsFrom`, publish under `wire-bootstrap-lang`.
  wire-modals has no lang files; follow Laravel's package-lang documentation (search-docs) for the shape.
- **Stubs**: `resources/stubs/` is new. Stub files are not Blade-compiled; the installer copies/edits them
  verbatim. Keep them out of `loadViewsFrom`.
- **Console command tests**: Testbench supports artisan command testing (`$this->artisan(...)`); the
  heavier filesystem assertions live in unit tests against `Support\Installer` pointed at temp dirs — do
  not shell out to npm in tests (inject a no-op process runner; assert the commands that *would* run).
- **CI**: same matrix as wire-modals (lint job + PHP 8.3/8.4 × Laravel 11/12/13 × Livewire 3/4). No npm job
  needed; the package JS is plain ESM consumed by the host app's bundler.
- **Sandbox integration**: path repository + `"rishadblack/wire-bootstrap": "*@dev"` in the root
  `composer.json` (the one authorised dependency change). In the sandbox, actually **run the installer** —
  it replaces the hand-written `resources/css/app.css` Bootstrap import with the SCSS entry; the sandbox is
  the installer's first real test. Keep wire-tomselect and wire-modals working afterwards (their imports
  move into the generated `wire-bootstrap-setup.js`).
- **Playground page**: `App\Livewire\Pages\FormsPlayground` + route `/forms` + a welcome-page button
  `Forms Playground (wire-bootstrap)`, pattern of `ModalsPlayground`. Feature test
  `tests/Feature/FormsPlaygroundTest.php`, browser test `tests/Browser/WireBootstrapTest.php` (background
  runs, ~30s check, per the memory note).
- **Boost guideline** (`core.blade.php`): `## Wire Bootstrap` heading, 8–10 bullets — what the package is,
  "activate the `wire-bootstrap-development` skill whenever…", the installer one-liner, the field-is-one-tag
  example, wire:model-derived id/name/error, options shapes, button loading, layout/theme-toggle, the
  "declare props, don't read `$attributes['...']`" rule, and the no-CDN/no-inline-style rule.
- **SKILL.md** triggers: classes extending `Rishadblack\WireBootstrap\View\Components\Field`,
  `<x-wire-bootstrap::*>` / `<x-wb::*>` tags, `wire-bootstrap:install`, editing `app.scss` Bootstrap
  overrides, the layout component, adding a new field component, testing fields with Pest.
