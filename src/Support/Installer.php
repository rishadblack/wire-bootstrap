<?php

namespace Rishadblack\WireBootstrap\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * The file-editing half of `wire-bootstrap:install`. Every step is idempotent
 * (a second run changes nothing) and conservative: files the installer cannot
 * parse are left untouched and reported under "manual" with the snippet to
 * apply by hand. Process execution (npm, composer) lives in the command, not
 * here, so this class is fully testable against a temp directory.
 */
class Installer
{
    protected const MARKER_BEGIN = '// wire-bootstrap:begin';

    protected const MARKER_END = '// wire-bootstrap:end';

    /** @var array<string, string> */
    public const NPM_DEPENDENCIES = [
        'bootstrap' => '^5.3.3',
        '@popperjs/core' => '^2.11.8',
        'flatpickr' => '^4.6.13',
        'quill' => '^2.0.3',
    ];

    /** @var array<string, string> */
    public const NPM_DEV_DEPENDENCIES = [
        'sass' => '^1.77.0',
    ];

    /** @var array{written: list<string>, skipped: list<string>, manual: array<string, string>, npm_changed: bool} */
    protected array $report = ['written' => [], 'skipped' => [], 'manual' => [], 'npm_changed' => false];

    public function __construct(
        protected Filesystem $files,
        protected string $basePath,
    ) {}

    /**
     * @param array{
     *     force?: bool,
     *     layout?: bool,
     *     layoutPath?: string|null,
     *     withTomselect?: bool,
     *     withModals?: bool,
     * } $options
     * @return array{written: list<string>, skipped: list<string>, manual: array<string, string>, npm_changed: bool}
     */
    public function run(array $options = []): array
    {
        $this->report = ['written' => [], 'skipped' => [], 'manual' => [], 'npm_changed' => false];

        $force = (bool) ($options['force'] ?? false);
        $withTomselect = (bool) ($options['withTomselect'] ?? false);
        $withModals = (bool) ($options['withModals'] ?? false);

        $this->mergeNodePackages();
        $this->writeScssEntry($force, $withTomselect, $withModals);
        $this->writeSetupScript($withTomselect, $withModals);
        $this->ensureAppJsImport();
        $this->updateViteConfig();

        if ($options['layout'] ?? true) {
            $this->publishLayout($options['layoutPath'] ?? null, $force, $withModals);
        }

        $this->publishConfig($force);

        return $this->report;
    }

    protected function mergeNodePackages(): void
    {
        $path = $this->path('package.json');

        try {
            $changed = (new NodePackages($this->files))->merge($path, self::NPM_DEPENDENCIES, self::NPM_DEV_DEPENDENCIES);
        } catch (RuntimeException $exception) {
            $this->report['manual']['package.json'] = $exception->getMessage()
                .' Add these yourself: '.json_encode(['dependencies' => self::NPM_DEPENDENCIES, 'devDependencies' => self::NPM_DEV_DEPENDENCIES]);

            return;
        }

        $this->report['npm_changed'] = $changed;
        $this->report[$changed ? 'written' : 'skipped'][] = 'package.json';
    }

    protected function writeScssEntry(bool $force, bool $withTomselect, bool $withModals): void
    {
        $target = $this->path('resources/scss/app.scss');

        if ($this->files->exists($target) && ! $force) {
            $this->report['skipped'][] = 'resources/scss/app.scss';

            return;
        }

        $styles = [];

        if ($withTomselect) {
            $styles[] = "@import 'tom-select/dist/css/tom-select.bootstrap5';";
        }

        if ($withModals) {
            $styles[] = "@import '../../vendor/rishadblack/wire-modals/resources/css/wire-modals';";
        }

        $this->writeStub('app.scss', $target, [
            '__WB_SIBLING_STYLES__' => $styles === [] ? '' : implode("\n", $styles)."\n",
        ]);

        $this->report['written'][] = 'resources/scss/app.scss';
    }

    /**
     * The setup script is a managed file: it is regenerated whenever its
     * expected content differs, so re-runs stay byte-identical.
     */
    protected function writeSetupScript(bool $withTomselect, bool $withModals): void
    {
        $target = $this->path('resources/js/wire-bootstrap-setup.js');

        $imports = [];

        if ($withTomselect) {
            $imports[] = "import '../../vendor/rishadblack/wire-tomselect/resources/js/wire-tomselect-all.js';";
        }

        if ($withModals) {
            $imports[] = "import '../../vendor/rishadblack/wire-modals/resources/js/wire-modals.js';";
        }

        $content = $this->renderStub('wire-bootstrap-setup.js', [
            '__WB_SIBLING_IMPORTS__' => $imports === [] ? '' : implode("\n", $imports)."\n",
        ]);

        if ($this->files->exists($target) && $this->files->get($target) === $content) {
            $this->report['skipped'][] = 'resources/js/wire-bootstrap-setup.js';

            return;
        }

        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->put($target, $content);
        $this->report['written'][] = 'resources/js/wire-bootstrap-setup.js';
    }

    protected function ensureAppJsImport(): void
    {
        $target = $this->path('resources/js/app.js');

        $block = self::MARKER_BEGIN."\nimport './wire-bootstrap-setup';\n".self::MARKER_END;

        if (! $this->files->exists($target)) {
            $this->files->ensureDirectoryExists(dirname($target));
            $this->files->put($target, $block."\n");
            $this->report['written'][] = 'resources/js/app.js';

            return;
        }

        $content = $this->files->get($target);

        if (str_contains($content, self::MARKER_BEGIN) && str_contains($content, self::MARKER_END)) {
            $pattern = '/'.preg_quote(self::MARKER_BEGIN, '/').'.*?'.preg_quote(self::MARKER_END, '/').'/s';
            $updated = (string) preg_replace($pattern, $block, $content, 1);
        } else {
            $updated = $block."\n".$content;
        }

        if ($updated === $content) {
            $this->report['skipped'][] = 'resources/js/app.js';

            return;
        }

        $this->files->put($target, $updated);
        $this->report['written'][] = 'resources/js/app.js';
    }

    protected function updateViteConfig(): void
    {
        $target = $this->path('vite.config.js');

        if (! $this->files->exists($target)) {
            $this->writeStub('vite.config.js', $target, []);
            $this->report['written'][] = 'vite.config.js';

            return;
        }

        $content = $this->files->get($target);
        $updated = $content;

        // 1. Make sure the scss entry is part of the laravel() plugin input.
        if (! str_contains($updated, 'resources/scss/app.scss')) {
            $withEntry = preg_replace(
                '/input:\s*\[/',
                "input: ['resources/scss/app.scss', ",
                $updated,
                1,
            );

            if ($withEntry === null || $withEntry === $updated) {
                $this->report['manual']['vite.config.js'] = "Add 'resources/scss/app.scss' to the laravel() plugin input array.";
            } else {
                $updated = $withEntry;
            }
        }

        // 2. Silence Bootstrap's @import deprecations in dart-sass.
        if (! str_contains($updated, 'preprocessorOptions')) {
            $css = "css: {\n        preprocessorOptions: {\n            scss: {\n                quietDeps: true,\n                silenceDeprecations: ['import'],\n            },\n        },\n    },";

            $withCss = preg_replace(
                '/defineConfig\(\{/',
                "defineConfig({\n    ".$css,
                $updated,
                1,
            );

            if ($withCss === null || $withCss === $updated) {
                $this->report['manual']['vite.config.js (scss options)'] = 'Add css.preprocessorOptions.scss = { quietDeps: true, silenceDeprecations: [\'import\'] } to vite.config.js.';
            } else {
                $updated = $withCss;
            }
        }

        if ($updated === $content) {
            $this->report['skipped'][] = 'vite.config.js';

            return;
        }

        $this->files->put($target, $updated);
        $this->report['written'][] = 'vite.config.js';
    }

    protected function publishLayout(?string $layoutPath, bool $force, bool $withModals): void
    {
        $target = $layoutPath ?? $this->path('resources/views/components/layouts/app.blade.php');

        if (! $this->isAbsolute($target)) {
            $target = $this->path($target);
        }

        $label = ltrim(str_replace($this->basePath, '', $target), '/\\');

        if ($this->files->exists($target) && ! $force) {
            $this->report['skipped'][] = $label;

            return;
        }

        $this->writeStub('layouts-app.blade.php', $target, [
            '__WB_HOST_LINE__' => $withModals ? "\n    <livewire:wire-modals.host />\n" : '',
        ]);

        $this->report['written'][] = $label;
    }

    protected function publishConfig(bool $force): void
    {
        $target = $this->path('config/wire-bootstrap.php');

        if ($this->files->exists($target) && ! $force) {
            $this->report['skipped'][] = 'config/wire-bootstrap.php';

            return;
        }

        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->copy(__DIR__.'/../../config/wire-bootstrap.php', $target);
        $this->report['written'][] = 'config/wire-bootstrap.php';
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function renderStub(string $stub, array $replacements): string
    {
        $content = $this->files->get(__DIR__.'/../../resources/stubs/'.$stub);

        return strtr($content, $replacements);
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function writeStub(string $stub, string $target, array $replacements): void
    {
        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->put($target, $this->renderStub($stub, $replacements));
    }

    protected function path(string $relative): string
    {
        return rtrim($this->basePath, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    protected function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
