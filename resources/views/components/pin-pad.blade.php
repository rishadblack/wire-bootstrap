{{--
    PIN pad: masked dots, digit keys 1-9 and 0, a delete key and OK. The keyboard
    works too (digits, Backspace, Enter, Escape clears) while the pad has focus, or
    anywhere on the page with `global-keys`. A PIN has `min` to `max` digits. OK (or
    Enter) submits once `min` digits are in; reaching `max` submits by itself unless
    `:auto-submit="false"`. Submitting dispatches the `wb-pin-submit` browser event
    ({ value }) and, with `submit="method"`, calls that Livewire method. wire:model sits
    on the pad (Alpine x-modelable) and receives the digits. Behaviour: Alpine `wbPinPad`.
--}}
@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'help' => null,
    'size' => null,
    'wrapperClass' => null,
    'min' => null,
    'max' => null,
    'submit' => null,
    'autoSubmit' => true,
    'disabled' => false,
    'globalKeys' => false,
    'autofocus' => false,
])

@php($field = \Rishadblack\WireBootstrap\Support\FieldState::resolve($attributes, [
    'label' => $label,
    'name' => $name,
    'id' => $id,
    'placeholder' => '',
    'help' => $help,
    'size' => $size,
    'floating' => false,
], $__env))

@php($bounds = \Rishadblack\WireBootstrap\Support\CodeOptions::pinBounds($min, $max))
@php($min = $bounds[0])
@php($max = $bounds[1])
@php($disabled = \Rishadblack\WireBootstrap\Support\FieldState::boolean($disabled))
@php($globalKeys = \Rishadblack\WireBootstrap\Support\FieldState::boolean($globalKeys))
@php($baseId = $field->id ?? 'wb-pin-'.$min.'-'.$max)
@php($describedBy = trim(($field->invalid ? $baseId.'-error ' : '').($field->help ? $baseId.'-help' : '')))

<div @class(['wb-field', 'wb-pin-field', $wrapperClass])>
    <div
        id="{{ $baseId }}"
        x-data="wbPinPad(@js([
            'min' => $min,
            'max' => $max,
            'submit' => is_string($submit) && $submit !== '' ? $submit : null,
            'autoSubmit' => \Rishadblack\WireBootstrap\Support\FieldState::boolean($autoSubmit, true),
            'disabled' => $disabled,
            'autofocus' => \Rishadblack\WireBootstrap\Support\FieldState::boolean($autofocus),
            'status' => __('wire-bootstrap::wire-bootstrap.pin_entered', ['count' => ':count', 'max' => ':max']),
        ]))"
        x-modelable="value"
        role="group"
        tabindex="{{ $disabled ? '-1' : '0' }}"
        @if ($field->label) aria-labelledby="{{ $baseId }}-label" @else aria-label="{{ __('wire-bootstrap::wire-bootstrap.pin_label') }}" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        @if ($disabled) aria-disabled="true" @endif
        x-bind:aria-busy="busy ? 'true' : 'false'"
        @if ($globalKeys) x-on:keydown.window="keydown($event)" @else x-on:keydown="keydown($event)" @endif
        {{ $attributes->class([
            'wb-pin-pad',
            'wb-pin-pad-sm' => $field->size === 'sm',
            'wb-pin-pad-lg' => $field->size === 'lg',
            'is-invalid' => $field->invalid,
            'is-disabled' => $disabled,
        ]) }}
    >
        @if ($field->label)
            <div class="wb-pin-label" id="{{ $baseId }}-label">{{ $field->label }}</div>
        @endif

        @if ($field->name)
            <input type="hidden" name="{{ $field->name }}" x-bind:value="value ?? ''" />
        @endif

        <div class="wb-pin-dots" dir="ltr" aria-hidden="true">
            @for ($i = 1; $i <= $max; $i++)
                <span class="wb-pin-dot" x-show="{{ $i }} <= shown()" x-bind:class="{ 'is-filled': length() >= {{ $i }} }" @if ($i > $min) style="display: none;" @endif></span>
            @endfor
        </div>
        <span class="visually-hidden" role="status" aria-live="polite" x-text="status()"></span>

        @if ($field->invalid)
            <div class="wb-pin-error invalid-feedback d-block" id="{{ $baseId }}-error" role="alert">{{ $field->error }}</div>
        @endif

        <div class="wb-pin-keys" dir="ltr">
            @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $digit)
                <button type="button" class="wb-pin-key" x-on:click="press({{ $digit }})" @if ($disabled) disabled @endif>{{ $digit }}</button>
            @endforeach
            <button type="button" class="wb-pin-key wb-pin-key-action" x-on:click="erase()" aria-label="{{ __('wire-bootstrap::wire-bootstrap.pin_erase') }}" @if ($disabled) disabled @endif>
                {{-- Bootstrap Icons (MIT): backspace --}}
                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.83 5.146a.5.5 0 0 0 0 .708L7.975 8l-2.147 2.146a.5.5 0 0 0 .707.708l2.147-2.147 2.146 2.147a.5.5 0 0 0 .707-.708L9.39 8l2.146-2.146a.5.5 0 0 0-.707-.708L8.683 7.293 6.536 5.146a.5.5 0 0 0-.707 0z"/><path d="M13.683 1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-7.08a2 2 0 0 1-1.519-.698L.241 8.65a1 1 0 0 1 0-1.302L5.084 1.7A2 2 0 0 1 6.603 1zm-7.08 1a1 1 0 0 0-.76.35L1 8l4.844 5.65a1 1 0 0 0 .759.35h7.08a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"/></svg>
            </button>
            <button type="button" class="wb-pin-key" x-on:click="press(0)" @if ($disabled) disabled @endif>0</button>
            <button type="button" class="wb-pin-key wb-pin-key-submit" x-on:click="done()" aria-label="{{ __('wire-bootstrap::wire-bootstrap.pin_submit') }}" x-bind:aria-disabled="length() < {{ $min }} ? 'true' : 'false'" @if ($disabled) disabled @endif>
                {{-- Bootstrap Icons (MIT): check-lg --}}
                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/></svg>
            </button>
        </div>
    </div>

    @if ($field->help)
        <div class="form-text text-center" id="{{ $baseId }}-help">{{ $field->help }}</div>
    @endif
</div>
