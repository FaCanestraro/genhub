<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class AdminTemplateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(EnsurePlatformAdmin::class),
        ];
    }

    public function index(Request $request)
    {
        return response()->json(Template::latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'  => 'required|string|max:255',
            'prompt' => 'required|string',
            'type'   => 'required|in:image,video',
        ]);

        $template = Template::create(['user_id' => $request->user()->id] + $data);

        return response()->json($template, 201);
    }

    public function update(Request $request, Template $template)
    {
        $data = $request->validate([
            'title'  => 'sometimes|string|max:255',
            'prompt' => 'sometimes|string',
            'type'   => 'sometimes|in:image,video',
        ]);

        $template->update($data);

        return response()->json($template);
    }

    public function destroy(Template $template)
    {
        if ($template->preview_path) {
            Storage::disk('r2')->delete($template->preview_path);
        }

        $template->delete();

        return response()->json(null, 204);
    }

    public function uploadPreview(Request $request, Template $template)
    {
        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm|max:51200']);

        if ($template->preview_path) {
            Storage::disk('r2')->delete($template->preview_path);
        }

        $path = $request->file('file')->store('templates', 'r2');
        $template->update(['preview_path' => $path]);

        return response()->json(['preview_path' => $path]);
    }
}
