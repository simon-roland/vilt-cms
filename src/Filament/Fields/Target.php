<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Schemas\Components\Component;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;

class Target extends BaseField
{
    public function setup($options): Component
    {
        return ToggleButtons::make('target')
            ->label(__('cms::cms.field_target'))
            ->default('_blank')
            ->required()
            ->options([
                '_blank' => __('cms::cms.field_new_window'),
                '_self' => __('cms::cms.field_current_window'),
            ])
            ->grouped()
            ->hidden(fn (Get $get) => $get('link_type') !== 'url');
    }
}
