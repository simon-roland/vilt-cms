<?php

namespace RolandSolutions\ViltCms\Traits;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait RegistersWebpConversions
{
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->withResponsiveImages()
            ->format('webp')
            ->quality(90);
    }
}
