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

it('renders a thumb with an image over its icon fallback', function () {
    $html = $this->render('<x-wire-bootstrap::thumb src="/img/box.png" icon="ti ti-box" alt="Box" />');

    expect($html)->toContain('class="wb-thumb wb-thumb-md"')
        ->toContain('role="img"')
        ->toContain('aria-label="Box"')
        ->toContain('<i class="ti ti-box"></i>')
        ->toContain('<img src="/img/box.png" alt="" loading="lazy" onerror="this.remove()">');
});

it('hides a thumb without alt text from screen readers and draws no image without src', function () {
    $html = $this->render('<x-wire-bootstrap::thumb icon="ti ti-photo" />');

    expect($html)->toContain('aria-hidden="true"')
        ->not->toContain('<img')
        ->not->toContain('role="img"');
});

it('sizes a thumb by name or in pixels and shapes it', function () {
    expect($this->render('<x-wire-bootstrap::thumb size="sm" shape="square" />'))
        ->toContain('class="wb-thumb wb-thumb-sm wb-thumb-square"');

    expect($this->render('<x-wire-bootstrap::thumb :size="36" />'))
        ->toContain('class="wb-thumb"')
        ->toContain('style="--wb-thumb-size: 36px"');
});

it('shows initials before the icon, and the slot when there is neither', function () {
    expect($this->render('<x-wire-bootstrap::thumb initials="AB" icon="ti ti-box" />'))
        ->toContain('<span class="wb-thumb-initials">AB</span>')
        ->not->toContain('ti-box');

    expect($this->render('<x-wire-bootstrap::thumb><b>?</b></x-wire-bootstrap::thumb>'))
        ->toContain('<b>?</b>');
});

it('renders a round avatar with the initials of the first and last name', function () {
    $html = $this->render('<x-wire-bootstrap::avatar name="rahim uddin khan" src="/p.jpg" size="sm" class="me-2" />');

    expect($html)->toContain('wb-thumb wb-thumb-sm wb-thumb-circle')
        ->toContain('me-2')
        ->toContain('aria-label="rahim uddin khan"')
        ->toContain('<span class="wb-thumb-initials">RK</span>')
        ->toContain('<img src="/p.jpg"');
});

it('takes initials from a Bangla name and escapes the name', function () {
    expect($this->render('<x-wire-bootstrap::avatar name="রহিম করিম" />'))
        ->toContain('<span class="wb-thumb-initials">রক</span>');

    expect($this->render('<x-wire-bootstrap::avatar name="Ann <script>" />'))
        ->toContain('aria-label="Ann &lt;script&gt;"')
        ->not->toContain('<script>');
});
