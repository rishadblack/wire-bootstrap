<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blade component alias prefix
    |--------------------------------------------------------------------------
    |
    | Every component is available as <x-wire-bootstrap::input.text> and, with
    | this alias, also as <x-wb::input.text>. Set to null to disable the alias.
    |
    */

    'alias_prefix' => 'wb',

    /*
    |--------------------------------------------------------------------------
    | Vite entry points
    |--------------------------------------------------------------------------
    |
    | What the <x-wire-bootstrap::layout> component passes to @vite. The
    | installer writes resources/scss/app.scss and keeps resources/js/app.js.
    |
    */

    'vite_entries' => ['resources/scss/app.scss', 'resources/js/app.js'],

    /*
    |--------------------------------------------------------------------------
    | Field defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'size' => null,                // null | 'sm' | 'lg'
        'floating' => false,           // Bootstrap floating labels by default
        'button_variant' => 'primary',
        'show_placeholder' => true,    // auto "Type {label}" placeholders
    ],

    /*
    |--------------------------------------------------------------------------
    | Label markers
    |--------------------------------------------------------------------------
    */

    'markers' => [
        'required' => true,            // render the required marker on required fields
        'optional' => true,            // render the optional marker on optional fields
    ],

    /*
    |--------------------------------------------------------------------------
    | Date field
    |--------------------------------------------------------------------------
    |
    | Display formats use flatpickr tokens (d-M-Y => 08-Oct-2026). The
    | "flatpickr" array is merged into every picker's options.
    |
    */

    'date' => [
        'display_format' => 'd-M-Y',
        'display_format_time' => 'd-M-Y h:i K',
        'flatpickr' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme (dark mode)
    |--------------------------------------------------------------------------
    |
    | When dark_mode is on, the layout renders a pre-paint snippet that applies
    | the persisted data-bs-theme before first paint, and the theme-toggle
    | component is available. "default" is used when the visitor never chose.
    |
    */

    'theme' => [
        'dark_mode' => true,
        'default' => 'light',          // 'light' | 'dark' | 'auto'
    ],

    /*
    |--------------------------------------------------------------------------
    | Blaze
    |--------------------------------------------------------------------------
    |
    | When livewire/blaze is installed and this is true, the package opts its
    | own component views into Blaze's function compiler automatically.
    |
    */

    'blaze' => true,

];
