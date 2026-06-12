<?php

use RolandSolutions\ViltCms\Database\Seeders\CmsShowcaseSeeder;
use RolandSolutions\ViltCms\Models\Navigation;
use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Models\PageContent;
use RolandSolutions\ViltCms\Models\SiteSettings;

it('seeds a published page set and navigations for every configured locale', function () {
    (new CmsShowcaseSeeder)->run();

    expect(Page::count())->toBe(2)
        ->and(PageContent::count())->toBe(4)   // 2 pages × 2 locales
        ->and(Navigation::count())->toBe(4);   // header + footer × 2 locales

    foreach (['en', 'da'] as $locale) {
        $frontpage = PageContent::where('locale', $locale)->where('is_frontpage', true)->first();

        expect($frontpage)->not->toBeNull()
            ->and($frontpage->isPublished())->toBeTrue()
            ->and($frontpage->meta)->toHaveKeys(['title', 'description']);

        $types = Navigation::where('locale', $locale)
            ->pluck('type')
            ->map(fn ($type) => $type->value ?? $type)
            ->sort()
            ->values()
            ->all();

        expect($types)->toBe(['footer', 'header']);
    }
});

it('translates the danish showcase content', function () {
    (new CmsShowcaseSeeder)->run();

    expect(PageContent::where('locale', 'da')->where('slug', 'om-os')->exists())->toBeTrue()
        ->and(PageContent::where('locale', 'en')->where('slug', 'about')->exists())->toBeTrue();

    $header = Navigation::where('locale', 'da')->where('type', 'header')->first();
    expect($header->items[0]['data']['label'])->toBe('Hjem');
});

it('serves the seeded content on the frontend in both locales', function () {
    (new CmsShowcaseSeeder)->run();

    $this->withHeaders(['X-Inertia' => 'true'])->get('/')->assertOk();
    $this->withHeaders(['X-Inertia' => 'true'])->get('/about')->assertOk();
    $this->withHeaders(['X-Inertia' => 'true'])->get('/da')->assertOk();
    $this->withHeaders(['X-Inertia' => 'true'])->get('/da/om-os')->assertOk();
});

it('seeds default site settings when none exist', function () {
    (new CmsShowcaseSeeder)->run();

    expect(SiteSettings::global()->data)
        ->toHaveKey('site_name')
        ->toHaveKey('title_format');
});

it('does not overwrite existing site settings', function () {
    SiteSettings::global()->update(['data' => ['site_name' => 'Custom']]);

    (new CmsShowcaseSeeder)->run();

    expect(SiteSettings::global()->data['site_name'])->toBe('Custom');
});

it('does nothing when content already exists', function () {
    Page::create(['name' => 'Existing']);

    (new CmsShowcaseSeeder)->run();

    expect(Page::count())->toBe(1)
        ->and(Navigation::count())->toBe(0);
});
