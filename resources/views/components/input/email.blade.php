{{-- Props are declared here and passed on raw: a bound value left in $attributes arrives already escaped. --}}
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
    'datalist' => null,
])

<x-wire-bootstrap::input.text type="email" :label="$label" :name="$name" :id="$id" :placeholder="$placeholder" :required="$required" :optional="$optional" :help="$help" :size="$size" :floating="$floating" :wrapper-class="$wrapperClass" :prepend="$prepend" :append="$append" :datalist="$datalist" {{ $attributes }}>{{ $slot }}</x-wire-bootstrap::input.text>
