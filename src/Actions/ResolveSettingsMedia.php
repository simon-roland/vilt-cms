<?php

namespace RolandSolutions\ViltCms\Actions;

use RolandSolutions\ViltCms\Models\Media;
use RolandSolutions\ViltCms\Models\MediaLibrary;

class ResolveSettingsMedia extends Action
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function handle(array $data): array
    {
        $uuids = [];

        foreach ($data as $value) {
            if (is_string($value) && preg_match(self::UUID_PATTERN, $value)) {
                $uuids[] = $value;
            }
        }

        if (empty($uuids)) {
            return $data;
        }

        $media = Media::whereIn('uuid', array_unique($uuids))
            ->whereHasMorph('model', [MediaLibrary::class])
            ->get()
            ->keyBy('uuid');

        foreach ($data as $key => $value) {
            if (is_string($value) && preg_match(self::UUID_PATTERN, $value) && $media->has($value)) {
                $item = $media->get($value);
                $data[$key . '_media'] = [
                    $this->isImage($item) ? $item->toImageArray() : $item->getCmsUrl(),
                ];
            }
        }

        return $data;
    }

    private function isImage(Media $media): bool
    {
        return str_starts_with($media->mime_type ?? '', 'image/');
    }
}
