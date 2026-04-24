<?php

namespace RolandSolutions\ViltCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RolandSolutions\ViltCms\Support\Locales;
use RolandSolutions\ViltCms\Traits\CleansUpOrphanedMedia;
use RolandSolutions\ViltCms\Traits\DeletesTempFilesOnSave;
use RolandSolutions\ViltCms\Traits\RegistersWebpConversions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PageContent extends Model implements HasMedia
{
    use CleansUpOrphanedMedia;
    use DeletesTempFilesOnSave;
    use InteractsWithMedia, RegistersWebpConversions {
        RegistersWebpConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $guarded = [];

    protected $casts = [
        'layout' => 'array',
        'meta' => 'array',
        'blocks' => 'array',
        'published_content' => 'array',
        'published_at' => 'datetime',
        'is_frontpage' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (PageContent $content) {
            $content->locale ??= Locales::default();
        });

        static::saving(function (PageContent $content) {
            if ($content->is_frontpage && $content->isDirty('is_frontpage')) {
                static::where('id', '!=', $content->id)
                    ->where('locale', $content->locale)
                    ->where('is_frontpage', true)
                    ->update(['is_frontpage' => null]);
            }
        });
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class)->withTrashed();
    }

    /**
     * Whether this locale has a published (live) version.
     */
    public function isPublished(): bool
    {
        return $this->published_content !== null;
    }

    /**
     * Whether the current draft content differs from the published snapshot.
     * Returns true when this locale has never been published.
     */
    public function hasDraftChanges(): bool
    {
        if (! $this->isPublished()) {
            return true;
        }

        return $this->layout !== ($this->published_content['layout'] ?? null)
            || $this->blocks !== ($this->published_content['blocks'] ?? null)
            || $this->meta !== ($this->published_content['meta'] ?? null);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_content');
    }

    public function scopeDraft($query)
    {
        return $query->whereNull('published_content');
    }

    public function scopeForLocale($query, ?string $locale = null)
    {
        return $query->where('locale', $locale ?? Locales::default());
    }
}
