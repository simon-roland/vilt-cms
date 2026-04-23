<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;
use RolandSolutions\ViltCms\CmsServiceProvider;
use RolandSolutions\ViltCms\Filament\Fields\MediaPicker;
use RolandSolutions\ViltCms\Models\PageContent;
use RolandSolutions\ViltCms\Rules\ReservedLocaleSlug;
use RolandSolutions\ViltCms\Support\Locales;

class PageForm
{
    public static function configure(Schema $schema, string $mode = 'draft'): Schema
    {
        return $schema
            ->components([
                // Status notice — only shown when editing an existing record
                TextEntry::make('status_notice')
                    ->hiddenLabel()
                    ->columnSpan(2)
                    ->html()
                    ->hiddenOn('create')
                    ->state(function (?PageContent $record) use ($mode): HtmlString {
                        if (! $record) {
                            return new HtmlString('');
                        }

                        if ($mode === 'published') {
                            return new HtmlString(
                                '<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 16px;color:#7f1d1d;font-size:14px;">'
                                .'<strong>'.__('cms::cms.page_edit_published_heading').'</strong> — '
                                .__('cms::cms.page_edit_published_notice')
                                .'</div>'
                            );
                        }

                        if (! $record->isPublished()) {
                            return new HtmlString(
                                '<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;color:#92400e;font-size:14px;">'
                                .'<strong>'.__('cms::cms.page_status_draft').'</strong> — '
                                .__('cms::cms.page_never_published_notice')
                                .'</div>'
                            );
                        }

                        if ($record->hasDraftChanges()) {
                            return new HtmlString(
                                '<div style="background:#fff7ed;border:1px solid #fdba74;border-radius:8px;padding:12px 16px;color:#7c2d12;font-size:14px;">'
                                .'<strong>'.__('cms::cms.page_status_draft').'</strong> — '
                                .__('cms::cms.page_draft_changes_notice')
                                .'</div>'
                            );
                        }

                        return new HtmlString(
                            '<div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:12px 16px;color:#14532d;font-size:14px;">'
                            .__('cms::cms.page_is_live_notice')
                            .'</div>'
                        );
                    }),

                TextEntry::make('frontpage_notice')
                    ->hiddenLabel()
                    ->columnSpan(2)
                    ->html()
                    ->hiddenOn('create')
                    ->state(function (?PageContent $record): HtmlString {
                        if (! $record || ! $record->is_frontpage) {
                            return new HtmlString('');
                        }

                        return new HtmlString(
                            '<div style="background:#eff6ff;border:1px solid #93c5fd;border-radius:8px;padding:10px 16px;color:#1e3a8a;font-size:14px;">'
                            .'⭐ '.__('cms::cms.page_frontpage_indicator')
                            .'</div>'
                        );
                    }),

                TextInput::make('name')
                    ->label(__('cms::cms.page_name'))
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, ?PageContent $record) => $record === null ? $set('slug', str($state)->slug()) : null)
                    ->required(),
                TextInput::make('slug')
                    ->rules([
                        new ReservedLocaleSlug],
                        fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $slug = str($value ?: $get('name') ?? '')->slug();
                            if (blank($slug)) {
                                $fail(__('validation.regex', ['attribute' => $attribute]));
                            }
                        }
                    )
                    ->unique(
                        table: 'page_contents',
                        column: 'slug',
                        ignorable: fn (?PageContent $record) => $record,
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('locale', Locales::default()),
                    )
                    ->disabled(fn (?PageContent $record) => $record !== null)
                    ->dehydrated(fn (?PageContent $record) => $record === null)
                    ->helperText(function (?PageContent $record) {
                        if ($record === null) {
                            return __('cms::cms.page_slug_helper');
                        }
                        if ($record->is_frontpage) {
                            return __('cms::cms.page_slug_frontpage_notice');
                        }

                        return null;
                    })
                    ->required(),
                Builder::make('layout')
                    ->blocks(CmsServiceProvider::getLayouts())
                    ->collapsible()
                    ->collapsed(fn (?PageContent $record) => $record !== null)
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
                    ->collapsed(fn (?PageContent $record) => $record !== null)
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
                        MediaPicker::make('meta.og_image')
                            ->label(__('cms::cms.seo_og_image')),
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
