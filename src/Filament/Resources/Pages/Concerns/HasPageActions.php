<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Unique;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Models\PageContent;
use RolandSolutions\ViltCms\Rules\ReservedLocaleSlug;

trait HasPageActions
{
    /**
     * Called after a successful unpublish. Override in subclasses to customise
     * the post-unpublish behaviour (e.g. redirect vs. form refresh).
     */
    protected function onUnpublish(PageContent $record): void
    {
        $this->refreshFormData(['published_content', 'published_at']);
    }

    protected function changeSlugAction(): Action
    {
        return Action::make('change_slug')
            ->label(__('cms::cms.page_change_slug'))
            ->icon('heroicon-o-link')
            ->color('gray')
            ->visible(fn ($record) => $record && ! $record->trashed() && ! $record->is_frontpage)
            ->modalHeading(__('cms::cms.page_change_slug_heading'))
            ->modalDescription(__('cms::cms.page_change_slug_description'))
            ->schema([
                TextInput::make('slug')
                    ->label(__('cms::cms.page_change_slug_field'))
                    ->default(fn ($record) => $record->slug)
                    ->rules([new ReservedLocaleSlug])
                    ->unique(
                        table: 'page_contents',
                        column: 'slug',
                        ignorable: fn ($record) => $record,
                        modifyRuleUsing: fn (Unique $rule, $record) => $rule->where('locale', $record->locale),
                    )
                    ->rules([
                        fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                            if (blank(str($value)->slug())) {
                                $fail(__('validation.regex', ['attribute' => $attribute]));
                            }
                        },
                    ])
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                $record->update(['slug' => $data['slug']]);

                $this->refreshFormData(['slug']);

                Notification::make()
                    ->title(__('cms::cms.page_change_slug_success'))
                    ->success()
                    ->send();
            });
    }

    protected function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label(__('cms::cms.page_duplicate'))
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->form([
                TextInput::make('name')
                    ->label(__('cms::cms.page_name'))
                    ->default(fn ($record) => $record->name)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', str($state)->slug()))
                    ->required(),
                TextInput::make('slug')
                    ->label(__('cms::cms.page_duplicate_slug'))
                    ->default(fn ($record) => $record->slug.'-copy')
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                $newPage = Page::create([]);

                $newContent = $newPage->contents()->create([
                    'locale' => $record->locale,
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'layout' => $record->layout,
                    'blocks' => $record->blocks,
                    'meta' => $record->meta,
                ]);

                Notification::make()
                    ->title(__('cms::cms.page_duplicate_success'))
                    ->success()
                    ->send();

                $this->redirect(PageResource::getUrl('edit', ['record' => $newContent]));
            });
    }

    protected function unpublishAction(): Action
    {
        return Action::make('unpublish')
            ->label(__('cms::cms.page_unpublish'))
            ->icon('heroicon-o-eye-slash')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('cms::cms.page_unpublish'))
            ->modalDescription(__('cms::cms.page_unpublish_confirm'))
            ->visible(fn ($record) => $record && $record->isPublished() && ! $record->trashed())
            ->action(function ($record) {
                $record->update([
                    'published_content' => null,
                    'published_at' => null,
                ]);

                $this->onUnpublish($record);

                Notification::make()
                    ->title(__('cms::cms.page_unpublish_success'))
                    ->success()
                    ->send();
            });
    }

    protected function setAsFrontpageAction(): Action
    {
        return Action::make('set_as_frontpage')
            ->label(__('cms::cms.page_set_as_frontpage'))
            ->icon('heroicon-o-star')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading(__('cms::cms.page_set_as_frontpage'))
            ->modalDescription(__('cms::cms.page_set_as_frontpage_confirm'))
            ->visible(fn ($record) => $record && ! $record->is_frontpage && $record->isPublished() && ! $record->trashed())
            ->action(function ($record) {
                $record->update(['is_frontpage' => true]);

                $this->refreshFormData(['is_frontpage']);

                Notification::make()
                    ->title(__('cms::cms.page_set_as_frontpage_success'))
                    ->success()
                    ->send();
            });
    }

    protected function secondaryActionsGroup(array $actions = []): ActionGroup
    {
        return ActionGroup::make(array_merge($actions, [
            DeleteAction::make()
                ->visible(fn ($record) => $record && ! $record->trashed()),

            RestoreAction::make()
                ->visible(fn ($record) => $record && $record->trashed()),

            ForceDeleteAction::make()
                ->visible(fn ($record) => $record && $record->trashed()),
        ]))->label(__('cms::cms.page_more_actions'));
    }
}
