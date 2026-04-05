<?php

namespace RolandSolutions\ViltCms\Filament\Blocks;

use RolandSolutions\ViltCms\Filament\Fields\ID;
use RolandSolutions\ViltCms\Filament\Fields\MediaPicker;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class Video extends BaseBlock
{
    public function setup(): Block
    {
        return Block::make('video')
            ->label(__('cms::cms.block_video'))
            ->schema([
                ID::make(),
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make(__('cms::cms.block_content_tab'))
                            ->schema([
                                TextInput::make('headline')
                                    ->label(__('cms::cms.block_headline')),
                                MediaPicker::make('video')
                                    ->label(__('cms::cms.block_video')),
                            ]),
                        Tab::make(__('cms::cms.block_style_tab'))
                            ->schema([
                                Select::make('padding')
                                    ->label(__('cms::cms.block_padding'))
                                    ->default('y')
                                    ->required()
                                    ->options(config('cms.padding', [])),
                            ]),
                    ]),

            ]);
    }
}
