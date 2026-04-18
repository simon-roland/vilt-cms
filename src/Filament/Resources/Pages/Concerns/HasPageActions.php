<?php

namespace RolandSolutions\ViltCms\Filament\Resources\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Validation\Rules\Unique;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Models\PageContent;
use RolandSolutions\ViltCms\Rules\ReservedLocaleSlug;
use RolandSolutions\ViltCms\Support\Locales;

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

    protected function localeUrl(PageContent $record): string
    {
        if ($record->is_frontpage) {
            return Locales::isDefault($record->locale)
                ? route('pages.frontpage')
                : route('pages.frontpage.localized', ['locale' => $record->locale]);
        }

        return Locales::isDefault($record->locale)
            ? route('pages.show', $record->slug)
            : route('pages.show.localized', ['locale' => $record->locale, 'page' => $record->slug]);
    }

    protected function localeSwitcherActions(): array
    {
        if (count(Locales::all()) <= 1) {
            return [];
        }

        $record = $this->getRecord();
        $siblings = PageContent::where('page_id', $record->page_id)->get()->keyBy('locale');

        $actions = [];

        foreach (Locales::all() as $key => $label) {
            $sibling = $siblings->get($key);

            if ($sibling) {
                $actions[] = Action::make("locale_{$key}")
                    ->label(strtoupper($key))
                    ->color($key === $record->locale ? 'primary' : 'gray')
                    ->badge($sibling->isPublished() ? __('cms::cms.page_status_published') : __('cms::cms.page_status_draft'))
                    ->badgeColor($sibling->isPublished() ? 'success' : 'warning')
                    ->disabled($key === $record->locale)
                    ->url($key === $record->locale ? null : PageResource::getUrl('edit', ['record' => $sibling]));
            } else {
                $actions[] = $this->addLocaleAction($key, $label);
            }
        }

        return [ActionGroup::make($actions)
            ->label(strtoupper($record->locale))
            ->icon('heroicon-o-language')
            ->color('gray')];
    }

    protected function addLocaleAction(string $targetLocale, string $targetLabel): Action
    {
        $sourceOptions = fn ($record) => PageContent::where('page_id', $record->page_id)
            ->where('locale', '!=', $targetLocale)
            ->get()
            ->mapWithKeys(fn (PageContent $c) => [$c->locale => __('cms::cms.page_add_locale_copy_from', ['locale' => Locales::all()[$c->locale] ?? $c->locale])])
            ->prepend(__('cms::cms.page_add_locale_blank'), '_blank')
            ->toArray();

        return Action::make("add_locale_{$targetLocale}")
            ->label(strtoupper($targetLocale).' — '.__('cms::cms.page_add_locale_heading'))
            ->icon('heroicon-o-plus')
            ->color('gray')
            ->modalHeading(__('cms::cms.page_add_locale_heading').' — '.$targetLabel)
            ->modalDescription(__('cms::cms.page_add_locale_description'))
            ->schema([
                Radio::make('source')
                    ->label(__('cms::cms.page_add_locale_source'))
                    ->options($sourceOptions)
                    ->default('_blank')
                    ->required(),
                TextInput::make('name')
                    ->label(__('cms::cms.page_name'))
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', str($state)->slug()))
                    ->required(),
                TextInput::make('slug')
                    ->label(__('cms::cms.page_duplicate_slug'))
                    ->rules([new ReservedLocaleSlug])
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(
                        table: 'page_contents',
                        column: 'slug',
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('locale', $targetLocale),
                    )
                    ->required(),
            ])
            ->action(function ($record, array $data) use ($targetLocale) {
                $attrs = [
                    'locale' => $targetLocale,
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'layout' => [],
                    'blocks' => null,
                    'meta' => null,
                ];

                if ($data['source'] !== '_blank') {
                    $source = PageContent::where('page_id', $record->page_id)
                        ->where('locale', $data['source'])
                        ->first();

                    if ($source) {
                        $attrs['layout'] = $source->layout;
                        $attrs['blocks'] = $source->blocks;
                        $attrs['meta'] = $source->meta;
                    }
                }

                $newContent = $record->page->contents()->create($attrs);

                Notification::make()
                    ->title(__('cms::cms.page_add_locale_success'))
                    ->success()
                    ->send();

                $this->redirect(PageResource::getUrl('edit', ['record' => $newContent]));
            });
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
            ->schema([
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
                $allContents = PageContent::where('page_id', $record->page_id)->get();

                $redirectTo = null;

                foreach ($allContents as $content) {
                    $isCurrentLocale = $content->locale === $record->locale;

                    $slug = $isCurrentLocale
                        ? $data['slug']
                        : $this->uniqueSlug($content->slug.'-copy', $content->locale);

                    $newContent = $newPage->contents()->create([
                        'locale' => $content->locale,
                        'name' => $isCurrentLocale ? $data['name'] : $content->name,
                        'slug' => $slug,
                        'layout' => $content->layout,
                        'blocks' => $content->blocks,
                        'meta' => $content->meta,
                    ]);

                    if ($isCurrentLocale) {
                        $redirectTo = $newContent;
                    }
                }

                Notification::make()
                    ->title(__('cms::cms.page_duplicate_success'))
                    ->success()
                    ->send();

                $this->redirect(PageResource::getUrl('edit', ['record' => $redirectTo ?? $newPage->contents()->first()]));
            });
    }

    protected function copyFromLocaleAction(): Action
    {
        return Action::make('copy_from_locale')
            ->label(__('cms::cms.page_copy_from_locale'))
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->visible(function ($record) {
                if (! $record || $record->trashed()) {
                    return false;
                }

                return count(Locales::all()) > 1
                    && PageContent::where('page_id', $record->page_id)
                        ->where('locale', '!=', $record->locale)
                        ->exists();
            })
            ->modalHeading(__('cms::cms.page_copy_from_locale_heading'))
            ->modalDescription(__('cms::cms.page_copy_from_locale_description'))
            ->schema([
                Select::make('source_locale')
                    ->label(__('cms::cms.page_copy_from_locale_source'))
                    ->options(fn ($record) => PageContent::where('page_id', $record->page_id)
                        ->where('locale', '!=', $record->locale)
                        ->get()
                        ->mapWithKeys(fn (PageContent $c) => [$c->locale => Locales::all()[$c->locale] ?? $c->locale])
                        ->toArray())
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                $source = PageContent::where('page_id', $record->page_id)
                    ->where('locale', $data['source_locale'])
                    ->firstOrFail();

                $record->update([
                    'layout' => $source->layout,
                    'blocks' => $source->blocks,
                    'meta' => $source->meta,
                ]);

                $this->fillForm();

                Notification::make()
                    ->title(__('cms::cms.page_copy_from_locale_success'))
                    ->success()
                    ->send();
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

    private function uniqueSlug(string $base, string $locale): string
    {
        $slug = $base;
        $i = 1;

        while (PageContent::where('locale', $locale)->where('slug', $slug)->exists()) {
            $i++;
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
