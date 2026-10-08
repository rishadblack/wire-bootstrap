@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'required' => false,
    'optional' => false,
    'help' => null,
    'size' => null,
    'wrapperClass' => null,
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

<x-wire-bootstrap::field :field="$field" :wrapper-class="$wrapperClass">
    <input
        type="color"
        @if ($field->id) id="{{ $field->id }}" @endif
        @if ($field->name) name="{{ $field->name }}" @endif
        @if ($field->required) required @endif
        {{ $attributes->class([
            'form-control',
            'form-control-color',
            'form-control-sm' => $field->size === 'sm',
            'form-control-lg' => $field->size === 'lg',
            'is-invalid' => $field->invalid,
        ]) }}
    />
</x-wire-bootstrap::field>
