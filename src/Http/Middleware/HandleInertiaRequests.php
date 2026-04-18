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

        $nav = Navigation::where('type', $type)
            ->where('locale', $locale)
            ->first();

        if ($nav) {
            if (! PreviewMode::active()) {
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
        }

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
                if (isset($item['data']['items']) && is_array($item['data']['items'])) {
                    $item['data']['items'] = $this->filterNavItems($item['data']['items'], $publishedPageIds);
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
        return array_merge(parent::share($request), [
            'ziggy' => (new Ziggy(null, URL::to('/')))->toArray(),
            'title' => config('app.name'),
            'header' => $this->loadNavigation('header'),
            'footer' => $this->loadNavigation('footer'),
            'settings' => Arr::except(
                ResolveSettingsMedia::make()->handle(
                    SiteSettings::getSingleton()->data ?? []
                ),
                ['head_scripts', 'body_start_scripts', 'body_end_scripts']
            ),
        ], $this->extraProps($request));
    }
}
