<?php

namespace RolandSolutions\ViltCms\Support;

class PreviewMode
{
    protected static ?\Closure $resolver = null;

    /**
     * Override the default preview mode check with a custom callback.
     *
     * Example (in AppServiceProvider::boot):
     *   PreviewMode::resolveUsing(fn() => auth()->check() && auth()->user()->isEditor());
     */
    public static function resolveUsing(\Closure $callback): void
    {
        static::$resolver = $callback;
    }

    /**
     * Whether the current request should show draft (unpublished) content.
     *
     * True when an authenticated user has explicitly enabled draft preview mode
     * via the CMS toolbar. Guests always see published content only.
     */
    public static function active(): bool
    {
        if (static::$resolver !== null) {
            return (bool) (static::$resolver)();
        }

        return auth()->check() && session('cms_preview_mode', 'published') === 'draft';
    }
}
