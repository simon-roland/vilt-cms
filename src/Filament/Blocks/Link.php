<?php

namespace RolandSolutions\ViltCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use RolandSolutions\ViltCms\Filament\Fields\ID;
use RolandSolutions\ViltCms\Filament\Fields\LinkType;
use RolandSolutions\ViltCms\Filament\Fields\Page;
use RolandSolutions\ViltCms\Filament\Fields\Target;
use RolandSolutions\ViltCms\Filament\Fields\URL;

class Link extends BaseBlock
{
    public function setup(): Block
    {
        return Block::make('link')
            ->label(fn (?array $state): string => empty($state['label']) ? __('cms::cms.block_link') : __('cms::cms.block_link').': '.$state['label'])
            ->schema([
                ID::make(),
                TextInput::make('label')
                    ->label(__('cms::cms.block_link_text'))
                    ->required(),
                LinkType::make(),
                URL::make(),
                Target::make(),
                Page::make(),
            ]);
    }
}
