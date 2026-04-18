<?php

use Illuminate\Database\QueryException;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Models\PageContent;

function makeContent(Page $page, array $attrs = []): PageContent
{
    return $page->contents()->create(array_merge([
        'locale' => 'en',
        'name' => 'Page',
        'slug' => 'page-'.uniqid(),
        'layout' => [['type' => 'default', 'data' => ['id' => 'abc']]],
    ], $attrs));
}

it('allows each locale its own frontpage', function () {
    $page = Page::create([]);
    $en = makeContent($page, ['locale' => 'en', 'slug' => 'en-home', 'is_frontpage' => true]);
    $da = makeContent($page, ['locale' => 'da', 'slug' => 'da-home', 'is_frontpage' => true]);

    expect($en->fresh()->is_frontpage)->toBeTrue();
    expect($da->fresh()->is_frontpage)->toBeTrue();
});

it('clears prior frontpage in same locale when a new one is set', function () {
    $pageA = Page::create([]);
    $pageB = Page::create([]);

    $a = makeContent($pageA, ['locale' => 'en', 'slug' => 'a', 'is_frontpage' => true]);
    $b = makeContent($pageB, ['locale' => 'en', 'slug' => 'b', 'is_frontpage' => true]);

    expect($a->fresh()->is_frontpage)->toBeNull();
    expect($b->fresh()->is_frontpage)->toBeTrue();
});

it('forbids two rows with is_frontpage=true in the same locale at the DB level', function () {
    $pageA = Page::create([]);
    $pageB = Page::create([]);

    makeContent($pageA, ['locale' => 'en', 'slug' => 'a', 'is_frontpage' => true]);

    // Bypass the booted() hook by inserting directly — DB constraint should catch it.
    expect(fn () => DB::table('page_contents')->insert([
        'page_id' => $pageB->id,
        'locale' => 'en',
        'name' => 'Other',
        'slug' => 'other',
        'layout' => json_encode([['type' => 'default', 'data' => ['id' => 'x']]]),
        'is_frontpage' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('allows many rows with is_frontpage = null in the same locale', function () {
    $pageA = Page::create([]);
    $pageB = Page::create([]);

    makeContent($pageA, ['locale' => 'en', 'slug' => 'a']);
    makeContent($pageB, ['locale' => 'en', 'slug' => 'b']);

    expect(PageContent::where('locale', 'en')->count())->toBe(2);
});

it('forbids duplicate (locale, slug)', function () {
    $pageA = Page::create([]);
    $pageB = Page::create([]);

    makeContent($pageA, ['locale' => 'en', 'slug' => 'same']);

    expect(fn () => makeContent($pageB, ['locale' => 'en', 'slug' => 'same']))
        ->toThrow(QueryException::class);
});

it('allows same slug across different locales', function () {
    $page = Page::create([]);
    makeContent($page, ['locale' => 'en', 'slug' => 'about']);
    makeContent($page, ['locale' => 'da', 'slug' => 'about']);

    expect(PageContent::where('slug', 'about')->count())->toBe(2);
});
