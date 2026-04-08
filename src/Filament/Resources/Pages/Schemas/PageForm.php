<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Schemas;

use RolandSolutions\ViltCms\CmsServiceProvider;
use RolandSolutions\ViltCms\Models\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;

class PageForm
{
    public static function configure(Schema $schema, string $mode = 'draft'): Schema
    {
        return $schema
            ->components([
                // Status notice — only shown when editing an existing record
                Placeholder::make('status_notice')
                    ->label('')
                    ->columnSpan(2)
                    ->html()
                    ->hiddenOn('create')
                    ->content(function (?Page $record) use ($mode): HtmlString {
                        if (!$record) {
                            return new HtmlString('');
                        }

                        if ($mode === 'published') {
                            return new HtmlString(
                                '<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 16px;color:#7f1d1d;font-size:14px;">'
                                . '<strong>' . __('cms::cms.page_edit_published_heading') . '</strong> — '
                                . __('cms::cms.page_edit_published_notice')
                                . '</div>'
                            );
                        }

                        if (!$record->isPublished()) {
                            return new HtmlString(
                                '<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;color:#92400e;font-size:14px;">'
                                . '<strong>' . __('cms::cms.page_status_draft') . '</strong> — '
                                . __('cms::cms.page_never_published_notice')
                                . '</div>'
                            );
                        }

                        if ($record->hasDraftChanges()) {
                            return new HtmlString(
                                '<div style="background:#fff7ed;border:1px solid #fdba74;border-radius:8px;padding:12px 16px;color:#7c2d12;font-size:14px;">'
                                . '<strong>' . __('cms::cms.page_status_draft') . '</strong> — '
                                . __('cms::cms.page_draft_changes_notice')
                                . '</div>'
                            );
                        }

                        return new HtmlString(
                            '<div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:12px 16px;color:#14532d;font-size:14px;">'
                            . '<strong>' . __('cms::cms.page_status_published') . '</strong> — '
                            . __('cms::cms.page_is_live_notice')
                            . '</div>'
                        );
                    }),

                Toggle::make('is_frontpage')
                    ->label(__('cms::cms.page_frontpage'))
                    ->columnSpan(2)
                    ->helperText(__('cms::cms.page_frontpage_helper'))
                    ->dehydrateStateUsing(fn (bool $state): ?bool => $state ?: null),
                TextInput::make('title')
                    ->label(__('cms::cms.title'))
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, ?Page $record) => $record === null ? $set('slug', str($state)->slug()) : null)
                    ->required(),
                TextInput::make('slug')
                    ->unique(modifyRuleUsing: function (Unique $rule, ?Page $record) {
                        return $rule->ignore($record?->id);
                    })
                    ->required(),
                Builder::make('layout')
                    ->blocks(CmsServiceProvider::getLayouts())
                    ->collapsible()
                    ->collapsed(fn (?Page $record) => $record !== null)
                    ->columnSpan(2)
                    ->required()
                    ->reorderable(false)
                    ->maxItems(1)
                    ->blockNumbers(false)
                    ->addActionLabel(__('cms::cms.page_select_layout')),
                Builder::make('blocks')
                    ->label(__('cms::cms.page_content_blocks'))
                    ->columnSpan(2)
                    ->collapsible()
                    ->collapsed(fn (?Page $record) => $record !== null)
                    ->blockPickerColumns(2)
                    ->addAction(fn (Action $action) => $action->label(__('cms::cms.page_add_content')))
                    ->addBetweenAction(fn (Action $action) => $action->label(__('cms::cms.insert_between')))
                    ->blocks(CmsServiceProvider::getBlocks()),
                Section::make(__('cms::cms.seo_section'))
                    ->columnSpan(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('meta.title')
                            ->label(__('cms::cms.seo_title'))
                            ->placeholder(__('cms::cms.seo_title_placeholder')),
                        Textarea::make('meta.description')
                            ->label(__('cms::cms.seo_description'))
                            ->rows(3),
                        Select::make('meta.robots')
                            ->label(__('cms::cms.seo_robots'))
                            ->options([
                                'index,follow' => 'index, follow',
                                'noindex,nofollow' => 'noindex, nofollow',
                                'noindex,follow' => 'noindex, follow',
                                'index,nofollow' => 'index, nofollow',
                            ])
                            ->default('index,follow'),
                    ]),
            ]);
    }
}
