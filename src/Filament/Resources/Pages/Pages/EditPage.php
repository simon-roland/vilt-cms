<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Pages;

use RolandSolutions\ViltCms\Actions\PublishPage;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
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

            // --- Primary contextual actions ---
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

            // --- Secondary actions menu ---
            ActionGroup::make([
                Action::make('unpublish')
                    ->label(__('cms::cms.page_unpublish'))
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('cms::cms.page_unpublish'))
                    ->modalDescription(__('cms::cms.page_unpublish_confirm'))
                    ->visible(fn ($record) => $record && $record->isPublished() && !$record->trashed())
                    ->action(function ($record) {
                        $record->update([
                            'published_content' => null,
                            'published_at'      => null,
                        ]);

                        $this->refreshFormData(['published_content', 'published_at']);

                        Notification::make()
                            ->title(__('cms::cms.page_unpublish_success'))
                            ->success()
                            ->send();
                    }),

                Action::make('set_as_frontpage')
                    ->label(__('cms::cms.page_set_as_frontpage'))
                    ->icon('heroicon-o-star')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('cms::cms.page_set_as_frontpage'))
                    ->modalDescription(__('cms::cms.page_set_as_frontpage_confirm'))
                    ->visible(fn ($record) => $record && !$record->is_frontpage && $record->isPublished() && !$record->trashed())
                    ->action(function ($record) {
                        $record->update(['is_frontpage' => true]);

                        $this->refreshFormData(['is_frontpage']);

                        Notification::make()
                            ->title(__('cms::cms.page_set_as_frontpage_success'))
                            ->success()
                            ->send();
                    }),

                Action::make('duplicate')
                    ->label(__('cms::cms.page_duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->form([
                        TextInput::make('title')
                            ->label(__('cms::cms.page_duplicate_title'))
                            ->default(fn ($record) => $record->title)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', str($state)->slug()))
                            ->required(),
                        TextInput::make('slug')
                            ->label(__('cms::cms.page_duplicate_slug'))
                            ->default(fn ($record) => $record->slug . '-copy')
                            ->helperText(__('cms::cms.page_slug_locked_after_create'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $newPage = \RolandSolutions\ViltCms\Models\Page::create([
                            'title'  => $data['title'],
                            'slug'   => $data['slug'],
                            'layout' => $record->layout,
                            'blocks' => $record->blocks,
                            'meta'   => $record->meta,
                        ]);

                        Notification::make()
                            ->title(__('cms::cms.page_duplicate_success'))
                            ->success()
                            ->send();

                        $this->redirect(PageResource::getUrl('edit', ['record' => $newPage]));
                    }),

                DeleteAction::make()
                    ->visible(fn ($record) => $record && !$record->trashed()),

                RestoreAction::make()
                    ->visible(fn ($record) => $record && $record->trashed()),

                ForceDeleteAction::make()
                    ->visible(fn ($record) => $record && $record->trashed()),
            ])->label(__('cms::cms.page_more_actions')),
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
