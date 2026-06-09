<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = $request->user()->templates()->latest()->get();
        return response()->json($templates);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'prompt'  => 'required|string',
            'type'    => 'required|in:image,video',
        ]);

        $template = $request->user()->templates()->create($data);

        return response()->json($template, 201);
    }

    public function update(Request $request, Template $template)
    {
        abort_if($template->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'title'  => 'sometimes|string|max:255',
            'prompt' => 'sometimes|string',
            'type'   => 'sometimes|in:image,video',
        ]);

        $template->update($data);

        return response()->json($template);
    }

    public function destroy(Request $request, Template $template)
    {
        abort_if($template->user_id !== $request->user()->id, 403);

        if ($template->preview_path) {
            Storage::disk('r2')->delete($template->preview_path);
        }

        $template->delete();

        return response()->json(null, 204);
    }

    public function uploadPreview(Request $request, Template $template)
    {
        abort_if($template->user_id !== $request->user()->id, 403);

        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm|max:51200']);

        if ($template->preview_path) {
            Storage::disk('r2')->delete($template->preview_path);
        }

        $path = $request->file('file')->store('templates', 'r2');
        $template->update(['preview_path' => $path]);

        return response()->json(['preview_path' => $path]);
    }
}
