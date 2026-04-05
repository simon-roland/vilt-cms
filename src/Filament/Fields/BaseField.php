<?php

namespace RolandSolutions\ViltCms\Filament\Fields;

use Filament\Forms\Components\Field;

abstract class BaseField
{
    abstract public function setup($options): Field;

    public static function make($options = []): Field
    {
        return (new static())->setup($options);
    }
}
