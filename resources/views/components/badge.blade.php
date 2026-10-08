@props([
    'variant' => 'secondary',
    'pill' => false,
])

<span {{ $attributes->class(['badge', 'text-bg-'.$variant, 'rounded-pill' => $pill]) }}>{{ $slot }}</span>
