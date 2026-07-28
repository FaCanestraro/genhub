<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        abort_if(!$request->user()->isOwner(), 403);

        $members = User::where('owner_id', $request->user()->accountId())
            ->with('role:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'role_id']);

        return response()->json($members);
    }

    public function store(Request $request)
    {
        abort_if(!$request->user()->isOwner(), 403);

        $accountId = $request->user()->accountId();

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_id'  => ['required', Rule::exists('roles', 'id')->where('user_id', $accountId)],
        ], [
            'name.required'     => 'Informe o nome do membro.',
            'email.required'    => 'Informe o e-mail do membro.',
            'email.email'       => 'Informe um e-mail válido.',
            'email.unique'      => 'Este e-mail já está sendo usado por outro usuário.',
            'password.required' => 'Informe uma senha.',
            'password.min'      => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed'=> 'A confirmação de senha não confere.',
            'role_id.required'  => 'Selecione um perfil de acesso.',
            'role_id.exists'    => 'Perfil de acesso inválido.',
        ]);

        $member = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'owner_id' => $accountId,
            'role_id'  => $data['role_id'],
        ]);

        return response()->json($member->load('role:id,name'), 201);
    }

    public function update(Request $request, User $team_member)
    {
        abort_if(!$request->user()->isOwner(), 403);
        abort_if($team_member->owner_id !== $request->user()->accountId(), 403);

        $accountId = $request->user()->accountId();

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($team_member->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role_id'  => ['required', Rule::exists('roles', 'id')->where('user_id', $accountId)],
        ], [
            'name.required'     => 'Informe o nome do membro.',
            'email.required'    => 'Informe o e-mail do membro.',
            'email.email'       => 'Informe um e-mail válido.',
            'email.unique'      => 'Este e-mail já está sendo usado por outro usuário.',
            'password.min'      => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed'=> 'A confirmação de senha não confere.',
            'role_id.required'  => 'Selecione um perfil de acesso.',
            'role_id.exists'    => 'Perfil de acesso inválido.',
        ]);

        $team_member->update([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'role_id' => $data['role_id'],
            ...(!empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
        ]);

        return response()->json($team_member->load('role:id,name'));
    }

    public function destroy(Request $request, User $team_member)
    {
        abort_if(!$request->user()->isOwner(), 403);
        abort_if($team_member->owner_id !== $request->user()->accountId(), 403);

        $team_member->delete();

        return response()->json(null, 204);
    }
}
