<?php

namespace RolandSolutions\ViltCms\Support;

use RolandSolutions\ViltCms\Models\LocaleDomainMapping;

class Locales
{
    /** @var array<string, ?string> */
    private static array $domainCache = [];

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

    public static function fromDomain(string $host): ?string
    {
        $host = strtolower($host);

        if (array_key_exists($host, static::$domainCache)) {
            return static::$domainCache[$host];
        }

        $locale = LocaleDomainMapping::where('domain', $host)->value('locale');

        return static::$domainCache[$host] = (is_string($locale) && static::isValid($locale)) ? $locale : null;
    }

    public static function flushDomainCache(): void
    {
        static::$domainCache = [];
    }
}
