@props([
    'title' => null,
    'toolbar' => null,
    'footer' => null,
    'bodyClass' => null,
])

<div {{ $attributes->class(['card']) }}>
    @if (filled($title) || filled($toolbar))
        <div class="card-header d-flex align-items-center">
            @if (filled($title))
                <h5 class="card-title mb-0">{{ $title }}</h5>
            @endif
            @if (filled($toolbar))
                <div class="ms-auto">{{ $toolbar }}</div>
            @endif
        </div>
    @endif
    <div @class(['card-body', $bodyClass])>
        {{ $slot }}
    </div>
    @if (filled($footer))
        <div class="card-footer">{{ $footer }}</div>
    @endif
</div>
