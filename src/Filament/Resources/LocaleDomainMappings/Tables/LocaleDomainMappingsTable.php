<?php

namespace RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use RolandSolutions\ViltCms\Support\Locales;

class LocaleDomainMappingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')
                    ->label(__('cms::cms.locale_domain_domain'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('locale')
                    ->label(__('cms::cms.locale_domain_locale'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Locales::all()[$state] ?? $state)
                    ->sortable(),
            ])
            ->defaultSort('domain')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
