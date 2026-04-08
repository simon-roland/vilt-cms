<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Pages;

use RolandSolutions\ViltCms\Actions\PublishPage;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    public bool $publishAfterSave = false;

    public function getHeading(): string|Htmlable
    {
        $record = $this->getRecord();

        if ($record && $record->isPublished()) {
            if ($record->hasDraftChanges()) {
                return __('cms::cms.page_edit_heading_draft_changes');
            }

            return __('cms::cms.page_edit_heading_published');
        }

        return __('cms::cms.page_edit_heading_draft');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label(__('cms::cms.page_publish'))
                ->icon('heroicon-o-arrow-up-circle')
                ->color('success')
                ->visible(fn ($record) => $record && !$record->trashed())
                ->action(function ($record) {
                    PublishPage::make()->handle($record);

                    Notification::make()
                        ->title(__('cms::cms.page_publish_success'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['published_content', 'published_at']);
                }),

            Action::make('edit_published')
                ->label(__('cms::cms.page_edit_published_button'))
                ->icon('heroicon-o-bolt')
                ->color('danger')
                ->visible(fn ($record) => $record && $record->isPublished() && $record->hasDraftChanges() && !$record->trashed())
                ->url(fn ($record) => PageResource::getUrl('edit-published', ['record' => $record])),

            Action::make('discard_draft')
                ->label(__('cms::cms.page_discard_draft'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading(__('cms::cms.page_discard_draft'))
                ->modalDescription(__('cms::cms.page_discard_draft_confirm'))
                ->visible(fn ($record) => $record && $record->isPublished() && $record->hasDraftChanges() && !$record->trashed())
                ->action(function ($record) {
                    $record->update([
                        'title'  => $record->published_content['title'],
                        'layout' => $record->published_content['layout'],
                        'blocks' => $record->published_content['blocks'] ?? null,
                        'meta'   => $record->published_content['meta'] ?? null,
                    ]);

                    $this->fillForm();

                    Notification::make()
                        ->title(__('cms::cms.page_discard_draft_success'))
                        ->success()
                        ->send();
                }),

            DeleteAction::make()
                ->visible(fn ($record) => $record && !$record->trashed()),

            RestoreAction::make()
                ->visible(fn ($record) => $record && $record->trashed()),

            ForceDeleteAction::make()
                ->visible(fn ($record) => $record && $record->trashed()),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save_as_draft')
                ->label(__('cms::cms.page_save_as_draft'))
                ->submit('save')
                ->keyBindings(['mod+s']),

            Action::make('save_and_publish')
                ->label(__('cms::cms.page_save_and_publish'))
                ->color('success')
                ->submit('saveAndPublish'),

            $this->getCancelFormAction(),
        ];
    }

    public function saveAndPublish(): void
    {
        $this->publishAfterSave = true;
        $this->save();
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $record->update($data);

        if ($this->publishAfterSave) {
            PublishPage::make()->handle($record);
            $this->publishAfterSave = false;
        }

        return $record;
    }
}
