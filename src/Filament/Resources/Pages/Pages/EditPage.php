<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Pages;

use RolandSolutions\ViltCms\Actions\PublishPage;
use RolandSolutions\ViltCms\Filament\Resources\Pages\Concerns\HasPageActions;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    use HasPageActions;

    protected static string $resource = PageResource::class;

    public bool $publishAfterSave = false;

    protected function getHeaderActions(): array
    {
        return [
            // --- Primary contextual actions ---
            Action::make('view_page')
                ->label(__('cms::cms.view_page'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->visible(fn ($record) => $record && !$record->trashed())
                ->url(function ($record) {
                    $bothVersions = $record->isPublished() && $record->hasDraftChanges();
                    $base = $record->is_frontpage ? route('pages.frontpage') : route('pages.show', $record->slug);

                    return $bothVersions ? $base . '?preview=draft' : $base;
                }),

            Action::make('publish')
                ->label(__('cms::cms.page_publish'))
                ->icon('heroicon-o-arrow-up-circle')
                ->color('success')
                ->visible(fn ($record) => $record && !$record->trashed() && $record->hasDraftChanges())
                ->action(function ($record) {
                    PublishPage::make()->handle($record);

                    Notification::make()
                        ->title(__('cms::cms.page_publish_success'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['published_content', 'published_at']);
                }),

            Action::make('save_as_draft')
                ->label(__('cms::cms.page_save_as_draft'))
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn ($record) => $record && $record->isPublished() && !$record->hasDraftChanges() && !$record->trashed())
                ->action(fn () => $this->save(shouldRedirect: false)),

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
                        'name'   => $record->published_content['name'] ?? $record->published_content['title'] ?? $record->name,
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

            // --- Secondary actions menu ---
            $this->secondaryActionsGroup([
                $this->changeSlugAction(),
                $this->unpublishAction(),
                $this->setAsFrontpageAction(),
                $this->duplicateAction(),
            ]),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->action(fn () => $this->saveOrPublish())
                ->keyBindings(['mod+s']),
            $this->getCancelFormAction(),
        ];
    }

    public function saveOrPublish(): void
    {
        $record = $this->getRecord();

        if ($record && $record->isPublished() && !$record->hasDraftChanges()) {
            $this->publishAfterSave = true;
        }

        $this->save(shouldRedirect: false);
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
