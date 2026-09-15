<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Jobs\ProcessGeneration;
use App\Models\Action;
use App\Models\Generation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class GenerationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermission::class.':generate,view',   only: ['index', 'show', 'standaloneHistory']),
            new Middleware(CheckPermission::class.':generate,create', only: ['generate', 'generateStandalone']),
            new Middleware(CheckPermission::class.':generate,edit',   only: ['renameSession', 'detach']),
            new Middleware(CheckPermission::class.':generate,delete', only: ['destroy', 'destroySession']),
        ];
    }

    public function generate(Request $request, Action $action)
    {
        abort_if($action->company_id !== $request->company()->id, 403);

        $request->validate([
            'type'   => 'required|in:image,text,carousel,video',
            'prompt' => 'nullable|string|max:2000',
        ]);

        $action->update(['status' => 'generating']);

        $generation = Generation::create([
            'action_id' => $action->id,
            'company_id' => $request->company()->id,
            'type' => $request->type,
            'status' => 'pending',
            'prompt' => $request->prompt,
            'started_at' => now(),
        ]);

        ProcessGeneration::dispatch(
            $generation->id,
            $request->type,
            $action->id,
            null,
            $action->product_ids ?? [],
            $request->prompt,
            $request->company()->id,
            $request->user()->id,
        );

        return response()->json($generation->load('assets'), 202);
    }

    public function generateStandalone(Request $request)
    {
        $request->validate([
            'type'         => 'required|in:image,text,carousel,video',
            'platform'     => 'required|in:instagram,tiktok,facebook,youtube',
            'content_type' => 'required|in:post,reel,carousel,story,tiktok_video',
            'brief'        => 'nullable|string|max:5000',
            'prompt'       => 'nullable|string|max:2000',
            'resolution'   => ['nullable', 'string', 'regex:/^\d+x\d+$/'],
            'quantity'     => 'nullable|integer|min:1|max:10',
            'product_ids'  => 'nullable|array',
            'session_id'   => 'nullable|string|max:36',
        ]);

        $transientAction = [
            'platform'   => $request->platform,
            'type'       => $request->content_type,
            'brief'      => $request->brief,
            'resolution' => $request->resolution ?? '1080x1080',
            'quantity'   => $request->quantity ?? 1,
        ];

        $generation = Generation::create([
            'action_id'  => null,
            'session_id' => $request->session_id,
            'company_id'    => $request->company()->id,
            'type'       => $request->type,
            'status'     => 'pending',
            'prompt'     => $request->brief . ($request->prompt ? "\n\n" . $request->prompt : ''),
            'started_at' => now(),
        ]);

        ProcessGeneration::dispatch(
            $generation->id,
            $request->type,
            null,
            $transientAction,
            $request->product_ids ?? [],
            $request->prompt,
            $request->company()->id,
            $request->user()->id,
        );

        return response()->json($generation->load('assets'), 202);
    }

    public function renameSession(Request $request, string $sessionId)
    {
        $request->validate(['title' => 'required|string|max:100']);

        Generation::where('company_id', $request->company()->id)
            ->where('session_id', $sessionId)
            ->update(['session_title' => $request->title]);

        return response()->json(null, 204);
    }

    public function destroySession(Request $request, string $sessionId)
    {
        $generations = Generation::where('company_id', $request->company()->id)
            ->where('session_id', $sessionId)
            ->with('assets')
            ->get();

        foreach ($generations as $generation) {
            $generation->assets->each(function ($asset) {
                \Illuminate\Support\Facades\Storage::disk($asset->disk)->delete($asset->path);
                $asset->delete();
            });
            $generation->delete();
        }

        return response()->json(null, 204);
    }

    public function standaloneHistory(Request $request)
    {
        $query = Generation::where('company_id', $request->company()->id)
            ->with('assets')
            ->latest();

        if ($request->filled('session_id')) {
            // Busca por sessão independente de estar vinculada a uma ação
            $query->where('session_id', $request->session_id);
        } else {
            // Sem filtro de sessão: retorna apenas gerações avulsas
            $query->whereNull('action_id');
        }

        return response()->json($query->get());
    }

    public function detach(Request $request, Generation $generation)
    {
        abort_if($generation->company_id !== $request->company()->id, 403);

        $generation->update(['action_id' => null]);

        return response()->json(null, 204);
    }

    public function destroy(Request $request, Generation $generation)
    {
        abort_if($generation->company_id !== $request->company()->id, 403);

        $generation->assets()->each(function ($asset) {
            \Illuminate\Support\Facades\Storage::disk($asset->disk)->delete($asset->path);
            $asset->delete();
        });

        $generation->delete();

        return response()->json(null, 204);
    }

    public function show(Request $request, Generation $generation)
    {
        abort_if($generation->company_id !== $request->company()->id, 403);

        return response()->json($generation->load('assets'));
    }

    public function index(Request $request)
    {
        $query = Generation::where('company_id', $request->company()->id)
            ->with('assets', 'action.campaign')
            ->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(24));
    }
}
