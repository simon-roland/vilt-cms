<?php

namespace RolandSolutions\ViltCms\Commands;

use Illuminate\Console\Command;
use RolandSolutions\ViltCms\Traits\PublishesStubs;

class CmsPublishCommand extends Command
{
    use PublishesStubs;

    protected $signature = 'cms:publish
        {--only=* : Asset groups to publish: ts, vue, css, config, php, settings-schema (default: all)}
        {--force : Overwrite existing files without confirmation}';

    protected $description = 'Republish CMS frontend assets (stubs) to the application';

    public function handle(): int
    {
        $only = $this->option('only');
        $force = $this->option('force');

        $allGroups = ['ts', 'vue', 'css', 'config', 'php', 'settings-schema'];
        $groups = empty($only) ? $allGroups : array_intersect($allGroups, $only);

        if (empty($groups)) {
            $this->error('No valid groups specified. Valid groups: ' . implode(', ', $allGroups));

            return self::FAILURE;
        }

        $this->info('Publishing CMS assets...');
        $this->newLine();

        $groupLabels = [
            'ts'              => 'TypeScript entrypoint',
            'vue'             => 'Vue components',
            'css'             => 'CSS entrypoint',
            'config'          => 'Config files',
            'php'             => 'Starter PHP classes',
            'settings-schema' => 'Site settings schema',
        ];

        $total = 0;

        foreach ($groups as $group) {
            $this->step($groupLabels[$group]);

            foreach ($this->groupFiles($group) as [$stub, $dest]) {
                $total += $this->publishFile($stub, $dest, $force);
            }
        }

        $this->newLine();
        $this->info("{$total} file(s) published.");

        return self::SUCCESS;
    }
}
