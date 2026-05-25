<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::where('user_id', $request->user()->id)
            ->with('lead:id,nome')
            ->latest('prazo');

        if ($request->filled('tab')) {
            match ($request->tab) {
                'pendentes'  => $query->where('concluida', false)->where(fn($q) => $q->whereNull('prazo')->orWhere('prazo', '>=', now())),
                'concluidas' => $query->where('concluida', true),
                'atrasadas'  => $query->where('concluida', false)->where('prazo', '<', now()),
                default      => null,
            };
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('titulo', 'like', "%$s%");
        }

        if ($request->filled('responsavel')) {
            $query->where('responsavel', $request->responsavel);
        }

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'      => 'required|string|max:255',
            'descricao'   => 'nullable|string',
            'lead_id'     => 'nullable|exists:leads,id',
            'responsavel' => 'nullable|string|max:255',
            'prazo'       => 'nullable|date',
        ]);

        $task = Task::create(['user_id' => $request->user()->id] + $data);

        return response()->json($task->load('lead:id,nome'), 201);
    }

    public function update(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'titulo'      => 'sometimes|string|max:255',
            'descricao'   => 'nullable|string',
            'lead_id'     => 'nullable|exists:leads,id',
            'responsavel' => 'nullable|string|max:255',
            'prazo'       => 'nullable|date',
            'concluida'   => 'boolean',
        ]);

        $task->update($data);

        return response()->json($task->load('lead:id,nome'));
    }

    public function destroy(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403);
        $task->delete();
        return response()->json(null, 204);
    }

    public function toggle(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403);
        $task->update(['concluida' => !$task->concluida]);
        return response()->json($task);
    }
}
