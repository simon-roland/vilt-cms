<?php

namespace RolandSolutions\ViltCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function contents(): HasMany
    {
        return $this->hasMany(PageContent::class);
    }

    public function content(?string $locale = null): ?PageContent
    {
        $locale ??= app()->getLocale();

        return $this->contents->firstWhere('locale', $locale);
    }
}
