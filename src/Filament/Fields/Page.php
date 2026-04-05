<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use RolandSolutions\ViltCms\Models\Page as PageModel;

class Page extends BaseField
{
    public function setup($options): Field
    {
        return Select::make('page_id')
            ->label(__('cms::cms.field_page'))
            ->required()
            ->hidden(fn (Get $get) => $get('link_type') !== 'page')
            ->options(function () {
                return PageModel::orderBy('title')
                    ->pluck('title', 'id')
                    ->toArray();
            })
            ->searchable()
            ->preload()
            ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) $state : null);
    }
}
