<?php

namespace RolandSolutions\ViltCms\Traits;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait CleansUpOrphanedMedia
{
    protected static function bootCleansUpOrphanedMedia()
    {
        static::updated(function ($model) {
            $currentIds = [];

            collect($model->casts)
                ->filter(fn ($cast) => $cast === 'array')
                ->keys()
                ->each(function ($key) use ($model, &$currentIds) {
                    $arr = $model->{$key} ?? [];
                    $ids = collect(self::collectAllIds($arr))->flatten()->toArray();
                    $currentIds = array_merge($currentIds, $ids);
                });

            $model->media()
                ->whereNotIn('custom_properties->id', $currentIds)
                ->get()
                ->each(function (Media $media) {
                    $media->delete();
                });
        });
    }

    protected static function collectAllIds($array)
    {
        $ids = [];
        foreach ($array as $key => $value) {
            if ($key === 'id') {
                $ids[] = $value;
            }
            if (is_array($value)) {
                $ids = array_merge($ids, self::collectAllIds($value));
            }
        }

        return $ids;
    }
}
