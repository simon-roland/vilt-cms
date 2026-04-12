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

        session(['cms_preview_mode' => $request->mode]);

        return redirect()->back();
    }
}
