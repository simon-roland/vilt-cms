<?php

namespace RolandSolutions\ViltCms\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Display the specified image.
     *
     * @param  string  $filename
     * @return Response
     */
    public function show($filename)
    {
        // Reject any path containing directory traversal sequences.
        if (str_contains($filename, '..')) {
            abort(404);
        }

        $path = Storage::disk(config('cms.media_disk'))->path($filename);

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }
}
