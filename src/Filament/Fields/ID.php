<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Str;

class ID extends BaseField
{
    public function setup($options): Field
    {
        return Hidden::make($options['name'] ?? 'id')
            ->default(fn () => Str::uuid()->toString());
    }
}
