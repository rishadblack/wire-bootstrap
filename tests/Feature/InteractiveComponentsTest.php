<?php

it('renders the date field with flatpickr mount data behind wire:ignore', function () {
    $html = $this->render('<x-wire-bootstrap::input.date label="Joined" wire:model="joined_at" :enable-dates="[\'25-Jun-2026\']" />');

    expect($html)->toContain('wire:ignore')
        ->toContain('x-data="wbDatepicker(')
        ->toContain('x-ref="input"')
        ->toContain('wire:model="joined_at"')
        ->toContain('2026-06-25')
        ->toContain('d-M-Y');
});

it('keys the date field wrapper by the derived id', function () {
    $html = $this->render('<x-wire-bootstrap::input.date wire:model="user.birthday" />');

    expect($html)->toMatch('/wire:key="wb-date-user-birthday-[0-9a-f]{6}"/');
});

it('renders tabs from items with the first tab active by default', function () {
    $html = $this->render(<<<'BLADE'
        <x-wire-bootstrap::tabs :items="['info' => 'Info', 'meta' => 'Meta']">
            <x-wire-bootstrap::tab key="info" active>Info pane</x-wire-bootstrap::tab>
            <x-wire-bootstrap::tab key="meta">Meta pane</x-wire-bootstrap::tab>
        </x-wire-bootstrap::tabs>
        BLADE);

    expect($html)->toContain('x-data="wbTabs(')
        ->toContain('nav nav-tabs')
        ->toContain('>Info</button>')
        ->toContain('>Meta</button>')
        ->toContain('nav-link active')
        ->toContain('tab-pane active show')
        ->toContain('Info pane')
        ->toContain('wire-modals:opened.window');
});

it('lets a custom nav slot replace the generated buttons', function () {
    $html = $this->render(<<<'BLADE'
        <x-wire-bootstrap::tabs>
            <x-slot:nav><li class="nav-item">Custom</li></x-slot:nav>
            <x-wire-bootstrap::tab key="a">A</x-wire-bootstrap::tab>
        </x-wire-bootstrap::tabs>
        BLADE);

    expect($html)->toContain('<li class="nav-item">Custom</li>');
});

it('renders the theme toggle with the configured default', function () {
    config()->set('wire-bootstrap.theme.default', 'dark');

    $html = $this->render('<x-wire-bootstrap::theme-toggle />');

    expect($html)->toContain('x-data="wbThemeToggle(')
        ->toContain('dark')
        ->toContain('x-on:click="toggle()"');
});
