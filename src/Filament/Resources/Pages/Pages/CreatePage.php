<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Rules\PageSlug;
use RolandSolutions\ViltCms\Support\Locales;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('cms::cms.page_name'))
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', (string) str($state)->slug()))
                ->required(),
            Select::make('locale')
                ->label(__('cms::cms.page_create_locale'))
                ->options(Locales::all())
                ->default(Locales::default())
                ->required()
                ->visible(count(Locales::all()) > 1),
            TextInput::make('slug')
                ->label(__('cms::cms.page_duplicate_slug'))
                ->rules([new PageSlug])
                ->unique(
                    table: 'page_contents',
                    column: 'slug',
                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('locale', $get('locale') ?? Locales::default()),
                )
                ->required(),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $page = Page::create(['name' => $data['name']]);

        return $page->contents()->create([
            'locale' => $data['locale'] ?? Locales::default(),
            'slug' => $data['slug'],
            'layout' => [],
        ]);
    }
}
