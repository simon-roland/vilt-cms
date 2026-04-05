<?php

namespace RolandSolutions\ViltCms\Traits;

use RolandSolutions\ViltCms\Actions\DeleteLivewireTempFiles;

trait DeletesTempFilesOnSave
{
    protected static function bootDeletesTempFilesOnSave()
    {
        static::saved(function ($model) {
            DeleteLivewireTempFiles::make()->handle();
        });
    }
}
