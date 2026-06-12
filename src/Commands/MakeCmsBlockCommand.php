<?php

namespace RolandSolutions\ViltCms\Commands;

use Illuminate\Console\Command;
use RolandSolutions\ViltCms\Traits\GeneratesStubFiles;

class MakeCmsBlockCommand extends Command
{
    use GeneratesStubFiles;

    protected $signature = 'cms:make-block {name : The name of the block (e.g. Hero)}';

    protected $description = 'Create a new CMS block (PHP class + Vue component)';

    public function handle(): int
    {
        $parts = $this->normalizeName($this->argument('name'));

        if ($parts === null) {
            $this->error('Invalid block name — use letters and digits, starting with a letter (e.g. Hero, FeatureGrid).');

            return self::FAILURE;
        }

        [$class, $name, $label] = $parts;

        $this->writeStub(
            __DIR__.'/../../stubs/block.php.stub',
            app_path("Cms/Blocks/{$class}Block.php"),
            ['{{ class }}' => $class, '{{ name }}' => $name, '{{ label }}' => $label],
        );

        $this->writeStub(
            __DIR__.'/../../stubs/block.vue.stub',
            base_path("resources/js/cms/blocks/{$class}Block.vue"),
            ['{{ label }}' => $label],
        );

        $this->newLine();
        $this->info("Block '{$class}' created.");

        return self::SUCCESS;
    }
}
