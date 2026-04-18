<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Support\Locales;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $page = Page::create([]);

        return $page->contents()->create(array_merge(
            ['locale' => Locales::default()],
            $data,
        ));
    }
}
