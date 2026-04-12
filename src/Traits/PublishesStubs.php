<?php

namespace RolandSolutions\ViltCms\Traits;

trait PublishesStubs
{
    /**
     * Individual file mappings: stub path → destination path.
     * These are stable singletons with no obvious convention, so they remain explicit.
     */
    private function fileMappings(): array
    {
        $s = __DIR__ . '/../../stubs';

        return [
            'ts' => [
                ["{$s}/app.ts.stub", base_path('resources/js/app.ts')],
            ],
            'css' => [
                ["{$s}/app.css.stub", base_path('resources/css/app.css')],
            ],
            'config' => [
                ["{$s}/tsconfig.json.stub", base_path('tsconfig.json')],
                ["{$s}/vite.config.js.stub", base_path('vite.config.js')],
            ],
            // Root-level vue files that don't follow the stubs/vue/ convention
            'vue' => [
                ["{$s}/Page.vue.stub",    base_path('resources/js/pages/Page.vue')],
            ],
            'settings-schema' => [
                ["{$s}/SiteSettingsSchema.php.stub", app_path('Cms/SiteSettingsSchema.php')],
            ],
        ];
    }

    /**
     * Directory mappings: [stub subdir, dest dir].
     * All *.stub files in the stub dir are discovered automatically —
     * add/remove stubs from these directories without touching this command.
     */
    private function dirMappings(): array
    {
        return [
            'vue' => [
                ['vue/blocks',     base_path('resources/js/cms/blocks')],
                ['vue/layouts',    base_path('resources/js/cms/layouts')],
                ['vue/components', base_path('resources/js/cms/components')],
            ],
            'php' => [
                ['blocks',  app_path('Cms/Blocks')],
                ['layouts', app_path('Cms/Layouts')],
                ['fields',  app_path('Cms/Fields')],
            ],
        ];
    }

    /**
     * Returns all [stub, dest] pairs for a group, combining explicit file mappings
     * with auto-discovered files from directory mappings.
     */
    private function groupFiles(string $group): array
    {
        $stubsDir = __DIR__ . '/../../stubs';
        $files = $this->fileMappings()[$group] ?? [];

        foreach ($this->dirMappings()[$group] ?? [] as [$relDir, $destDir]) {
            foreach (glob("{$stubsDir}/{$relDir}/*.stub") as $stubFile) {
                $files[] = [$stubFile, $destDir . '/' . basename($stubFile, '.stub')];
            }
        }

        return $files;
    }

    /**
     * Copy a stub to its destination.
     *
     * Behaviour:
     *   $force=true               → always overwrite, no prompt
     *   $force=false, $confirmOverwrite=true  → prompt if file exists (publish default)
     *   $force=false, $confirmOverwrite=false → silently skip if file exists (install default)
     *
     * @return int 1 if the file was written, 0 if skipped
     */
    private function publishFile(
        string $stub,
        string $dest,
        bool $force = false,
        bool $confirmOverwrite = true,
    ): int {
        $relative = $this->relativePath($dest);

        if (file_exists($dest)) {
            if (!$force) {
                if (!$confirmOverwrite || !$this->confirm("  Overwrite {$relative}?", false)) {
                    $this->skip("Skipped {$relative}");

                    return 0;
                }
            }
        } else {
            if (!is_dir(dirname($dest))) {
                mkdir(dirname($dest), 0755, true);
            }
        }

        copy($stub, $dest);
        $this->done($relative);

        return 1;
    }

    private function relativePath(string $absolute): string
    {
        $base = base_path();

        if (str_starts_with($absolute, $base)) {
            return ltrim(substr($absolute, strlen($base)), DIRECTORY_SEPARATOR);
        }

        return $absolute;
    }

    private function step(string $message): void
    {
        $this->line("  <fg=blue>→</> {$message}...");
    }

    private function done(string $message): void
    {
        $this->line("  <fg=green>✓</> {$message}");
    }

    private function skip(string $message): void
    {
        $this->line("  <fg=yellow>–</> {$message}");
    }
}
