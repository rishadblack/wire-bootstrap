<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Rishadblack\WireBootstrap\Support\CodeOptions;

function shareCodeErrors(array $messages): void
{
    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag($messages));
    view()->share('errors', $errors);
}

/**
 * The Alpine mount data is JSON inside JSON.parse('...') with quotes escaped
 * as backslash-u0022; turn them back into quotes so assertions stay readable.
 */
function alpineJson(string $html): string
{
    return str_replace(chr(92).'u0022', '"', $html);
}

// input.code -------------------------------------------------------------------

it('renders four groups of four with wire:model on the alpine group', function () {
    $html = alpineJson($this->render('<x-wb::input.code label="Licence key" wire:model="licence" />'));

    expect($html)->toContain('x-data="wbCode(')
        ->toContain('x-modelable="value"')
        ->toContain('wire:model="licence"')
        ->toContain('role="group"')
        ->toContain('dir="ltr"')
        ->toContain('aria-labelledby="licence-label"')
        ->toContain('<label class="form-label" id="licence-label" for="licence">Licence key</label>')
        ->toContain('id="licence"')
        ->toContain('id="licence-4"')
        ->not->toContain('id="licence-5"')
        ->toContain('inputmode="numeric"')
        ->toContain('Licence key, part 1 of 4')
        ->toContain('Licence key, part 4 of 4')
        ->toContain('x-on:paste.prevent="pasted(0, $event)"')
        ->toContain('x-on:keydown="keydown(3, $event)"');

    expect(substr_count($html, 'class="form-control wb-code-group"'))->toBe(4)
        ->and(substr_count($html, 'wb-code-separator'))->toBe(3)
        ->and($html)->toContain('[4,4,4,4]')
        ->and($html)->toContain('separator":"-"')
        ->and($html)->toContain('formatted":false');
});

it('takes groups, length and separator props', function () {
    $html = alpineJson($this->render('<x-wb::input.code wire:model="code" groups="3" length="2" separator=" " />'));

    expect($html)->toContain('[2,2,2]')
        ->toContain('separator":" "')
        ->toContain('size="2"')
        ->toContain('--wb-code-length: 2;');

    expect(substr_count($html, 'wb-code-group"'))->toBe(3);
});

it('accepts a list of group lengths', function () {
    $html = alpineJson($this->render('<x-wb::input.code wire:model="phone" :groups="[3, 3, 4]" />'));

    expect($html)->toContain('[3,3,4]')
        ->toContain('size="3"')
        ->toContain('size="4"');
});

it('hides the separator when it is empty', function () {
    $html = alpineJson($this->render('<x-wb::input.code wire:model="otp" groups="6" length="1" separator="" />'));

    expect($html)->not->toContain('wb-code-separator');
});

it('reads its defaults from config', function () {
    config()->set('wire-bootstrap.code', ['groups' => 2, 'length' => 5, 'separator' => '/']);

    $html = alpineJson($this->render('<x-wb::input.code wire:model="code" />'));

    expect($html)->toContain('[5,5]')
        ->toContain('>/</span>');
});

it('binds the formatted value and allows letters when asked', function () {
    $html = alpineJson($this->render('<x-wb::input.code wire:model="key" formatted alphanumeric />'));

    expect($html)->toContain('formatted":true')
        ->toContain('alphanumeric":true')
        ->toContain('inputmode="text"')
        ->toContain('autocapitalize="characters"');
});

it('shows the validation error, aria-invalid and help text', function () {
    shareCodeErrors(['licence' => ['The licence key is invalid.']]);

    $html = alpineJson($this->render('<x-wb::input.code label="Key" wire:model.live="licence" help="16 digits" />'));

    expect($html)->toContain('invalid-feedback d-block" id="licence-error">The licence key is invalid.')
        ->toContain('aria-invalid="true"')
        ->toContain('wb-code is-invalid')
        ->toContain('aria-describedby="licence-error licence-help"')
        ->toContain('<div class="form-text" id="licence-help">16 digits</div>');
});

it('renders required marker, disabled, autofocus, size and the hidden name input', function () {
    $html = alpineJson($this->render('<x-wb::input.code label="Key" name="key" required disabled autofocus size="lg" class="mt-2" />'));

    expect($html)->toContain('(*)')
        ->toContain('aria-required="true"')
        ->toContain(' disabled')
        ->toContain(' autofocus')
        ->toContain('form-control-lg')
        ->toContain('wb-code-lg')
        ->toContain('mt-2')
        ->toContain('<input type="hidden" name="key" x-bind:value="value" />');

    expect(substr_count($html, 'autofocus'))->toBe(1);
});

it('labels the group itself when there is no label', function () {
    $html = alpineJson($this->render('<x-wb::input.code wire:model="code" />'));

    expect($html)->toContain('aria-label="Code"')
        ->toContain('Code, part 1 of 4')
        ->not->toContain('<label');
});

it('builds group lengths from counts, lists and config', function () {
    expect(CodeOptions::groups(4, 4))->toBe([4, 4, 4, 4])
        ->and(CodeOptions::groups('3', '2'))->toBe([2, 2, 2])
        ->and(CodeOptions::groups([3, 3, 4], null))->toBe([3, 3, 4])
        ->and(CodeOptions::groups('3,3,4', null))->toBe([3, 3, 4])
        ->and(CodeOptions::groups(0, 0))->toBe([1])
        ->and(CodeOptions::groups([0, 'x'], 2))->toBe([2, 2, 2, 2])
        ->and(CodeOptions::groups(null, null))->toBe([4, 4, 4, 4]);
});

it('builds the code mount data with normalised booleans', function () {
    expect(CodeOptions::code([4], null, 'false', 'true'))->toBe([
        'groups' => [4],
        'separator' => '-',
        'alphanumeric' => false,
        'formatted' => true,
    ]);
});

// pin-pad ----------------------------------------------------------------------

it('renders the pin pad with keys, dots and the bound alpine root', function () {
    $html = alpineJson($this->render('<x-wb::pin-pad label="Enter your PIN" wire:model="pin" />'));

    expect($html)->toContain('x-data="wbPinPad(')
        ->toContain('x-modelable="value"')
        ->toContain('wire:model="pin"')
        ->toContain('role="group"')
        ->toContain('tabindex="0"')
        ->toContain('aria-labelledby="pin-label"')
        ->toContain('<div class="wb-pin-label" id="pin-label">Enter your PIN</div>')
        ->toContain('x-on:keydown="keydown($event)"')
        ->not->toContain('keydown.window')
        ->toContain('role="status" aria-live="polite"')
        ->toContain('aria-label="Delete last digit"')
        ->toContain('aria-label="OK"')
        ->toContain('x-on:click="press(0)"')
        ->toContain('x-on:click="press(9)"')
        ->toContain('x-on:click="erase()"')
        ->toContain('x-on:click="done()"')
        ->toContain('length() < 4')
        ->toContain('min":4')
        ->toContain('max":6')
        ->toContain('autoSubmit":true')
        ->toContain(':count of :max digits entered');

    expect(substr_count($html, 'class="wb-pin-dot"'))->toBe(6)
        ->and(substr_count($html, 'style="display: none;"'))->toBe(2)
        ->and(substr_count($html, 'class="wb-pin-key"'))->toBe(10)
        ->and($html)->toContain('dir="ltr"');
});

it('takes min, max, submit, auto-submit, global keys and autofocus', function () {
    $html = alpineJson($this->render('<x-wb::pin-pad wire:model="pin" min="5" max="5" submit="unlock" :auto-submit="false" global-keys autofocus />'));

    expect($html)->toContain('min":5')
        ->toContain('max":5')
        ->toContain('submit":"unlock"')
        ->toContain('autoSubmit":false')
        ->toContain('autofocus":true')
        ->toContain('x-on:keydown.window="keydown($event)"')
        ->toContain('aria-label="PIN"');

    expect(substr_count($html, 'class="wb-pin-dot"'))->toBe(5)
        ->and($html)->not->toContain('style="display: none;"');
});

it('reads the pin bounds from config and clamps them', function () {
    config()->set('wire-bootstrap.pin_pad', ['min' => 3, 'max' => 8]);

    expect(CodeOptions::pinBounds(null, null))->toBe([3, 8])
        ->and(CodeOptions::pinBounds(6, 4))->toBe([6, 6])
        ->and(CodeOptions::pinBounds(0, 99))->toBe([1, 12]);

    $html = alpineJson($this->render('<x-wb::pin-pad wire:model="pin" />'));

    expect(substr_count($html, 'class="wb-pin-dot"'))->toBe(8);
});

it('renders the disabled state', function () {
    $html = alpineJson($this->render('<x-wb::pin-pad wire:model="pin" disabled />'));

    expect($html)->toContain('aria-disabled="true"')
        ->toContain('tabindex="-1"')
        ->toContain('is-disabled')
        ->toContain('disabled":true');

    expect(substr_count($html, ' disabled '))->toBeGreaterThanOrEqual(10);
});

it('renders the error state from the bag', function () {
    shareCodeErrors(['pin' => ['Wrong PIN.']]);

    $html = alpineJson($this->render('<x-wb::pin-pad wire:model.live="pin" help="Ask your manager" size="sm" />'));

    expect($html)->toContain('wb-pin-pad wb-pin-pad-sm is-invalid')
        ->toContain('id="pin-error" role="alert">Wrong PIN.</div>')
        ->toContain('aria-describedby="pin-error pin-help"')
        ->toContain('id="pin-help">Ask your manager</div>');
});

it('renders a hidden input for plain form posts when named', function () {
    $html = alpineJson($this->render('<x-wb::pin-pad name="pin" />'));

    expect($html)->toContain('<input type="hidden" name="pin" x-bind:value="value ?? \'\'" />');
});

it('ships the alpine components and styles', function () {
    $js = file_get_contents(__DIR__.'/../../resources/js/wire-bootstrap.js');
    $scss = file_get_contents(__DIR__.'/../../resources/scss/wire-bootstrap.scss');

    expect($js)->toContain("Alpine.data('wbCode'")
        ->toContain("Alpine.data('wbPinPad'")
        ->toContain("'wb-pin-submit'")
        ->toContain('$wire.$call(config.submit)')
        ->and($scss)->toContain('.wb-code-group')
        ->toContain('.wb-pin-key');
});
