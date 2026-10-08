<?php

namespace Rishadblack\WireBootstrap\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * Minimal, non-destructive package.json editing: adds missing dependency
 * entries, never downgrades or removes anything, and leaves every other key
 * untouched.
 */
class NodePackages
{
    public function __construct(protected Filesystem $files) {}

    /**
     * @param  array<string, string>  $dependencies
     * @param  array<string, string>  $devDependencies
     * @return bool whether the file changed
     */
    public function merge(string $path, array $dependencies = [], array $devDependencies = []): bool
    {
        if (! $this->files->exists($path)) {
            throw new RuntimeException("package.json not found at [{$path}].");
        }

        $decoded = json_decode($this->files->get($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException("package.json at [{$path}] is not valid JSON.");
        }

        $changed = false;

        foreach (['dependencies' => $dependencies, 'devDependencies' => $devDependencies] as $section => $additions) {
            foreach ($additions as $package => $constraint) {
                if (isset($decoded[$section][$package])) {
                    continue;
                }

                $decoded[$section][$package] = $constraint;
                ksort($decoded[$section]);
                $changed = true;
            }
        }

        if ($changed) {
            $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $this->files->put($path, $encoded.PHP_EOL);
        }

        return $changed;
    }
}
