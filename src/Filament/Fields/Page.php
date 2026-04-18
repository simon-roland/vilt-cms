<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use RolandSolutions\ViltCms\Models\PageContent;
use RolandSolutions\ViltCms\Support\Locales;

class Page extends BaseField
{
    public function setup($options): Component
    {
        return Select::make('page_id')
            ->label(__('cms::cms.field_page'))
            ->required()
            ->hidden(fn (Get $get) => $get('link_type') !== 'page')
            ->options(function () {
                return PageContent::where('locale', Locales::default())
                    ->orderBy('name')
                    ->pluck('name', 'page_id')
                    ->toArray();
            })
            ->searchable()
            ->preload()
            ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) $state : null);
    }
}
