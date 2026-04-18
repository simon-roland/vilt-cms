<?php

namespace RolandSolutions\ViltCms\Filament\Resources\User\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use RolandSolutions\ViltCms\Filament\Resources\User\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
