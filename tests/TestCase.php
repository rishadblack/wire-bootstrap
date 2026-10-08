<?php

namespace Rishadblack\WireBootstrap\Tests;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\BlazeServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Rishadblack\WireBootstrap\WireBootstrapServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            BlazeServiceProvider::class,
            WireBootstrapServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.debug', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        view()->addNamespace('fixtures', __DIR__.'/Fixtures/views');
    }

    /**
     * Render a Blade string (component tags allowed) and return the HTML.
     *
     * @param  array<string, mixed>  $data
     */
    protected function render(string $template, array $data = []): string
    {
        return Blade::render($template, $data);
    }
}
