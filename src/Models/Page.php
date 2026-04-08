<?php

namespace RolandSolutions\ViltCms\Models;

use RolandSolutions\ViltCms\Enum\PageStatus;
use RolandSolutions\ViltCms\Traits\CleansUpOrphanedMedia;
use RolandSolutions\ViltCms\Traits\DeletesTempFilesOnSave;
use RolandSolutions\ViltCms\Traits\RegistersWebpConversions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia
{
    use CleansUpOrphanedMedia;
    use DeletesTempFilesOnSave;
    use SoftDeletes;
    use InteractsWithMedia, RegistersWebpConversions {
        RegistersWebpConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $guarded = [];

    protected $casts = [
        'layout' => 'array',
        'meta' => 'array',
        'blocks' => 'array',
        'status' => PageStatus::class,
        'is_frontpage' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if ($page->is_frontpage && $page->isDirty('is_frontpage')) {
                static::where('id', '!=', $page->id)
                    ->where('status', $page->status)
                    ->where('is_frontpage', true)
                    ->update(['is_frontpage' => null]);
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function scopeDraft($query)
    {
        return $query->where('status', PageStatus::Draft);
    }

    public function scopePublished($query)
    {
        return $query->where('status', PageStatus::Published);
    }
}
