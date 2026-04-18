<?php

namespace RolandSolutions\ViltCms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RolandSolutions\ViltCms\Support\Locales;

/**
 * Stage 1 stub: resolves the active locale from the `{locale}` route parameter
 * or falls back to the configured default. Domain-based detection, prefix
 * redirects, and missing-locale handling are added in Stage 5.
 */
class LocaleDetectionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->route('locale');

        if (! is_string($locale) || ! Locales::isValid($locale)) {
            $locale = Locales::default();
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
