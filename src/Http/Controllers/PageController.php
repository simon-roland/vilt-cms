<?php

namespace RolandSolutions\ViltCms\Http\Controllers;

use RolandSolutions\ViltCms\Actions\AddMediaToPage;
use RolandSolutions\ViltCms\Actions\ReplacePageID;
use RolandSolutions\ViltCms\Actions\ResolveBlockResources;
use RolandSolutions\ViltCms\Http\Resources\PageResource;
use RolandSolutions\ViltCms\Models\Page;

class PageController extends Controller
{
    public function frontpage()
    {
        $page = Page::where('is_frontpage', true)
            ->with('media')
            ->first();

        if (!$page) {
            abort(404);
        }

        return $this->renderPage($page);
    }

    public function show($page)
    {
        $page = Page::where('slug', $page)
            ->with('media')
            ->first();

        if (!$page || $page->is_frontpage) {
            return redirect()->route('pages.frontpage');
        }

        return $this->renderPage($page);
    }

    private function renderPage(Page $page)
    {
        AddMediaToPage::make()->handle($page);
        ResolveBlockResources::make()->handle($page);
        $page->blocks = ReplacePageID::make()->handle($page->blocks);

        return inertia('Page', [
            'page' => PageResource::make($page),
        ]);
    }
}
