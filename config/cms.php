<?php

use App\Models\User;

return [
    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    | The Eloquent model used for authentication and the admin user resource.
    | Must implement \Filament\Models\Contracts\FilamentUser.
    */
    'user_model' => env('CMS_USER_MODEL', User::class),

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

    /*
    |--------------------------------------------------------------------------
    | Admin panel path
    |--------------------------------------------------------------------------
    | The URL path segment your Filament panel is registered under.
    | This is used to prevent the CMS page router from intercepting admin URLs.
    | Change this if you have customised your panel's ->path() setting.
    */
    'panel_path' => env('CMS_PANEL_PATH', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    | Supported content locales keyed by locale code. Each key must be a valid
    | URL segment (lowercase alphanumeric + dashes). A page slug cannot collide
    | with any configured locale key.
    |
    | Example:
    |   'locales' => ['en' => 'English', 'da' => 'Dansk'],
    */
    'locales' => [
        'en' => 'English',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default locale
    |--------------------------------------------------------------------------
    | The locale served when no locale prefix is present in the URL. Must be
    | one of the keys in `locales` above. The default locale never carries a
    | path prefix (/about-us); secondary locales are prefixed (/da/om-os).
    */
    'default_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Missing locale behaviour
    |--------------------------------------------------------------------------
    | What to do when a URL resolves to a page that has no PageContent in the
    | active locale. `redirect` sends the visitor to the active locale's
    | frontpage (404 if that locale has no frontpage). `404` always 404s.
    | Consumed by LocaleDetectionMiddleware (Stage 5).
    */
    'missing_locale_behavior' => 'redirect',

    /*
    |--------------------------------------------------------------------------
    | Navigation fallback
    |--------------------------------------------------------------------------
    | When no navigation exists for the current locale: `default_locale` falls
    | back to the default locale's nav; `empty` serves an empty nav. Consumed
    | by the navigation renderer (Stage 3).
    */
    'navigation_fallback' => 'default_locale',
];
