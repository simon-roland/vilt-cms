<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Schemas;

use RolandSolutions\ViltCms\Enum\PageStatus;
use RolandSolutions\ViltCms\CmsServiceProvider;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('is_frontpage')
                    ->label(__('cms::cms.page_frontpage'))
                    ->columnSpan(2)
                    ->helperText(__('cms::cms.page_frontpage_helper'))
                    ->dehydrateStateUsing(fn (bool $state): ?bool => $state ?: null),
                TextInput::make('title')
                    ->label(__('cms::cms.title'))
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', str($state)->slug()))
                    ->required(),
                TextInput::make('slug')
                    ->unique(modifyRuleUsing: function (Unique $rule) {
                        return $rule->where('status', PageStatus::Draft);
                    })
                    ->required(),
                Builder::make('layout')
                    ->blocks(CmsServiceProvider::getLayouts())
                    ->collapsible()
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
