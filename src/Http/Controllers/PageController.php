<?php

namespace RolandSolutions\ViltCms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RolandSolutions\ViltCms\Actions\AddMediaToPage;
use RolandSolutions\ViltCms\Actions\ReplacePageID;
use RolandSolutions\ViltCms\Actions\ResolveBlockResources;
use RolandSolutions\ViltCms\Actions\ResolveSettingsMedia;
use RolandSolutions\ViltCms\Filament\Pages\ManageSiteSettings;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource as FilamentPageResource;
use RolandSolutions\ViltCms\Http\Resources\PageResource;
use RolandSolutions\ViltCms\Models\PageContent;
use RolandSolutions\ViltCms\Support\Locales;
use RolandSolutions\ViltCms\Support\PreviewMode;

class PageController extends Controller
{
    public function frontpage(Request $request)
    {
        if ($redirect = $this->applyPreviewParam($request)) {
            return $redirect;
        }

        $useDraft = $this->wantsDraftPreview();
        $locale = app()->getLocale();

        $query = PageContent::query()
            ->where('locale', $locale)
            ->where('is_frontpage', true)
            ->with('media');

        if (! $useDraft && ! auth()->check()) {
            $query->whereNotNull('published_content');
        }

        $page = $query->first();

        if (! $page) {
            abort(404);
        }

        return $this->renderPage($page, $useDraft);
    }

    public function show(Request $request, ?string $locale, ?string $slug = null)
    {
        // Route shapes: /{slug} (locale absent) or /{locale}/{slug}
        // Laravel splices route params positionally when names don't match,
        // so when only {slug} is present, it arrives in the `$locale` slot.
        if ($slug === null) {
            $slug = $locale;
        }

        if ($redirect = $this->applyPreviewParam($request)) {
            return $redirect;
        }

        $useDraft = $this->wantsDraftPreview();
        $locale = app()->getLocale();

        $query = PageContent::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->with('media');

        if (! $useDraft && ! auth()->check()) {
            $query->whereNotNull('published_content');
        }

        $page = $query->first();

        if (! $page) {
            return $this->missingLocaleResponse();
        }

        if ($page->is_frontpage) {
            return $this->frontpageRedirect();
        }

        return $this->renderPage($page, $useDraft);
    }

    private function missingLocaleResponse()
    {
        if (config('cms.missing_locale_behavior') === '404') {
            abort(404);
        }

        $locale = app()->getLocale();

        $query = PageContent::query()
            ->where('locale', $locale)
            ->where('is_frontpage', true);

        if (! auth()->check() && ! PreviewMode::active()) {
            $query->whereNotNull('published_content');
        }

        if (! $query->exists()) {
            abort(404);
        }

        return $this->frontpageRedirect();
    }

    private function frontpageRedirect()
    {
        $locale = app()->getLocale();
        $domainDefault = Locales::fromDomain(request()->getHost()) ?? Locales::default();

        if ($locale === $domainDefault) {
            return redirect()->route('pages.frontpage');
        }

        return redirect()->route('pages.frontpage.localized', ['locale' => $locale]);
    }

    private function wantsDraftPreview(): bool
    {
        return PreviewMode::active();
    }

    /**
     * If the request carries ?preview=draft|published, persist it to the session
     * and redirect to the same URL without the query param (keep URLs clean).
     * Only applied for authenticated users.
     */
    private function applyPreviewParam(Request $request): ?RedirectResponse
    {
        $mode = $request->query('preview');

        if (! in_array($mode, ['draft', 'published'], true) || ! auth()->check()) {
            return null;
        }

        $locale = app()->getLocale();
        $key = "cms_preview_mode.{$locale}";
        session([$key => $mode]);

        return redirect($request->fullUrlWithoutQuery(['preview']));
    }

    private function renderPage(PageContent $page, bool $useDraft)
    {
        $locale = $page->locale;

        // Capture before overlay mutates the model
        $hasDraftChanges = $page->hasDraftChanges();

        // For guest rendering, overlay the published snapshot onto the model instance
        if (! $useDraft && $page->published_content) {
            // Backward compat: old snapshots only have 'title', new ones have 'name'
            $page->name = $page->published_content['name'] ?? $page->published_content['title'] ?? $page->name;
            $page->layout = $page->published_content['layout'];
            $page->blocks = $page->published_content['blocks'] ?? null;
            $page->meta = $page->published_content['meta'] ?? null;
        }

        AddMediaToPage::make()->handle($page);
        ResolveBlockResources::make()->handle($page);
        if (is_array($page->meta)) {
            $page->meta = ResolveSettingsMedia::make()->handle($page->meta);
        }
        $page->blocks = ReplacePageID::make()->handle($page->blocks, $locale);
        $page->layout = ReplacePageID::make()->handle($page->layout, $locale);

        $cmsToolbar = null;

        if (auth()->check()) {
            $cmsToolbar = [
                'hasPublished' => $page->isPublished(),
                'hasDraft' => $hasDraftChanges,
                'previewMode' => session('cms_preview_mode', 'published'),
                'updatedAt' => $page->updated_at->toIso8601String(),
                'editUrl' => FilamentPageResource::getUrl('edit', ['record' => $page]),
                'pagesUrl' => FilamentPageResource::getUrl('index'),
                'newPageUrl' => FilamentPageResource::getUrl('create'),
                'settingsUrl' => ManageSiteSettings::getUrl(),
                'labels' => [
                    'pages' => __('cms::cms.toolbar_pages'),
                    'newPage' => __('cms::cms.toolbar_new_page'),
                    'settings' => __('cms::cms.toolbar_settings'),
                    'edit' => __('cms::cms.toolbar_edit'),
                    'draft' => __('cms::cms.toolbar_draft'),
                    'published' => __('cms::cms.toolbar_published'),
                    'edited' => __('cms::cms.toolbar_edited'),
                ],
            ];
        }

        return inertia('Page', [
            'page' => PageResource::make($page),
            'locale_variants' => $this->localeVariants($page),
            'cmsToolbar' => $cmsToolbar,
        ]);
    }

    /**
     * Per-locale variants of the current page, used for hreflang alternates
     * and locale switchers. `available` means a published PageContent exists.
     * `url` is the switch target: the variant itself when reachable for the
     * current visitor, otherwise that locale's frontpage, otherwise null.
     *
     * @return array<string, array{slug: ?string, available: bool, url: ?string}>|null
     */
    private function localeVariants(PageContent $page): ?array
    {
        $locales = Locales::all();

        if (count($locales) < 2) {
            return null;
        }

        $includeDrafts = auth()->check() || PreviewMode::active();

        $siblings = PageContent::query()
            ->where('page_id', $page->page_id)
            ->get()
            ->keyBy('locale');

        $frontpages = PageContent::query()
            ->where('is_frontpage', true)
            ->when(! $includeDrafts, fn ($query) => $query->whereNotNull('published_content'))
            ->get()
            ->keyBy('locale');

        $domainDefault = Locales::fromDomain(request()->getHost()) ?? Locales::default();

        $variants = [];

        foreach (array_keys($locales) as $locale) {
            $sibling = $siblings->get($locale);
            $reachable = $sibling !== null && ($includeDrafts || $sibling->isPublished());

            if ($reachable) {
                $url = $sibling->is_frontpage
                    ? $this->localizedUrl('pages.frontpage', $locale, $domainDefault)
                    : $this->localizedUrl('pages.show', $locale, $domainDefault, ['slug' => $sibling->slug]);
            } elseif ($frontpages->has($locale)) {
                $url = $this->localizedUrl('pages.frontpage', $locale, $domainDefault);
            } else {
                $url = null;
            }

            $variants[$locale] = [
                'slug' => $sibling?->slug,
                'available' => $sibling?->isPublished() ?? false,
                'url' => $url,
            ];
        }

        return $variants;
    }

    private function localizedUrl(string $route, string $locale, string $domainDefault, array $params = []): string
    {
        if ($locale === $domainDefault) {
            return route($route, $params, false);
        }

        return route("{$route}.localized", ['locale' => $locale] + $params, false);
    }
}
