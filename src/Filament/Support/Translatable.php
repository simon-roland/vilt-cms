<?php

namespace RolandSolutions\ViltCms\Filament\Support;

use Illuminate\Support\Arr;

/**
 * Tracks which Filament field state-paths are marked `->translatable()`.
 *
 * Populated during schema rendering via the `translatable` macro registered
 * in CmsServiceProvider::boot(). ManageSiteSettings::save() reads this
 * registry to split form state between the global SiteSettings row and the
 * per-locale row.
 */
class Translatable
{
    /** @var array<string, true> */
    private static array $fields = [];

    public static function mark(string $statePath): void
    {
        self::$fields[$statePath] = true;
    }

    public static function isTranslatable(string $statePath): bool
    {
        return isset(self::$fields[$statePath]);
    }

    /**
     * All registered state-paths.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::$fields);
    }

    public static function reset(): void
    {
        self::$fields = [];
    }

    /**
     * Return the state-path with any leading container prefix (e.g. `data.`) stripped.
     */
    public static function stripPrefix(string $statePath, string $prefix = 'data.'): string
    {
        return str_starts_with($statePath, $prefix) ? substr($statePath, strlen($prefix)) : $statePath;
    }

    /**
     * Extract the per-locale overrides from a saved form state: only translatable
     * keys whose value actually differs from the global row are persisted, so
     * untouched fields keep inheriting future global changes.
     *
     * @param  array<string, mixed>  $state
     * @param  array<int, string>  $translatableKeys
     * @param  array<string, mixed>  $global
     * @return array<string, mixed>
     */
    public static function overrides(array $state, array $translatableKeys, array $global): array
    {
        return array_filter(
            Arr::only($state, $translatableKeys),
            fn ($value, $key) => $value !== ($global[$key] ?? null),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
