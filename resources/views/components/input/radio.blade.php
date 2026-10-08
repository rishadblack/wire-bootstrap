@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'required' => false,
    'optional' => false,
    'help' => null,
    'inline' => false,
    'wrapperClass' => null,
    'options' => null,
    'type' => 'radio',
])

@php($inline = \Rishadblack\WireBootstrap\Support\FieldState::boolean($inline))

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

@php($checkboxGroup = $type === 'checkbox')

<fieldset @class(['wb-field', $wrapperClass])>
    @if ($field->label)
        <legend class="form-label fs-6 mb-1">{{ $field->label }}@if ($field->requiredMarker) <span class="text-danger">{{ $field->requiredMarker }}</span>@elseif ($field->optionalMarker) <span class="text-muted">{{ $field->optionalMarker }}</span>@endif</legend>
    @endif

    @foreach (\Rishadblack\WireBootstrap\Support\Options::normalize($options) as $option)
        <div @class(['form-check', 'form-check-inline' => $inline])>
            <input
                type="{{ $checkboxGroup ? 'checkbox' : 'radio' }}"
                id="{{ $field->id }}-{{ $loop->index }}"
                @if ($field->name) name="{{ $field->name }}{{ $checkboxGroup ? '[]' : '' }}" @endif
                value="{{ $option['value'] }}"
                @if ($field->required && ! $checkboxGroup) required @endif
                {{ $attributes->class(['form-check-input', 'is-invalid' => $field->invalid]) }}
            />
            <label class="form-check-label" for="{{ $field->id }}-{{ $loop->index }}">{{ $option['label'] }}</label>
        </div>
    @endforeach

    @if ($field->invalid)
        <div class="invalid-feedback d-block">{{ $field->error }}</div>
    @endif
    @if ($field->help)
        <div class="form-text">{{ $field->help }}</div>
    @endif
</fieldset>
