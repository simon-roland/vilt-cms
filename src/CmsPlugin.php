<?php

namespace RolandSolutions\ViltCms;

use Filament\Contracts\Plugin;
use Filament\Panel;
use RolandSolutions\ViltCms\Filament\Pages\ManageMediaLibrary;
use RolandSolutions\ViltCms\Filament\Pages\ManageSiteSettings;
use RolandSolutions\ViltCms\Filament\Resources\Navigations\NavigationResource;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource;
use RolandSolutions\ViltCms\Filament\Resources\User\UserResource;

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
        $panel
            ->resources([
                PageResource::class,
                NavigationResource::class,
                UserResource::class,
            ])
            ->pages([
                ManageMediaLibrary::class,
                ManageSiteSettings::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
    }
}
