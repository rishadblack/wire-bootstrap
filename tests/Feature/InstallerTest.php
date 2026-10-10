<?php

use Illuminate\Filesystem\Filesystem;
use Rishadblack\WireBootstrap\Support\Installer;

function makeApp(array $files = []): string
{
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wb-install-'.uniqid();

    $fs = new Filesystem;
    $fs->makeDirectory($base, recursive: true);

    $defaults = [
        'package.json' => json_encode([
            'private' => true,
            'scripts' => ['build' => 'vite build', 'dev' => 'vite'],
            'devDependencies' => ['vite' => '^8.0.0', 'laravel-vite-plugin' => '^3.1'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        'resources/js/app.js' => "import './bootstrap';\n",
        'vite.config.js' => <<<'JS'
            import laravel from 'laravel-vite-plugin';
            import { defineConfig } from 'vite';

            export default defineConfig({
                plugins: [
                    laravel({
                        input: ['resources/css/app.css', 'resources/js/app.js'],
                        refresh: true,
                    }),
                ],
            });
            JS,
    ];

    foreach (array_merge($defaults, $files) as $path => $content) {
        if ($content === null) {
            continue;
        }

        $full = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
        $fs->ensureDirectoryExists(dirname($full));
        $fs->put($full, $content);
    }

    return $base;
}

function installInto(string $base, array $options = []): array
{
    return (new Installer(new Filesystem, $base))->run($options);
}

function readApp(string $base, string $path): string
{
    return (new Filesystem)->get($base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path));
}

it('writes every file a fresh app needs', function () {
    $base = makeApp();

    $report = installInto($base);

    expect($report['written'])->toContain('package.json')
        ->toContain('resources/scss/app.scss')
        ->toContain('resources/js/wire-bootstrap-setup.js')
        ->toContain('resources/js/app.js')
        ->toContain('vite.config.js')
        ->toContain('config/wire-bootstrap.php')
        ->and($report['manual'])->toBe([]);

    expect(readApp($base, 'resources/scss/app.scss'))->toContain("@import 'bootstrap/scss/bootstrap';")
        ->toContain("@import '../../vendor/rishadblack/wire-bootstrap/resources/scss/wire-bootstrap';")
        ->and(readApp($base, 'resources/js/wire-bootstrap-setup.js'))->toContain('window.bootstrap = bootstrap')
        ->and(readApp($base, 'resources/js/app.js'))->toContain("import './wire-bootstrap-setup';")
        ->and(readApp($base, 'resources/views/components/layouts/app.blade.php'))->toContain('<x-wire-bootstrap::layout');
});

it('adds npm dependencies without touching existing entries', function () {
    $base = makeApp();

    installInto($base);

    $package = json_decode(readApp($base, 'package.json'), true);

    expect($package['dependencies'])->toHaveKeys(['bootstrap', '@popperjs/core', 'flatpickr', 'quill'])
        ->and($package['devDependencies'])->toHaveKey('sass')
        ->and($package['devDependencies']['vite'])->toBe('^8.0.0')
        ->and($package['scripts'])->toBe(['build' => 'vite build', 'dev' => 'vite']);
});

it('keeps an existing dependency version', function () {
    $base = makeApp([
        'package.json' => json_encode(['dependencies' => ['bootstrap' => '^5.2.0']]),
    ]);

    installInto($base);

    expect(json_decode(readApp($base, 'package.json'), true)['dependencies']['bootstrap'])->toBe('^5.2.0');
});

it('extends the vite input array and adds the scss options', function () {
    $base = makeApp();

    installInto($base);

    $vite = readApp($base, 'vite.config.js');

    expect($vite)->toContain("input: ['resources/scss/app.scss', 'resources/css/app.css', 'resources/js/app.js']")
        ->toContain('preprocessorOptions')
        ->toContain('quietDeps: true')
        ->toContain("silenceDeprecations: ['import']");
});

it('is byte-identical on a second run', function () {
    $base = makeApp();

    installInto($base);

    $snapshot = collect([
        'package.json', 'resources/scss/app.scss', 'resources/js/wire-bootstrap-setup.js',
        'resources/js/app.js', 'vite.config.js', 'resources/views/components/layouts/app.blade.php',
    ])->mapWithKeys(fn (string $file) => [$file => readApp($base, $file)]);

    $report = installInto($base);

    expect($report['written'])->toBe([])
        ->and($report['npm_changed'])->toBeFalse();

    $snapshot->each(fn (string $content, string $file) => expect(readApp($base, $file))->toBe($content));
});

it('replaces the app.js marker block instead of duplicating it', function () {
    $base = makeApp([
        'resources/js/app.js' => "// wire-bootstrap:begin\nimport './old-setup';\n// wire-bootstrap:end\nconsole.log('mine');\n",
    ]);

    installInto($base);

    $appJs = readApp($base, 'resources/js/app.js');

    expect(substr_count($appJs, 'wire-bootstrap:begin'))->toBe(1)
        ->and($appJs)->toContain("import './wire-bootstrap-setup';")
        ->not->toContain('old-setup')
        ->toContain("console.log('mine');");
});

it('keeps an edited scss entry unless forced', function () {
    $base = makeApp(['resources/scss/app.scss' => '// my custom scss']);

    $report = installInto($base);

    expect($report['skipped'])->toContain('resources/scss/app.scss')
        ->and(readApp($base, 'resources/scss/app.scss'))->toBe('// my custom scss');

    installInto($base, ['force' => true]);

    expect(readApp($base, 'resources/scss/app.scss'))->toContain("@import 'bootstrap/scss/bootstrap';");
});

it('leaves an unrecognised vite config untouched and reports manual steps', function () {
    $custom = "export default { build: { } };\n";
    $base = makeApp(['vite.config.js' => $custom]);

    $report = installInto($base);

    expect(readApp($base, 'vite.config.js'))->toBe($custom)
        ->and($report['manual'])->toHaveKey('vite.config.js')
        ->and($report['manual'])->toHaveKey('vite.config.js (scss options)');
});

it('writes a full vite config when none exists', function () {
    $base = makeApp(['vite.config.js' => null]);
    (new Filesystem)->delete($base.DIRECTORY_SEPARATOR.'vite.config.js');

    installInto($base);

    expect(readApp($base, 'vite.config.js'))->toContain('laravel-vite-plugin')
        ->toContain('resources/scss/app.scss');
});

it('skips the layout when disabled and respects a custom layout path', function () {
    $base = makeApp();

    $report = installInto($base, ['layout' => false]);

    expect(collect($report['written'])->filter(fn (string $f) => str_contains($f, 'layouts')))->toBeEmpty();

    installInto($base, ['layoutPath' => 'resources/views/layouts/app.blade.php']);

    expect(readApp($base, 'resources/views/layouts/app.blade.php'))->toContain('<x-wire-bootstrap::layout');
});

it('includes sibling package imports when they are installed', function () {
    $base = makeApp();

    installInto($base, ['withTomselect' => true, 'withModals' => true]);

    expect(readApp($base, 'resources/js/wire-bootstrap-setup.js'))
        ->toContain('wire-tomselect-all.js')
        ->toContain('wire-modals.js')
        ->and(readApp($base, 'resources/scss/app.scss'))
        ->toContain('tom-select.bootstrap5')
        ->toContain('wire-modals')
        ->and(readApp($base, 'resources/views/components/layouts/app.blade.php'))
        ->toContain('<livewire:wire-modals.host />');
});

it('reports invalid package.json as a manual step without failing', function () {
    $base = makeApp(['package.json' => '{not json']);

    $report = installInto($base);

    expect($report['manual'])->toHaveKey('package.json')
        ->and(readApp($base, 'package.json'))->toBe('{not json');
});
