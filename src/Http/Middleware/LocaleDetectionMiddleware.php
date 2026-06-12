<?php

namespace RolandSolutions\ViltCms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RolandSolutions\ViltCms\Support\Locales;

class LocaleDetectionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $domainLocale = Locales::fromDomain($request->getHost()) ?? Locales::default();

        $routeLocale = $request->route('locale');
        $hasLocalePrefix = is_string($routeLocale) && Locales::isValid($routeLocale);

        if ($hasLocalePrefix && $routeLocale === $domainLocale) {
            $pageSlug = $request->route('slug');
            $path = $pageSlug ? '/'.$pageSlug : '/';
            $query = $request->getQueryString();

            return redirect()->to($path.($query ? '?'.$query : ''), 301);
        }

        $locale = $hasLocalePrefix ? $routeLocale : $domainLocale;

        app()->setLocale($locale);

        return $next($request);
    }
}
