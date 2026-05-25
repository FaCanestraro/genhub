<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = $request->user()->campaigns()
            ->withCount('actions')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

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

        $campaign = $request->user()->campaigns()->create($data);

        return response()->json($campaign, 201);
    }

    public function show(Request $request, Campaign $campaign)
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);

        $campaign->load(['actions' => fn ($q) => $q->with('latestGeneration')->latest()]);

        return response()->json($campaign);
    }

    public function update(Request $request, Campaign $campaign)
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);

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
        abort_if($campaign->user_id !== $request->user()->id, 403);

        $campaign->delete();

        return response()->json(null, 204);
    }
}
