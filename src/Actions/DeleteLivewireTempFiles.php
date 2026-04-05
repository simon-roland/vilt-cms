<?php

namespace RolandSolutions\ViltCms\Actions;

class DeleteLivewireTempFiles extends Action
{
    public const DELETE_AFTER_SECONDS = 3600; // 1 hour

    public function handle()
    {
        $path = storage_path('app/livewire-tmp');
        $files = glob($path . '/*');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > self::DELETE_AFTER_SECONDS) {
                unlink($file);
            }
        }
    }
}
