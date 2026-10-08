---
name: wire-bootstrap-development
description: Use this skill when building UI with rishadblack/wire-bootstrap. Trigger when rendering or editing <x-wire-bootstrap::*> or <x-wb::*> Blade tags (input.text, input.select, input.date, input.editor, input.password, checkbox/switch/radio groups, button, card, tabs, badge, alert, empty-state, theme-toggle, layout), running or configuring php artisan wire-bootstrap:install, editing resources/scss/app.scss Bootstrap overrides or wire-bootstrap-setup.js, calling Rishadblack\WireBootstrap\Support\FieldState, Options, DateOptions or EditorOptions, adding a custom field component on the Field foundation, configuring config/wire-bootstrap.php (alias prefix, defaults, markers, date formats, theme, blaze), integrating livewire/blaze, or testing these components with Pest.
---

# Wire Bootstrap Development

Livewire-native Bootstrap 5.3 components. The readme in `vendor/rishadblack/wire-bootstrap/readme.md` is
the full reference; this skill is the working knowledge.

## Setup

- New app: `php artisan wire-bootstrap:install` (idempotent; `--force` republishes, `--no-layout`,
  `--no-npm`, `--skip-build`, `--no-blaze`). It manages `package.json`, `resources/scss/app.scss`,
  `resources/js/wire-bootstrap-setup.js`, a marker block in `app.js`, `vite.config.js`, the Livewire
  layout and the config.
- Existing Bootstrap app: import `vendor/rishadblack/wire-bootstrap/resources/js/wire-bootstrap.js` after
  whatever sets `window.bootstrap`; set `window.flatpickr` if you use `input.date`.
- Run `npm run build` after installer or JS changes.

## Fields

```blade
<x-wb::input.text label="Name" wire:model="name" required />
<x-wb::input.email label="Email" wire:model="email" prepend="@" />
<x-wb::input.select label="Country" wire:model="country" :options="['bd' => 'Bangladesh']" />
<x-wb::input.radio label="Plan" wire:model="plan" inline :options="['free' => 'Free', 'pro' => 'Pro']" />
<x-wb::input.date label="Joined" wire:model="joined_at" :max-date="now()->format('Y-m-d')" />
<x-wb::input.password label="Password" wire:model="password" help="Min 8 characters." />
<x-wb::input.file label="Photos" wire:model="photos" multiple accept="image/*" />
```

- `wire:model` drives everything: `name`, a collision-free `id` (dots sanitized + hash suffix), and the
  validation error key (modifiers like `.live.debounce` are fine).
- Common props: `label`, `name`, `id`, `placeholder` (`""` suppresses), `required`, `optional`, `help`,
  `size` (`sm`/`lg`), `floating`, `wrapper-class`, `prepend`/`append` (plain text auto-wraps in
  `input-group-text`; pass markup via `<x-slot:append>`).
- `required` renders a marker + `aria-required`, never the native attribute — the browser must not block
  `wire:submit` before server-side validation. Always validate in the component.
- Options shapes: `['key' => 'Label']` or `[['value' => 1, 'name' => 'Label']]`; `<option>` slot wins.
  `multiple` on select drops the placeholder row and appends `[]` to the name.
- `input.select-search`: put a wire-tomselect component in the slot and pass `name="bound_property"` so
  the label/error chrome targets the right key; without a slot it falls back to a plain select.

## Buttons, cards, tabs

```blade
<x-wb::button type="submit" loading="save">Save</x-wb::button>
<x-wb::button variant="danger" outline icon="bi bi-trash" wire:click="delete({{ $id }})">Delete</x-wb::button>

<x-wb::card title="Users">
    <x-slot:toolbar><x-wb::badge variant="info" pill>12</x-wb::badge></x-slot:toolbar>
    ...
    <x-slot:footer>...</x-slot:footer>
</x-wb::card>

<x-wb::tabs :items="['info' => 'Info', 'meta' => 'Meta']">
    <x-wb::tab key="info" active>...</x-wb::tab>
    <x-wb::tab key="meta">...</x-wb::tab>
</x-wb::tabs>
```

- A button with `loading`/`wire:click`/`wire:target` disables itself and shows a spinner during the
  request. Inside `<form wire:submit>` use `type="submit"` with `loading="method"` — do not also add
  `wire:click` (double fire).
- Tabs are Alpine state; inside a wire-modals modal they reset to the default tab on reopen
  (`:reset-on-modal-open="false"` to keep).

## Dates

Accepted date spellings everywhere (`min-date`, `enable-dates`, ...): `Y-m-d`, `d-M-Y`, `d-m-Y`,
`strtotime` input. `:enable-dates="['disable_all']"` disables all dates. Runtime update from PHP:
`$this->dispatch('wire-bootstrap:date-options', model: 'joined_at', enable: ['2026-06-25'])`.
Display format comes from `config('wire-bootstrap.date.display_format')` (flatpickr tokens).

## Rich text

```blade
<x-wb::input.editor label="Body" wire:model="body" toolbar="full" height="16rem" />
```

Quill 2 (snow) behind `wire:ignore`; the HTML binds through a hidden input, so `wire:model` works
unchanged. `toolbar` is `basic` (default), `full` or a Quill toolbar array; `read-only` and `height`
available. Needs `window.Quill` (the installer sets it up). The value is user HTML — sanitize before
echoing with `{!! !!}`. Without Quill the field degrades to a plain textarea.

## Layout and theme

Published layout wraps `<x-wire-bootstrap::layout>` (slots: `nav`, `footer`, `head`, `scripts`; stacks
`css`/`js`; `@vite` entries from `wire-bootstrap.vite_entries`). `<x-wb::theme-toggle />` flips
`data-bs-theme` and persists in `localStorage` (`wb-theme`); the layout's pre-paint snippet (the package's
single inline script) prevents the theme flash and renders only when `theme.dark_mode` is on.

## Rules

- Bootstrap `data-bs-*` + Alpine only: no jQuery, no loose `<script>` or DOM listeners outside Alpine.
- Components are anonymous with full `@props` contracts; logic lives in `Support\FieldState`,
  `Support\Options`, `Support\DateOptions` — never read `$attributes['foo']` as a prop in a view.
- New custom field: `@props` + `FieldState::resolve($attributes, [...], $__env)` +
  `<x-wire-bootstrap::field :field="$field">` around your control, control classes via
  `$attributes->class([...])` with `'is-invalid' => $field->invalid`.
- No CDN references or pushed `<style>` blocks in views; style via SCSS overrides or published views.
- With `livewire/blaze` installed the provider auto-opts the package views into the function compiler
  (`wire-bootstrap.blaze` config). Keep that in mind for published/overridden views too: fold nothing that
  reads `$errors` or other request state.

## Testing

```php
// Markup: components are plain Blade.
$html = Blade::render('<x-wb::input.text label="Name" wire:model="name" />');

// Behaviour: ordinary Livewire tests; errors render automatically.
Livewire::test(ProfileForm::class)->call('save')->assertHasErrors('email')->assertSee('is-invalid', false);
```

Browser checks: flatpickr instance lives on the input (`el._flatpickr`); set dates in tests with
`setDate(new Date(...), true)` (strings would be parsed with the display format).

## Troubleshooting

- Date field is a plain input → `window.flatpickr` missing or package JS not imported; re-run the
  installer and `npm run build`.
- Submit does nothing → form must use `wire:submit` + `type="submit"`; fields never block natively.
- Error not showing on a custom control → pass `name="property"` to `input.select-search` (error key).
- Sass `@import` deprecation noise → the installer's `vite.config.js` css block silences it.
- Theme flashes → `theme.dark_mode` must be on so the layout renders the pre-paint snippet.
