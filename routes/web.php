<?php

use Illuminate\Support\Facades\Route;
use RolandSolutions\ViltCms\Http\Controllers\MediaController;
use RolandSolutions\ViltCms\Http\Controllers\PageController;
use RolandSolutions\ViltCms\Http\Controllers\PreviewModeController;
use RolandSolutions\ViltCms\Http\Middleware\LocaleDetectionMiddleware;

Route::post('/cms/preview-mode', PreviewModeController::class)
    ->middleware('auth')
    ->name('cms.preview-mode');

Route::get('/media/{filename}', [MediaController::class, 'show'])
    ->where('filename', '.*')
    ->name('media');

$pageSlugPattern = '(?!'.preg_quote(config('cms.panel_path', 'admin'), '/').'$)[a-zA-Z0-9-]+';
$localeKeys = array_keys(config('cms.locales', []));
$localePattern = empty($localeKeys) ? '(?!)' : implode('|', array_map('preg_quote', $localeKeys));

Route::middleware(LocaleDetectionMiddleware::class)->group(function () use ($pageSlugPattern, $localePattern) {
    Route::get('/', [PageController::class, 'frontpage'])
        ->name('pages.frontpage');

    Route::get('/{locale}', [PageController::class, 'frontpage'])
        ->where('locale', $localePattern)
        ->name('pages.frontpage.localized');

    Route::get('/{page}', [PageController::class, 'show'])
        ->where('page', $pageSlugPattern)
        ->name('pages.show');

    Route::get('/{locale}/{page}', [PageController::class, 'show'])
        ->where('locale', $localePattern)
        ->where('page', $pageSlugPattern)
        ->name('pages.show.localized');
});

Route::fallback(function () {
    return redirect('/');
});
