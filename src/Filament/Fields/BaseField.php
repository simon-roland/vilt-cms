<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Schemas\Components\Component;

abstract class BaseField
{
    abstract public function setup($options): Component;

    public static function make($options = []): Component
    {
        return (new static())->setup($options);
    }
}
