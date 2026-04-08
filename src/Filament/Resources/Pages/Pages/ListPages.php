<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Pages;

use RolandSolutions\ViltCms\Enum\PageStatus;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('cms::cms.page_tab_all'))
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([SoftDeletingScope::class])),
            'draft' => Tab::make(__('cms::cms.page_status_draft'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PageStatus::Draft)),
            'published' => Tab::make(__('cms::cms.page_status_published'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PageStatus::Published)),
            'trashed' => Tab::make(__('cms::cms.page_trashed'))
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }
}
