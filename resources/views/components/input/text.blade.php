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
    'type' => 'text',
    'datalist' => null,
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

<x-wire-bootstrap::field :field="$field" :wrapper-class="$wrapperClass" :prepend="$prepend" :append="$append">
    <input
        type="{{ $type }}"
        @if ($field->id) id="{{ $field->id }}" @endif
        @if ($field->name) name="{{ $field->name }}" @endif
        @if ($field->placeholder !== null) placeholder="{{ $field->placeholder }}" @endif
        @if ($field->required) required @endif
        @if ($datalist) list="{{ $field->id }}-list" @endif
        {{ $attributes->class([
            'form-control',
            'form-control-sm' => $field->size === 'sm',
            'form-control-lg' => $field->size === 'lg',
            'is-invalid' => $field->invalid,
        ]) }}
    />
    @if ($datalist)
        <datalist id="{{ $field->id }}-list">
            @foreach (\Rishadblack\WireBootstrap\Support\Options::normalize($datalist) as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </datalist>
    @endif
</x-wire-bootstrap::field>
