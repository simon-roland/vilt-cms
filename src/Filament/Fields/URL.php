<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

class URL extends BaseField
{
    public function setup($options): Component
    {
        return TextInput::make('url')
            ->label('URL')
            ->required()
            ->hidden(fn (Get $get) => $get('link_type') !== 'url')
            ->placeholder('https://')
            ->url();
    }
}
