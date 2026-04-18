<?php

namespace RolandSolutions\ViltCms\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaFolder extends Model
{
    protected $fillable = ['name', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id')->orderBy('name');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'media_folder_id');
    }

    /**
     * Returns ordered collection from root down to (and including) this folder.
     */
    public function ancestors(): Collection
    {
        $chain = new Collection;
        $current = $this;

        while ($current !== null) {
            $chain->prepend($current);
            $current = $current->parent_id ? MediaFolder::find($current->parent_id) : null;
        }

        return $chain;
    }

    public function hasDescendantMedia(): bool
    {
        if ($this->media()->exists()) {
            return true;
        }

        foreach ($this->children as $child) {
            if ($child->hasDescendantMedia()) {
                return true;
            }
        }

        return false;
    }
}
