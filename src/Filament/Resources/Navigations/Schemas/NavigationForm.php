<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Navigations\Schemas;

use RolandSolutions\ViltCms\Enum\NavigationType;
use RolandSolutions\ViltCms\Filament\Blocks\Dropdown;
use RolandSolutions\ViltCms\Filament\Blocks\Link;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class NavigationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label(__('cms::cms.type'))
                    ->options(NavigationType::options())
                    ->unique()
                    ->columnSpan(2)
                    ->required(),
                Builder::make('items')
                    ->label(__('cms::cms.navigation_menu_items'))
                    ->blocks([
                        Link::make(),
                        Dropdown::make(),
                    ])
                    ->collapsible()
                    ->columnSpan(2)
                    ->required()
                    ->reorderable()
                    ->blockNumbers()
                    ->addActionLabel(__('cms::cms.navigation_add_item'))
                    ->addBetweenActionLabel(__('cms::cms.insert_between')),
            ]);
    }
}
