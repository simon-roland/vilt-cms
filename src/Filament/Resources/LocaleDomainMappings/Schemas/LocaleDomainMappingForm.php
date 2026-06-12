<?php

namespace RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use RolandSolutions\ViltCms\Support\Locales;

class LocaleDomainMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('locale')
                    ->label(__('cms::cms.locale_domain_locale'))
                    ->options(Locales::all())
                    ->default(Locales::default())
                    ->required(),
                TextInput::make('domain')
                    ->label(__('cms::cms.locale_domain_domain'))
                    ->helperText(__('cms::cms.locale_domain_domain_helper'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : strtolower(trim($state))),
            ]);
    }
}
