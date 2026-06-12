<?php

namespace RolandSolutions\ViltCms\Commands;

use Illuminate\Console\Command;
use RolandSolutions\ViltCms\Traits\GeneratesStubFiles;

class MakeCmsFieldCommand extends Command
{
    use GeneratesStubFiles;

    protected $signature = 'cms:make-field {name : The name of the field (e.g. Actions)}';

    protected $description = 'Create a new CMS field (reusable PHP form component)';

    public function handle(): int
    {
        $parts = $this->normalizeName($this->argument('name'));

        if ($parts === null) {
            $this->error('Invalid field name — use letters and digits, starting with a letter (e.g. Actions, OpeningHours).');

            return self::FAILURE;
        }

        [$class, $name, $label] = $parts;

        $this->writeStub(
            __DIR__.'/../../stubs/field.php.stub',
            app_path("Cms/Fields/{$class}Field.php"),
            ['{{ class }}' => $class, '{{ name }}' => $name, '{{ label }}' => $label],
        );

        $this->newLine();
        $this->info("Field '{$class}' created.");

        return self::SUCCESS;
    }
}
