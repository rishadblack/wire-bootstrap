@props([
    'name' => null,
    'id' => null,
])

@php($field = \Rishadblack\WireBootstrap\Support\FieldState::resolve($attributes, [
    'name' => $name,
    'id' => $id,
], $__env))

<input
    type="hidden"
    @if ($field->id) id="{{ $field->id }}" @endif
    @if ($field->name) name="{{ $field->name }}" @endif
    {{ $attributes }}
/>
