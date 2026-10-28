{{-- Props are declared here and passed on raw: a bound value left in $attributes arrives already escaped. --}}
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
])

<x-wire-bootstrap::input.checkbox switch :label="$label" :name="$name" :id="$id" :required="$required" :optional="$optional" :help="$help" :inline="$inline" :value="$value" :wrapper-class="$wrapperClass" {{ $attributes }}>{{ $slot }}</x-wire-bootstrap::input.checkbox>
