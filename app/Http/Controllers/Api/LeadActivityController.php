<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LeadActivityController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermission::class.':leads,view',   only: ['index']),
            new Middleware(CheckPermission::class.':leads,create', only: ['store']),
            new Middleware(CheckPermission::class.':leads,delete', only: ['destroy']),
        ];
    }

    public function index(Request $request, Lead $lead)
    {
        abort_if($lead->user_id !== $request->user()->accountId(), 403);

        return response()->json(
            $lead->activities()->with('user:id,name')->latest()->get()
        );
    }

    public function store(Request $request, Lead $lead)
    {
        abort_if($lead->user_id !== $request->user()->accountId(), 403);

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
        abort_if($lead->user_id !== $request->user()->accountId(), 403);
        $activity->delete();
        return response()->json(null, 204);
    }
}
