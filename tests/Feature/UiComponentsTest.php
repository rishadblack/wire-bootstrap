<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

it('renders a button with the default variant', function () {
    expect($this->render('<x-wire-bootstrap::button>Save</x-wire-bootstrap::button>'))
        ->toContain('btn btn-primary')
        ->toContain('type="button"')
        ->toContain('Save');
});

it('renders button variants, outline, sizes and submit type', function () {
    $html = $this->render('<x-wire-bootstrap::button variant="danger" outline size="sm" type="submit">Delete</x-wire-bootstrap::button>');

    expect($html)->toContain('btn-outline-danger')
        ->toContain('btn-sm')
        ->toContain('type="submit"')
        ->not->toContain('btn-danger ');
});

it('wires the loading state from wire:click automatically', function () {
    $html = $this->render('<x-wire-bootstrap::button wire:click="save">Save</x-wire-bootstrap::button>');

    expect($html)->toContain('wire:loading.attr="disabled"')
        ->toContain('wire:target="save"')
        ->toContain('spinner-border')
        ->toContain('wire:loading.remove');
});

it('respects an explicit wire:target without duplicating it', function () {
    $html = $this->render('<x-wire-bootstrap::button wire:click="save" wire:target="save,delete">Save</x-wire-bootstrap::button>');

    expect(substr_count($html, 'wire:target="save,delete"'))->toBeGreaterThanOrEqual(1)
        ->and($html)->not->toContain('wire:target="save"');
});

it('renders a plain button without loading markup when no wire target exists', function () {
    expect($this->render('<x-wire-bootstrap::button>Plain</x-wire-bootstrap::button>'))
        ->not->toContain('spinner-border')
        ->not->toContain('wire:loading');
});

it('renders a card with title, toolbar, body and footer', function () {
    $html = $this->render(<<<'BLADE'
        <x-wire-bootstrap::card title="Users" body-class="p-0">
            <x-slot:toolbar><button type="button">Add</button></x-slot:toolbar>
            Body here
            <x-slot:footer>Footer here</x-slot:footer>
        </x-wire-bootstrap::card>
        BLADE);

    expect($html)->toContain('card-header')
        ->toContain('<h5 class="card-title mb-0">Users</h5>')
        ->toContain('<button type="button">Add</button>')
        ->toContain('card-body p-0')
        ->toContain('Body here')
        ->toContain('<div class="card-footer">Footer here</div>');
});

it('renders a bare card without header or footer', function () {
    $html = $this->render('<x-wire-bootstrap::card>Just body</x-wire-bootstrap::card>');

    expect($html)->not->toContain('card-header')
        ->not->toContain('card-footer');
});

it('renders alerts with variant and dismissible wiring', function () {
    $html = $this->render('<x-wire-bootstrap::alert variant="warning" dismissible>Careful</x-wire-bootstrap::alert>');

    expect($html)->toContain('alert alert-warning alert-dismissible fade show')
        ->toContain('role="alert"')
        ->toContain('data-bs-dismiss="alert"');
});

it('renders an empty state with action slot', function () {
    $html = $this->render('<x-wire-bootstrap::empty-state title="Nothing here" description="Add your first note."><button type="button">Add</button></x-wire-bootstrap::empty-state>');

    expect($html)->toContain('Nothing here')
        ->toContain('Add your first note.')
        ->toContain('<button type="button">Add</button>');
});

it('renders the layout skeleton with title, slots and theme snippet', function () {
    $this->withoutVite();

    $html = $this->render(<<<'BLADE'
        <x-wire-bootstrap::layout title="Dashboard" class="bg-body-tertiary">
            <x-slot:nav><nav>Menu</nav></x-slot:nav>
            Page body
            <x-slot:footer><footer>Bottom</footer></x-slot:footer>
        </x-wire-bootstrap::layout>
        BLADE);

    expect($html)->toContain('<!DOCTYPE html>')
        ->toContain('<title>Dashboard</title>')
        ->toContain('data-bs-theme="light"')
        ->toContain('wb-theme')
        ->toContain('<nav>Menu</nav>')
        ->toContain('Page body')
        ->toContain('<footer>Bottom</footer>')
        ->toContain('<body class="bg-body-tertiary">');
});

it('omits the theme snippet when dark mode is disabled', function () {
    $this->withoutVite();
    config()->set('wire-bootstrap.theme.dark_mode', false);

    expect($this->render('<x-wire-bootstrap::layout>Hi</x-wire-bootstrap::layout>'))
        ->not->toContain('wb-theme');
});

it('wraps a custom control with field chrome in select-search', function () {
    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag(['city_id' => ['Pick a city.']]));
    view()->share('errors', $errors);

    $html = $this->render('<x-wire-bootstrap::input.select-search label="City" name="city_id" required><div class="fake-tomselect"></div></x-wire-bootstrap::input.select-search>');

    expect($html)->toContain('fake-tomselect')
        ->toContain('<span class="text-danger">(*)</span>')
        ->toContain('Pick a city.')
        ->not->toContain('form-select');
});

it('falls back to a plain select in select-search without a slot', function () {
    $html = $this->render('<x-wire-bootstrap::input.select-search label="City" wire:model="city" :options="[\'d\' => \'Dhaka\']" />');

    expect($html)->toContain('form-select')
        ->toContain('<option value="d">Dhaka</option>');
});
