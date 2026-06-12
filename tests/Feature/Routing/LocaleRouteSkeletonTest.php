<?php

use RolandSolutions\ViltCms\Models\Page;
use RolandSolutions\ViltCms\Support\Locales;

/**
 * Verifies the Stage 1 routing skeleton: default-locale URLs work without
 * a prefix, and the LocaleDetectionMiddleware sets app()->getLocale().
 * Full redirect/prefix logic arrives in Stage 5.
 */
it('resolves app locale to the configured default on unprefixed URLs', function () {
    $page = Page::create(['name' => 'About']);
    $page->contents()->create([
        'locale' => Locales::default(),
        'slug' => 'about',
        'layout' => [['type' => 'default', 'data' => ['id' => 'x']]],
        'published_content' => [
            'layout' => [['type' => 'default', 'data' => ['id' => 'x']]],
            'blocks' => null, 'meta' => null,
        ],
        'published_at' => now(),
    ]);

    expect(app()->getLocale())->toBe(Locales::default());
});
