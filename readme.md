# wire-bootstrap

> Livewire-native Bootstrap 5.3 UI: one artisan command sets up Bootstrap, Vite and a base layout in a
> fresh Laravel app, and every form field is a single Blade tag that brings its own label, `wire:model`
> binding, ids, validation errors and loading states — restyle it per project with SCSS variables and
> published views, without owning the plumbing.

```blade
<x-wb::input.text label="Name" wire:model="name" required />
```

renders the label with a required marker, derives the input `id` and `name` from `wire:model`, shows the
field's validation error with Bootstrap's `is-invalid` / `invalid-feedback` styling, and builds a
placeholder from the label — ten lines of Bootstrap form markup in one tag.

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [What the installer does](#what-the-installer-does)
- [Manual installation](#manual-installation)
- [Your first form](#your-first-form)
- [Field reference](#field-reference)
- [Components](#components)
- [Layout and dark mode](#layout-and-dark-mode)
- [Performance (Blaze)](#performance-blaze)
- [Configuration reference](#configuration-reference)
- [Theming](#theming)
- [Extending](#extending)
- [Sibling packages](#sibling-packages)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [License](#license)

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.3+ |
| Laravel | 11, 12 or 13 |
| Livewire | 3 or 4 |
| Bootstrap | 5.3 (installed for you by the installer) |
| livewire/blaze | optional, auto-detected (90%+ faster component rendering) |

## Installation

```bash
composer require rishadblack/wire-bootstrap
php artisan wire-bootstrap:install
```

That is the whole setup for a fresh Laravel + Livewire app. Flags:

| Flag | Effect |
|---|---|
| `--no-npm` | edit `package.json` but do not run `npm install` |
| `--skip-build` | do not run `npm run build` at the end |
| `--no-layout` | do not publish the app layout |
| `--no-blaze` | do not `composer require livewire/blaze` |
| `--force` | overwrite files a previous run published |

The command is idempotent: a second run changes nothing, and files it cannot safely edit are left
untouched with the manual snippet printed instead.

## What the installer does

1. Adds `bootstrap`, `@popperjs/core` and `flatpickr` (and `sass` as a dev dependency) to `package.json`
   without touching existing entries, then runs `npm install`.
2. Writes `resources/scss/app.scss`: a documented variable-override section, the Bootstrap source import,
   and the styles the installed `wire-*` packages need.
3. Writes `resources/js/wire-bootstrap-setup.js` (managed — regenerated on re-runs): exposes
   `window.bootstrap` and `window.flatpickr`, imports this package's Alpine components and, when
   installed, the wire-tomselect and wire-modals scripts. A marker block in `resources/js/app.js` imports
   it.
4. Extends `vite.config.js`: adds the SCSS entry to the `laravel()` input and silences dart-sass's
   `@import` deprecation (Bootstrap 5.3 still uses `@import`; Bootstrap 6 moves to `@use`).
5. Publishes the app layout to wherever your Livewire config points (`component_layout` on Livewire 4,
   `layout` on Livewire 3) — a thin wrapper around `<x-wire-bootstrap::layout>`.
6. Publishes `config/wire-bootstrap.php` and offers `livewire/blaze`.

## Manual installation

Already have Bootstrap 5.3 on `window.bootstrap`? Skip the installer entirely — the components only need
their Alpine layer:

```js
// after the import that sets window.bootstrap
import flatpickr from 'flatpickr';
window.flatpickr = flatpickr; // only needed for <x-wb::input.date>
import '../../vendor/rishadblack/wire-bootstrap/resources/js/wire-bootstrap.js';
```

## Your first form

```blade
<form wire:submit="save">
    <x-wb::card title="Profile">
        <x-wb::input.text label="Name" wire:model="name" required />
        <x-wb::input.email label="Email" wire:model="email" required prepend="@" />
        <x-wb::input.select label="Country" wire:model="country" :options="['bd' => 'Bangladesh', 'fi' => 'Finland']" />
        <x-wb::input.date label="Joined at" wire:model="joined_at" :max-date="now()->format('Y-m-d')" />
        <x-wb::input.checkbox label="I agree to the terms" wire:model="agree" required />

        <x-slot:footer>
            <x-wb::button type="submit" loading="save">Save</x-wb::button>
        </x-slot:footer>
    </x-wb::card>
</form>
```

`$this->validate()` errors appear under each field automatically; the save button disables itself and
shows a spinner while the request runs. Every component is available under both namespaces:
`<x-wire-bootstrap::input.text>` and the configurable short alias `<x-wb::input.text>`.

## Field reference

Props shared by every field:

| Prop | Default | Meaning |
|---|---|---|
| `label` | — | label text; no label element when omitted |
| `name` / `id` | derived from `wire:model` | dotted models get a sanitized, collision-free id |
| `placeholder` | `Type {label}` | pass `placeholder=""` to suppress |
| `required` / `optional` | false | marker next to the label (`(*)` / `(Optional)`, from the lang file) + `aria-required` |
| `help` | — | muted `form-text` under the field |
| `size` | config | `sm` or `lg` |
| `floating` | config | Bootstrap floating label (text-like fields) |
| `wrapper-class` | — | classes for the outer `<div>` |
| `prepend` / `append` | — | input group; plain text is wrapped in `input-group-text`, markup (slot) is passed through |

Note on `required`: the native HTML attribute would let the browser block the submit before Livewire's
server-side validation runs, so fields render `aria-required="true"` and the visual marker instead —
validate in the component.

Extra props per field:

| Field | Extra props |
|---|---|
| `input.text` | `type`, `datalist` (array) |
| `input.email`, `input.number`, `input.hidden` | thin variants of text (`min`/`max`/`step` pass through as attributes) |
| `input.password` | `toggle` (eye button, default on) |
| `input.textarea` | `rows` |
| `input.select` | `options` (`key => label` or `[['value' => ..., 'name' => ...]]`), `<option>` slot, `multiple` (drops the placeholder, appends `[]` to the name) |
| `input.select-search` | field chrome around a custom control in the slot (wire-tomselect), plain select fallback without one |
| `input.checkbox` / `input.switch` | `value`, `inline` |
| `input.radio` / `input.checkbox-group` | `options`, `inline`; one error block for the group |
| `input.date` | `date`, `time`, `format`, `min-date`, `max-date`, `inline`, `default-date`, `enable-dates`, `disable-dates`, `range` |
| `input.file` | `multiple`, `accept`; Livewire upload progress bar built in |
| `input.color`, `input.range` | range: `min`, `max`, `step`, `show-value` |

Any other attribute (`wire:model` modifiers, `data-*`, `maxlength`, `disabled`, `readonly`, …) passes
through to the control element, with `class` merged.

### Dates

`input.date` mounts flatpickr behind `wire:ignore` and keeps `wire:model` in sync. Dates you pass
(`min-date`, `enable-dates`, …) may be `Y-m-d`, `d-M-Y`, `d-m-Y` or anything `strtotime` understands.
Pass `['disable_all']` as `enable-dates` to disable every date. Update enabled dates at runtime from the
component:

```php
$this->dispatch('wire-bootstrap:date-options', model: 'joined_at', enable: ['2026-06-25', '2026-06-26']);
```

## Components

- **`button`** — `variant` (default from config), `outline`, `size`, `icon` (class string), `type`
  (default `button`), `loading` (a `wire:target`). With `wire:click` or `loading`, the button disables
  itself and swaps in a spinner while the action runs.
- **`card`** — `title`, `toolbar` slot (right side of the header), `footer` slot, `body-class`.
- **`tabs` / `tab`** — `:items="['key' => 'Title']"` generates the nav (or use the `nav` slot);
  `<x-wb::tab key="..." active>` panes; Alpine-driven, and inside a wire-modals modal the tabs reset to
  the default when the modal reopens (`:reset-on-modal-open="false"` to keep).
- **`badge`** — `variant`, `pill`.
- **`alert`** — `variant`, `dismissible` (Bootstrap's `data-bs-dismiss`, no JS needed).
- **`empty-state`** — `title`, `description`, action slot.
- **`theme-toggle`** — light/dark button (`with-auto` adds an auto step), persists in `localStorage`.
- **`layout`** — see below.

## Layout and dark mode

```blade
<x-wire-bootstrap::layout :title="$title ?? null" class="bg-body-tertiary">
    <x-slot:nav><nav>…your navbar…</nav></x-slot:nav>
    {{ $slot }}
    <x-slot:footer>…</x-slot:footer>
</x-wire-bootstrap::layout>
```

The layout renders the HTML skeleton: meta, `<title>`, `@vite` (entries from config), `@stack('css')` /
`@stack('js')`, `head` and `scripts` slots, and body attributes pass through. It ships **no navbar or
sidebar** — navigation is your app's.

With `theme.dark_mode` on, a tiny pre-paint snippet applies the visitor's stored `data-bs-theme` before
first paint (no flash). That snippet is the package's single inline script; under a strict CSP, publish
the view and add your nonce to it, or disable dark mode.

## Performance (Blaze)

Install [`livewire/blaze`](https://github.com/livewire/blaze) (the installer offers it) and wire-bootstrap
automatically opts its component views into Blaze's function compiler — Blade component overhead drops by
90%+ on component-heavy pages. No configuration; set `wire-bootstrap.blaze => false` to opt out. Every
component here is an anonymous component specifically so Blaze can compile it. Verify with
`Blaze::debug()`. Opt your own components in with `Blaze::optimize()->in(resource_path('views/components'))`.

## Configuration reference

`php artisan vendor:publish --tag=wire-bootstrap-config`

| Key | Default | Meaning |
|---|---|---|
| `alias_prefix` | `wb` | second component namespace; `null` disables |
| `vite_entries` | scss + js | what the layout passes to `@vite` |
| `defaults.size` | `null` | global field size (`sm` / `lg`) |
| `defaults.floating` | `false` | floating labels by default |
| `defaults.button_variant` | `primary` | |
| `defaults.show_placeholder` | `true` | auto `Type {label}` placeholders |
| `markers.required` / `markers.optional` | `true` | label markers |
| `date.display_format` / `display_format_time` | `d-M-Y` / `d-M-Y h:i K` | flatpickr display formats |
| `date.flatpickr` | `[]` | extra options merged into every picker |
| `theme.dark_mode` | `true` | theme toggle + pre-paint snippet |
| `theme.default` | `light` | `light` / `dark` / `auto` |
| `blaze` | `true` | auto-opt views into Blaze when installed |

## Theming

1. **SCSS variables** — the installer's `resources/scss/app.scss` has an overrides section above the
   Bootstrap import; everything in the package is plain Bootstrap 5.3 classes and CSS variables, so your
   overrides restyle it all.
2. **Lang lines** — `php artisan vendor:publish --tag=wire-bootstrap-lang` to change the markers,
   placeholder pattern and loading text (or translate them).
3. **Views** — `php artisan vendor:publish --tag=wire-bootstrap-views` for full markup control.

## Extending

A new field is one Blade file reusing the foundation:

```blade
{{-- resources/views/components/input/slug.blade.php (your app) --}}
@props(['label' => null, 'name' => null, 'id' => null, 'required' => false, 'optional' => false, 'help' => null, 'size' => null])

@php($field = \Rishadblack\WireBootstrap\Support\FieldState::resolve($attributes, [
    'label' => $label, 'name' => $name, 'id' => $id, 'required' => $required,
    'optional' => $optional, 'help' => $help, 'size' => $size, 'floating' => false,
], $__env))

<x-wire-bootstrap::field :field="$field">
    <input type="text" id="{{ $field->id }}" name="{{ $field->name }}" pattern="[a-z0-9-]+"
        {{ $attributes->class(['form-control', 'is-invalid' => $field->invalid]) }} />
</x-wire-bootstrap::field>
```

`FieldState` gives you the derived id/name, the error state, markers and placeholder;
`<x-wire-bootstrap::field>` renders the chrome.

## Sibling packages

The installer detects and wires these automatically:

- [rishadblack/wire-tomselect](https://github.com/rishadblack/wire-tomselect) — searchable dropdowns;
  pair with `<x-wb::input.select-search>` for the field chrome.
- [rishadblack/wire-modals](https://github.com/rishadblack/wire-modals) — on-demand Livewire components in
  Bootstrap modals; tabs inside a modal reset when it reopens.
- [rishadblack/i-reports](https://github.com/rishadblack/i-reports) — paginated, exportable reports in the
  same Bootstrap 5.3 look.

## Testing

```php
it('shows the validation error', function () {
    Livewire::test(ProfileForm::class)
        ->call('save')
        ->assertHasErrors('email')
        ->assertSee('is-invalid', false);
});
```

Fields are plain Blade components — `Blade::render('<x-wb::input.text ... />')` works for markup
assertions, and `Livewire::test()` covers binding and validation.

## Troubleshooting

- **"wbDatepicker is not defined" / date field is a plain input** — the package JS is not loaded, or
  flatpickr is missing from the bundle. Re-run `wire-bootstrap:install`, then `npm run build`.
- **Sass deprecation warnings about `@import`** — expected with Bootstrap 5.3; the installer silences
  them via `css.preprocessorOptions.scss` in `vite.config.js`. Re-run the installer or copy that block.
- **Submit does nothing** — make sure the form uses `wire:submit` and the button `type="submit"`. The
  fields themselves never block submission (no native `required`).
- **Errors not showing** — the error key is the `wire:model` path; with a custom control and
  `input.select-search`, pass `name="your_property"` so the chrome knows which error to show.
- **Dark mode flashes or is stuck** — the stored theme lives in `localStorage` under `wb-theme`; the
  pre-paint snippet only renders when `theme.dark_mode` is on.

## License

MIT. See [license.md](license.md).
