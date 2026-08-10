<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Models\Action;
use App\Models\Campaign;
use App\Models\Generation;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ActionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermission::class.':campaigns,view',   only: ['allActions', 'index', 'show']),
            new Middleware(CheckPermission::class.':campaigns,create', only: ['store']),
            new Middleware(CheckPermission::class.':campaigns,edit',   only: ['update']),
            new Middleware(CheckPermission::class.':campaigns,delete', only: ['destroy']),
        ];
    }

    public function allActions(Request $request)
    {
        $actions = Action::where('company_id', $request->company()->id)
            ->with('latestGeneration', 'campaign')
            ->latest()
            ->get();

        return response()->json($actions);
    }

    public function index(Request $request, Campaign $campaign)
    {
        abort_if($campaign->company_id !== $request->company()->id, 403);

        $actions = $campaign->actions()
            ->with('latestGeneration.assets')
            ->latest()
            ->get();

        return response()->json($actions);
    }

    public function store(Request $request, Campaign $campaign)
    {
        abort_if($campaign->company_id !== $request->company()->id, 403);

        $data = $request->validate([
            'type'                 => 'required|in:post,reel,carousel,story,tiktok_video',
            'platform'             => 'required|in:instagram,tiktok,facebook,youtube',
            'title'                => 'required|string|max:255',
            'brief'                => 'nullable|string',
            'product_ids'          => 'nullable|array',
            'resolution'           => 'nullable|string',
            'quantity'             => 'integer|min:1|max:10',
            'scheduled_at'         => 'nullable|date',
            'attach_generation_ids'=> 'nullable|array',
            'attach_generation_ids.*' => 'integer',
        ]);

        $attachIds = $data['attach_generation_ids'] ?? [];
        unset($data['attach_generation_ids']);

        $action = $campaign->actions()->create([
            ...$data,
            'company_id' => $request->company()->id,
        ]);

        if ($attachIds) {
            Generation::where('company_id', $request->company()->id)
                ->whereIn('id', $attachIds)
                ->update(['action_id' => $action->id]);
        }

        $campaign->increment('actions_count');

        AuditLogger::log('campaigns', 'action.created', "Ação \"{$action->title}\" criada na campanha \"{$campaign->name}\"", [
            'subject' => $action,
            'input' => $data,
        ]);

        return response()->json($action, 201);
    }

    public function show(Request $request, Action $action)
    {
        abort_if($action->company_id !== $request->company()->id, 403);

        $action->load('generations.assets', 'campaign');

        $products = \App\Models\Product::whereIn('id', $action->product_ids ?? [])
            ->get(['id', 'name', 'description', 'price', 'category', 'images']);

        $action->setAttribute('products', $products);

        return response()->json($action);
    }

    public function update(Request $request, Action $action)
    {
        abort_if($action->company_id !== $request->company()->id, 403);

        $data = $request->validate([
            'title'                   => 'sometimes|string|max:255',
            'brief'                   => 'nullable|string',
            'caption'                 => 'nullable|string',
            'hashtags'                => 'nullable|array',
            'status'                  => 'in:draft,generating,ready,scheduled,published,failed',
            'scheduled_at'            => 'nullable|date',
            'platform'                => 'sometimes|in:instagram,tiktok,facebook,youtube',
            'type'                    => 'sometimes|in:post,reel,carousel,story,tiktok_video',
            'resolution'              => 'nullable|string',
            'quantity'                => 'integer|min:1|max:10',
            'product_ids'             => 'nullable|array',
            'attach_generation_ids'   => 'nullable|array',
            'attach_generation_ids.*' => 'integer',
        ]);

        $attachIds = $data['attach_generation_ids'] ?? [];
        unset($data['attach_generation_ids']);

        $action->update($data);

        if ($attachIds) {
            Generation::where('company_id', $request->company()->id)
                ->whereIn('id', $attachIds)
                ->update(['action_id' => $action->id]);
        }

        AuditLogger::log('campaigns', 'action.updated', "Ação \"{$action->title}\" atualizada", [
            'subject' => $action,
            'input' => $data,
        ]);

        return response()->json($action);
    }

    public function destroy(Request $request, Action $action)
    {
        abort_if($action->company_id !== $request->company()->id, 403);

        $actionTitle = $action->title;
        $action->campaign->decrement('actions_count');
        $action->delete();

        AuditLogger::log('campaigns', 'action.deleted', "Ação \"{$actionTitle}\" excluída");

        return response()->json(null, 204);
    }
}
