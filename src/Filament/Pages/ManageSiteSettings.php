<?php

namespace RolandSolutions\ViltCms\Filament\Pages;

use RolandSolutions\ViltCms\CmsServiceProvider;
use RolandSolutions\ViltCms\Models\SiteSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use BackedEnum;
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

    public function mount(): void
    {
        $this->form->fill(SiteSettings::getSingleton()->data ?? []);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components(CmsServiceProvider::getSiteSettingsFields());
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $settings = SiteSettings::getSingleton();
        $settings->data = $state;
        $settings->save();

        Notification::make()
            ->title(__('cms::cms.settings_saved'))
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::pages/settings.actions.save.label'))
                ->submit('save'),
        ];
    }
}
