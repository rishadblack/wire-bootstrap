@props([
    'items' => null,
    'default' => null,
    'resetOnModalOpen' => true,
    'nav' => null,
])

@php($normalizedItems = \Rishadblack\WireBootstrap\Support\Options::normalize($items))
@php($default ??= $normalizedItems[0]['value'] ?? null)
@php($resetOnModalOpen = \Rishadblack\WireBootstrap\Support\FieldState::boolean($resetOnModalOpen, true))

<div
    x-data="wbTabs(@js(['default' => $default, 'resetOnModalOpen' => $resetOnModalOpen]))"
    x-on:wire-modals:opened.window="onModalOpened($event)"
    {{ $attributes }}
>
    <ul class="nav nav-tabs" role="tablist">
        @if ($normalizedItems !== [])
            @foreach ($normalizedItems as $item)
                <li class="nav-item" role="presentation">
                    <button
                        type="button"
                        @class(['nav-link', 'active' => $item['value'] === $default])
                        x-bind:class="{ active: isActive(@js($item['value'])) }"
                        x-on:click="select(@js($item['value']))"
                        role="tab"
                    >{{ $item['label'] }}</button>
                </li>
            @endforeach
        @else
            {{ $nav }}
        @endif
    </ul>
    <div class="tab-content">
        {{ $slot }}
    </div>
</div>
