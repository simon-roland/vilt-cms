<?php

namespace RolandSolutions\ViltCms\Filament\Resources\User\Pages;

use RolandSolutions\ViltCms\Filament\Resources\User\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
