# wire-bootstrap — architecture and public API

Everything here is the design to implement. Where a detail is marked *(verify)*, confirm it against the
installed Livewire/Bootstrap versions in Phase 0 before relying on it.

## Mental model

The package is three things, strictly layered so each is usable without the next:

1. **An installer** — `php artisan wire-bootstrap:install` turns a fresh Laravel + Livewire app into a
   working Bootstrap 5.3 frontend: npm dependencies, SCSS entry with variable-override hooks,
   `window.bootstrap`, Vite wiring, a published app layout. Run once per project, idempotent.
2. **A layout component** — `<x-wire-bootstrap::layout>` is the HTML skeleton (doctype, meta, `@vite`,
   stacks, `data-bs-theme` dark-mode handling). The installer publishes a thin
   `resources/views/components/layouts/app.blade.php` that wraps it; the app edits the published file, the
   skeleton stays upgradable in the package.
3. **A component library** — Livewire-native Bootstrap 5.3 form fields and UI primitives. Every field ships
   label, `wire:model`-derived id/name, validation error display and attribute passthrough, so a form field
   is one tag.

Dependency direction: the library works without the installer (any app that already has Bootstrap 5.3 on
`window.bootstrap` can use the components, exactly like wire-modals). The installer exists so a new project
needs zero manual wiring.

## File tree

```
packages/rishadblack/wire-bootstrap/
├── composer.json, LICENSE, license.md, contributing.md, readme.md, changelog.md, .gitignore
├── pint.json, phpstan.neon, phpunit.xml, .github/workflows/tests.yml
├── config/wire-bootstrap.php
├── src/
│   ├── WireBootstrapServiceProvider.php
│   ├── Console/InstallCommand.php
│   └── Support/
│       ├── Installer.php            # file edits, testable without artisan
│       ├── NodePackages.php         # package.json read/merge/write
│       ├── StubWriter.php           # publish stub with marker-based idempotent appends
│       ├── FieldState.php           # resolves a field's label/id/name/error/placeholder/markers from $attributes + props
│       ├── Options.php              # normalises the two select/radio option shapes
│       └── DateOptions.php          # flatpickr option building + date conversions (ported Theme logic)
├── resources/
│   ├── views/components/            # ALL components are anonymous (a Blaze requirement):
│   │   ├── layout.blade.php, theme-toggle.blade.php
│   │   ├── field.blade.php          # shared wrapper: label → control slot → invalid-feedback → help
│   │   ├── input/{text,textarea,number,password,email,select,select-search,
│   │   │          checkbox,checkbox-group,switch,radio,date,file,color,range,hidden}.blade.php
│   │   ├── button.blade.php, card.blade.php, tabs.blade.php, tab.blade.php
│   │   └── badge.blade.php, alert.blade.php, empty-state.blade.php
│   ├── js/wire-bootstrap.js         # Alpine pieces: datepicker, password toggle, theme toggle, copy
│   ├── css/wire-bootstrap.css       # tiny; Bootstrap CSS variables only
│   ├── stubs/                       # what the installer writes into the app
│   │   ├── app.scss                 # Bootstrap source imports + override sections
│   │   ├── wire-bootstrap-setup.js  # imports bootstrap, sets window.bootstrap, imports package js
│   │   ├── layouts-app.blade.php    # wraps <x-wire-bootstrap::layout>
│   │   └── vite.config.js           # only used when no vite.config exists
│   ├── lang/en/wire-bootstrap.php   # marker words: required, optional, loading, select placeholder
│   └── boost/
│       ├── guidelines/core.blade.php
│       └── skills/wire-bootstrap-development/SKILL.md
├── tests/ (TestCase.php, Pest.php, Feature/*, Fixtures/)
└── docs/ (these files)
```

## The installer

### Command

```
php artisan wire-bootstrap:install
    {--no-npm       : edit package.json but do not run npm install}
    {--skip-build   : do not run npm run build at the end}
    {--no-layout    : do not publish the app layout}
    {--no-blaze     : do not composer require livewire/blaze into the app}
    {--force        : overwrite files this command previously published}
```

No interactive prompts when `--no-interaction` is passed; sensible defaults for everything.

### What it does, in order (each step idempotent — running twice changes nothing)

1. **npm dependencies** (`NodePackages`): merge into `package.json` — dependencies `bootstrap ^5.3`,
   `@popperjs/core ^2.11`, `flatpickr ^4.6`; devDependency `sass ^1.77`. Never downgrade an existing entry;
   never touch unrelated keys; preserve key order and indentation. Run `npm install` unless `--no-npm`.
2. **SCSS entry**: write `resources/scss/app.scss` from the stub — a documented "your variable overrides"
   section, the Bootstrap source imports, an optional dark-mode section, then package CSS imports and a
   "your styles" section. *(verify in Phase 0: the exact import style Bootstrap 5.3 documents for Sass and
   which deprecation silences the current `sass` package needs — e.g. `quietDeps`/`silenceDeprecations` in
   Vite's `css.preprocessorOptions.scss`; put the working incantation in the stub/vite guidance, not in
   user-facing troubleshooting.)*
3. **JS setup file**: write `resources/js/wire-bootstrap-setup.js` from the stub: `import * as bootstrap
   from 'bootstrap'; window.bootstrap = bootstrap;` plus the package JS import, plus — when the
   corresponding composer package is installed — the wire-tomselect and wire-modals imports (detect via
   `class_exists` on their providers; include tom-select css note in the scss stub the same way). Then add
   `import './wire-bootstrap-setup';` to the top of `resources/js/app.js` between marker comments
   (`// wire-bootstrap:begin` / `:end`) so re-runs replace, never duplicate.
4. **Vite config**: add `resources/scss/app.scss` to the `laravel()` plugin `input` array and add the scss
   preprocessor options. Edit conservatively: parse with targeted regex anchored on the `laravel(`/`input:`
   shapes Laravel ships; if the file does not match (custom config), **do not edit** — print the exact
   manual snippet instead. Never corrupt a config the command cannot parse. If `resources/css/app.css` was
   the only stylesheet and contains nothing but Bootstrap imports, tell the user it can be removed from the
   input array (do not delete files).
5. **Layout**: unless `--no-layout`, publish `resources/views/components/layouts/app.blade.php` from the
   stub *(verify in Phase 0 that this is the default full-page component layout location for both Livewire 3
   and 4 in the supported Laravel versions, and publish to the detected convention)*. Skip when the file
   exists and `--force` is absent.
6. **Config**: publish `config/wire-bootstrap.php` (same as `vendor:publish --tag=wire-bootstrap-config`).
7. **Build**: run `npm run build` unless `--skip-build` or `--no-npm`.
8. **Report**: print a checklist of what was written, skipped (exists), or needs manual action, and the
   minimal usage example.

The file-editing logic lives in `Support\Installer` (plain class, injected filesystem paths) so it is unit
testable against temp directories without artisan or a real app.

## Layout and theme toggle

`<x-wire-bootstrap::layout>`:

- Props: `title` (string, defaults to `config('app.name')`).
- Slots: default slot (page body), `head` (extra head tags), `nav` (rendered before the body slot),
  `footer`, `scripts` (before `</body>`).
- Renders: doctype, `lang` from app locale, charset/viewport meta, `<title>`, `@vite([...])` reading the
  entry points from `config('wire-bootstrap.vite_entries')`, `@stack('css')` / `@stack('js')`,
  `data-bs-theme` on `<html>` driven by the theme-toggle persistence (see below), `{{ $attributes }}` on
  `<body>`.
- Contains **no** navbar/sidebar markup. Navigation is the app's; the `nav` slot is just a place for it.

`<x-wire-bootstrap::theme-toggle>`: a button that cycles light/dark (optionally auto), persisting to
`localStorage` and setting `data-bs-theme` on `<html>`. Implemented in `wire-bootstrap.js` as an Alpine
component plus a tiny pre-paint snippet in the layout head to avoid the flash of wrong theme — the snippet
must be CSP-compatible *(verify: an external module that runs before CSS paint, or document the one
`nonce`-able inline script as the single CSP exception, decided in Phase 0)*.

## Performance: Blaze (`livewire/blaze`)

High priority per the user: forms render dozens of fields per page and the library must stay fast at scale.
Blaze (by the Livewire team) compiles **anonymous** Blade components into plain PHP functions (91–97% less
rendering overhead), with opt-in memoization (`memo: true`, slot-free components only) and compile-time
folding (`fold: true`, no global state). Class components are not supported — which is why every component
in this package is anonymous.

Rules:

- `livewire/blaze` is a **suggest** (+ `require-dev` for tests), never a hard require. The provider opts the
  package views in only when it is installed:
  `if (class_exists(\Livewire\Blaze\Blaze::class)) { Blaze::optimize()->in(__DIR__.'/../resources/views/components'); }`
  *(verify the exact facade/API and that directory opt-in reaches prefixed package components in Phase 0;
  fall back to `@blaze` directives per view plus a no-op `@blaze` directive registered when Blaze is absent).*
- The default win is the **function compiler** on every package view. **Folding is forbidden** for anything
  touching `$errors`, `auth()`, `request()`, session, time or CSRF — i.e. every field. Candidates for
  `memo`/`fold` are only the static leaves (e.g. a future icon component); decide per view with a comment
  explaining why it is safe.
- Views must respect Blaze's limits: no `$component` variable, no reliance on view composers/creators or
  `View::share()`, components rendered via tags only (never the `view()` helper), `@aware` not used across
  the Blade/Blaze boundary (we simply don't use `@aware`).
- Compose with anonymous components instead of `@include` chains so the whole field tree compiles under
  Blaze; each field does exactly one `FieldState::resolve()` call.
- The installer offers Blaze to the app: `wire-bootstrap:install` runs `composer require livewire/blaze`
  unless `--no-blaze` (skipped silently when the app's constraints can't take it; print the manual command
  instead) and the published layout/readme mention `Blaze::optimize()->in(resource_path('views/components'))`
  for the app's own components.
- The Boost guideline and readme get a short "performance" section: what Blaze gives, what the package opts
  in automatically, how to verify with `Blaze::debug()`.

## Field foundation

`Support\FieldState` is the one place field plumbing lives. Every input view starts with a complete
`@props([...])` block, then one line — `@php($field = \Rishadblack\WireBootstrap\Support\FieldState::resolve($attributes, [...props...]))`
— and renders through the shared `field.blade.php` wrapper component. No logic in views beyond that;
`FieldState` is a plain readonly value object, fully unit-testable without rendering.

Props common to all fields (declared in each view's `@props`, resolved by `FieldState`):

| Prop | Type | Default | Meaning |
|---|---|---|---|
| `label` | ?string | null | label text; no label rendered when null |
| `name` | ?string | wire:model value | input `name`; derived when absent |
| `id` | ?string | derived | valid HTML id derived from `name` (dots → `-`, plus short hash suffix to avoid collisions) |
| `placeholder` | ?string | from label via lang line | pass `''` to suppress |
| `required` | bool | false | `(*)` marker (lang file) + `required` attribute |
| `optional` | bool | false | `(Optional)` marker (lang file) |
| `help` | ?string | null | muted `form-text` under the field |
| `size` | ?string | config default | `sm` / `lg` → `form-control-sm` etc. |
| `floating` | bool | config default | Bootstrap floating label (only on fields that support it) |
| `readOnly` / `disabled` | bool | false | forwarded attributes |

Slots common to all text-like fields: `prepend`, `append` (wrapped in `input-group` automatically when
present).

Shared behaviour in `FieldState` + the `field.blade.php` wrapper component (one implementation, zero
duplication; the wrapper is itself an anonymous component so Blaze optimises it too):

- Resolve the wire:model expression once (`$attributes->wire('model')`); error key = modifier-stripped
  wire:model value, falling back to `name`.
- Render order: label → control (with input-group when prepend/append) → `invalid-feedback` from `$errors`
  → help text. Floating variant swaps label position.
- `is-invalid` on the control when the error bag has the key; never a `valid-status` prop (drop it).
- `{{ $attributes }}` passthrough lands on the **control element** — `@props` already pulled the declared
  props out; the outer wrapper takes only `class` via an explicit `wrapperClass` prop.
- Every generated id must be stable across re-renders (derived, not random) so Livewire morphing and
  `<label for>` keep working.
- Boolean props passed as strings (`floating="false"`) are normalised in `FieldState` (the Theme's
  `filter_var` lesson), so both `:floating="false"` and sloppy string forms behave.

## Component inventory and contracts (v1)

Grouped Blade names under two interchangeable namespaces: `<x-wire-bootstrap::input.text>` and the short
alias `<x-wb::input.text>` (alias prefix from config, registerable off).

| Component | Extra props beyond Field | Notes |
|---|---|---|
| `input.text` | `datalist` (array) | type text; datalist rendered when non-empty |
| `input.email`, `input.number`, `input.hidden` | number: `min`, `max`, `step` | thin type variants of text |
| `input.password` | `toggle` (bool, default true) | append eye button (Alpine) switching type |
| `input.textarea` | `rows`, `autosize` (bool, off, no lib — CSS `field-sizing` when available) | |
| `input.select` | `options` (array), `placeholder` row toggleable via `placeholder=''` | options as `key => label` or list of `['value','name']` maps — keep both Theme shapes; slot wins over `options` |
| `input.select-search` | forwards to a wire-tomselect component **when installed**, otherwise renders `input.select` with a note in debug | no hard dependency; the prop surface stays the select one |
| `input.checkbox` | `value`, `inline` | a real checkbox (not a switch) |
| `input.switch` | `inline` | `form-switch` |
| `input.checkbox-group`, `input.radio` | `options`, `inline` | group renders one error block for the set |
| `input.date` | `date`, `time`, `format`, `minDate`, `maxDate`, `inline`, `defaultDate`, `enableDates`, `disableDates`, `range` (bool) | flatpickr via the package Alpine component; keep the Theme's runtime `enable-dates-updated` re-feed as a documented Livewire event (`wire-bootstrap:date-options`); display format family defaults from config |
| `input.file` | `multiple`, `accept`, `preview` (bool, images) | plain Bootstrap file input + Livewire upload progress bar (`wire:loading` on `livewire-upload-progress`) |
| `input.color`, `input.range` | range: `min`, `max`, `step`, `showValue` | native inputs |
| `button` | `variant` (primary…link, default from config), `outline`, `size`, `icon` (class string), `label`/slot, `loading` (wire:target override) | keeps the Theme behaviour: when the button has `wire:click`/`wire:target`, auto-wire `wire:loading.attr="disabled"` + spinner; `type="button"` default, `type="submit"` opt-in |
| `card` | slots `title`, `toolbar`, `footer`; prop `bodyClass` | no pushed styles |
| `tabs` / `tab` | tabs: `default` (tab key), `resetOnModalOpen` (bool, default true when inside a wire-modals modal) | Alpine state; `tab` takes `key`, `title` (or `title` slot), `active` |
| `badge` | `variant`, `pill` | Bootstrap `text-bg-*`; `bg-label-*` stays a Vuexy/app concern |
| `alert` | `variant`, `dismissible`, `icon` | |
| `empty-state` | `title`, `description`, slot for action | simple centered block |

Deliberately **not** in v1 (document in the readme as planned): typeahead, editor (Quill), dropzone
multi-file, date-range as a distinct component (covered by `input.date range`), text-copy, spacing/level,
media (ERP-only forever).

## Browser layer (`resources/js/wire-bootstrap.js`)

**Bootstrap + Alpine only — this is a hard rule, not a preference.** The file contains nothing but Alpine
component registrations; there is no other JavaScript in the package.

- Registers Alpine data components on `alpine:init` (Livewire's bundled Alpine): `wbDatepicker` (flatpickr;
  reads `window.flatpickr` or an ESM import — decide in Phase 0 based on how the stub imports it),
  `wbPasswordToggle`, `wbThemeToggle`, `wbCopy` (reserved), `wbTabs`.
- No jQuery, no vanilla-JS DOM scripts, no `document.querySelector`/`addEventListener` outside an Alpine
  component, no `eval`, no inline event handlers in views (CSP rule, same as wire-modals); all behaviour
  attaches via Alpine directives (`x-data`, `x-on`, `x-show`, `$wire`) in the component views.
- Prefer Bootstrap's declarative `data-bs-*` attributes over any JS at all (alert dismiss, collapse,
  tooltips). Bootstrap's JS API is used through `window.bootstrap` only where no `data-bs-*` attribute can
  express the behaviour (none expected for v1 fields; tabs are Alpine-driven).
- The file is imported by the installer's `wire-bootstrap-setup.js`; apps that skip the installer import it
  manually (documented in the readme).

## Config `config/wire-bootstrap.php`

```php
return [
    'alias_prefix' => 'wb',            // second Blade component namespace; null disables
    'vite_entries' => ['resources/scss/app.scss', 'resources/js/app.js'],
    'defaults' => [
        'size' => null,                // null | 'sm' | 'lg'
        'floating' => false,
        'button_variant' => 'primary',
        'show_placeholder' => true,    // auto "Type {label}" placeholders
    ],
    'markers' => [
        'required' => true,            // render (*) on required fields
        'optional' => true,
    ],
    'date' => [
        'display_format' => 'd-M-Y',
        'display_format_time' => 'd-M-Y h:i K',
        'flatpickr' => [],             // extra default options merged into every picker
    ],
    'theme' => [
        'dark_mode' => true,           // theme-toggle + pre-paint snippet active
        'default' => 'light',          // 'light' | 'dark' | 'auto'
    ],
    'blaze' => true,                   // auto-opt package views into livewire/blaze when installed
];
```

Keep config static (no `env()`), like the sibling packages.

## Service provider

Mirror `WireTomselectServiceProvider` + `IReportsServiceProvider`: `register()` merges config; `boot()`
loads views and translations, registers `Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'wire-bootstrap')`
plus the same path under the alias prefix when configured *(verify double registration in Phase 0)*,
registers the `InstallCommand`, opts the views into Blaze when `livewire/blaze` is installed (see the
Performance section), and publishes under `runningInConsole()` with tags `wire-bootstrap-config`,
`wire-bootstrap-views`, `wire-bootstrap-lang`. Nested names come from the directory layout
(`components/input/text.blade.php` → `input.text`).

## Test matrix (Pest + Testbench, inside the package)

Field foundation (run against `input.text` as the representative, then thinner per-component tests):

- renders label / no label when null; required and optional markers; marker lang line overridable.
- id and name derived from `wire:model` (`user.name` → valid id, name `user.name`); explicit `name`/`id`
  win; two fields with dotted models that only differ by separator do not collide.
- error from the bag renders `invalid-feedback` + `is-invalid`, keyed by wire:model with modifiers stripped
  (`wire:model.live.debounce` still matches).
- attribute passthrough reaches the control, not the wrapper; `class` merges onto the control; declared
  props do not leak into HTML.
- placeholder: default from label, explicit, suppressed by `''`, disabled by config.
- `size`, `floating`, `prepend`/`append` (input-group wrapping), `help`, `readOnly`, `disabled`.

Per component: select option shapes (both Theme shapes + slot precedence), checkbox-group/radio option
rendering and single error block, password toggle markup, date renders flatpickr mount data with converted
enable/disable dates (port the Theme's date-conversion cases as the test fixtures), number min/max/step,
file multiple/accept, button variant/outline/size matrix + loading markup appears only with a wire target +
submit opt-in, card slots, tabs default/active and reset listener opt-out, badge/alert variants, layout
slots and title, theme-toggle markup per config.

Blaze (require-dev lane):

- with `livewire/blaze` installed and config `blaze => true`, the provider registers the package views for
  optimization and every foundation test above still passes (run the field foundation suite under Blaze);
  with `blaze => false` or the package absent, nothing is registered and rendering is unchanged.
- `FieldState` unit tests run without any rendering (pure PHP), covering derivation, markers, placeholder
  rules, boolean normalisation and error-key stripping.

Installer (unit tests on `Support\Installer` with temp dirs + one artisan feature test):

- fresh app: all files written, package.json merged (order preserved, existing versions kept), app.js gains
  the marker block once, vite config input extended.
- re-run: zero changes (byte-identical files); `--force` republishes stubs; marker block replaced not
  duplicated.
- unparseable vite.config: file untouched, manual instructions printed, exit code still 0.
- `--no-layout`, `--no-npm`, `--skip-build` honoured; existing `app.scss` not overwritten without `--force`.

## Browser scenarios (sandbox app, `tests/Browser/WireBootstrapTest.php`)

The sandbox gets `/forms` (`App\Livewire\Pages\FormsPlayground`) exercising every field bound to one
component with validation rules:

1. Submit empty → required fields show `invalid-feedback`, controls have `is-invalid`; fix and resubmit →
   feedback gone, success alert shown.
2. Date field opens flatpickr, picking a date updates the Livewire property (assert via a rendered echo);
   disabled dates not selectable.
3. Password toggle switches input type.
4. Select with both option shapes binds; changing the select updates a dependent echo.
5. Button with `wire:click` shows the spinner while a slow action runs and disables itself.
6. Theme toggle flips `data-bs-theme` and survives reload.
7. Tabs switch; when the playground's wire-modals modal reopens, tabs inside reset to the default.
8. `assertNoJavaScriptErrors` on every scenario.

## Later: Sylloo adoption (separate task, after 1.0.0; summarised for the future migration prompt)

- The ERP keeps `x-theme::input.*` names: each Theme input view becomes a thin wrapper rendering the
  wire-bootstrap component with ERP defaults (`lang()` labels, sizes). Zero edits in consumer blades —
  the same alias strategy as `WithDynamicComponent` in the wire-modals migration.
- `item-input/*`, `media`, `level`, menus and layouts stay ERP-owned.
- The installer is **not** run against Sylloo (Vuexy owns its asset pipeline); only the component library is
  adopted there. The installer is for new projects (desktop-pos, future apps, this sandbox).
- Button variant files collapse onto `<x-wb::button variant="danger">` wrappers; `waves-effect` is added by
  the ERP wrapper, not the package.
