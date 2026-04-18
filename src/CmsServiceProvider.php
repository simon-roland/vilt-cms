<?php

namespace RolandSolutions\ViltCms;

use Filament\Schemas\Components\Tabs;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use RolandSolutions\ViltCms\Commands\CmsInstallCommand;
use RolandSolutions\ViltCms\Commands\CmsPublishCommand;
use RolandSolutions\ViltCms\Commands\MakeCmsBlockCommand;
use RolandSolutions\ViltCms\Commands\MakeCmsFieldCommand;
use RolandSolutions\ViltCms\Commands\MakeCmsLayoutCommand;
use RolandSolutions\ViltCms\Filament\Blocks\BaseBlock;
use RolandSolutions\ViltCms\Filament\Pages\Schemas\DefaultSiteSettingsSchema;
use RolandSolutions\ViltCms\Filament\Resources\Navigations\Schemas\DefaultNavigationFormSchema;
use RolandSolutions\ViltCms\Livewire\MediaPickerField;
use RolandSolutions\ViltCms\Models\Media;
use Spatie\Image\Drivers\Gd\GdDriver;
use Spatie\Image\Drivers\Imagick\ImagickDriver;

class CmsServiceProvider extends ServiceProvider
{
    protected static array $blocks = [];

    protected static array $layouts = [];

    protected static string $siteSettingsSchema = DefaultSiteSettingsSchema::class;

    protected static string $navigationFormSchema = DefaultNavigationFormSchema::class;

    public static function getBlocks(): array
    {
        return static::$blocks;
    }

    public static function getLayouts(): array
    {
        return static::$layouts;
    }

    public static function getSiteSettingsFields(): array
    {
        $tabs = DefaultSiteSettingsSchema::tabs();

        $userClass = static::$siteSettingsSchema;

        if ($userClass !== DefaultSiteSettingsSchema::class && method_exists($userClass, 'extraTabs')) {
            $tabs = array_merge($tabs, $userClass::extraTabs());
        }

        return [
            Tabs::make('settings')
                ->tabs($tabs)
                ->persistTabInQueryString('settings-tab')
                ->columnSpanFull(),
        ];
    }

    public static function getNavigationFormBlocks(): array
    {
        return (static::$navigationFormSchema)::blocks();
    }

    public function register(): void
    {
        // Register translations early (in register phase, not boot) so they are available
        // when Filament calls CmsPlugin::register() during AdminPanelProvider::register().
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'cms');

        $this->app['config']->set(
            'media-library.media_model',
            Media::class,
        );

        // Ensure new uploads go to the configured cms media disk.
        $this->app['config']->set(
            'media-library.disk_name',
            config('cms.media_disk'),
        );

        // Raise Livewire's temporary upload limit to 500 MB to support video uploads.
        $this->app['config']->set(
            'livewire.temporary_file_upload.rules',
            ['required', 'file', 'max:512000'],
        );

        // Raise Spatie media library's max file size to 500 MB to support video uploads.
        $this->app['config']->set(
            'media-library.max_file_size',
            1024 * 1024 * 500,
        );

        // Use Imagick for better WebP quality and broader format support, fall back to GD.
        $this->app['config']->set(
            'media-library.image_driver',
            extension_loaded('imagick')
                ? ImagickDriver::class
                : GdDriver::class,
        );
    }

    public function boot(): void
    {
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cms');

        $this->mergeConfigFrom(__DIR__.'/../config/cms.php', 'cms');

        $this->publishes([
            __DIR__.'/../config/cms.php' => config_path('cms.php'),
        ], 'cms-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'cms-migrations');

        $this->publishes([
            __DIR__.'/../resources/views/app.blade.php' => resource_path('views/app.blade.php'),
        ], 'cms-views');

        $this->publishes([
            __DIR__.'/../lang' => lang_path('vendor/cms'),
        ], 'cms-lang');

        $this->publishes([
            __DIR__.'/../stubs' => base_path('stubs/cms'),
        ], 'cms-stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CmsInstallCommand::class,
                CmsPublishCommand::class,
                MakeCmsBlockCommand::class,
                MakeCmsFieldCommand::class,
                MakeCmsLayoutCommand::class,
            ]);
        }

        Livewire::component('media-picker-field', MediaPickerField::class);

        $this->discoverBlocks();
        $this->discoverLayouts();
        $this->discoverSiteSettingsSchema();
        $this->discoverNavigationFormSchema();

        // Filament resources and pages are registered via CmsPlugin::make()
        // added to the panel in AdminPanelProvider.
    }

    private function discoverSiteSettingsSchema(): void
    {
        $class = 'App\\Cms\\SiteSettingsSchema';

        if (class_exists($class) && method_exists($class, 'extraTabs')) {
            static::$siteSettingsSchema = $class;
        }
    }

    private function discoverNavigationFormSchema(): void
    {
        $class = 'App\\Cms\\NavigationFormSchema';

        if (class_exists($class) && method_exists($class, 'blocks')) {
            static::$navigationFormSchema = $class;
        }
    }

    private function discoverBlocks(): void
    {
        $dir = app_path('Cms/Blocks');

        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*.php') as $file) {
            $class = 'App\\Cms\\Blocks\\'.basename($file, '.php');

            if (class_exists($class) && is_subclass_of($class, BaseBlock::class)) {
                static::$blocks[] = $class::make();
            }
        }
    }

    private function discoverLayouts(): void
    {
        $dir = app_path('Cms/Layouts');

        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*.php') as $file) {
            $class = 'App\\Cms\\Layouts\\'.basename($file, '.php');

            if (class_exists($class) && is_subclass_of($class, BaseBlock::class)) {
                static::$layouts[] = $class::make();
            }
        }
    }
}
