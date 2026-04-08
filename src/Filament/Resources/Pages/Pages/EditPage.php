<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Pages;

use RolandSolutions\ViltCms\Enum\PageStatus;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use RolandSolutions\ViltCms\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label(__('cms::cms.page_publish'))
                ->icon('heroicon-o-arrow-up-circle')
                ->color('success')
                ->visible(fn ($record) => $record && !$record->trashed() && $record->status === PageStatus::Draft)
                ->action(function ($record) {
                    $this->publishDraft($record);

                    Notification::make()
                        ->title(__('cms::cms.page_publish_success'))
                        ->success()
                        ->send();
                }),

            DeleteAction::make()
                ->modalHeading(__('cms::cms.page_delete_both'))
                ->modalDescription(__('cms::cms.page_delete_both_body'))
                ->action(function ($record) {
                    Page::where('slug', $record->slug)->delete();
                    $this->redirect(PageResource::getUrl('index'));
                }),

            RestoreAction::make()
                ->action(function ($record) {
                    Page::withTrashed()->where('slug', $record->slug)->restore();
                    $this->redirect(PageResource::getUrl('index'));
                }),

            ForceDeleteAction::make()
                ->action(function ($record) {
                    Page::withTrashed()->where('slug', $record->slug)->forceDelete();
                    $this->redirect(PageResource::getUrl('index'));
                }),
        ];
    }

    private function publishDraft(Page $draft): void
    {
        $published = Page::where('slug', $draft->slug)
            ->where('status', PageStatus::Published)
            ->first();

        $data = [
            'title'        => $draft->title,
            'layout'       => $draft->layout,
            'blocks'       => $draft->blocks,
            'meta'         => $draft->meta,
            'is_frontpage' => $draft->is_frontpage,
        ];

        if ($published) {
            $published->update($data);
        } else {
            Page::create(array_merge($data, [
                'slug'   => $draft->slug,
                'status' => PageStatus::Published,
            ]));
        }
    }
}
