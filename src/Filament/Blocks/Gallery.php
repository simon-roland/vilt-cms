<?php

namespace RolandSolutions\ViltCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use RolandSolutions\ViltCms\Filament\Fields\ID;
use RolandSolutions\ViltCms\Filament\Fields\MediaPicker;

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
