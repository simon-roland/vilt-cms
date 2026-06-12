<?php

use Illuminate\Foundation\Auth\User as AuthUser;
use RolandSolutions\ViltCms\Support\PreviewMode;

it('reads the preview mode per locale', function () {
    session(['cms_preview_mode' => ['en' => 'draft', 'da' => 'published']]);

    expect(PreviewMode::mode('en'))->toBe('draft')
        ->and(PreviewMode::mode('da'))->toBe('published')
        ->and(PreviewMode::mode('de'))->toBe('published');
});

it('defaults to the current app locale', function () {
    app()->setLocale('da');
    session(['cms_preview_mode' => ['da' => 'draft']]);

    expect(PreviewMode::mode())->toBe('draft');
});

it('falls back to published for malformed session values', function () {
    session(['cms_preview_mode' => ['en' => ['nested' => 'junk']]]);

    expect(PreviewMode::mode('en'))->toBe('published');
});

it('is only active for authenticated users in draft mode', function () {
    session(['cms_preview_mode' => ['en' => 'draft']]);

    expect(PreviewMode::active())->toBeFalse();

    $this->be(new class extends AuthUser {});

    expect(PreviewMode::active())->toBeTrue();

    session(['cms_preview_mode' => ['en' => 'published']]);

    expect(PreviewMode::active())->toBeFalse();
});
