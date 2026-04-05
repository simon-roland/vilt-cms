<?php

use RolandSolutions\ViltCms\Http\Controllers\MediaController;
use RolandSolutions\ViltCms\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'frontpage'])
    ->name('pages.frontpage');

Route::get('/media/{filename}', [MediaController::class, 'show'])
    ->where('filename', '.*')
    ->name('media');

Route::get('/{page}', [PageController::class, 'show'])
    ->where('page', '[a-zA-Z0-9-]+')
    ->name('pages.show');

Route::fallback(function () {
    return redirect('/');
});
