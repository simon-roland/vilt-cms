<?php

use Illuminate\Support\Facades\Validator;
use RolandSolutions\ViltCms\Rules\PageSlug;

it('rejects a slug that matches a configured locale key', function () {
    $validator = Validator::make(
        ['slug' => 'en'],
        ['slug' => [new PageSlug]]
    );

    expect($validator->fails())->toBeTrue();
});

it('rejects a slug that matches a secondary locale key', function () {
    $validator = Validator::make(
        ['slug' => 'da'],
        ['slug' => [new PageSlug]]
    );

    expect($validator->fails())->toBeTrue();
});

it('accepts a slug that does not match any locale key', function () {
    $validator = Validator::make(
        ['slug' => 'about-us'],
        ['slug' => [new PageSlug]]
    );

    expect($validator->fails())->toBeFalse();
});

it('rejects a malformed slug', function () {
    $validator = Validator::make(
        ['slug' => 'Not A Slug!'],
        ['slug' => [new PageSlug]]
    );

    expect($validator->fails())->toBeTrue();
});

it('rejects a slug with trailing whitespace', function () {
    $validator = Validator::make(
        ['slug' => 'about-us '],
        ['slug' => [new PageSlug]]
    );

    expect($validator->fails())->toBeTrue();
});
