<?php

namespace Rishadblack\WireBootstrap\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'wire-bootstrap:install
        {--no-npm : Edit package.json but do not run npm install}
        {--skip-build : Do not run npm run build at the end}
        {--no-layout : Do not publish the app layout}
        {--no-blaze : Do not composer require livewire/blaze into the app}
        {--force : Overwrite files this command previously published}';

    protected $description = 'Set up Bootstrap 5.3, Vite and the wire-bootstrap base layout in this application';

    public function handle(): int
    {
        $this->error('The installer is not implemented yet.');

        return self::FAILURE;
    }
}
