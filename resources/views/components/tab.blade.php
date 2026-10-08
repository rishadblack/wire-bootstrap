@props([
    'key',
    'active' => false,
])

@php($active = \Rishadblack\WireBootstrap\Support\FieldState::boolean($active))

<div
    @class(['tab-pane', 'active show' => $active])
    x-bind:class="{ 'active show': isActive(@js($key)) }"
    role="tabpanel"
    {{ $attributes }}
>{{ $slot }}</div>
