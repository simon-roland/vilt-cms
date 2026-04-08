<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Tables;

use RolandSolutions\ViltCms\Enum\PageStatus;
use RolandSolutions\ViltCms\Models\Page;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                IconColumn::make('is_frontpage')
                    ->label(__('cms::cms.page_frontpage'))
                    ->boolean()
                    ->getStateUsing(fn ($record) => (bool) $record->is_frontpage)
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label(__('cms::cms.page_status'))
                    ->getStateUsing(fn ($record) => $record->status === PageStatus::Draft
                        ? __('cms::cms.page_status_draft')
                        : __('cms::cms.page_status_published'))
                    ->color(fn ($record) => $record->status === PageStatus::Draft ? 'warning' : 'success'),
                TextColumn::make('title')
                    ->label(__('cms::cms.title'))
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
                    BulkAction::make('delete')
                        ->label(__('filament-actions::delete.multiple.label'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('cms::cms.page_delete_both'))
                        ->modalDescription(__('cms::cms.page_delete_both_body'))
                        ->action(function (Collection $records) {
                            $slugs = $records->pluck('slug')->unique()->all();
                            Page::whereIn('slug', $slugs)->delete();
                        })
                        ->deselectRecordsAfterCompletion(),
                    RestoreBulkAction::make()
                        ->action(function (Collection $records) {
                            $slugs = $records->pluck('slug')->unique()->all();
                            Page::withTrashed()->whereIn('slug', $slugs)->restore();
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            $slugs = $records->pluck('slug')->unique()->all();
                            Page::withTrashed()->whereIn('slug', $slugs)->forceDelete();
                        }),
                ]),
            ]);
    }
}
