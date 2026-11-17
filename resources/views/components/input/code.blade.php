{{--
    Grouped code field: one box per group ("1234-5678-9012-3456" is 4 groups of 4).
    Typing moves on to the next box, Backspace in an empty box goes back, and a
    pasted code (with or without separators) fills every box. wire:model sits on
    the group (Alpine x-modelable) and receives the plain value ("1234567890123456"),
    or the separated one with `formatted`. Behaviour: Alpine `wbCode`.
--}}
@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'required' => false,
    'optional' => false,
    'help' => null,
    'size' => null,
    'wrapperClass' => null,
    'groups' => null,
    'length' => null,
    'separator' => null,
    'alphanumeric' => false,
    'formatted' => false,
    'disabled' => false,
    'autofocus' => false,
])

@php($field = \Rishadblack\WireBootstrap\Support\FieldState::resolve($attributes, [
    'label' => $label,
    'name' => $name,
    'id' => $id,
    'placeholder' => '',
    'required' => $required,
    'optional' => $optional,
    'help' => $help,
    'size' => $size,
    'floating' => false,
], $__env))

@php($lengths = \Rishadblack\WireBootstrap\Support\CodeOptions::groups($groups, $length))
@php($options = \Rishadblack\WireBootstrap\Support\CodeOptions::code($lengths, $separator, $alphanumeric, $formatted))
@php($disabled = \Rishadblack\WireBootstrap\Support\FieldState::boolean($disabled))
@php($autofocus = \Rishadblack\WireBootstrap\Support\FieldState::boolean($autofocus))
@php($baseId = $field->id ?? 'wb-code-'.substr(md5(implode(',', $lengths).$field->label), 0, 6))
@php($describedBy = trim(($field->invalid ? $baseId.'-error ' : '').($field->help ? $baseId.'-help' : '')))
@php($groupLabel = $field->label ?? __('wire-bootstrap::wire-bootstrap.code_label'))

<div @class(['wb-field', 'wb-code-field', $wrapperClass])>
    @if ($field->label)
        <label class="form-label" id="{{ $baseId }}-label" for="{{ $baseId }}">{{ $field->label }}@if ($field->requiredMarker) <span class="text-danger">{{ $field->requiredMarker }}</span>@elseif ($field->optionalMarker) <span class="text-muted">{{ $field->optionalMarker }}</span>@endif</label>
    @endif

    <div
        x-data="wbCode(@js($options))"
        x-modelable="value"
        role="group"
        dir="ltr"
        @if ($field->label) aria-labelledby="{{ $baseId }}-label" @else aria-label="{{ $groupLabel }}" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'wb-code',
            'wb-code-sm' => $field->size === 'sm',
            'wb-code-lg' => $field->size === 'lg',
            'is-invalid' => $field->invalid,
        ]) }}
    >
        @if ($field->name)
            <input type="hidden" name="{{ $field->name }}" x-bind:value="value" />
        @endif
        @foreach ($lengths as $index => $groupLength)
            <input
                type="text"
                id="{{ $index === 0 ? $baseId : $baseId.'-'.($index + 1) }}"
                x-ref="group{{ $index }}"
                inputmode="{{ $options['alphanumeric'] ? 'text' : 'numeric' }}"
                autocomplete="off"
                autocapitalize="{{ $options['alphanumeric'] ? 'characters' : 'off' }}"
                spellcheck="false"
                size="{{ $groupLength }}"
                style="--wb-code-length: {{ $groupLength }};"
                aria-label="{{ __('wire-bootstrap::wire-bootstrap.code_group', ['label' => $groupLabel, 'number' => $index + 1, 'total' => count($lengths)]) }}"
                @if ($field->required) aria-required="true" @endif
                @if ($field->invalid) aria-invalid="true" @endif
                @if ($disabled) disabled @endif
                @if ($autofocus && $index === 0) autofocus @endif
                x-on:focus="$event.target.select()"
                x-on:input="typed({{ $index }}, $event)"
                x-on:paste.prevent="pasted({{ $index }}, $event)"
                x-on:keydown="keydown({{ $index }}, $event)"
                @class([
                    'form-control',
                    'wb-code-group',
                    'form-control-sm' => $field->size === 'sm',
                    'form-control-lg' => $field->size === 'lg',
                    'is-invalid' => $field->invalid,
                ])
            />
            @if (! $loop->last && $options['separator'] !== '')
                <span class="wb-code-separator" aria-hidden="true">{{ $options['separator'] }}</span>
            @endif
        @endforeach
    </div>

    @if ($field->invalid)
        <div class="invalid-feedback d-block" id="{{ $baseId }}-error">{{ $field->error }}</div>
    @endif
    @if ($field->help)
        <div class="form-text" id="{{ $baseId }}-help">{{ $field->help }}</div>
    @endif
</div>
