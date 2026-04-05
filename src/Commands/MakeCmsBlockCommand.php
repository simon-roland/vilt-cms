<?php

namespace RolandSolutions\ViltCms\Commands;

use Illuminate\Console\Command;

class MakeCmsBlockCommand extends Command
{
    protected $signature = 'cms:make-block {name : The name of the block (e.g. Hero)}';

    protected $description = 'Create a new CMS block (PHP class + Vue component)';

    public function handle(): int
    {
        $name = $this->argument('name');
        $class = ucfirst($name);
        $blockName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
        $label = ucwords(str_replace(['-', '_'], ' ', $blockName));

        $this->createPhpClass($class, $blockName, $label);
        $this->createVueComponent($class, $label);

        $this->newLine();
        $this->info("Block '{$class}' created.");

        return self::SUCCESS;
    }

    private function createPhpClass(string $class, string $name, string $label): void
    {
        $dest = app_path("Cms/Blocks/{$class}Block.php");

        if (file_exists($dest)) {
            $this->warn("  {$dest} already exists — skipping");
            return;
        }

        if (! is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        $stub = file_get_contents(__DIR__ . '/../../stubs/block.php.stub');
        $stub = str_replace(['{{ class }}', '{{ name }}', '{{ label }}'], [$class, $name, $label], $stub);

        file_put_contents($dest, $stub);
        $this->line("  <fg=green>✓</> app/Cms/Blocks/{$class}Block.php");
    }

    private function createVueComponent(string $class, string $label): void
    {
        $dest = base_path("resources/js/cms/blocks/{$class}Block.vue");

        if (file_exists($dest)) {
            $this->warn("  {$dest} already exists — skipping");
            return;
        }

        if (! is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        $stub = file_get_contents(__DIR__ . '/../../stubs/block.vue.stub');
        $stub = str_replace('{{ label }}', $label, $stub);

        file_put_contents($dest, $stub);
        $this->line("  <fg=green>✓</> resources/js/cms/blocks/{$class}Block.vue");
    }
}
