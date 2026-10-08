@props([
    'variant' => 'info',
    'dismissible' => false,
])

@php($dismissible = \Rishadblack\WireBootstrap\Support\FieldState::boolean($dismissible))

<div {{ $attributes->class(['alert', 'alert-'.$variant, 'alert-dismissible fade show' => $dismissible]) }} role="alert">
    {{ $slot }}
    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
