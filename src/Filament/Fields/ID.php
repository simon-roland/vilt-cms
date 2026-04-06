<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Str;

class ID extends BaseField
{
    public function setup($options): Component
    {
        return Hidden::make($options['name'] ?? 'id')
            ->default(fn () => Str::uuid()->toString());
    }
}
