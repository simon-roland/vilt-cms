<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use RolandSolutions\ViltCms\Actions\PublishPage;
use RolandSolutions\ViltCms\Filament\Resources\Pages\Concerns\HasPageActions;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use RolandSolutions\ViltCms\Support\Locales;

class EditPage extends EditRecord
{
    use HasPageActions;

    protected static string $resource = PageResource::class;

    public bool $publishAfterSave = false;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->page->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (count(Locales::all()) <= 1) {
            return null;
        }

        $record = $this->getRecord();
        $label = Locales::all()[$record->locale] ?? $record->locale;
        $status = $record->isPublished()
            ? __('cms::cms.page_status_published')
            : __('cms::cms.page_status_draft');

        return $label.' · '.$status;
    }

    protected function getHeaderActions(): array
    {
        return array_merge(
            $this->localeSwitcherActions(),
            [
                // --- Primary contextual actions ---
                Action::make('view_page')
                    ->label(__('cms::cms.view_page'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn ($record) => $record && ! $record->page->trashed())
                    ->url(function ($record) {
                        $bothVersions = $record->isPublished() && $record->hasDraftChanges();
                        $base = $this->localeUrl($record);

                        return $bothVersions ? $base.'?preview=draft' : $base;
                    }),

                Action::make('publish')
                    ->label(__('cms::cms.page_publish'))
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record && ! $record->page->trashed() && $record->hasDraftChanges())
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
                    ->visible(fn ($record) => $record && $record->isPublished() && ! $record->hasDraftChanges() && ! $record->page->trashed())
                    ->action(fn () => $this->save(shouldRedirect: false)),

                Action::make('edit_published')
                    ->label(__('cms::cms.page_edit_published_button'))
                    ->icon('heroicon-o-bolt')
                    ->color('danger')
                    ->visible(fn ($record) => $record && $record->isPublished() && $record->hasDraftChanges() && ! $record->page->trashed())
                    ->url(fn ($record) => PageResource::getUrl('edit-published', ['record' => $record])),

                Action::make('discard_draft')
                    ->label(__('cms::cms.page_discard_draft'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(__('cms::cms.page_discard_draft'))
                    ->modalDescription(__('cms::cms.page_discard_draft_confirm'))
                    ->visible(fn ($record) => $record && $record->isPublished() && $record->hasDraftChanges() && ! $record->page->trashed())
                    ->action(function ($record) {
                        $record->update([
                            'layout' => $record->published_content['layout'],
                            'blocks' => $record->published_content['blocks'] ?? null,
                            'meta' => $record->published_content['meta'] ?? null,
                        ]);

                        $this->fillForm();

                        Notification::make()
                            ->title(__('cms::cms.page_discard_draft_success'))
                            ->success()
                            ->send();
                    }),

                // --- Secondary actions menu ---
                $this->secondaryActionsGroup([
                    $this->renamePageAction(),
                    $this->changeSlugAction(),
                    $this->copyFromLocaleAction(),
                    $this->unpublishAction(),
                    $this->setAsFrontpageAction(),
                    $this->duplicateAction(),
                ]),
            ]
        );
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

        if ($record && $record->isPublished() && ! $record->hasDraftChanges()) {
            $this->publishAfterSave = true;
        }

        $this->save(shouldRedirect: false);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        if ($this->publishAfterSave) {
            PublishPage::make()->handle($record);
            $this->publishAfterSave = false;
        }

        return $record;
    }
}
