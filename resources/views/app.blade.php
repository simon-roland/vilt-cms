<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php $cmsSettings = \RolandSolutions\ViltCms\Models\SiteSettings::getSingleton()->data ?? []; @endphp
    {!! $cmsSettings['head_scripts'] ?? '' !!}
    @vite('resources/js/app.ts')
    <x-inertia::head />
  </head>
  <body>
    {!! $cmsSettings['body_start_scripts'] ?? '' !!}
    <x-inertia::app />
    {!! $cmsSettings['body_end_scripts'] ?? '' !!}
  </body>
</html>
