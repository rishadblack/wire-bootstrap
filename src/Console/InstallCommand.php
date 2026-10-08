<?php

namespace Rishadblack\WireBootstrap\Console;

use Illuminate\Console\Command;
use Livewire\Blaze\Blaze;
use Rishadblack\WireBootstrap\Support\Installer;
use Rishadblack\WireModals\WireModalsServiceProvider;
use Rishadblack\WireTomselect\WireTomselectServiceProvider;

class InstallCommand extends Command
{
    protected $signature = 'wire-bootstrap:install
        {--no-npm : Edit package.json but do not run npm install}
        {--skip-build : Do not run npm run build at the end}
        {--no-layout : Do not publish the app layout}
        {--no-blaze : Do not composer require livewire/blaze into the app}
        {--force : Overwrite files this command previously published}';

    protected $description = 'Set up Bootstrap 5.3, Vite and the wire-bootstrap base layout in this application';

    public function handle(Installer $installer): int
    {
        $report = $installer->run([
            'force' => (bool) $this->option('force'),
            'layout' => ! $this->option('no-layout'),
            'layoutPath' => $this->layoutViewPath(),
            'withTomselect' => class_exists(WireTomselectServiceProvider::class),
            'withModals' => class_exists(WireModalsServiceProvider::class),
        ]);

        foreach ($report['written'] as $file) {
            $this->components->task($file, fn (): bool => true);
        }

        foreach ($report['skipped'] as $file) {
            $this->components->twoColumnDetail($file, 'kept (use --force to overwrite)');
        }

        foreach ($report['manual'] as $file => $instruction) {
            $this->components->warn("{$file}: {$instruction}");
        }

        if (! $this->option('no-blaze') && ! class_exists(Blaze::class)) {
            $this->runProcess('composer require livewire/blaze --no-interaction');
        }

        if (! $this->option('no-npm')) {
            $this->runProcess('npm install');

            if (! $this->option('skip-build')) {
                $this->runProcess('npm run build');
            }
        }

        $this->newLine();
        $this->components->info('wire-bootstrap is ready.');
        $this->line('    Try a field in any Livewire view:');
        $this->line('    <x-wb::input.text label="Name" wire:model="name" required />');

        return self::SUCCESS;
    }

    /**
     * Where the application's default Livewire page layout lives: Livewire 4
     * uses component_layout (optionally namespace::view), Livewire 3 uses
     * layout, and new apps fall back to the Livewire 3 default location.
     */
    protected function layoutViewPath(): ?string
    {
        $layout = config('livewire.component_layout');

        if (is_string($layout) && $layout !== '') {
            if (str_contains($layout, '::')) {
                [$namespace, $view] = explode('::', $layout, 2);
                $root = config("livewire.component_namespaces.{$namespace}");

                if (is_string($root) && $root !== '') {
                    return rtrim($root, '/\\').'/'.str_replace('.', '/', $view).'.blade.php';
                }

                return null;
            }

            return resource_path('views/'.str_replace('.', '/', $layout).'.blade.php');
        }

        $layout = config('livewire.layout');

        if (is_string($layout) && $layout !== '') {
            return resource_path('views/'.str_replace('.', '/', $layout).'.blade.php');
        }

        return resource_path('views/components/layouts/app.blade.php');
    }

    protected function runProcess(string $command): void
    {
        $this->components->info("Running: {$command}");

        passthru($command, $exitCode);

        if ($exitCode !== 0) {
            $this->components->warn("`{$command}` exited with code {$exitCode}; run it manually.");
        }
    }
}
