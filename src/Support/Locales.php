<?php

namespace RolandSolutions\ViltCms\Support;

class Locales
{
    public static function all(): array
    {
        return config('cms.locales', []);
    }

    public static function keys(): array
    {
        return array_keys(static::all());
    }

    public static function default(): string
    {
        return config('cms.default_locale');
    }

    public static function isValid(string $locale): bool
    {
        return array_key_exists($locale, static::all());
    }

    public static function isDefault(string $locale): bool
    {
        return $locale === static::default();
    }
}
