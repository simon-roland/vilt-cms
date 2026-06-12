<?php

namespace RolandSolutions\ViltCms\Traits;

use Illuminate\Support\Str;

trait GeneratesStubFiles
{
    /**
     * Normalize a user-supplied generator name into [StudlyClass, kebab-name, Label].
     * Accepts "Hero", "featureGrid", "feature-grid", "feature grid" — all yield
     * the same class. Returns null when no valid PHP class name can be derived.
     *
     * @return array{0: string, 1: string, 2: string}|null
     */
    private function normalizeName(string $name): ?array
    {
        $class = Str::studly($name);

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $class)) {
            return null;
        }

        $kebab = Str::kebab($class);
        $label = ucwords(str_replace('-', ' ', $kebab));

        return [$class, $kebab, $label];
    }

    /**
     * Render a stub with the given replacements and write it to $dest.
     * Existing files are never overwritten.
     */
    private function writeStub(string $stub, string $dest, array $replacements): bool
    {
        $relative = ltrim(str_replace(base_path(), '', $dest), DIRECTORY_SEPARATOR);

        if (file_exists($dest)) {
            $this->warn("  {$relative} already exists — skipping");

            return false;
        }

        if (! is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        file_put_contents($dest, str_replace(
            array_keys($replacements),
            array_values($replacements),
            file_get_contents($stub),
        ));

        $this->line("  <fg=green>✓</> {$relative}");

        return true;
    }
}
