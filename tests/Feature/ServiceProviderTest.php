<?php

use Illuminate\Support\Facades\Artisan;

it('merges the package config', function () {
    expect(config('wire-bootstrap.alias_prefix'))->toBe('wb')
        ->and(config('wire-bootstrap.defaults.button_variant'))->toBe('primary')
        ->and(config('wire-bootstrap.blaze'))->toBeTrue();
});

it('registers the install command', function () {
    expect(array_key_exists('wire-bootstrap:install', Artisan::all()))->toBeTrue();
});

it('loads the package translations', function () {
    expect(__('wire-bootstrap::wire-bootstrap.optional_marker'))->toBe('(Optional)');
});
