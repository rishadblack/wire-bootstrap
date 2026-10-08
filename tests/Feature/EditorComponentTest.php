<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Rishadblack\WireBootstrap\Support\EditorOptions;

it('renders the editor with a hidden bound input behind wire:ignore', function () {
    $html = $this->render('<x-wire-bootstrap::input.editor label="Body" wire:model="body" />');

    expect($html)->toContain('wire:ignore')
        ->toContain('x-data="wbEditor(')
        ->toContain('type="hidden"')
        ->toContain('x-ref="input"')
        ->toContain('wire:model="body"')
        ->toContain('id="body"')
        ->toContain('for="body"')
        ->toContain('Body</label>')
        ->toContain('x-ref="editor"')
        ->toContain('min-height: 10rem');
});

it('passes placeholder, toolbar preset and read-only state to the alpine component', function () {
    $html = $this->render('<x-wire-bootstrap::input.editor label="Body" wire:model="body" toolbar="full" read-only height="20rem" />');

    expect($html)->toContain('code-block')
        ->toContain('readOnly\\u0022:true')
        ->toContain('min-height: 20rem');
});

it('shows the validation error below the editor', function () {
    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag(['body' => ['The body field is required.']]));
    view()->share('errors', $errors);

    $html = $this->render('<x-wire-bootstrap::input.editor label="Body" wire:model="body" />');

    expect($html)->toContain('invalid-feedback d-block')
        ->toContain('The body field is required.')
        ->toContain('wb-editor is-invalid');
});

it('provides basic and full toolbar presets and passes arrays through', function () {
    $basic = EditorOptions::toolbar('basic');
    $full = EditorOptions::toolbar('full');
    $custom = [['bold']];

    expect($basic)->toContain(['bold', 'italic', 'underline'])
        ->and(json_encode($full))->toContain('header')->toContain('code-block')
        ->and(EditorOptions::toolbar($custom))->toBe($custom)
        ->and(EditorOptions::toolbar(null))->toBe($basic);
});
