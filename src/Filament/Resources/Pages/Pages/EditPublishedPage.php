<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use RolandSolutions\ViltCms\Filament\Resources\Pages\Concerns\HasPageActions;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use RolandSolutions\ViltCms\Filament\Resources\Pages\Schemas\PageForm;
use RolandSolutions\ViltCms\Models\PageContent;

class EditPublishedPage extends EditRecord
{
    use HasPageActions;

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
        /** @var PageContent $record */
        $record = $this->getRecord();

        // Backward compat: old snapshots only have 'title', new ones have 'name'
        $data['name'] = $record->published_content['name'] ?? $record->published_content['title'] ?? $record->name;
        $data['layout'] = $record->published_content['layout'] ?? $record->layout;
        $data['blocks'] = $record->published_content['blocks'] ?? $record->blocks;
        $data['meta'] = $record->published_content['meta'] ?? $record->meta;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update([
            'published_content' => [
                'name' => $data['name'],
                'layout' => $data['layout'] ?? null,
                'blocks' => $data['blocks'] ?? null,
                'meta' => $data['meta'] ?? null,
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

    /**
     * After unpublishing from the published-edit view, redirect back to the draft editor.
     */
    protected function onUnpublish(PageContent $record): void
    {
        $this->redirect(PageResource::getUrl('edit', ['record' => $record]));
    }

    protected function getHeaderActions(): array
    {
        return array_merge(
            $this->localeSwitcherActions(),
            [
                Action::make('back_to_draft')
                    ->label(__('cms::cms.page_edit_published_back'))
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->url(fn ($record) => PageResource::getUrl('edit', ['record' => $record])),

                Action::make('view_page')
                    ->label(__('cms::cms.view_page'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn ($record) => $record && ! $record->trashed())
                    ->url(function ($record) {
                        $bothVersions = $record->isPublished() && $record->hasDraftChanges();
                        $base = $this->localeUrl($record);

                        return $bothVersions ? $base.'?preview=published' : $base;
                    }),

                $this->secondaryActionsGroup([
                    $this->changeSlugAction(),
                    $this->setAsFrontpageAction(),
                    $this->duplicateAction(),
                    $this->unpublishAction(),
                ]),
            ]
        );
    }
}
