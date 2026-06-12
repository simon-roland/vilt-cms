<?php

namespace RolandSolutions\ViltCms\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Inertia\Middleware;
use RolandSolutions\ViltCms\Actions\ReplacePageID;
use RolandSolutions\ViltCms\Actions\ResolveSettingsMedia;
use RolandSolutions\ViltCms\Models\Navigation;
use RolandSolutions\ViltCms\Models\PageContent;
use RolandSolutions\ViltCms\Models\SiteSettings;
use RolandSolutions\ViltCms\Support\Locales;
use RolandSolutions\ViltCms\Support\PreviewMode;
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
        $locale = app()->getLocale();
        $default = Locales::default();

        $nav = Navigation::where('type', $type)
            ->where('locale', $locale)
            ->first();

        if (! $nav && $locale !== $default && config('cms.navigation_fallback') === 'default_locale') {
            $nav = Navigation::where('type', $type)
                ->where('locale', $default)
                ->first();
        }

        if (! $nav) {
            return [];
        }

        // Logged-in users keep links to draft pages — they can open those
        // pages directly (see PageController), so the nav must match. Guests
        // only see published links unless a PreviewMode resolver says otherwise.
        if (! auth()->check() && ! PreviewMode::active()) {
            $publishedPageIds = array_flip(
                PageContent::query()
                    ->where('locale', $locale)
                    ->whereNotNull('published_content')
                    ->pluck('page_id')
                    ->all()
            );

            $nav->items = $this->filterNavItems($nav->items, $publishedPageIds);
        }

        $nav->items = ReplacePageID::make()->handle($nav->items, $locale);

        return $nav->items ?? [];
    }

    protected function filterNavItems(array $items, array $publishedPageIds): array
    {
        $filtered = [];

        foreach ($items as $item) {
            if (! is_array($item) || empty($item['type'])) {
                $filtered[] = $item;

                continue;
            }

            if ($item['type'] === 'link') {
                $data = $item['data'] ?? [];
                if (($data['link_type'] ?? '') === 'page') {
                    $pageId = $data['page_id'] ?? null;
                    if ($pageId === null || ! isset($publishedPageIds[$pageId])) {
                        continue;
                    }
                }
                $filtered[] = $item;
            } elseif ($item['type'] === 'dropdown') {
                $children = $item['data']['items'] ?? [];
                $item['data']['items'] = is_array($children)
                    ? $this->filterNavItems($children, $publishedPageIds)
                    : [];

                // A group whose links were all filtered out would render as a
                // dead menu item — drop it entirely.
                if (empty($item['data']['items'])) {
                    continue;
                }

                $filtered[] = $item;
            } else {
                $filtered[] = $item;
            }
        }

        return array_values($filtered);
    }

    public function share(Request $request): array
    {
        // Locale-dependent props are closures: Inertia resolves them when the
        // response renders, after LocaleDetectionMiddleware has set the app
        // locale — regardless of where this middleware sits in the stack.
        return array_merge(parent::share($request), [
            'ziggy' => (new Ziggy(null, URL::to('/')))
                ->filter(['filament.*', 'livewire.*'], false)
                ->toArray(),
            'title' => config('app.name'),
            'locale' => fn () => app()->getLocale(),
            'locales' => Locales::all(),
            'defaultLocale' => fn () => Locales::fromDomain($request->getHost()) ?? Locales::default(),
            'header' => fn () => $this->loadNavigation('header'),
            'footer' => fn () => $this->loadNavigation('footer'),
            'settings' => fn () => Arr::except(
                ResolveSettingsMedia::make()->handle(
                    SiteSettings::getResolved(app()->getLocale())
                ),
                ['head_scripts', 'body_start_scripts', 'body_end_scripts']
            ),
        ], $this->extraProps($request));
    }
}
