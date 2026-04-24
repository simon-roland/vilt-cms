<?php

namespace RolandSolutions\ViltCms\Actions;

use RolandSolutions\ViltCms\Models\Media;
use RolandSolutions\ViltCms\Models\MediaLibrary;
use RolandSolutions\ViltCms\Models\Page;

class AddMediaToPage extends Action
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function handle(Page $page)
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
            $fields = is_array($block['data'] ?? null) ? $block['data'] : $block;

            if (!is_array($fields)) {
                continue;
            }

            $uuids = array_merge($uuids, $this->collectFieldUuidsFromMap($fields));
        }

        return $uuids;
    }

    protected function collectFieldUuidsFromMap(array $fields): array
    {
        $uuids = [];

        foreach ($fields as $key => $value) {
            if ($key === 'id') {
                continue;
            }

            if (is_string($value) && preg_match(self::UUID_PATTERN, $value)) {
                $uuids[] = $value;
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    if (is_string($item) && preg_match(self::UUID_PATTERN, $item)) {
                        $uuids[] = $item;
                    } elseif (is_array($item)) {
                        // Nested block (Builder) or repeater item (flat assoc array)
                        $uuids = array_merge($uuids, $this->collectFieldUuids([$item]));
                    }
                }
            }
        }

        return $uuids;
    }

    protected function attachMediaToBlocks(&$blocks, $pageMedia, $libraryMedia)
    {
        if (!is_array($blocks)) {
            return;
        }

        foreach ($blocks as &$block) {
            if (!is_array($block)) {
                continue;
            }

            // Existing: inline page-attached media by block id (Builder blocks only)
            if (isset($block['data']['id']) && $pageMedia->has($block['data']['id'])) {
                $block['data']['media'] = $pageMedia->get($block['data']['id'])->map(function ($item) {
                    return $this->isImage($item) ? $item->toImageArray() : $item->getCmsUrl();
                })->all();
            }

            // Builder block wraps fields under 'data'; repeater items are flat assoc arrays.
            if (isset($block['data']) && is_array($block['data'])) {
                $this->resolveFieldsInMap($block['data'], $pageMedia, $libraryMedia);
            } else {
                $this->resolveFieldsInMap($block, $pageMedia, $libraryMedia);
            }
        }
    }

    protected function resolveFieldsInMap(array &$fields, $pageMedia, $libraryMedia)
    {
        foreach ($fields as $key => &$value) {
            if ($key === 'id') {
                continue;
            }

            // Single UUID → resolve to media array
            if (is_string($value) && preg_match(self::UUID_PATTERN, $value) && $libraryMedia->has($value)) {
                $media = $libraryMedia->get($value);
                $fields[$key . '_media'] = [
                    $this->isImage($media) ? $media->toImageArray() : $media->getCmsUrl(),
                ];
            }
            // Array of UUID strings → resolve each; otherwise recurse (nested blocks or repeater items)
            elseif (is_array($value) && !empty($value)) {
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

                    if (!empty($resolved)) {
                        $fields[$key . '_media'] = $resolved;
                    }
                } else {
                    $this->attachMediaToBlocks($value, $pageMedia, $libraryMedia);
                }
            }
        }
    }

    public function isImage($media): bool
    {
        return isset($media->mime_type) && str_starts_with($media->mime_type, 'image');
    }
}
