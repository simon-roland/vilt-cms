<?php

namespace RolandSolutions\ViltCms\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class Media extends SpatieMedia
{
    protected static function booted(): void
    {
        static::saving(function (self $media) {
            if ($media->hasCustomProperty('media_folder_id')) {
                $media->media_folder_id = $media->getCustomProperty('media_folder_id') ?: null;
            }
        });
    }

    public function mediaFolder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'media_folder_id');
    }

    public function toImageArray(): array
    {
        $responsive = $this->responsive_images['webp'] ?? [];
        $urls = $responsive['urls'] ?? [];
        $mediaId = $this->id;
        $diskName = config('cms.media_disk');

        $mediaUrl = function (string $relative) use ($diskName): string {
            try {
                return Storage::disk($diskName)->url($relative);
            } catch (\RuntimeException) {
                return route('media', ['filename' => $relative]);
            }
        };

        $src = isset($urls[0])
            ? $mediaUrl("{$mediaId}/responsive-images/{$urls[0]}")
            : $this->getCmsUrl();

        $srcset = collect($urls)->map(function ($url) use ($mediaId, $mediaUrl) {
            if (preg_match('/_(\d+)_\d+\.webp$/', $url, $m)) {
                return $mediaUrl("{$mediaId}/responsive-images/{$url}").' '.$m[1].'w';
            }

            return $mediaUrl("{$mediaId}/responsive-images/{$url}");
        })->implode(', ');

        return [
            'id' => $this->uuid,
            'src' => $src,
            'srcset' => $srcset,
            'sizes' => '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw',
            'placeholder' => $responsive['base64svg'] ?? null,
        ];
    }

    public function getCmsUrl(string $conversion = ''): string
    {
        $diskName = $this->disk;
        $absolutePath = $this->getPath($conversion);
        $diskRoot = Storage::disk($diskName)->path('');
        $relative = str_replace('\\', '/', ltrim(str_replace($diskRoot, '', $absolutePath), '/\\'));

        try {
            return Storage::disk($diskName)->url($relative);
        } catch (\RuntimeException) {
            return route('media', ['filename' => $relative]);
        }
    }

    public function getFirstResponsiveImage(): ?string
    {
        $responsive = $this->responsive_images['webp'] ?? [];
        if (empty($responsive['urls'])) {
            return null;
        }

        $firstUrl = $responsive['urls'][0];
        $relative = "{$this->id}/responsive-images/{$firstUrl}";

        try {
            return Storage::disk($this->conversions_disk ?: $this->disk)->url($relative);
        } catch (\RuntimeException) {
            return route('media', ['filename' => $relative]);
        }
    }
}
