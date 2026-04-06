<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Schemas\Components\Component;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Utilities\Get;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;

class Image extends BaseField
{
    public function setup($options): Component
    {
        return SpatieMediaLibraryFileUpload::make($options['name'] ?? 'image')
            ->disk(config('cms.media_disk'))
            ->customProperties(fn (Get $get) => ['id' => $get('id')])
            ->filterMediaUsing(
                fn (MediaCollection $media, Get $get): MediaCollection => $media->where(
                    'custom_properties.id',
                    $get('id'),
                ),
            )
            ->image()
            ->imageEditor()
            ->downloadable()
            ->deletable()
            ->conversion('webp')
            ->imageEditorAspectRatioOptions([
                null,
                '16:9',
                '5:4',
                '4:3',
                '5:3',
                '1:1',
            ])
            ->maxSize(10 * 1024);
    }
}
