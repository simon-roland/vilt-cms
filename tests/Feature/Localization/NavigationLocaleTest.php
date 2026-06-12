<?php

use Illuminate\Database\QueryException;
use RolandSolutions\ViltCms\Enum\NavigationType;
use RolandSolutions\ViltCms\Http\Middleware\HandleInertiaRequests;
use RolandSolutions\ViltCms\Models\Navigation;
use RolandSolutions\ViltCms\Models\Page;

function makeNav(string $locale, NavigationType $type, array $items): Navigation
{
    return Navigation::create([
        'type' => $type,
        'locale' => $locale,
        'items' => $items,
    ]);
}

function loadNav(string $locale, string $type): array
{
    app()->setLocale($locale);

    $mw = new class extends HandleInertiaRequests
    {
        public function run(string $type): array
        {
            return $this->loadNavigation($type);
        }
    };

    return $mw->run($type);
}

it('loads the navigation matching the current locale', function () {
    makeNav('en', NavigationType::Header, [
        ['type' => 'link', 'data' => ['label' => 'About EN', 'link_type' => 'url', 'url' => '/about']],
    ]);
    makeNav('da', NavigationType::Header, [
        ['type' => 'link', 'data' => ['label' => 'Om DA', 'link_type' => 'url', 'url' => '/om-os']],
    ]);

    $items = loadNav('da', 'header');

    expect($items)->toHaveCount(1)
        ->and($items[0]['data']['label'])->toBe('Om DA');
});

it('falls back to default locale nav when navigation_fallback=default_locale', function () {
    config(['cms.navigation_fallback' => 'default_locale']);

    makeNav('en', NavigationType::Header, [
        ['type' => 'link', 'data' => ['label' => 'Default', 'link_type' => 'url', 'url' => '/']],
    ]);

    $items = loadNav('da', 'header');

    expect($items)->toHaveCount(1)
        ->and($items[0]['data']['label'])->toBe('Default');
});

it('serves an empty nav when navigation_fallback=empty', function () {
    config(['cms.navigation_fallback' => 'empty']);

    makeNav('en', NavigationType::Header, [
        ['type' => 'link', 'data' => ['label' => 'EN', 'link_type' => 'url', 'url' => '/']],
    ]);

    $items = loadNav('da', 'header');

    expect($items)->toBe([]);
});

it('drops links whose target has no published content in the current locale', function () {
    $page = Page::create(['name' => 'Shared']);
    // Only publish in English.
    $page->contents()->create([
        'locale' => 'en',
        'slug' => 'shared',
        'layout' => [], 'blocks' => [], 'meta' => [],
        'published_content' => ['layout' => [], 'blocks' => [], 'meta' => []],
        'published_at' => now(),
    ]);
    // Draft in Danish (no published_content).
    $page->contents()->create([
        'locale' => 'da',
        'slug' => 'delt',
        'layout' => [], 'blocks' => [], 'meta' => [],
    ]);

    makeNav('da', NavigationType::Header, [
        ['type' => 'link', 'data' => ['label' => 'Delt', 'link_type' => 'page', 'page_id' => $page->id]],
        ['type' => 'link', 'data' => ['label' => 'Extern', 'link_type' => 'url', 'url' => 'https://example.com']],
    ]);

    $items = loadNav('da', 'header');

    // Page-link dropped because no published content in 'da'; URL link survives.
    expect($items)->toHaveCount(1)
        ->and($items[0]['data']['label'])->toBe('Extern');
});

it('allows the same type across different locales', function () {
    makeNav('en', NavigationType::Header, []);
    makeNav('da', NavigationType::Header, []);

    expect(Navigation::count())->toBe(2);
});

it('forbids duplicate (type, locale)', function () {
    makeNav('en', NavigationType::Header, []);

    expect(fn () => makeNav('en', NavigationType::Header, []))
        ->toThrow(QueryException::class);
});
