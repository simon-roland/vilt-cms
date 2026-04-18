<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;

class LinkType extends BaseField
{
    public function setup($options): Component
    {
        return ToggleButtons::make('link_type')
            ->label(__('cms::cms.field_link_type'))
            ->grouped()
            ->default('page')
            ->options([
                'page' => __('cms::cms.field_page'),
                'url' => __('cms::cms.field_url'),
            ])
            ->icons([
                'page' => 'heroicon-o-home',
                'url' => 'heroicon-o-globe-alt',
            ])
            ->live();
    }
}
