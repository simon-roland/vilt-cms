<?php

namespace RolandSolutions\ViltCms\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSettings extends Model
{
    protected $table = 'site_settings';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public static function getSingleton(): static
    {
        return static::firstOrCreate(['id' => 1], ['data' => []]);
    }
}
