<?php

namespace RolandSolutions\ViltCms\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
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
                return $mediaUrl("{$mediaId}/responsive-images/{$url}") . ' ' . $m[1] . 'w';
            }

            return $mediaUrl("{$mediaId}/responsive-images/{$url}");
        })->implode(', ');

        [$width, $height] = $this->resolveDimensions($urls);

        return [
            'id' => $this->uuid,
            'src' => $src,
            'srcset' => $srcset,
            'sizes' => '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw',
            'placeholder' => $responsive['base64svg'] ?? null,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * @param  array<int, string>  $responsiveUrls
     * @return array{0: int|null, 1: int|null}
     */
    protected function resolveDimensions(array $responsiveUrls): array
    {
        $largest = end($responsiveUrls);
        if ($largest && preg_match('/_(\d+)_(\d+)\.webp$/', $largest, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        if (! str_starts_with($this->mime_type ?? '', 'image/')) {
            return [null, null];
        }

        return Cache::rememberForever("cms_media_dims_{$this->id}_{$this->updated_at?->timestamp}", function () {
            try {
                $size = @getimagesize($this->getPath());
                if (is_array($size) && isset($size[0], $size[1])) {
                    return [(int) $size[0], (int) $size[1]];
                }
            } catch (\Throwable) {
                // ignore — fall through
            }

            return [null, null];
        });
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
