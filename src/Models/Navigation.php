<?php

namespace RolandSolutions\ViltCms\Models;

use Illuminate\Database\Eloquent\Model;
use RolandSolutions\ViltCms\Enum\NavigationType;

class Navigation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'type' => NavigationType::class,
        'items' => 'array',
    ];
}
