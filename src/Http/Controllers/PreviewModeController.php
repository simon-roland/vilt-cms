<?php

namespace RolandSolutions\ViltCms\Http\Controllers;

use Illuminate\Http\Request;

class PreviewModeController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'mode' => ['required', 'in:draft,published'],
        ]);

        $locale = app()->getLocale();
        session(["cms_preview_mode.{$locale}" => $request->mode]);

        return redirect()->back();
    }
}
