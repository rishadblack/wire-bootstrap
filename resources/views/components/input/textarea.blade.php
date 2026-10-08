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
    'rows' => 3,
])

@php($field = \Rishadblack\WireBootstrap\Support\FieldState::resolve($attributes, [
    'label' => $label,
    'name' => $name,
    'id' => $id,
    'placeholder' => $placeholder,
    'required' => $required,
    'optional' => $optional,
    'help' => $help,
    'size' => $size,
    'floating' => $floating,
], $__env))

<x-wire-bootstrap::field :field="$field" :wrapper-class="$wrapperClass">
    <textarea
        @if ($field->id) id="{{ $field->id }}" @endif
        @if ($field->name) name="{{ $field->name }}" @endif
        @if ($field->placeholder !== null) placeholder="{{ $field->placeholder }}" @endif
        @if ($field->required) required @endif
        rows="{{ $rows }}"
        {{ $attributes->class([
            'form-control',
            'form-control-sm' => $field->size === 'sm',
            'form-control-lg' => $field->size === 'lg',
            'is-invalid' => $field->invalid,
        ]) }}
    >{{ $slot }}</textarea>
</x-wire-bootstrap::field>
