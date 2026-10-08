{{--
    Field chrome for a searchable dropdown. Put a wire-tomselect Livewire
    component (or any custom control) in the slot and this wraps it with the
    package's label, error and help rendering; with no slot it falls back to a
    plain <x-wire-bootstrap::input.select> using the same props.
--}}
@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'required' => false,
    'optional' => false,
    'help' => null,
    'wrapperClass' => null,
    'options' => null,
])

@if (trim($slot) !== '')
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

    <div @class(['wb-field', $wrapperClass])>
        @if ($field->label)
            <label class="form-label" @if ($field->id) for="{{ $field->id }}" @endif>{{ $field->label }}@if ($field->requiredMarker) <span class="text-danger">{{ $field->requiredMarker }}</span>@elseif ($field->optionalMarker) <span class="text-muted">{{ $field->optionalMarker }}</span>@endif</label>
        @endif
        {{ $slot }}
        @if ($field->invalid)
            <div class="invalid-feedback d-block">{{ $field->error }}</div>
        @endif
        @if ($field->help)
            <div class="form-text">{{ $field->help }}</div>
        @endif
    </div>
@else
    <x-wire-bootstrap::input.select
        {{ $attributes }}
        :label="$label"
        :name="$name"
        :id="$id"
        :required="$required"
        :optional="$optional"
        :help="$help"
        :wrapper-class="$wrapperClass"
        :options="$options"
    />
@endif
