{{--
    Rich text editor (Quill 2, "snow" theme). The wire:model binding sits on a
    hidden input; the Alpine component writes the HTML there on every edit.
    The value is user-supplied HTML: sanitize it before echoing with {!! !!}.
--}}
@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'placeholder' => null,
    'required' => false,
    'optional' => false,
    'help' => null,
    'wrapperClass' => null,
    'toolbar' => 'basic',
    'height' => '10rem',
    'readOnly' => false,
])

@php($field = \Rishadblack\WireBootstrap\Support\FieldState::resolve($attributes, [
    'label' => $label,
    'name' => $name,
    'id' => $id,
    'placeholder' => $placeholder,
    'required' => $required,
    'optional' => $optional,
    'help' => $help,
    'floating' => false,
], $__env))

<div @class(['wb-field', $wrapperClass])>
    @if ($field->label)
        <label class="form-label" @if ($field->id) for="{{ $field->id }}" @endif>{{ $field->label }}@if ($field->requiredMarker) <span class="text-danger">{{ $field->requiredMarker }}</span>@elseif ($field->optionalMarker) <span class="text-muted">{{ $field->optionalMarker }}</span>@endif</label>
    @endif

    <div
        wire:ignore
        wire:key="wb-editor-{{ $field->id }}"
        x-data="wbEditor(@js([
            'placeholder' => $field->placeholder,
            'toolbar' => \Rishadblack\WireBootstrap\Support\EditorOptions::toolbar($toolbar),
            'readOnly' => \Rishadblack\WireBootstrap\Support\FieldState::boolean($readOnly),
        ]))"
        @class(['wb-editor', 'is-invalid' => $field->invalid])
    >
        <input
            type="hidden"
            x-ref="input"
            @if ($field->id) id="{{ $field->id }}" @endif
            @if ($field->name) name="{{ $field->name }}" @endif
            @if ($field->required) aria-required="true" @endif
            {{ $attributes }}
        />
        <div x-ref="editor" class="d-none bg-body" style="min-height: {{ $height }};"></div>
        <textarea
            x-ref="fallback"
            class="form-control d-none"
            rows="4"
            @if ($field->placeholder !== null) placeholder="{{ $field->placeholder }}" @endif
            x-on:input="$refs.input.value = $event.target.value; $refs.input.dispatchEvent(new Event('input', { bubbles: true }))"
        ></textarea>
    </div>

    @if ($field->invalid)
        <div class="invalid-feedback d-block">{{ $field->error }}</div>
    @endif
    @if ($field->help)
        <div class="form-text">{{ $field->help }}</div>
    @endif
</div>
