<?php

use Illuminate\Support\Facades\Validator;
use RolandSolutions\ViltCms\Rules\ReservedLocaleSlug;

it('rejects a slug that matches a configured locale key', function () {
    $validator = Validator::make(
        ['slug' => 'en'],
        ['slug' => [new ReservedLocaleSlug]]
    );

    expect($validator->fails())->toBeTrue();
});

it('rejects a slug that matches a secondary locale key', function () {
    $validator = Validator::make(
        ['slug' => 'da'],
        ['slug' => [new ReservedLocaleSlug]]
    );

    expect($validator->fails())->toBeTrue();
});

it('accepts a slug that does not match any locale key', function () {
    $validator = Validator::make(
        ['slug' => 'about-us'],
        ['slug' => [new ReservedLocaleSlug]]
    );

    expect($validator->fails())->toBeFalse();
});
