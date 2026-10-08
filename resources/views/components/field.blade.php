{{-- Shared field wrapper: label, control slot, input groups, floating labels, error, help. --}}
@props([
    'field',
    'wrapperClass' => null,
    'prepend' => null,
    'append' => null,
])

@php($hasGroup = filled($prepend) || filled($append))

<div @class(['wb-field', $wrapperClass])>
    @if ($field->label && ! $field->floating)
        <label class="form-label" for="{{ $field->id }}">{{ $field->label }}@if ($field->requiredMarker) <span class="text-danger">{{ $field->requiredMarker }}</span>@elseif ($field->optionalMarker) <span class="text-muted">{{ $field->optionalMarker }}</span>@endif</label>
    @endif

    @if ($hasGroup)
        <div @class(['input-group', 'has-validation', 'input-group-sm' => $field->size === 'sm', 'input-group-lg' => $field->size === 'lg'])>
            @if (filled($prepend))
                @if (str_contains((string) $prepend, '<')){{ $prepend }}@else<span class="input-group-text">{{ (string) $prepend }}</span>@endif
            @endif
            {{ $slot }}
            @if (filled($append))
                @if (str_contains((string) $append, '<')){{ $append }}@else<span class="input-group-text">{{ (string) $append }}</span>@endif
            @endif
            @if ($field->invalid)
                <div class="invalid-feedback">{{ $field->error }}</div>
            @endif
        </div>
    @elseif ($field->floating)
        <div class="form-floating">
            {{ $slot }}
            <label for="{{ $field->id }}">{{ $field->label }}@if ($field->requiredMarker) <span class="text-danger">{{ $field->requiredMarker }}</span>@elseif ($field->optionalMarker) <span class="text-muted">{{ $field->optionalMarker }}</span>@endif</label>
            @if ($field->invalid)
                <div class="invalid-feedback">{{ $field->error }}</div>
            @endif
        </div>
    @else
        {{ $slot }}
        @if ($field->invalid)
            <div class="invalid-feedback">{{ $field->error }}</div>
        @endif
    @endif

    @if ($field->help)
        <div class="form-text">{{ $field->help }}</div>
    @endif
</div>
