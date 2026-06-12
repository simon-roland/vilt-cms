<?php

namespace RolandSolutions\ViltCms\Commands;

use Illuminate\Console\Command;
use RolandSolutions\ViltCms\Traits\GeneratesStubFiles;

class MakeCmsLayoutCommand extends Command
{
    use GeneratesStubFiles;

    protected $signature = 'cms:make-layout {name : The name of the layout (e.g. Default)}';

    protected $description = 'Create a new CMS layout (PHP class + Vue component)';

    public function handle(): int
    {
        $parts = $this->normalizeName($this->argument('name'));

        if ($parts === null) {
            $this->error('Invalid layout name — use letters and digits, starting with a letter (e.g. Default, TwoColumn).');

            return self::FAILURE;
        }

        [$class, $name, $label] = $parts;

        $this->writeStub(
            __DIR__.'/../../stubs/layout.php.stub',
            app_path("Cms/Layouts/{$class}Layout.php"),
            ['{{ class }}' => $class, '{{ name }}' => $name, '{{ label }}' => $label],
        );

        $this->writeStub(
            __DIR__.'/../../stubs/layout.vue.stub',
            base_path("resources/js/cms/layouts/{$class}Layout.vue"),
            ['{{ label }}' => $label],
        );

        $this->newLine();
        $this->info("Layout '{$class}' created.");

        return self::SUCCESS;
    }
}
