@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'required' => false,
    'optional' => false,
    'help' => null,
    'wrapperClass' => null,
    'min' => null,
    'max' => null,
    'step' => null,
    'showValue' => false,
])

@php($showValue = \Rishadblack\WireBootstrap\Support\FieldState::boolean($showValue))

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

<x-wire-bootstrap::field :field="$field" :wrapper-class="$wrapperClass">
    <div @if ($showValue) x-data="{ value: '' }" x-init="value = $refs.range.value" @endif>
        <input
            type="range"
            x-ref="range"
            @if ($showValue) x-on:input="value = $refs.range.value" @endif
            @if ($field->id) id="{{ $field->id }}" @endif
            @if ($field->name) name="{{ $field->name }}" @endif
            @if ($min !== null) min="{{ $min }}" @endif
            @if ($max !== null) max="{{ $max }}" @endif
            @if ($step !== null) step="{{ $step }}" @endif
            {{ $attributes->class(['form-range', 'is-invalid' => $field->invalid]) }}
        />
        @if ($showValue)
            <output class="form-text" for="{{ $field->id }}" x-text="value"></output>
        @endif
    </div>
</x-wire-bootstrap::field>
