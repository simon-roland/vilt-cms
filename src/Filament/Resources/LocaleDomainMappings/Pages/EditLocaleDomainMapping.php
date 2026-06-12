<?php

namespace RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\LocaleDomainMappingResource;

class EditLocaleDomainMapping extends EditRecord
{
    protected static string $resource = LocaleDomainMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
