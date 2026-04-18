<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Navigations\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use RolandSolutions\ViltCms\Filament\Resources\Navigations\NavigationResource;

class ListNavigations extends ListRecords
{
    protected static string $resource = NavigationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false),
        ];
    }
}
