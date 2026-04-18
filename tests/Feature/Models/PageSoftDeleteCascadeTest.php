<?php

use Illuminate\Support\Carbon;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Models\PageContent;

function makePageWithContents(array $locales = ['en', 'da']): Page
{
    $page = Page::create([]);

    foreach ($locales as $locale) {
        $page->contents()->create([
            'locale' => $locale,
            'name' => "Page {$locale}",
            'slug' => "page-{$locale}-".uniqid(),
            'layout' => [['type' => 'default', 'data' => ['id' => 'abc']]],
        ]);
    }

    return $page->fresh();
}

it('soft-deletes contents when the page is soft-deleted', function () {
    $page = makePageWithContents();

    $page->delete();

    expect(PageContent::count())->toBe(0);
    expect(PageContent::withTrashed()->count())->toBe(2);
});

it('restoring a page restores contents trashed at the same moment', function () {
    $page = makePageWithContents();

    $page->delete();
    $page->restore();

    expect(PageContent::count())->toBe(2);
});

it('restoring a page does not resurrect independently-trashed contents', function () {
    Carbon::setTestNow('2026-04-16 10:00:00');
    $page = makePageWithContents();

    // Independently trash the Danish content earlier (deleted_at = 10:00:00).
    $da = $page->contents->firstWhere('locale', 'da');
    $da->delete();

    // Advance the clock so the cascade timestamp is distinct.
    Carbon::setTestNow('2026-04-16 10:05:00');
    $page->delete();
    $page->refresh();
    $page->restore();

    // English was cascaded (trashed with the page) so it's restored.
    // Danish was trashed earlier — it should remain trashed.
    expect(PageContent::where('locale', 'en')->count())->toBe(1);
    expect(PageContent::where('locale', 'da')->count())->toBe(0);
    expect(PageContent::withTrashed()->where('locale', 'da')->count())->toBe(1);
});

it('hard-deleting a page removes all its contents via FK cascade', function () {
    $page = makePageWithContents();

    $page->forceDelete();

    expect(PageContent::withTrashed()->count())->toBe(0);
});
