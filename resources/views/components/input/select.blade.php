@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'placeholder' => null,
    'required' => false,
    'optional' => false,
    'help' => null,
    'size' => null,
    'floating' => null,
    'wrapperClass' => null,
    'prepend' => null,
    'append' => null,
    'options' => null,
])

@php($field = \Rishadblack\WireBootstrap\Support\FieldState::resolve($attributes, [
    'label' => $label,
    'name' => $name,
    'id' => $id,
    'placeholder' => is_string($placeholder)
        ? $placeholder
        : ($label !== null ? __('wire-bootstrap::wire-bootstrap.select_placeholder', ['label' => $label]) : null),
    'required' => $required,
    'optional' => $optional,
    'help' => $help,
    'size' => $size,
    'floating' => $floating,
], $__env))

@php($multiple = $attributes->has('multiple'))

<x-wire-bootstrap::field :field="$field" :wrapper-class="$wrapperClass" :prepend="$prepend" :append="$append">
    <select
        @if ($field->id) id="{{ $field->id }}" @endif
        @if ($field->name) name="{{ $field->name }}{{ $multiple ? '[]' : '' }}" @endif
        @if ($field->required) required @endif
        {{ $attributes->class([
            'form-select',
            'form-select-sm' => $field->size === 'sm',
            'form-select-lg' => $field->size === 'lg',
            'is-invalid' => $field->invalid,
        ]) }}
    >
        @if (! $multiple && $field->placeholder !== null)
            <option value="">{{ $field->placeholder }}</option>
        @endif
        @if ($options !== null)
            @foreach (\Rishadblack\WireBootstrap\Support\Options::normalize($options) as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>
</x-wire-bootstrap::field>
