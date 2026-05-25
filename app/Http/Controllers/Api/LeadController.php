<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::where('user_id', $request->user()->id)
            ->with('campaign:id,name')
            ->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('nome', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")
                ->orWhere('telefone', 'like', "%$s%")
            );
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('fonte'))  $query->where('fonte',  $request->fonte);
        if ($request->filled('responsavel')) $query->where('responsavel', $request->responsavel);

        return response()->json($query->paginate(50));
    }

    public function pipeline(Request $request)
    {
        $leads = Lead::where('user_id', $request->user()->id)
            ->select('id','nome','email','telefone','status','fonte','responsavel','created_at')
            ->latest()
            ->get()
            ->groupBy('status');

        return response()->json($leads);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'        => 'required|string|max:255',
            'email'       => 'nullable|email|max:255',
            'telefone'    => 'nullable|string|max:30',
            'status'      => 'in:novo_lead,contato_feito,qualificacao,cotacao,negociacao,fechado',
            'fonte'       => 'in:site,social,email,indicacao,manual,api',
            'local'       => 'nullable|string|max:255',
            'responsavel' => 'nullable|string|max:255',
            'notas'       => 'nullable|string',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ]);

        $lead = Lead::create(['user_id' => $request->user()->id] + $data);

        return response()->json($lead, 201);
    }

    public function show(Request $request, Lead $lead)
    {
        abort_if($lead->user_id !== $request->user()->id, 403);
        return response()->json(
            $lead->load('campaign:id,name', 'tasks', 'activities.user:id,name')
        );
    }

    public function update(Request $request, Lead $lead)
    {
        abort_if($lead->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'nome'        => 'sometimes|string|max:255',
            'email'       => 'nullable|email|max:255',
            'telefone'    => 'nullable|string|max:30',
            'status'      => 'in:novo_lead,contato_feito,qualificacao,cotacao,negociacao,fechado',
            'fonte'       => 'in:site,social,email,indicacao,manual,api',
            'local'       => 'nullable|string|max:255',
            'responsavel' => 'nullable|string|max:255',
            'notas'       => 'nullable|string',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ]);

        $oldStatus = $lead->status;
        $lead->update($data);

        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            LeadActivity::create([
                'lead_id'   => $lead->id,
                'user_id'   => $request->user()->id,
                'type'      => 'status_change',
                'titulo'    => 'Mudança de Status',
                'descricao' => "Status alterado de {$oldStatus} para {$data['status']}",
                'dados'     => ['de' => $oldStatus, 'para' => $data['status']],
            ]);
        }

        return response()->json($lead->load('activities.user:id,name'));
    }

    public function destroy(Request $request, Lead $lead)
    {
        abort_if($lead->user_id !== $request->user()->id, 403);
        $lead->delete();
        return response()->json(null, 204);
    }
}
