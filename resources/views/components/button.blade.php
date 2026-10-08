@props([
    'variant' => null,
    'outline' => false,
    'size' => null,
    'icon' => null,
    'label' => null,
    'type' => 'button',
    'loading' => null,
])

@php($variant = $variant ?? config('wire-bootstrap.defaults.button_variant', 'primary'))
@php($outline = \Rishadblack\WireBootstrap\Support\FieldState::boolean($outline))
@php($target = $loading ?? $attributes->get('wire:target') ?? $attributes->get('wire:click'))

<button
    type="{{ $type }}"
    @if ($target)
        wire:loading.attr="disabled"
        @if (! $attributes->has('wire:target')) wire:target="{{ $target }}" @endif
    @endif
    {{ $attributes->class([
        'btn',
        ($outline ? 'btn-outline-' : 'btn-').$variant,
        'btn-sm' => $size === 'sm',
        'btn-lg' => $size === 'lg',
    ]) }}
>
    @if ($target)
        <span wire:loading.remove wire:target="{{ $target }}">@if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i> @endif{{ $label ?? $slot }}</span>
        <span wire:loading wire:target="{{ $target }}"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __('wire-bootstrap::wire-bootstrap.loading') }}</span>
    @else
        @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i> @endif{{ $label ?? $slot }}
    @endif
</button>
