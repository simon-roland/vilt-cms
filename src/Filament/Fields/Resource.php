<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Schemas\Components\Component;
use Filament\Forms\Components\Hidden;

class Resource extends BaseField
{
    public function setup($options): Component
    {
        return Hidden::make('_resource')
            ->default(fn () => $options['default'] ?? null)
            ->dehydrateStateUsing(fn ($state) => $state !== null ? (string) $state : $options['default'] ?? null);
    }
}
