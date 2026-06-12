<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Navigations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use RolandSolutions\ViltCms\Support\Locales;

class NavigationsTable
{
    public static function configure(Table $table): Table
    {
        $multiLocale = count(Locales::all()) > 1;

        return $table
            ->columns([
                TextColumn::make('locale')
                    ->label(__('cms::cms.navigation_locale'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Locales::all()[$state] ?? $state)
                    ->sortable()
                    ->visible($multiLocale),
                TextColumn::make('type')
                    ->label(__('cms::cms.type'))
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('updated_at')
                    ->label(__('cms::cms.updated_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('cms::cms.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort(fn ($query) => $multiLocale
                ? $query->orderBy('locale')->orderBy('type')
                : $query->orderBy('type'))
            ->filters([
                //
            ])
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
