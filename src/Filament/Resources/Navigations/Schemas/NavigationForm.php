<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Navigations\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use RolandSolutions\ViltCms\CmsServiceProvider;
use RolandSolutions\ViltCms\Enum\NavigationType;
use RolandSolutions\ViltCms\Models\Navigation;
use RolandSolutions\ViltCms\Support\Locales;

class NavigationForm
{
    public static function configure(Schema $schema): Schema
    {
        $multiLocale = count(Locales::all()) > 1;

        return $schema
            ->components([
                Select::make('locale')
                    ->label(__('cms::cms.navigation_locale'))
                    ->options(Locales::all())
                    ->default(Locales::default())
                    ->required()
                    ->disabled(fn (?Navigation $record) => $record !== null)
                    ->dehydrated()
                    ->visible($multiLocale)
                    ->columnSpan(2),
                Select::make('type')
                    ->label(__('cms::cms.type'))
                    ->options(NavigationType::options())
                    ->unique(
                        modifyRuleUsing: fn ($rule, Get $get) => $rule->where('locale', $get('locale') ?? Locales::default()),
                        ignoreRecord: true,
                    )
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
