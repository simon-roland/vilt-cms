<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

class URL extends BaseField
{
    public function setup($options): Field
    {
        return TextInput::make('url')
            ->label('URL')
            ->required()
            ->hidden(fn (Get $get) => $get('link_type') !== 'url')
            ->placeholder('https://')
            ->url();
    }
}
