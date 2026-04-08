<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Navigations\Schemas;

use RolandSolutions\ViltCms\Filament\Blocks\Dropdown;
use RolandSolutions\ViltCms\Filament\Blocks\Link;

class DefaultNavigationFormSchema
{
    public static function blocks(): array
    {
        return [
            Link::make(),
            Dropdown::make(),
        ];
    }
}
