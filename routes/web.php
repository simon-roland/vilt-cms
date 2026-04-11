<?php

use RolandSolutions\ViltCms\Http\Controllers\MediaController;
use RolandSolutions\ViltCms\Http\Controllers\PageController;
use RolandSolutions\ViltCms\Http\Controllers\PreviewModeController;
use Illuminate\Support\Facades\Route;

Route::post('/cms/preview-mode', PreviewModeController::class)
    ->middleware('auth')
    ->name('cms.preview-mode');

Route::get('/', [PageController::class, 'frontpage'])
    ->name('pages.frontpage');

Route::get('/media/{filename}', [MediaController::class, 'show'])
    ->where('filename', '.*')
    ->name('media');

Route::get('/{page}', [PageController::class, 'show'])
    ->where('page', '(?!' . preg_quote(config('cms.panel_path', 'admin'), '/') . '$)[a-zA-Z0-9-]+')
    ->name('pages.show');

Route::fallback(function () {
    return redirect('/');
});
