<?php

namespace RolandSolutions\ViltCms\Http\Controllers;

use RolandSolutions\ViltCms\Actions\AddMediaToPage;
use RolandSolutions\ViltCms\Actions\ReplacePageID;
use RolandSolutions\ViltCms\Actions\ResolveBlockResources;
use RolandSolutions\ViltCms\Filament\Resources\Pages\PageResource as FilamentPageResource;
use RolandSolutions\ViltCms\Http\Resources\PageResource;
use RolandSolutions\ViltCms\Models\Page;

class PageController extends Controller
{
    public function frontpage()
    {
        $useDraft = $this->wantsDraftPreview();

        $query = Page::where('is_frontpage', true)->with('media');

        if (!$useDraft) {
            $query->whereNotNull('published_content');
        }

        $page = $query->first();

        if (!$page) {
            abort(404);
        }

        return $this->renderPage($page, $useDraft);
    }

    public function show($slug)
    {
        $useDraft = $this->wantsDraftPreview();

        $query = Page::where('slug', $slug)->with('media');

        if (!$useDraft) {
            $query->whereNotNull('published_content');
        }

        $page = $query->first();

        if (!$page || $page->is_frontpage) {
            return redirect()->route('pages.frontpage');
        }

        return $this->renderPage($page, $useDraft);
    }

    private function wantsDraftPreview(): bool
    {
        return auth()->check() && session('cms_preview_mode', 'published') === 'draft';
    }

    private function renderPage(Page $page, bool $useDraft)
    {
        // For guest rendering, overlay the published snapshot onto the model instance
        if (!$useDraft && $page->published_content) {
            $page->title  = $page->published_content['title'];
            $page->layout = $page->published_content['layout'];
            $page->blocks = $page->published_content['blocks'] ?? null;
            $page->meta   = $page->published_content['meta'] ?? null;
        }

        AddMediaToPage::make()->handle($page);
        ResolveBlockResources::make()->handle($page);
        $page->blocks = ReplacePageID::make()->handle($page->blocks);
        $page->layout = ReplacePageID::make()->handle($page->layout);

        $cmsToolbar = null;

        if (auth()->check()) {
            $cmsToolbar = [
                'hasPublished' => $page->isPublished(),
                'hasDraft'     => true,
                'previewMode'  => session('cms_preview_mode', 'published'),
                'status'       => $useDraft ? 0 : 1,
                'updatedAt'    => $page->updated_at->toIso8601String(),
                'editUrl'      => FilamentPageResource::getUrl('edit', ['record' => $page->id]),
            ];
        }

        return inertia('Page', [
            'page'       => PageResource::make($page),
            'cmsToolbar' => $cmsToolbar,
        ]);
    }
}
