<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;

class Resource extends BaseField
{
    public function setup($options): Field
    {
        return Hidden::make('_resource')
            ->default(fn () => $options['default'] ?? null)
            ->dehydrateStateUsing(fn ($state) => $state !== null ? (string) $state : $options['default'] ?? null);
    }
}
