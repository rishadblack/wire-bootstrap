<?php

namespace Rishadblack\WireBootstrap;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;
use Rishadblack\WireBootstrap\Console\InstallCommand;
use Rishadblack\WireBootstrap\Support\Installer;

class WireBootstrapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wire-bootstrap.php', 'wire-bootstrap');

        $this->app->bind(
            Installer::class,
            fn ($app): Installer => new Installer(
                new Filesystem,
                $app->basePath(),
            ),
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'wire-bootstrap');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'wire-bootstrap');

        $components = __DIR__.'/../resources/views/components';

        Blade::anonymousComponentPath($components, 'wire-bootstrap');

        $alias = config('wire-bootstrap.alias_prefix');

        if (is_string($alias) && $alias !== '' && $alias !== 'wire-bootstrap') {
            Blade::anonymousComponentPath($components, $alias);
        }

        $this->registerBlaze($components);

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);

            $this->publishes([
                __DIR__.'/../config/wire-bootstrap.php' => config_path('wire-bootstrap.php'),
            ], 'wire-bootstrap-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/wire-bootstrap'),
            ], 'wire-bootstrap-views');

            $this->publishes([
                __DIR__.'/../resources/lang' => $this->app->langPath('vendor/wire-bootstrap'),
            ], 'wire-bootstrap-lang');
        }
    }

    /**
     * Opt the package views into livewire/blaze's function compiler when the
     * package is installed. Folding and memoization stay off: every field
     * reads request state ($errors), which Blaze must never fold.
     */
    protected function registerBlaze(string $componentsPath): void
    {
        if (! config('wire-bootstrap.blaze', true)) {
            return;
        }

        if (! class_exists(Blaze::class) || ! $this->app->bound('blaze')) {
            return;
        }

        Blaze::optimize()->in($componentsPath);
    }
}
