@props([
    'src' => null,
    'alt' => '',
    'name' => null,
    'initials' => null,
    'icon' => null,
    'size' => 'md',
    'shape' => 'rounded',
])

@php
    // The fallback shows when there is no image or the image fails to load: initials (given, or from the name),
    // else an icon (its full class list, e.g. "ti ti-box"), else the slot.
    $letters = filled($initials) ? $initials : null;

    if ($letters === null && filled($name)) {
        $words = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = $words[0] ?? '';
        $last = count($words) > 1 ? $words[count($words) - 1] : '';
        $letters = mb_strtoupper(mb_substr($first, 0, 1).mb_substr($last, 0, 1));
    }

    $sizeClass = in_array($size, ['sm', 'md', 'lg'], true) ? 'wb-thumb-'.$size : null;
    $sizeStyle = is_numeric($size) ? '--wb-thumb-size: '.(int) $size.'px' : null;
@endphp

<span {{ $attributes->class([
        'wb-thumb',
        $sizeClass,
        'wb-thumb-circle' => $shape === 'circle',
        'wb-thumb-square' => $shape === 'square',
    ])->merge(array_filter([
        'style' => $sizeStyle,
        'role' => filled($alt) ? 'img' : null,
        'aria-label' => filled($alt) ? $alt : null,
        'aria-hidden' => filled($alt) ? null : 'true',
    ])) }}>
    @if (filled($letters))
        <span class="wb-thumb-initials">{{ $letters }}</span>
    @elseif (filled($icon))
        <i class="{{ $icon }}"></i>
    @else
        {{ $slot }}
    @endif
    @if (filled($src))
        <img src="{{ $src }}" alt="" loading="lazy" onerror="this.remove()">
    @endif
</span>
