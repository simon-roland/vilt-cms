<?php

namespace RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\LocaleDomainMappingResource;

class ListLocaleDomainMappings extends ListRecords
{
    protected static string $resource = LocaleDomainMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false),
        ];
    }
}
