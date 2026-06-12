<?php

namespace RolandSolutions\ViltCms\Models;

use Illuminate\Database\Eloquent\Model;
use RolandSolutions\ViltCms\Traits\RegistersWebpConversions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MediaLibrary extends Model implements HasMedia
{
    use InteractsWithMedia, RegistersWebpConversions {
        RegistersWebpConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $table = 'media_library';

    protected $guarded = [];

    public static function instance(): static
    {
        return static::firstOrCreate(['name' => 'default']);
    }
}
