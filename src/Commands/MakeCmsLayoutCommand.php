<?php

namespace RolandSolutions\ViltCms\Commands;

use Illuminate\Console\Command;

class MakeCmsLayoutCommand extends Command
{
    protected $signature = 'cms:make-layout {name : The name of the layout (e.g. Default)}';

    protected $description = 'Create a new CMS layout (PHP class + Vue component)';

    public function handle(): int
    {
        $name = $this->argument('name');
        $class = ucfirst($name);
        $layoutName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
        $label = ucwords(str_replace(['-', '_'], ' ', $layoutName));

        $this->createPhpClass($class, $layoutName, $label);
        $this->createVueComponent($class, $label);

        $this->newLine();
        $this->info("Layout '{$class}' created.");

        return self::SUCCESS;
    }

    private function createPhpClass(string $class, string $name, string $label): void
    {
        $dest = app_path("Cms/Layouts/{$class}Layout.php");

        if (file_exists($dest)) {
            $this->warn("  {$dest} already exists — skipping");

            return;
        }

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        $stub = file_get_contents(__DIR__ . '/../../stubs/layout.php.stub');
        $stub = str_replace(['{{ class }}', '{{ name }}', '{{ label }}'], [$class, $name, $label], $stub);

        file_put_contents($dest, $stub);
        $this->line("  <fg=green>✓</> app/Cms/Layouts/{$class}Layout.php");
    }

    private function createVueComponent(string $class, string $label): void
    {
        $dest = base_path("resources/js/cms/layouts/{$class}Layout.vue");

        if (file_exists($dest)) {
            $this->warn("  {$dest} already exists — skipping");

            return;
        }

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        $stub = file_get_contents(__DIR__ . '/../../stubs/layout.vue.stub');
        $stub = str_replace('{{ label }}', $label, $stub);

        file_put_contents($dest, $stub);
        $this->line("  <fg=green>✓</> resources/js/cms/layouts/{$class}Layout.vue");
    }
}
