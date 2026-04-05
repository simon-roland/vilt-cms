<?php

namespace RolandSolutions\ViltCms\Filament\Blocks;

use RolandSolutions\ViltCms\Filament\Fields\ID;
use RolandSolutions\ViltCms\Filament\Fields\MediaPicker;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;

class Gallery extends BaseBlock
{
    public function setup(): Block
    {
        return Block::make('gallery')
            ->label(__('cms::cms.block_gallery'))
            ->schema([
                ID::make(),
                TextInput::make('headline')
                    ->label(__('cms::cms.block_headline')),
                MediaPicker::make('images')
                    ->label(__('cms::cms.block_images'))
                    ->multiple(),
            ]);
    }
}
