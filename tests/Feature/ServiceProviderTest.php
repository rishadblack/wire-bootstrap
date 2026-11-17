<?php

use Illuminate\Support\Facades\Artisan;

it('merges the package config', function () {
    expect(config('wire-bootstrap.alias_prefix'))->toBe('wb')
        ->and(config('wire-bootstrap.defaults.button_variant'))->toBe('primary')
        ->and(config('wire-bootstrap.blaze'))->toBeTrue()
        ->and(config('wire-bootstrap.code'))->toBe(['groups' => 4, 'length' => 4, 'separator' => '-'])
        ->and(config('wire-bootstrap.pin_pad'))->toBe(['min' => 4, 'max' => 6]);
});

it('registers the install command', function () {
    expect(array_key_exists('wire-bootstrap:install', Artisan::all()))->toBeTrue();
});

it('loads the package translations', function () {
    expect(__('wire-bootstrap::wire-bootstrap.optional_marker'))->toBe('(Optional)');
});
