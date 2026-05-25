<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    public function index(Request $request, Lead $lead)
    {
        abort_if($lead->user_id !== $request->user()->id, 403);

        return response()->json(
            $lead->activities()->with('user:id,name')->latest()->get()
        );
    }

    public function store(Request $request, Lead $lead)
    {
        abort_if($lead->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'titulo'    => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'type'      => 'in:nota,tarefa,arquivo,status_change',
        ]);

        $activity = LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'type'    => $data['type'] ?? 'nota',
            'titulo'  => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
        ]);

        return response()->json($activity->load('user:id,name'), 201);
    }

    public function destroy(Request $request, Lead $lead, LeadActivity $activity)
    {
        abort_if($lead->user_id !== $request->user()->id, 403);
        $activity->delete();
        return response()->json(null, 204);
    }
}
