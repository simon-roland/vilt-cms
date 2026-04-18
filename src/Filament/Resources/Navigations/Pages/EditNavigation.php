<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Navigations\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use RolandSolutions\ViltCms\Filament\Resources\Navigations\NavigationResource;

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
