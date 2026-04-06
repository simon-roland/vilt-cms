<?php

namespace RolandSolutions\ViltCms\Http\Middleware;

use RolandSolutions\ViltCms\Actions\ReplacePageID;
use RolandSolutions\ViltCms\Actions\ResolveSettingsMedia;
use RolandSolutions\ViltCms\Models\Navigation;
use RolandSolutions\ViltCms\Models\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    protected function extraProps(Request $request): array
    {
        return [];
    }

    protected function loadNavigation(string $type): array
    {
        $nav = Navigation::firstWhere('type', $type);

        if ($nav) {
            $nav->items = ReplacePageID::make()->handle($nav->items);
        }

        return $nav->items ?? [];
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'ziggy' => (new Ziggy(null, URL::to('/')))->toArray(),
            'title' => config('app.name'),
            'header' => $this->loadNavigation('header'),
            'footer' => $this->loadNavigation('footer'),
            'settings' => ResolveSettingsMedia::make()->handle(
                SiteSettings::getSingleton()->data ?? []
            ),
        ], $this->extraProps($request));
    }
}
