<?php

use RolandSolutions\ViltCms\Models\LocaleDomainMapping;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Support\Locales;

beforeEach(function () {
    Locales::flushDomainCache();
});

function createContent(Page $page, string $locale, string $slug, bool $published = true, bool $frontpage = false): void
{
    $layout = [['type' => 'default', 'data' => ['id' => 'x']]];

    $page->contents()->create([
        'locale' => $locale,
        'slug' => $slug,
        'layout' => $layout,
        'blocks' => [],
        'meta' => [],
        'is_frontpage' => $frontpage ? true : null,
        'published_content' => $published ? [
            'layout' => $layout,
            'blocks' => [],
            'meta' => [],
        ] : null,
        'published_at' => $published ? now() : null,
    ]);
}

it('exposes locale variants with per-locale slugs and prefix-aware URLs', function () {
    $page = Page::create(['name' => 'About']);
    createContent($page, 'en', 'about');
    createContent($page, 'da', 'om-os');

    $response = $this->withHeaders(['X-Inertia' => 'true'])->get('/about');

    $variants = $response->json('props.locale_variants');

    expect($variants)->toBe([
        'en' => ['slug' => 'about', 'available' => true, 'url' => '/about'],
        'da' => ['slug' => 'om-os', 'available' => true, 'url' => '/da/om-os'],
    ]);
});

it('marks unpublished sibling locales unavailable and falls back to that locale frontpage', function () {
    $frontpage = Page::create(['name' => 'Forside']);
    createContent($frontpage, 'da', 'forside', frontpage: true);

    $page = Page::create(['name' => 'About']);
    createContent($page, 'en', 'about');
    createContent($page, 'da', 'om-os', published: false);

    $variants = $this->withHeaders(['X-Inertia' => 'true'])->get('/about')
        ->json('props.locale_variants');

    expect($variants['da'])->toBe(['slug' => 'om-os', 'available' => false, 'url' => '/da']);
});

it('returns a null url when the locale has neither the page nor a frontpage', function () {
    $page = Page::create(['name' => 'About']);
    createContent($page, 'en', 'about');

    $variants = $this->withHeaders(['X-Inertia' => 'true'])->get('/about')
        ->json('props.locale_variants');

    expect($variants['da'])->toBe(['slug' => null, 'available' => false, 'url' => null]);
});

it('builds variant URLs relative to the domain default locale', function () {
    LocaleDomainMapping::create(['domain' => 'example.dk', 'locale' => 'da']);

    $page = Page::create(['name' => 'About']);
    createContent($page, 'en', 'about');
    createContent($page, 'da', 'om-os');

    $variants = $this->withHeaders(['X-Inertia' => 'true'])->get('http://example.dk/om-os')
        ->json('props.locale_variants');

    // On the Danish domain, da is unprefixed and en carries the prefix.
    expect($variants['da']['url'])->toBe('/om-os')
        ->and($variants['en']['url'])->toBe('/en/about');
});

it('omits locale variants when only one locale is configured', function () {
    config(['cms.locales' => ['en' => 'English']]);

    $page = Page::create(['name' => 'About']);
    createContent($page, 'en', 'about');

    $this->withHeaders(['X-Inertia' => 'true'])->get('/about')
        ->assertOk()
        ->assertJsonPath('props.locale_variants', null);
});

it('shares the domain-aware default locale with the frontend', function () {
    LocaleDomainMapping::create(['domain' => 'example.dk', 'locale' => 'da']);

    $page = Page::create(['name' => 'Om os']);
    createContent($page, 'da', 'om-os');

    $this->withHeaders(['X-Inertia' => 'true'])->get('http://example.dk/om-os')
        ->assertJsonPath('props.defaultLocale', 'da')
        ->assertJsonPath('props.locale', 'da');

    Locales::flushDomainCache();

    $page2 = Page::create(['name' => 'About']);
    createContent($page2, 'en', 'about');

    $this->withHeaders(['X-Inertia' => 'true'])->get('http://localhost/about')
        ->assertJsonPath('props.defaultLocale', 'en');
});
