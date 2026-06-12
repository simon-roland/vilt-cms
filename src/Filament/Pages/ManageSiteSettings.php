<?php

namespace RolandSolutions\ViltCms\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use RolandSolutions\ViltCms\CmsServiceProvider;
use RolandSolutions\ViltCms\Filament\Support\Translatable;
use RolandSolutions\ViltCms\Models\SiteSettings;
use RolandSolutions\ViltCms\Support\Locales;
use UnitEnum;

class ManageSiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    protected static ?string $title = null;

    public static function getNavigationGroup(): ?string
    {
        return __('cms::cms.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('cms::cms.settings_title');
    }

    public function getTitle(): string
    {
        return __('cms::cms.settings_title');
    }

    protected string $view = 'cms::filament.pages.manage-site-settings';

    public ?array $data = [];

    public ?string $editingLocale = null;

    public function mount(): void
    {
        $this->editingLocale = null;
        $this->form->fill($this->loadDataForLocale(null));
    }

    public function form(Schema $schema): Schema
    {
        // Scope the registry to this schema build: ->translatable() marks fields
        // as they are instantiated below, and stale entries from other schemas
        // (or earlier requests on long-running runtimes) must not leak in.
        Translatable::reset();

        $multiLocale = count(Locales::all()) > 1;

        $components = [];

        if ($multiLocale) {
            $components[] = Select::make('_editing_locale')
                ->label(__('cms::cms.site_settings_editing_locale'))
                ->options([
                    '' => __('cms::cms.site_settings_global_label'),
                ] + Locales::all())
                ->selectablePlaceholder(false)
                ->default('')
                ->dehydrated(false)
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->switchLocale($state === '' ? null : $state);
                });
        }

        $settingsFields = CmsServiceProvider::getSiteSettingsFields();
        $translatableNames = array_map(
            fn (string $p) => Translatable::stripPrefix($p),
            Translatable::all()
        );
        $components = array_merge($components, $this->applyLocaleDisabling($settingsFields, $translatableNames));

        return $schema
            ->statePath('data')
            ->components($components);
    }

    public function switchLocale(?string $locale): void
    {
        $this->editingLocale = $locale;
        $state = $this->loadDataForLocale($locale);
        $state['_editing_locale'] = $locale ?? '';
        $this->form->fill($state);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        unset($state['_editing_locale']);

        if ($this->editingLocale === null) {
            $global = SiteSettings::global();
            $global->data = $state;
            $global->save();
        } else {
            $translatableKeys = array_map(
                fn (string $path) => Translatable::stripPrefix($path),
                Translatable::all()
            );

            $localeData = Translatable::overrides(
                $state,
                $translatableKeys,
                SiteSettings::global()->data ?? []
            );

            if ($localeData === []) {
                // No overrides left — drop the row so the locale fully inherits.
                SiteSettings::where('locale', $this->editingLocale)->delete();
            } else {
                SiteSettings::updateOrCreate(
                    ['locale' => $this->editingLocale],
                    ['data' => $localeData]
                );
            }
        }

        Notification::make()
            ->title(__('cms::cms.settings_saved'))
            ->success()
            ->send();
    }

    /**
     * Recursively walk the component tree and disable non-translatable fields
     * when a specific locale is being edited, so admins cannot accidentally
     * edit global-only fields in a per-locale context.
     *
     * @param  array<Component>  $components
     * @param  string[]  $translatableNames
     * @return array<Component>
     */
    private function applyLocaleDisabling(array $components, array $translatableNames): array
    {
        foreach ($components as $component) {
            if ($component instanceof Field) {
                if (! in_array($component->getName(), $translatableNames, true)) {
                    $component
                        ->disabled(fn () => $this->editingLocale !== null)
                        ->hint(fn () => $this->editingLocale !== null
                            ? __('cms::cms.site_settings_non_translatable_when_locale')
                            : null);
                }
            } else {
                $children = $component->getDefaultChildComponents();
                if (is_array($children) && count($children) > 0) {
                    $component->schema($this->applyLocaleDisabling($children, $translatableNames));
                }
            }
        }

        return $components;
    }

    private function loadDataForLocale(?string $locale): array
    {
        return $locale === null
            ? (SiteSettings::global()->data ?? [])
            : SiteSettings::getResolved($locale);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('cms::cms.settings_save'))
                ->submit('save'),
        ];
    }
}
