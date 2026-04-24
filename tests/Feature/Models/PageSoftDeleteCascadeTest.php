<?php

use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Models\PageContent;

function makePageWithContents(array $locales = ['en', 'da']): Page
{
    $page = Page::create(['name' => 'Test page '.uniqid()]);

    foreach ($locales as $locale) {
        $page->contents()->create([
            'locale' => $locale,
            'slug' => "page-{$locale}-".uniqid(),
            'layout' => [['type' => 'default', 'data' => ['id' => 'abc']]],
        ]);
    }

    return $page->fresh();
}

it('hides contents of a soft-deleted page via the page relation default scope', function () {
    $page = makePageWithContents();

    $page->delete();

    // PageContent rows themselves still exist — they are not soft-deletable.
    expect(PageContent::count())->toBe(2);

    // But the parent Page is hidden from the default scope.
    expect(Page::count())->toBe(0);
    expect(Page::withTrashed()->count())->toBe(1);
});

it('restoring a page makes it visible again', function () {
    $page = makePageWithContents();

    $page->delete();
    $page->restore();

    expect(Page::count())->toBe(1);
    expect(PageContent::count())->toBe(2);
});

it('force-deleting a page removes all its contents via FK cascade', function () {
    $page = makePageWithContents();

    $page->forceDelete();

    expect(Page::withTrashed()->count())->toBe(0);
    expect(PageContent::count())->toBe(0);
});
