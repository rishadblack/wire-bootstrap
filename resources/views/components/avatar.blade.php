@props([
    'src' => null,
    'name' => null,
    'initials' => null,
    'icon' => null,
    'alt' => null,
    'size' => 'md',
    'shape' => 'circle',
])

{{-- A person's picture: a round thumb whose fallback is the initials of the name. --}}
<x-wire-bootstrap::thumb :src="$src" :name="$name" :initials="$initials" :icon="$icon" :alt="$alt ?? $name ?? ''"
    :size="$size" :shape="$shape" {{ $attributes }}>{{ $slot }}</x-wire-bootstrap::thumb>
