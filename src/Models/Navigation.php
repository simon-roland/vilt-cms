<?php

namespace RolandSolutions\ViltCms\Models;

use RolandSolutions\ViltCms\Enum\NavigationType;
use Illuminate\Database\Eloquent\Model;

class Navigation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'type' => NavigationType::class,
        'items' => 'array',
    ];
}
