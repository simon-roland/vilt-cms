<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Navigations\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use RolandSolutions\ViltCms\CmsServiceProvider;
use RolandSolutions\ViltCms\Enum\NavigationType;
use RolandSolutions\ViltCms\Models\Navigation;

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
                    ->blocks(CmsServiceProvider::getNavigationFormBlocks())
                    ->collapsible()
                    ->collapsed(fn (?Navigation $record) => $record !== null)
                    ->columnSpan(2)
                    ->required()
                    ->reorderable()
                    ->blockNumbers()
                    ->addActionLabel(__('cms::cms.navigation_add_item'))
                    ->addBetweenActionLabel(__('cms::cms.insert_between')),
            ]);
    }
}
