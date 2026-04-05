<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Navigations\Pages;

use RolandSolutions\ViltCms\Filament\Resources\Navigations\NavigationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNavigation extends EditRecord
{
    protected static string $resource = NavigationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
