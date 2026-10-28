{{-- Props are declared here and passed on raw: a bound value left in $attributes arrives already escaped. --}}
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
])

<x-wire-bootstrap::input.radio type="checkbox" :label="$label" :name="$name" :id="$id" :required="$required" :optional="$optional" :help="$help" :inline="$inline" :wrapper-class="$wrapperClass" :options="$options" {{ $attributes }}>{{ $slot }}</x-wire-bootstrap::input.radio>
