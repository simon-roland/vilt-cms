<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use RolandSolutions\ViltCms\Support\Locales;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('locale', Locales::default())
                ->with('page.contents')
            )
            ->columns(array_filter([
                TextColumn::make('published_content')
                    ->badge()
                    ->label(__('cms::cms.page_status'))
                    ->getStateUsing(fn ($record) => $record->isPublished()
                        ? __('cms::cms.page_status_published')
                        : __('cms::cms.page_status_draft'))
                    ->color(fn ($record) => $record->isPublished() ? 'success' : 'warning'),
                TextColumn::make('name')
                    ->label(__('cms::cms.name'))
                    ->sortable()
                    ->searchable(),
                count(Locales::all()) > 1
                    ? TextColumn::make('locale_badges')
                        ->label(__('cms::cms.page_locales'))
                        ->getStateUsing(fn () => '')
                        ->formatStateUsing(function ($state, $record) {
                            $siblings = $record->page->contents->keyBy('locale');

                            return new HtmlString(
                                collect(Locales::all())->map(function ($label, $key) use ($siblings) {
                                    $content = $siblings->get($key);

                                    if (! $content) {
                                        $color = 'background:#e5e7eb;color:#6b7280;';
                                    } elseif ($content->isPublished()) {
                                        $color = 'background:#dcfce7;color:#166534;';
                                    } else {
                                        $color = 'background:#fef3c7;color:#92400e;';
                                    }

                                    return '<span style="'.$color.'display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:500;margin-right:4px;">'.e(strtoupper($key)).'</span>';
                                })->implode('')
                            );
                        })
                    : null,
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
            ]))
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
