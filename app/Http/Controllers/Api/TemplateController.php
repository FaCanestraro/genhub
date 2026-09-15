<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Template::orderByDesc('is_favorite')->latest()->get()
        );
    }

    public function toggleFavorite(Template $template)
    {
        $template->update(['is_favorite' => !$template->is_favorite]);

        return response()->json(['is_favorite' => $template->is_favorite]);
    }
}
