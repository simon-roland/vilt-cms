<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    | The Eloquent model used for authentication and the admin user resource.
    | Must implement \Filament\Models\Contracts\FilamentUser.
    */
    'user_model' => env('CMS_USER_MODEL', \App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Media disk
    |--------------------------------------------------------------------------
    | The filesystem disk used for storing uploaded media files.
    | Defaults to the "public" disk when not set.
    */
    'media_disk' => env('CMS_MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Button styles
    |--------------------------------------------------------------------------
    | Options surfaced in the Button block's style selector.
    | Each entry is [value => label]. Leave empty to hide the style field.
    |
    | Example:
    |   'buttons' => [
    |       'primary'   => 'Primary',
    |       'secondary' => 'Secondary',
    |   ],
    */
    'buttons' => [],

    /*
    |--------------------------------------------------------------------------
    | Padding options
    |--------------------------------------------------------------------------
    | Vertical padding options surfaced in the Video block's spacing selector.
    | Each entry is [value => label]. Leave empty to hide the padding field.
    |
    | Example:
    |   'padding' => [
    |       'sm' => 'Small',
    |       'md' => 'Medium',
    |       'lg' => 'Large',
    |   ],
    */
    'padding' => [],
];
