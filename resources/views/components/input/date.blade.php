@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'placeholder' => null,
    'required' => false,
    'optional' => false,
    'help' => null,
    'size' => null,
    'wrapperClass' => null,
    'date' => true,
    'time' => false,
    'format' => null,
    'minDate' => null,
    'maxDate' => null,
    'inline' => false,
    'defaultDate' => null,
    'enableDates' => null,
    'disableDates' => null,
    'range' => false,
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
    'floating' => false,
], $__env))

@php($picker = \Rishadblack\WireBootstrap\Support\DateOptions::build([
    'date' => $date,
    'time' => $time,
    'format' => $format,
    'minDate' => $minDate,
    'maxDate' => $maxDate,
    'inline' => $inline,
    'defaultDate' => $defaultDate,
    'enableDates' => $enableDates,
    'disableDates' => $disableDates,
    'range' => $range,
]))

<x-wire-bootstrap::field :field="$field" :wrapper-class="$wrapperClass">
    <div wire:ignore wire:key="wb-date-{{ $field->id }}" x-data="wbDatepicker(@js($picker + ['model' => $field->model]))">
        <input
            type="text"
            x-ref="input"
            @if ($field->id) id="{{ $field->id }}" @endif
            @if ($field->name) name="{{ $field->name }}" @endif
            @if ($field->placeholder !== null) placeholder="{{ $field->placeholder }}" @endif
            @if ($field->required) aria-required="true" @endif
            {{ $attributes->class([
                'form-control',
                'form-control-sm' => $field->size === 'sm',
                'form-control-lg' => $field->size === 'lg',
                'is-invalid' => $field->invalid,
            ]) }}
        />
    </div>
</x-wire-bootstrap::field>
