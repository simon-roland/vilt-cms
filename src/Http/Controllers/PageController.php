<?php

namespace RolandSolutions\ViltCms\Http\Controllers;

use RolandSolutions\ViltCms\Actions\AddMediaToPage;
use RolandSolutions\ViltCms\Actions\ReplacePageID;
use RolandSolutions\ViltCms\Actions\ResolveBlockResources;
use RolandSolutions\ViltCms\Enum\PageStatus;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource as FilamentPageResource;
use RolandSolutions\ViltCms\Http\Resources\PageResource;
use RolandSolutions\ViltCms\Models\Page;

class PageController extends Controller
{
    public function frontpage()
    {
        $status = $this->resolvePreviewStatus();

        $page = Page::where('is_frontpage', true)
            ->where('status', $status)
            ->with('media')
            ->first();

        // Fallback: if no version exists for requested status, try the other
        if (!$page) {
            $page = Page::where('is_frontpage', true)->with('media')->first();
        }

        if (!$page) {
            abort(404);
        }

        return $this->renderPage($page);
    }

    public function show($slug)
    {
        $status = $this->resolvePreviewStatus();

        $page = Page::where('slug', $slug)
            ->where('status', $status)
            ->with('media')
            ->first();

        // Fallback: if no version exists for requested status, try the other
        if (!$page) {
            $page = Page::where('slug', $slug)->with('media')->first();
        }

        if (!$page || $page->is_frontpage) {
            return redirect()->route('pages.frontpage');
        }

        return $this->renderPage($page);
    }

    private function resolvePreviewStatus(): PageStatus
    {
        if (auth()->check() && session('cms_preview_mode', 'published') === 'draft') {
            return PageStatus::Draft;
        }

        return PageStatus::Published;
    }

    private function renderPage(Page $page)
    {
        AddMediaToPage::make()->handle($page);
        ResolveBlockResources::make()->handle($page);
        $page->blocks = ReplacePageID::make()->handle($page->blocks);
        $page->layout = ReplacePageID::make()->handle($page->layout);

        $cmsToolbar = null;

        if (auth()->check()) {
            $cmsToolbar = [
                'status'       => $page->status->value,
                'updatedAt'    => $page->updated_at->toIso8601String(),
                'hasDraft'     => Page::where('slug', $page->slug)->where('status', PageStatus::Draft)->exists(),
                'hasPublished' => Page::where('slug', $page->slug)->where('status', PageStatus::Published)->exists(),
                'previewMode'  => session('cms_preview_mode', 'published'),
                'editUrl'      => FilamentPageResource::getUrl('edit', ['record' => $page->id]),
            ];
        }

        return inertia('Page', [
            'page'       => PageResource::make($page),
            'cmsToolbar' => $cmsToolbar,
        ]);
    }
}
