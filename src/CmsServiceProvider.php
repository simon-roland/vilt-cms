<?php

namespace RolandSolutions\ViltCms;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use RolandSolutions\ViltCms\Commands\CmsInstallCommand;
use RolandSolutions\ViltCms\Commands\CmsPublishCommand;
use RolandSolutions\ViltCms\Commands\MakeCmsBlockCommand;
use RolandSolutions\ViltCms\Commands\MakeCmsLayoutCommand;
use RolandSolutions\ViltCms\Livewire\MediaPickerField;

class CmsServiceProvider extends ServiceProvider
{
    protected static array $blocks = [];

    protected static array $layouts = [];

    public static function registerBlocks(array $blocks): void
    {
        static::$blocks = array_merge(static::$blocks, $blocks);
    }

    public static function registerLayouts(array $layouts): void
    {
        static::$layouts = array_merge(static::$layouts, $layouts);
    }

    public static function getBlocks(): array
    {
        return static::$blocks;
    }

    public static function getLayouts(): array
    {
        return static::$layouts;
    }

    public function register(): void
    {
        $this->app['config']->set(
            'media-library.media_model',
            \RolandSolutions\ViltCms\Models\Media::class,
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
    }

    public function boot(): void
    {
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'cms');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cms');

        $this->mergeConfigFrom(__DIR__ . '/../config/cms.php', 'cms');

        $this->publishes([
            __DIR__ . '/../config/cms.php' => config_path('cms.php'),
        ], 'cms-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'cms-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views/app.blade.php' => resource_path('views/app.blade.php'),
        ], 'cms-views');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/cms'),
        ], 'cms-lang');

        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/cms'),
        ], 'cms-stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CmsInstallCommand::class,
                CmsPublishCommand::class,
                MakeCmsBlockCommand::class,
                MakeCmsLayoutCommand::class,
            ]);
        }

        Livewire::component('media-picker-field', MediaPickerField::class);

        // Filament resources and pages are registered via CmsPlugin::make()
        // added to the panel in AdminPanelProvider.
    }
}
