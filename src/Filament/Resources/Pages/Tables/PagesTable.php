<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Models\PageContent;
use RolandSolutions\ViltCms\Support\Locales;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $defaultLocale = Locales::default();

                // Show one row per page: prefer the default-locale content, else the
                // lowest-id content. Also scope out PageContents whose parent Page is
                // trashed — the TernaryFilter below toggles that scope.
                $query
                    ->where(function (Builder $inner) use ($defaultLocale) {
                        $inner->where(function (Builder $q) use ($defaultLocale) {
                            $q->where('locale', $defaultLocale);
                        })->orWhere(function (Builder $q) use ($defaultLocale) {
                            $q->whereNotIn('page_id', PageContent::query()
                                ->where('locale', $defaultLocale)
                                ->select('page_id'))
                                ->whereRaw('page_contents.id = (SELECT MIN(pc2.id) FROM page_contents pc2 WHERE pc2.page_id = page_contents.page_id)');
                        });
                    })
                    ->with(['page', 'page.contents']);
            })
            ->columns([
                TextColumn::make('published_content')
                    ->badge()
                    ->label(__('cms::cms.page_status'))
                    ->getStateUsing(fn ($record) => $record->isPublished()
                        ? __('cms::cms.page_status_published')
                        : __('cms::cms.page_status_draft'))
                    ->color(fn ($record) => $record->isPublished() ? 'success' : 'warning'),
                TextColumn::make('page.name')
                    ->label(__('cms::cms.page_name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('locale_badges')
                    ->label(__('cms::cms.page_locales'))
                    ->html()
                    ->getStateUsing(function ($record) {
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
                    ->visible(count(Locales::all()) > 1),
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
                TernaryFilter::make('trashed')
                    ->label(__('filament-tables::filters/trashed.label'))
                    ->placeholder(__('filament-tables::filters/trashed.placeholder'))
                    ->trueLabel(__('filament-tables::filters/trashed.true_label'))
                    ->falseLabel(__('filament-tables::filters/trashed.false_label'))
                    ->queries(
                        true: fn (Builder $q) => $q->whereHas('page', fn ($sub) => $sub->withTrashed()),
                        false: fn (Builder $q) => $q->whereHas('page', fn ($sub) => $sub->onlyTrashed()),
                        blank: fn (Builder $q) => $q->whereHas('page'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete_pages')
                        ->label(__('cms::cms.page_delete_page'))
                        ->icon('heroicon-o-archive-box-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            Page::whereIn('id', $records->pluck('page_id'))->get()->each->delete();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('restore_pages')
                        ->label(__('cms::cms.page_restore_page'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            Page::onlyTrashed()->whereIn('id', $records->pluck('page_id'))->get()->each->restore();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('force_delete_pages')
                        ->label(__('cms::cms.page_force_delete_page'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            Page::withTrashed()->whereIn('id', $records->pluck('page_id'))->get()->each->forceDelete();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
