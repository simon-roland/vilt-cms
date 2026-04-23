<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Navigations;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use RolandSolutions\ViltCms\Filament\Resources\Navigations\Pages\CreateNavigation;
use RolandSolutions\ViltCms\Filament\Resources\Navigations\Pages\EditNavigation;
use RolandSolutions\ViltCms\Filament\Resources\Navigations\Pages\ListNavigations;
use RolandSolutions\ViltCms\Filament\Resources\Navigations\Schemas\NavigationForm;
use RolandSolutions\ViltCms\Filament\Resources\Navigations\Tables\NavigationsTable;
use RolandSolutions\ViltCms\Models\Navigation;
use UnitEnum;

class NavigationResource extends Resource
{
    protected static ?string $model = Navigation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getModelLabel(): string
    {
        return __('cms::cms.navigation_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cms::cms.navigation_plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('cms::cms.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return NavigationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NavigationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNavigations::route('/'),
            'create' => CreateNavigation::route('/create'),
            'edit' => EditNavigation::route('/{record}/edit'),
        ];
    }
}
