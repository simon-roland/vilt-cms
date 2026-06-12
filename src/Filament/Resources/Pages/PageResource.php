<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use RolandSolutions\ViltCms\Filament\Resources\Pages\Pages\CreatePage;
use RolandSolutions\ViltCms\Filament\Resources\Pages\Pages\EditPage;
use RolandSolutions\ViltCms\Filament\Resources\Pages\Pages\EditPublishedPage;
use RolandSolutions\ViltCms\Filament\Resources\Pages\Pages\ListPages;
use RolandSolutions\ViltCms\Filament\Resources\Pages\Schemas\PageForm;
use RolandSolutions\ViltCms\Filament\Resources\Pages\Tables\PagesTable;
use RolandSolutions\ViltCms\Models\PageContent;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = PageContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    protected static string|UnitEnum|null $navigationGroup = null;

    public static function getModelLabel(): string
    {
        return __('cms::cms.page_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cms::cms.page_plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('cms::cms.navigation_group');
    }

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
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
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
            'edit-published' => EditPublishedPage::route('/{record}/edit-published'),
        ];
    }
}
