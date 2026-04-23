<?php

namespace RolandSolutions\ViltCms\Models;

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
        'layout'            => 'array',
        'meta'              => 'array',
        'blocks'            => 'array',
        'published_content' => 'array',
        'published_at'      => 'datetime',
        'is_frontpage'      => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if ($page->is_frontpage && $page->isDirty('is_frontpage')) {
                static::where('id', '!=', $page->id)
                    ->where('is_frontpage', true)
                    ->update(['is_frontpage' => null]);
            }
        });
    }

    /**
     * Whether this page has a published (live) version.
     */
    public function isPublished(): bool
    {
        return $this->published_content !== null;
    }

    /**
     * Whether the current draft content differs from the published snapshot.
     * Returns true when the page has never been published.
     */
    public function hasDraftChanges(): bool
    {
        if (!$this->isPublished()) {
            return true;
        }

        return $this->name !== ($this->published_content['name'] ?? $this->published_content['title'] ?? null)
            || $this->layout !== ($this->published_content['layout'] ?? null)
            || $this->blocks !== ($this->published_content['blocks'] ?? null)
            || $this->meta !== ($this->published_content['meta'] ?? null);
    }

    /**
     * Pages that have been published (have a live snapshot).
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_content');
    }

    /**
     * Pages that have never been published.
     */
    public function scopeDraft($query)
    {
        return $query->whereNull('published_content');
    }
}
