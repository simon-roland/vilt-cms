<?php

namespace RolandSolutions\ViltCms\Tests;

use Illuminate\Support\Facades\View;
use Inertia\ServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RolandSolutions\ViltCms\CmsServiceProvider;
use RolandSolutions\ViltCms\Http\Middleware\HandleInertiaRequests;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            ServiceProvider::class,
            CmsServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(__DIR__.'/../resources/views');

        // Mirror the consuming-app setup: the Inertia middleware lives in the
        // web group, so shared props are present on package-route responses.
        // Registered after boot — the framework rebuilds the web group during
        // bootstrap, which would clobber an earlier push.
        $this->app['router']->pushMiddlewareToGroup('web', HandleInertiaRequests::class);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('cms.locales', [
            'en' => 'English',
            'da' => 'Dansk',
        ]);
        $app['config']->set('cms.default_locale', 'en');
    }
}
