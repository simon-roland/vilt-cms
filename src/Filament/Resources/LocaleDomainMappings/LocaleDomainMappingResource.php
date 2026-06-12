<?php

namespace RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\Pages\CreateLocaleDomainMapping;
use RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\Pages\EditLocaleDomainMapping;
use RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\Pages\ListLocaleDomainMappings;
use RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\Schemas\LocaleDomainMappingForm;
use RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\Tables\LocaleDomainMappingsTable;
use RolandSolutions\ViltCms\Models\LocaleDomainMapping;
use UnitEnum;

class LocaleDomainMappingResource extends Resource
{
    protected static ?string $model = LocaleDomainMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?int $navigationSort = 3;

    protected static string|UnitEnum|null $navigationGroup = null;

    public static function getModelLabel(): string
    {
        return __('cms::cms.locale_domain_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cms::cms.locale_domain_plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('cms::cms.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return LocaleDomainMappingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocaleDomainMappingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocaleDomainMappings::route('/'),
            'create' => CreateLocaleDomainMapping::route('/create'),
            'edit' => EditLocaleDomainMapping::route('/{record}/edit'),
        ];
    }
}
