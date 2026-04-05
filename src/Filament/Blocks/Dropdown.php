<?php

namespace RolandSolutions\ViltCms\Filament\Blocks;

use RolandSolutions\ViltCms\Filament\Fields\ID;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;

class Dropdown extends BaseBlock
{
    public function setup(): Block
    {
        return Block::make('dropdown')
            ->label(fn (?array $state): string => empty($state['label']) ? __('cms::cms.block_dropdown') : __('cms::cms.block_dropdown') . ': ' . $state['label'])
            ->schema([
                ID::make(),
                TextInput::make('label')
                    ->label(__('cms::cms.name'))
                    ->required(),
                Builder::make('items')
                    ->label('Links')
                    ->blocks([
                        Link::make(),
                    ])
                    ->collapsible()
                    ->columnSpan(2)
                    ->required()
                    ->reorderable()
                    ->blockNumbers()
                    ->addActionLabel(__('cms::cms.add'))
                    ->addBetweenActionLabel(__('cms::cms.insert_between')),
            ]);
    }
}
