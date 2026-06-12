<?php

use RolandSolutions\ViltCms\Models\LocaleDomainMapping;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Support\Locales;

beforeEach(function () {
    Locales::flushDomainCache();
});

function makePublishedContent(Page $page, string $locale, string $slug, bool $frontpage = false): void
{
    $layout = [['type' => 'default', 'data' => ['id' => 'x']]];

    $page->contents()->create([
        'locale' => $locale,
        'slug' => $slug,
        'layout' => $layout,
        'blocks' => [],
        'meta' => [],
        'is_frontpage' => $frontpage ? true : null,
        'published_content' => [
            'layout' => $layout,
            'blocks' => [],
            'meta' => [],
        ],
        'published_at' => now(),
    ]);
}

it('picks up domain mapping changes within the same process', function () {
    $mapping = LocaleDomainMapping::create(['domain' => 'example.dk', 'locale' => 'da']);
    expect(Locales::fromDomain('example.dk'))->toBe('da');

    $mapping->update(['locale' => 'en']);
    expect(Locales::fromDomain('example.dk'))->toBe('en');

    $mapping->delete();
    expect(Locales::fromDomain('example.dk'))->toBeNull();
});

it('resolves the domain-mapped locale when no URL prefix is present', function () {
    LocaleDomainMapping::create(['domain' => 'example.dk', 'locale' => 'da']);

    $page = Page::create(['name' => 'Om os']);
    makePublishedContent($page, 'da', 'om-os');

    $this->withHeaders(['X-Inertia' => 'true'])->get('http://example.dk/om-os')->assertOk();

    expect(app()->getLocale())->toBe('da');
});

it('falls back to default locale when the domain is not mapped', function () {
    $page = Page::create(['name' => 'About']);
    makePublishedContent($page, 'en', 'about');

    $this->withHeaders(['X-Inertia' => 'true'])->get('http://unmapped.test/about')->assertOk();

    expect(app()->getLocale())->toBe('en');
});

it('301-redirects to the unprefixed URL when the locale prefix equals the domain default', function () {
    $this->withHeaders(['X-Inertia' => 'true'])->get('http://localhost/en/about')
        ->assertStatus(301)
        ->assertRedirect('/about');
});

it('301-redirects to the unprefixed frontpage when the locale prefix equals the domain default', function () {
    $this->withHeaders(['X-Inertia' => 'true'])->get('http://localhost/en')
        ->assertStatus(301)
        ->assertRedirect('/');
});

it('preserves the query string on redirect', function () {
    $this->withHeaders(['X-Inertia' => 'true'])->get('http://localhost/en/about?utm=foo')
        ->assertStatus(301)
        ->assertRedirect('/about?utm=foo');
});

it('serves secondary-locale prefix content', function () {
    $page = Page::create(['name' => 'Om os']);
    makePublishedContent($page, 'da', 'om-os');

    $this->withHeaders(['X-Inertia' => 'true'])->get('http://localhost/da/om-os')->assertOk();

    expect(app()->getLocale())->toBe('da');
});

it('lets the URL prefix win over the domain default when they differ', function () {
    LocaleDomainMapping::create(['domain' => 'example.dk', 'locale' => 'da']);

    $page = Page::create(['name' => 'About']);
    makePublishedContent($page, 'en', 'about');

    $this->withHeaders(['X-Inertia' => 'true'])->get('http://example.dk/en/about')->assertOk();

    expect(app()->getLocale())->toBe('en');
});

it('redirects missing pages to the locale frontpage when missing_locale_behavior=redirect', function () {
    config(['cms.missing_locale_behavior' => 'redirect']);

    LocaleDomainMapping::create(['domain' => 'example.dk', 'locale' => 'da']);

    $page = Page::create(['name' => 'Forside']);
    makePublishedContent($page, 'da', 'forside', frontpage: true);

    $this->withHeaders(['X-Inertia' => 'true'])->get('http://example.dk/doesnt-exist')
        ->assertStatus(302)
        ->assertRedirect('/');
});

it('404s missing pages when missing_locale_behavior=404', function () {
    config(['cms.missing_locale_behavior' => '404']);

    $this->withHeaders(['X-Inertia' => 'true'])->get('http://localhost/doesnt-exist')->assertNotFound();
});

it('404s missing pages when redirect is configured but the locale has no frontpage', function () {
    config(['cms.missing_locale_behavior' => 'redirect']);

    $this->withHeaders(['X-Inertia' => 'true'])->get('http://localhost/doesnt-exist')->assertNotFound();
});

it('redirects to the prefixed frontpage when the active locale is not the domain default', function () {
    config(['cms.missing_locale_behavior' => 'redirect']);

    $page = Page::create(['name' => 'Forside']);
    makePublishedContent($page, 'da', 'forside', frontpage: true);

    $this->withHeaders(['X-Inertia' => 'true'])->get('http://localhost/da/doesnt-exist')
        ->assertStatus(302)
        ->assertRedirect('/da');
});
