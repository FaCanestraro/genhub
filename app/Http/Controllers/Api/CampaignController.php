<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CampaignController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermission::class.':campaigns,view',   only: ['index', 'show']),
            new Middleware(CheckPermission::class.':campaigns,create', only: ['store']),
            new Middleware(CheckPermission::class.':campaigns,edit',   only: ['update']),
            new Middleware(CheckPermission::class.':campaigns,delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $campaigns = Campaign::where('user_id', $request->user()->accountId())
            ->withCount('actions')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objective' => 'nullable|string|max:255',
            'status' => 'in:draft,active,paused,finished,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
            'goal_leads' => 'nullable|integer|min:0',
            'goal_sales' => 'nullable|integer|min:0',
        ]);

        $campaign = Campaign::create(['user_id' => $request->user()->accountId()] + $data);

        return response()->json($campaign, 201);
    }

    public function show(Request $request, Campaign $campaign)
    {
        abort_if($campaign->user_id !== $request->user()->accountId(), 403);

        $campaign->load(['actions' => fn ($q) => $q->with('latestGeneration')->latest()]);

        return response()->json($campaign);
    }

    public function update(Request $request, Campaign $campaign)
    {
        abort_if($campaign->user_id !== $request->user()->accountId(), 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'objective' => 'nullable|string|max:255',
            'status' => 'in:draft,active,paused,finished,archived',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
            'goal_leads' => 'nullable|integer|min:0',
            'goal_sales' => 'nullable|integer|min:0',
        ]);

        $campaign->update($data);

        return response()->json($campaign);
    }

    public function destroy(Request $request, Campaign $campaign)
    {
        abort_if($campaign->user_id !== $request->user()->accountId(), 403);

        $campaign->delete();

        return response()->json(null, 204);
    }
}
