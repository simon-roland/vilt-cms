<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Pages;

use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use RolandSolutions\ViltCms\Filament\Resources\Pages\Schemas\PageForm;
use RolandSolutions\ViltCms\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class EditPublishedPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    public function getHeading(): string|Htmlable
    {
        return __('cms::cms.page_edit_published_heading');
    }

    public function form(Schema $schema): Schema
    {
        return PageForm::configure($schema, 'published');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Page $record */
        $record = $this->getRecord();

        $data['title']  = $record->published_content['title'] ?? $record->title;
        $data['layout'] = $record->published_content['layout'] ?? $record->layout;
        $data['blocks'] = $record->published_content['blocks'] ?? $record->blocks;
        $data['meta']   = $record->published_content['meta'] ?? $record->meta;

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $record->update([
            'published_content' => [
                'title'  => $data['title'],
                'layout' => $data['layout'] ?? null,
                'blocks' => $data['blocks'] ?? null,
                'meta'   => $data['meta'] ?? null,
            ],
            'published_at' => now(),
        ]);

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('cms::cms.page_edit_published_save_success');
    }

    protected function getRedirectUrl(): string
    {
        return PageResource::getUrl('edit-published', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_draft')
                ->label(__('cms::cms.page_edit_published_back'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn ($record) => PageResource::getUrl('edit', ['record' => $record])),

            DeleteAction::make()
                ->visible(fn ($record) => $record && !$record->trashed()),

            RestoreAction::make()
                ->visible(fn ($record) => $record && $record->trashed()),

            ForceDeleteAction::make()
                ->visible(fn ($record) => $record && $record->trashed()),
        ];
    }
}
