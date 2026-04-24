<?php

use Illuminate\Database\QueryException;
use RolandSolutions\ViltCms\Actions\PublishPage;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Models\PageContent;

function createPageWithContent(array $locales = ['en'], array $attrs = [], string $name = 'Test page'): Page
{
    $page = Page::create(['name' => $name.' '.uniqid()]);

    foreach ($locales as $locale) {
        $page->contents()->create(array_merge([
            'locale' => $locale,
            'slug' => $locale.'-page-'.uniqid(),
            'layout' => [['type' => 'default', 'data' => ['id' => 'abc']]],
            'blocks' => [['type' => 'text', 'data' => ['body' => "Content in {$locale}"]]],
            'meta' => ['title' => ucfirst($locale).' Title'],
        ], $attrs));
    }

    return $page;
}

it('can add a blank locale to an existing page', function () {
    $page = createPageWithContent(['en']);

    $daContent = $page->contents()->create([
        'locale' => 'da',
        'slug' => 'dansk-side',
        'layout' => [],
        'blocks' => null,
        'meta' => null,
    ]);

    expect($daContent->exists)->toBeTrue();
    expect($daContent->locale)->toBe('da');
    expect($daContent->page_id)->toBe($page->id);
    expect($daContent->blocks)->toBeNull();
    expect($daContent->published_content)->toBeNull();
    expect($page->contents()->count())->toBe(2);
});

it('can add a locale by copying content from another locale', function () {
    $page = createPageWithContent(['en']);
    $enContent = $page->contents()->where('locale', 'en')->first();

    $daContent = $page->contents()->create([
        'locale' => 'da',
        'slug' => 'dansk-kopi',
        'layout' => $enContent->layout,
        'blocks' => $enContent->blocks,
        'meta' => $enContent->meta,
    ]);

    expect($daContent->layout)->toBe($enContent->layout);
    expect($daContent->blocks)->toBe($enContent->blocks);
    expect($daContent->meta)->toBe($enContent->meta);
    expect($daContent->published_content)->toBeNull();
});

it('duplicating a page copies all locale contents', function () {
    $page = createPageWithContent(['en', 'da']);
    $allContents = $page->contents;

    $newPage = Page::create(['name' => $page->name.' (copy)']);

    foreach ($allContents as $content) {
        $newPage->contents()->create([
            'locale' => $content->locale,
            'slug' => $content->slug.'-copy',
            'layout' => $content->layout,
            'blocks' => $content->blocks,
            'meta' => $content->meta,
        ]);
    }

    expect($newPage->contents()->count())->toBe(2);
    expect($newPage->contents()->where('locale', 'en')->exists())->toBeTrue();
    expect($newPage->contents()->where('locale', 'da')->exists())->toBeTrue();

    // Slugs should be unique (suffixed)
    $originalSlugs = $allContents->pluck('slug')->toArray();
    $newSlugs = $newPage->contents->pluck('slug')->toArray();

    foreach ($newSlugs as $slug) {
        expect($originalSlugs)->not->toContain($slug);
    }
});

it('can copy content from one locale to another without affecting published version', function () {
    $page = createPageWithContent(['en', 'da']);
    $enContent = $page->contents()->where('locale', 'en')->first();
    $daContent = $page->contents()->where('locale', 'da')->first();

    // Publish DA first
    PublishPage::make()->handle($daContent);
    $daContent->refresh();
    $originalPublished = $daContent->published_content;

    // Now copy layout/blocks from EN to DA (overwrites draft)
    $daContent->update([
        'layout' => $enContent->layout,
        'blocks' => $enContent->blocks,
        'meta' => $enContent->meta,
    ]);

    $daContent->refresh();

    // Draft content changed
    expect($daContent->blocks)->toBe($enContent->blocks);
    expect($daContent->meta)->toBe($enContent->meta);

    // Published version unchanged
    expect($daContent->published_content)->toBe($originalPublished);
});

it('publishes locales independently', function () {
    $page = createPageWithContent(['en', 'da']);
    $enContent = $page->contents()->where('locale', 'en')->first();
    $daContent = $page->contents()->where('locale', 'da')->first();

    // Publish EN only
    PublishPage::make()->handle($enContent);

    $enContent->refresh();
    $daContent->refresh();

    expect($enContent->isPublished())->toBeTrue();
    expect($daContent->isPublished())->toBeFalse();

    // Now modify and publish DA
    $daContent->update(['meta' => ['title' => 'Updated DA title']]);
    PublishPage::make()->handle($daContent);

    $enContent->refresh();
    $daContent->refresh();

    // EN published meta is unchanged; DA now has new meta
    expect($enContent->published_content['meta']['title'])->toBe('En Title');
    expect($daContent->published_content['meta']['title'])->toBe('Updated DA title');
});

it('scopes slug uniqueness to locale', function () {
    createPageWithContent(['en'], ['slug' => 'about']);
    $pageB = Page::create(['name' => 'B']);

    // Same slug in different locale should work
    $daContent = $pageB->contents()->create([
        'locale' => 'da',
        'slug' => 'about',
        'layout' => [],
    ]);

    expect($daContent->exists)->toBeTrue();

    // Same slug in same locale should fail (DB unique constraint)
    $pageC = Page::create(['name' => 'C']);

    expect(fn () => $pageC->contents()->create([
        'locale' => 'en',
        'slug' => 'about',
        'layout' => [],
    ]))->toThrow(QueryException::class);
});

it('lists pages from default locale with eager-loaded siblings', function () {
    $page1 = createPageWithContent(['en', 'da']);
    $page2 = createPageWithContent(['en']);

    // Query matches PagesTable::configure modifyQueryUsing logic
    $results = PageContent::where('locale', 'en')
        ->with('page.contents')
        ->get();

    expect($results)->toHaveCount(2);

    $first = $results->first(fn ($r) => $r->page_id === $page1->id);
    expect($first->page->contents)->toHaveCount(2);

    $second = $results->first(fn ($r) => $r->page_id === $page2->id);
    expect($second->page->contents)->toHaveCount(1);
});

it('publish snapshot no longer stores per-locale name', function () {
    $page = createPageWithContent(['en']);
    $content = $page->contents()->first();

    PublishPage::make()->handle($content);
    $content->refresh();

    expect($content->published_content)->toHaveKeys(['layout', 'blocks', 'meta']);
    expect($content->published_content)->not->toHaveKey('name');
});
