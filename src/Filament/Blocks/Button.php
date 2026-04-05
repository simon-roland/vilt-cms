<?php

namespace RolandSolutions\ViltCms\Filament\Blocks;

use RolandSolutions\ViltCms\Filament\Fields\ID;
use RolandSolutions\ViltCms\Filament\Fields\LinkType;
use RolandSolutions\ViltCms\Filament\Fields\Page;
use RolandSolutions\ViltCms\Filament\Fields\Target;
use RolandSolutions\ViltCms\Filament\Fields\URL;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;

class Button extends BaseBlock
{
    public function setup(): Block
    {
        return Block::make('button')
            ->label(fn (?array $state): string => $state['label'] ?? __('cms::cms.block_button'))
            ->schema([
                ID::make(),
                TextInput::make('label')
                    ->label(__('cms::cms.block_button_text'))
                    ->required(),
                ToggleButtons::make('style')
                    ->label(__('cms::cms.block_style'))
                    ->default('primary')
                    ->grouped()
                    ->options(config('cms.buttons', [])),
                LinkType::make(),
                URL::make(),
                Target::make(),
                Page::make(),
            ]);
    }
}
