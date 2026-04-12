<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                BadgeColumn::make('published_content')
                    ->label(__('cms::cms.page_status'))
                    ->getStateUsing(fn ($record) => $record->isPublished()
                        ? __('cms::cms.page_status_published')
                        : __('cms::cms.page_status_draft'))
                    ->color(fn ($record) => $record->isPublished() ? 'success' : 'warning'),
                TextColumn::make('name')
                    ->label(__('cms::cms.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('cms::cms.updated_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('cms::cms.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
