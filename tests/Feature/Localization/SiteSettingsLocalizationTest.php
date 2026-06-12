<?php

use Filament\Forms\Components\TextInput;
use RolandSolutions\ViltCms\Filament\Support\Translatable;
use RolandSolutions\ViltCms\Http\Middleware\HandleInertiaRequests;
use RolandSolutions\ViltCms\Models\SiteSettings;

beforeEach(function () {
    Translatable::reset();
});

it('global() returns a reusable row with locale null', function () {
    $row = SiteSettings::global();

    expect($row->locale)->toBeNull()
        ->and(SiteSettings::global()->id)->toBe($row->id);
});

it('getResolved() returns global when no locale row exists', function () {
    SiteSettings::global()->update(['data' => ['site_name' => 'My Site', 'og_image' => 'global.png']]);

    expect(SiteSettings::getResolved('da'))->toBe([
        'site_name' => 'My Site',
        'og_image' => 'global.png',
    ]);
});

it('getResolved() deep-merges locale over global', function () {
    SiteSettings::global()->update(['data' => [
        'site_name' => 'Global',
        'og_image' => 'global.png',
        'nested' => ['a' => 1, 'b' => 2],
    ]]);

    SiteSettings::create([
        'locale' => 'da',
        'data' => [
            'site_name' => 'Globalt',
            'nested' => ['b' => 20, 'c' => 30],
        ],
    ]);

    expect(SiteSettings::getResolved('da'))->toBe([
        'site_name' => 'Globalt',
        'og_image' => 'global.png',
        'nested' => ['a' => 1, 'b' => 20, 'c' => 30],
    ]);
});

it('getResolved() returns global values for locales with no override', function () {
    SiteSettings::global()->update(['data' => ['site_name' => 'Hello']]);
    SiteSettings::create(['locale' => 'da', 'data' => ['site_name' => 'Hej']]);

    expect(SiteSettings::getResolved('en'))->toBe(['site_name' => 'Hello'])
        ->and(SiteSettings::getResolved('da'))->toBe(['site_name' => 'Hej']);
});

it('the ->translatable() macro registers the field state-path', function () {
    TextInput::make('site_name')->translatable();

    $registered = Translatable::all();

    // Either 'site_name' (no container) or 'data.site_name' — we just need it registered.
    expect($registered)->not->toBeEmpty();
    $stripped = array_map(fn ($p) => Translatable::stripPrefix($p), $registered);
    expect($stripped)->toContain('site_name');
});

it('Translatable::stripPrefix removes the data. prefix', function () {
    expect(Translatable::stripPrefix('data.site_name'))->toBe('site_name')
        ->and(Translatable::stripPrefix('site_name'))->toBe('site_name');
});

it('overrides() keeps only translatable values that differ from global', function () {
    $global = ['site_name' => 'Global', 'og_image' => 'global.png', 'twitter_handle' => '@x'];
    $state = ['site_name' => 'Globalt', 'og_image' => 'global.png', 'twitter_handle' => '@y'];

    $overrides = Translatable::overrides($state, ['site_name', 'og_image'], $global);

    // og_image equals global → inherits; twitter_handle is not translatable → dropped.
    expect($overrides)->toBe(['site_name' => 'Globalt']);
});

it('overrides() persists an explicit null that clears a global value', function () {
    $overrides = Translatable::overrides(
        ['og_image' => null],
        ['og_image'],
        ['og_image' => 'global.png']
    );

    expect($overrides)->toBe(['og_image' => null]);
});

it('HandleInertiaRequests shares locale-resolved settings', function () {
    SiteSettings::global()->update(['data' => ['site_name' => 'Global', 'head_scripts' => '<!-- global -->']]);
    SiteSettings::create(['locale' => 'da', 'data' => ['site_name' => 'Globalt']]);

    app()->setLocale('da');

    $mw = new HandleInertiaRequests;
    $shared = $mw->share(request());

    // Locale-dependent props are lazy closures — resolve them like Inertia would.
    $settings = value($shared['settings']);

    expect($settings['site_name'])->toBe('Globalt')
        ->and(value($shared['locale']))->toBe('da')
        ->and($settings)->not->toHaveKey('head_scripts');
});
