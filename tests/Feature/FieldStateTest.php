<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;
use Rishadblack\WireBootstrap\Support\FieldState;

function resolveField(array $attributes = [], array $props = []): FieldState
{
    return FieldState::resolve(new ComponentAttributeBag($attributes), $props);
}

it('derives name, id and error key from wire:model', function () {
    $field = resolveField(['wire:model' => 'email']);

    expect($field->model)->toBe('email')
        ->and($field->name)->toBe('email')
        ->and($field->id)->toBe('email')
        ->and($field->errorKey)->toBe('email');
});

it('matches wire:model with modifiers', function () {
    $field = resolveField(['wire:model.live.debounce.500ms' => 'search']);

    expect($field->model)->toBe('search')
        ->and($field->errorKey)->toBe('search');
});

it('derives a sanitized collision-free id from a dotted model', function () {
    $dotted = resolveField(['wire:model' => 'user.name']);
    $plain = resolveField(['wire:model' => 'user-name']);

    expect($dotted->id)->toStartWith('user-name-')
        ->and($dotted->id)->not->toBe($plain->id)
        ->and($plain->id)->toBe('user-name')
        ->and($dotted->name)->toBe('user.name');
});

it('keeps the derived id stable across resolves', function () {
    $first = resolveField(['wire:model' => 'user.name']);
    $second = resolveField(['wire:model' => 'user.name']);

    expect($first->id)->toBe($second->id);
});

it('prefers explicit name and id over derived ones', function () {
    $field = resolveField(['wire:model' => 'email'], ['name' => 'contact_email', 'id' => 'the-email']);

    expect($field->name)->toBe('contact_email')
        ->and($field->id)->toBe('the-email')
        ->and($field->errorKey)->toBe('email');
});

it('falls back to the explicit name when there is no wire:model', function () {
    $field = resolveField([], ['name' => 'plain']);

    expect($field->model)->toBeNull()
        ->and($field->name)->toBe('plain')
        ->and($field->errorKey)->toBe('plain');
});

it('normalizes boolean props from strings and valueless attributes', function () {
    expect(FieldState::boolean(true))->toBeTrue()
        ->and(FieldState::boolean(''))->toBeTrue()
        ->and(FieldState::boolean('false'))->toBeFalse()
        ->and(FieldState::boolean('1'))->toBeTrue()
        ->and(FieldState::boolean(null, true))->toBeTrue()
        ->and(FieldState::boolean(null))->toBeFalse();
});

it('builds the default placeholder from the label', function () {
    $field = resolveField([], ['label' => 'Name']);

    expect($field->placeholder)->toBe('Type Name');
});

it('keeps an explicit placeholder and suppresses an empty one', function () {
    expect(resolveField([], ['label' => 'Name', 'placeholder' => 'Your name'])->placeholder)->toBe('Your name')
        ->and(resolveField([], ['label' => 'Name', 'placeholder' => ''])->placeholder)->toBeNull();
});

it('disables auto placeholders by config but keeps them for floating labels', function () {
    config()->set('wire-bootstrap.defaults.show_placeholder', false);

    expect(resolveField([], ['label' => 'Name'])->placeholder)->toBeNull()
        ->and(resolveField([], ['label' => 'Name', 'floating' => true])->placeholder)->toBe('Name');
});

it('accepts only sm and lg sizes and falls back to the config default', function () {
    expect(resolveField([], ['size' => 'sm'])->size)->toBe('sm')
        ->and(resolveField([], ['size' => 'huge'])->size)->toBeNull();

    config()->set('wire-bootstrap.defaults.size', 'lg');

    expect(resolveField([])->size)->toBe('lg');
});

it('only floats when there is a label', function () {
    expect(resolveField([], ['label' => 'Name', 'floating' => true])->floating)->toBeTrue()
        ->and(resolveField([], ['floating' => true])->floating)->toBeFalse();
});

it('exposes required and optional markers from the lang file', function () {
    $required = resolveField([], ['label' => 'Name', 'required' => true]);
    $optional = resolveField([], ['label' => 'Name', 'optional' => true]);

    expect($required->requiredMarker)->toBe('(*)')
        ->and($required->optionalMarker)->toBeNull()
        ->and($optional->optionalMarker)->toBe('(Optional)');
});

it('hides markers when disabled in config', function () {
    config()->set('wire-bootstrap.markers.required', false);

    expect(resolveField([], ['required' => true])->requiredMarker)->toBeNull();
});

it('reads the validation error for the field from the shared error bag', function () {
    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag(['email' => ['The email field is required.']]));
    view()->share('errors', $errors);

    $field = resolveField(['wire:model' => 'email']);

    expect($field->invalid)->toBeTrue()
        ->and($field->error)->toBe('The email field is required.');
});

it('reports no error when the bag has none for the field', function () {
    $field = resolveField(['wire:model' => 'email']);

    expect($field->invalid)->toBeFalse()
        ->and($field->error)->toBeNull();
});
