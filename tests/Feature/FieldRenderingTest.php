<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareErrors(array $messages): void
{
    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag($messages));
    view()->share('errors', $errors);
}

it('renders a text field with label bound to the input', function () {
    $html = $this->render('<x-wire-bootstrap::input.text label="Name" wire:model="name" />');

    expect($html)->toContain('<label class="form-label" for="name">Name</label>')
        ->toContain('type="text"')
        ->toContain('id="name"')
        ->toContain('name="name"')
        ->toContain('wire:model="name"')
        ->toContain('placeholder="Type Name"')
        ->toContain('form-control');
});

it('renders under the wb alias namespace', function () {
    $html = $this->render('<x-wb::input.text label="Name" wire:model="name" />');

    expect($html)->toContain('id="name"');
});

it('renders no label element when no label is given', function () {
    $html = $this->render('<x-wire-bootstrap::input.text wire:model="name" />');

    expect($html)->not->toContain('<label');
});

it('renders required and optional markers', function () {
    $required = $this->render('<x-wire-bootstrap::input.text label="Name" wire:model="name" required />');
    $optional = $this->render('<x-wire-bootstrap::input.text label="Name" wire:model="name" optional />');

    // aria-required instead of the native attribute: native HTML5 validation
    // would block wire:submit before Livewire's server-side validation runs.
    expect($required)->toContain('<span class="text-danger">(*)</span>')
        ->toContain('aria-required="true"')
        ->and($optional)->toContain('<span class="text-muted">(Optional)</span>');
});

it('shows the validation error and marks the input invalid', function () {
    shareErrors(['email' => ['The email field is required.']]);

    $html = $this->render('<x-wire-bootstrap::input.text label="Email" wire:model="email" />');

    expect($html)->toContain('is-invalid')
        ->toContain('<div class="invalid-feedback">The email field is required.</div>');
});

it('matches errors for wire:model with modifiers', function () {
    shareErrors(['email' => ['Broken.']]);

    $html = $this->render('<x-wire-bootstrap::input.text label="Email" wire:model.live="email" />');

    expect($html)->toContain('is-invalid');
});

it('passes extra attributes through to the control and merges classes', function () {
    $html = $this->render('<x-wire-bootstrap::input.text wire:model="name" data-test="x" class="extra" maxlength="10" />');

    expect($html)->toContain('data-test="x"')
        ->toContain('maxlength="10"')
        ->toMatch('/class="[^"]*form-control[^"]*extra[^"]*"|class="[^"]*extra[^"]*form-control[^"]*"/');
});

it('does not leak declared props into the html', function () {
    $html = $this->render('<x-wire-bootstrap::input.text label="Name" help="Some help" wire:model="name" />');

    expect($html)->not->toContain('label="')
        ->not->toContain('help="');
});

it('applies size classes to control and input group', function () {
    $html = $this->render('<x-wire-bootstrap::input.text wire:model="name" size="sm" prepend="@" />');

    expect($html)->toContain('form-control-sm')
        ->toContain('input-group-sm');
});

it('renders a floating label after the input', function () {
    $html = $this->render('<x-wire-bootstrap::input.text label="Name" wire:model="name" :floating="true" />');

    expect($html)->toContain('form-floating')
        ->and(strpos($html, '<input'))->toBeLessThan(strpos($html, '<label'));
});

it('wraps plain prepend and append text in input-group-text', function () {
    $html = $this->render('<x-wire-bootstrap::input.text wire:model="price" prepend="$" append=".00" />');

    expect($html)->toContain('input-group')
        ->toContain('<span class="input-group-text">$</span>')
        ->toContain('<span class="input-group-text">.00</span>');
});

it('passes markup prepend slots through unwrapped', function () {
    $html = $this->render('<x-wire-bootstrap::input.text wire:model="q"><x-slot:append><button type="button" class="btn btn-outline-secondary">Go</button></x-slot:append></x-wire-bootstrap::input.text>');

    expect($html)->toContain('<button type="button" class="btn btn-outline-secondary">Go</button>')
        ->not->toContain('input-group-text');
});

it('renders help text under the field', function () {
    $html = $this->render('<x-wire-bootstrap::input.text wire:model="name" help="We never share it." />');

    expect($html)->toContain('<div class="form-text">We never share it.</div>');
});

it('renders a datalist when given', function () {
    $html = $this->render('<x-wire-bootstrap::input.text wire:model="city" :datalist="[\'Dhaka\' => \'Dhaka\']" />');

    expect($html)->toContain('<datalist id="city-list">')
        ->toContain('list="city-list"')
        ->toContain('<option value="Dhaka">Dhaka</option>');
});

it('suppresses the placeholder with an empty string', function () {
    $html = $this->render('<x-wire-bootstrap::input.text label="Name" wire:model="name" placeholder="" />');

    expect($html)->not->toContain('placeholder=');
});
