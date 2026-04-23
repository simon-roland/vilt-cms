<?php

namespace RolandSolutions\ViltCms\Actions;

use RolandSolutions\ViltCms\Models\Media;
use RolandSolutions\ViltCms\Models\MediaLibrary;
use RolandSolutions\ViltCms\Models\PageContent;

class AddMediaToPage extends Action
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function handle(PageContent $page)
    {
        // Existing: page-attached inline media (legacy support)
        $pageMedia = $page->media->groupBy(function ($media) {
            return $media->getCustomProperty('id') ?? 'default';
        })->map(function ($items) {
            return $items->sortBy('order_column')->values();
        });

        // New: collect all UUID strings from block data and pre-load matching library media
        $allUuids = [];
        foreach (['layout', 'meta', 'blocks'] as $key) {
            if (is_array($page->{$key})) {
                $allUuids = array_merge($allUuids, $this->collectFieldUuids($page->{$key}));
            }
        }

        $libraryMedia = Media::whereIn('uuid', array_unique($allUuids))
            ->whereHasMorph('model', [MediaLibrary::class])
            ->get()
            ->keyBy('uuid');

        foreach (['layout', 'meta', 'blocks'] as $key) {
            $array = $page->{$key};
            if (is_array($array)) {
                $this->attachMediaToBlocks($array, $pageMedia, $libraryMedia);
                $page->{$key} = $array;
            }
        }
    }

    protected function collectFieldUuids(array $blocks): array
    {
        $uuids = [];

        foreach ($blocks as $block) {
            if (! isset($block['data']) || ! is_array($block['data'])) {
                continue;
            }

            foreach ($block['data'] as $key => $value) {
                if ($key === 'id') {
                    continue;
                }

                if (is_string($value) && preg_match(self::UUID_PATTERN, $value)) {
                    $uuids[] = $value;
                } elseif (is_array($value)) {
                    // Array of UUID strings
                    foreach ($value as $item) {
                        if (is_string($item) && preg_match(self::UUID_PATTERN, $item)) {
                            $uuids[] = $item;
                        } elseif (is_array($item)) {
                            // Nested blocks (e.g. buttons)
                            $uuids = array_merge($uuids, $this->collectFieldUuids([$item]));
                        }
                    }
                }
            }
        }

        return $uuids;
    }

    protected function attachMediaToBlocks(&$blocks, $pageMedia, $libraryMedia)
    {
        if (! is_array($blocks)) {
            return;
        }

        foreach ($blocks as &$block) {
            // Existing: inline page-attached media by block id
            if (isset($block['data']['id']) && $pageMedia->has($block['data']['id'])) {
                $block['data']['media'] = $pageMedia->get($block['data']['id'])->map(function ($item) {
                    return $this->isImage($item) ? $item->toImageArray() : $item->getCmsUrl();
                })->all();
            }

            if (isset($block['data']) && is_array($block['data'])) {
                foreach ($block['data'] as $key => &$value) {
                    if ($key === 'id') {
                        continue;
                    }

                    // Single UUID → resolve to media array
                    if (is_string($value) && preg_match(self::UUID_PATTERN, $value) && $libraryMedia->has($value)) {
                        $media = $libraryMedia->get($value);
                        $block['data'][$key.'_media'] = [
                            $this->isImage($media) ? $media->toImageArray() : $media->getCmsUrl(),
                        ];
                    }
                    // Array of UUID strings → resolve each
                    elseif (is_array($value) && ! empty($value)) {
                        $allAreUuids = collect($value)->every(
                            fn ($v) => is_string($v) && preg_match(self::UUID_PATTERN, $v)
                        );

                        if ($allAreUuids) {
                            $resolved = collect($value)->map(function ($uuid) use ($libraryMedia) {
                                if ($libraryMedia->has($uuid)) {
                                    $media = $libraryMedia->get($uuid);

                                    return $this->isImage($media) ? $media->toImageArray() : $media->getCmsUrl();
                                }

                                return null;
                            })->filter()->values()->all();

                            if (! empty($resolved)) {
                                $block['data'][$key.'_media'] = $resolved;
                            }
                        } else {
                            // Recurse for nested blocks (e.g. buttons)
                            $this->attachMediaToBlocks($value, $pageMedia, $libraryMedia);
                        }
                    }
                }
            }
        }
    }

    public function isImage($media): bool
    {
        return isset($media->mime_type) && str_starts_with($media->mime_type, 'image');
    }
}
