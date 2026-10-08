@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->class(['text-center', 'text-body-secondary', 'py-5']) }}>
    @if (filled($title))
        <h5 class="mb-1">{{ $title }}</h5>
    @endif
    @if (filled($description))
        <p class="mb-3">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
