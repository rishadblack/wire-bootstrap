@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'required' => false,
    'optional' => false,
    'help' => null,
    'size' => null,
    'wrapperClass' => null,
    'multiple' => false,
    'accept' => null,
])

@php($multiple = \Rishadblack\WireBootstrap\Support\FieldState::boolean($multiple))

@php($field = \Rishadblack\WireBootstrap\Support\FieldState::resolve($attributes, [
    'label' => $label,
    'name' => $name,
    'id' => $id,
    'placeholder' => '',
    'required' => $required,
    'optional' => $optional,
    'help' => $help,
    'size' => $size,
    'floating' => false,
], $__env))

<x-wire-bootstrap::field :field="$field" :wrapper-class="$wrapperClass">
    <div x-data="{ uploading: false, progress: 0 }"
        x-on:livewire-upload-start="uploading = true"
        x-on:livewire-upload-finish="uploading = false; progress = 0"
        x-on:livewire-upload-cancel="uploading = false; progress = 0"
        x-on:livewire-upload-error="uploading = false"
        x-on:livewire-upload-progress="progress = $event.detail.progress">
        <input
            type="file"
            @if ($field->id) id="{{ $field->id }}" @endif
            @if ($field->name) name="{{ $field->name }}{{ $multiple ? '[]' : '' }}" @endif
            @if ($multiple) multiple @endif
            @if ($accept) accept="{{ $accept }}" @endif
            @if ($field->required) required @endif
            {{ $attributes->class([
                'form-control',
                'form-control-sm' => $field->size === 'sm',
                'form-control-lg' => $field->size === 'lg',
                'is-invalid' => $field->invalid,
            ]) }}
        />
        <div class="progress mt-2" x-show="uploading" x-cloak style="height: 0.5rem;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" x-bind:style="`width: ${progress}%`" x-bind:aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
</x-wire-bootstrap::field>
