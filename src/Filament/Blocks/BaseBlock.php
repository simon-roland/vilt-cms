<?php

namespace RolandSolutions\ViltCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;

abstract class BaseBlock
{
    abstract public function setup(): Block;

    public static function make(): Block
    {
        return (new static)->setup();
    }
}
