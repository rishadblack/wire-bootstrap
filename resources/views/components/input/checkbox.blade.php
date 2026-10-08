@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'required' => false,
    'optional' => false,
    'help' => null,
    'inline' => false,
    'value' => null,
    'wrapperClass' => null,
    'switch' => false,
])

@php($inline = \Rishadblack\WireBootstrap\Support\FieldState::boolean($inline))
@php($switch = \Rishadblack\WireBootstrap\Support\FieldState::boolean($switch))

@php($field = \Rishadblack\WireBootstrap\Support\FieldState::resolve($attributes, [
    'label' => $label,
    'name' => $name,
    'id' => $id,
    'placeholder' => '',
    'required' => $required,
    'optional' => $optional,
    'help' => $help,
    'floating' => false,
], $__env))

<div @class(['form-check', 'form-switch' => $switch, 'form-check-inline' => $inline, $wrapperClass])>
    <input
        type="checkbox"
        @if ($switch) role="switch" @endif
        @if ($field->id) id="{{ $field->id }}" @endif
        @if ($field->name) name="{{ $field->name }}" @endif
        @if ($value !== null) value="{{ $value }}" @endif
        @if ($field->required) aria-required="true" @endif
        {{ $attributes->class(['form-check-input', 'is-invalid' => $field->invalid]) }}
    />
    @if ($field->label)
        <label class="form-check-label" for="{{ $field->id }}">{{ $field->label }}@if ($field->requiredMarker) <span class="text-danger">{{ $field->requiredMarker }}</span>@elseif ($field->optionalMarker) <span class="text-muted">{{ $field->optionalMarker }}</span>@endif</label>
    @endif
    @if ($field->invalid)
        <div class="invalid-feedback">{{ $field->error }}</div>
    @endif
    @if ($field->help)
        <div class="form-text">{{ $field->help }}</div>
    @endif
</div>
