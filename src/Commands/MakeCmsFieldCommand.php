<?php

namespace RolandSolutions\ViltCms\Commands;

use Illuminate\Console\Command;

class MakeCmsFieldCommand extends Command
{
    protected $signature = 'cms:make-field {name : The name of the field (e.g. Actions)}';

    protected $description = 'Create a new CMS field (reusable PHP form component)';

    public function handle(): int
    {
        $name = $this->argument('name');
        $class = ucfirst($name);
        $fieldName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
        $label = ucwords(str_replace(['-', '_'], ' ', $fieldName));

        $this->createPhpClass($class, $fieldName, $label);

        $this->newLine();
        $this->info("Field '{$class}' created.");

        return self::SUCCESS;
    }

    private function createPhpClass(string $class, string $name, string $label): void
    {
        $dest = app_path("Cms/Fields/{$class}Field.php");

        if (file_exists($dest)) {
            $this->warn("  {$dest} already exists — skipping");

            return;
        }

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        $stub = file_get_contents(__DIR__ . '/../../stubs/field.php.stub');
        $stub = str_replace(['{{ class }}', '{{ name }}', '{{ label }}'], [$class, $name, $label], $stub);

        file_put_contents($dest, $stub);
        $this->line("  <fg=green>✓</> app/Cms/Fields/{$class}Field.php");
    }
}
