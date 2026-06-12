<?php

namespace RolandSolutions\ViltCms\Models;

use Illuminate\Database\Eloquent\Model;
use RolandSolutions\ViltCms\Support\Locales;

class LocaleDomainMapping extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => Locales::flushDomainCache());
        static::deleted(fn () => Locales::flushDomainCache());
    }
}
