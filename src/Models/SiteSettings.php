<?php

namespace RolandSolutions\ViltCms\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSettings extends Model
{
    protected $table = 'site_settings';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * The global (locale-null) settings row. Non-translatable fields live here.
     */
    public static function global(): static
    {
        return static::firstOrCreate(['locale' => null], ['data' => []]);
    }

    /**
     * The per-locale overrides row, if any.
     */
    public static function forLocale(string $locale): ?static
    {
        return static::where('locale', $locale)->first();
    }

    /**
     * Deep-merge global settings with the locale-specific overrides. Locale wins.
     *
     * @return array<string, mixed>
     */
    public static function getResolved(string $locale): array
    {
        $global = static::global()->data ?? [];
        $override = static::forLocale($locale)?->data ?? [];

        return array_replace_recursive($global, $override);
    }

    /**
     * Back-compat shim for callers still using the pre-locale singleton.
     *
     * @deprecated Use global() or getResolved($locale) instead.
     */
    public static function getSingleton(): static
    {
        return static::global();
    }
}
