<?php

namespace RolandSolutions\ViltCms;

use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use RolandSolutions\ViltCms\Filament\Pages\ManageMediaLibrary;
use RolandSolutions\ViltCms\Filament\Pages\ManageSiteSettings;
use RolandSolutions\ViltCms\Filament\Resources\LocaleDomainMappings\LocaleDomainMappingResource;
use RolandSolutions\ViltCms\Filament\Resources\Navigations\NavigationResource;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use RolandSolutions\ViltCms\Filament\Resources\User\UserResource;
use RolandSolutions\ViltCms\Support\Locales;

class CmsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'cms';
    }

    public function register(Panel $panel): void
    {
        $resources = [
            PageResource::class,
            NavigationResource::class,
            UserResource::class,
        ];

        if (count(Locales::all()) > 1) {
            $resources[] = LocaleDomainMappingResource::class;
        }

        $panel
            ->resources($resources)
            ->pages([
                ManageMediaLibrary::class,
                ManageSiteSettings::class,
            ])
            ->homeUrl(fn () => PageResource::getUrl('index'))
            ->navigationItems([
                NavigationItem::make(__('cms::cms.view_site'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn () => route('pages.frontpage'))
                    ->sort(99),
            ]);
    }

    public function boot(Panel $panel): void {}
}
