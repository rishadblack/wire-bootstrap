<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

it('renders email and number as typed text fields', function () {
    expect($this->render('<x-wire-bootstrap::input.email label="Email" wire:model="email" />'))
        ->toContain('type="email"')
        ->and($this->render('<x-wire-bootstrap::input.number label="Qty" wire:model="qty" min="1" max="99" step="1" />'))
        ->toContain('type="number"')
        ->toContain('min="1"')
        ->toContain('max="99"')
        ->toContain('step="1"');
});

it('renders a bare hidden input without field chrome', function () {
    $html = $this->render('<x-wire-bootstrap::input.hidden wire:model="token" />');

    expect($html)->toContain('type="hidden"')
        ->toContain('name="token"')
        ->not->toContain('form-control')
        ->not->toContain('<label');
});

it('renders a textarea with rows and slot content', function () {
    $html = $this->render('<x-wire-bootstrap::input.textarea label="Note" wire:model="note" rows="5">Hello</x-wire-bootstrap::input.textarea>');

    expect($html)->toContain('<textarea')
        ->toContain('rows="5"')
        ->toContain('>Hello</textarea>');
});

it('renders select options from a key-label map', function () {
    $html = $this->render('<x-wire-bootstrap::input.select label="Country" wire:model="country" :options="[\'bd\' => \'Bangladesh\', \'fi\' => \'Finland\']" />');

    expect($html)->toContain('form-select')
        ->toContain('<option value="">Select Country</option>')
        ->toContain('<option value="bd">Bangladesh</option>')
        ->toContain('<option value="fi">Finland</option>');
});

it('renders select options from value-name maps', function () {
    $html = $this->render('<x-wire-bootstrap::input.select wire:model="city" :options="[[\'value\' => 1, \'name\' => \'Dhaka\'], [\'value\' => 2, \'name\' => \'Khulna\']]" />');

    expect($html)->toContain('<option value="1">Dhaka</option>')
        ->toContain('<option value="2">Khulna</option>');
});

it('prefers slot options over none and keeps them raw', function () {
    $html = $this->render('<x-wire-bootstrap::input.select wire:model="x"><option value="a">A</option></x-wire-bootstrap::input.select>');

    expect($html)->toContain('<option value="a">A</option>');
});

it('drops the placeholder option and appends [] to the name on multiple selects', function () {
    $html = $this->render('<x-wire-bootstrap::input.select label="Tags" wire:model="tags" multiple :options="[\'a\' => \'A\']" />');

    expect($html)->toContain('name="tags[]"')
        ->toContain('multiple')
        ->not->toContain('<option value="">');
});

it('renders a real checkbox and a switch variant', function () {
    $checkbox = $this->render('<x-wire-bootstrap::input.checkbox label="Agree" wire:model="agree" />');
    $switch = $this->render('<x-wire-bootstrap::input.switch label="Active" wire:model="active" />');

    expect($checkbox)->toContain('form-check-input')
        ->toContain('type="checkbox"')
        ->not->toContain('form-switch')
        ->and($switch)->toContain('form-switch')
        ->toContain('role="switch"');
});

it('renders checkbox value and inline mode', function () {
    $html = $this->render('<x-wire-bootstrap::input.checkbox label="A" wire:model="letters" value="a" inline />');

    expect($html)->toContain('value="a"')
        ->toContain('form-check-inline');
});

it('renders a radio group with shared name and indexed ids', function () {
    $html = $this->render('<x-wire-bootstrap::input.radio label="Plan" wire:model="plan" :options="[\'free\' => \'Free\', \'pro\' => \'Pro\']" />');

    expect($html)->toContain('type="radio"')
        ->toContain('id="plan-0"')
        ->toContain('id="plan-1"')
        ->toContain('value="free"')
        ->toContain('value="pro"')
        ->and(substr_count($html, 'name="plan"'))->toBe(2);
});

it('renders a checkbox group with array name', function () {
    $html = $this->render('<x-wire-bootstrap::input.checkbox-group label="Tags" wire:model="tags" :options="[\'a\' => \'A\', \'b\' => \'B\']" />');

    expect($html)->toContain('type="checkbox"')
        ->and(substr_count($html, 'name="tags[]"'))->toBe(2);
});

it('shows one always-visible error block for a group', function () {
    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag(['plan' => ['Pick one.']]));
    view()->share('errors', $errors);

    $html = $this->render('<x-wire-bootstrap::input.radio label="Plan" wire:model="plan" :options="[\'a\' => \'A\']" />');

    expect($html)->toContain('invalid-feedback d-block')
        ->and(substr_count($html, 'Pick one.'))->toBe(1);
});

it('renders a password field with a visibility toggle', function () {
    $html = $this->render('<x-wire-bootstrap::input.password label="Password" wire:model="password" />');

    expect($html)->toContain('type="password"')
        ->toContain('x-data="{ show: false }"')
        ->toContain('x-on:click="show = ! show"');
});

it('renders a password field without the toggle when disabled', function () {
    $html = $this->render('<x-wire-bootstrap::input.password label="Password" wire:model="password" :toggle="false" />');

    expect($html)->toContain('type="password"')
        ->not->toContain('x-data');
});

it('renders a color input with the color class', function () {
    $html = $this->render('<x-wire-bootstrap::input.color label="Brand" wire:model="color" />');

    expect($html)->toContain('type="color"')
        ->toContain('form-control-color');
});

it('renders a range input with bounds and live value output', function () {
    $html = $this->render('<x-wire-bootstrap::input.range label="Amount" wire:model="amount" min="0" max="10" step="2" show-value />');

    expect($html)->toContain('form-range')
        ->toContain('min="0"')
        ->toContain('max="10"')
        ->toContain('step="2"')
        ->toContain('<output');
});

it('renders a file input with multiple, accept and upload progress', function () {
    $html = $this->render('<x-wire-bootstrap::input.file label="Photos" wire:model="photos" multiple accept="image/*" />');

    expect($html)->toContain('type="file"')
        ->toContain('name="photos[]"')
        ->toContain('accept="image/*"')
        ->toContain('livewire-upload-progress')
        ->toContain('progress-bar');
});

it('renders a badge with variant and pill', function () {
    expect($this->render('<x-wire-bootstrap::badge variant="danger" pill>Hot</x-wire-bootstrap::badge>'))
        ->toContain('text-bg-danger')
        ->toContain('rounded-pill')
        ->toContain('>Hot<');
});
