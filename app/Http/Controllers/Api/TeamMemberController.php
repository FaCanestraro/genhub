<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        abort_if(!$request->companyMembership()->is_owner, 403);

        $members = CompanyUser::where('company_id', $request->company()->id)
            ->where('is_owner', false)
            ->with(['user:id,name,email,phone', 'role:id,name'])
            ->get()
            ->map(fn (CompanyUser $m) => [
                'id' => $m->user->id,
                'name' => $m->user->name,
                'email' => $m->user->email,
                'phone' => $m->user->phone,
                'role_id' => $m->role_id,
                'role' => $m->role,
            ])
            ->sortBy('name')
            ->values();

        return response()->json($members);
    }

    /**
     * Invites someone to this company. If the e-mail already belongs to an existing user
     * (e.g. someone who owns/works at another company), they're just linked to this company
     * with the chosen role — a person's account is shared across every company they belong to,
     * so we never create a second, disconnected User row for the same e-mail.
     */
    public function store(Request $request)
    {
        abort_if(!$request->companyMembership()->is_owner, 403);

        $company = $request->company();
        $existingUser = User::where('email', $request->input('email'))->first();

        $data = $request->validate([
            'name'     => $existingUser ? 'nullable|string|max:255' : 'required|string|max:255',
            'email'    => 'required|email',
            'password' => $existingUser ? 'nullable' : 'required|string|min:8|confirmed',
            'role_id'  => ['required', Rule::exists('roles', 'id')->where('company_id', $company->id)],
        ], [
            'name.required'     => 'Informe o nome do membro.',
            'email.required'    => 'Informe o e-mail do membro.',
            'email.email'       => 'Informe um e-mail válido.',
            'password.required' => 'Informe uma senha para criar o novo usuário.',
            'password.min'      => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed'=> 'A confirmação de senha não confere.',
            'role_id.required'  => 'Selecione um perfil de acesso.',
            'role_id.exists'    => 'Perfil de acesso inválido.',
        ]);

        $member = $existingUser ?: User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        abort_if(
            CompanyUser::where('company_id', $company->id)->where('user_id', $member->id)->exists(),
            422,
            'Esse usuário já faz parte da equipe desta empresa.'
        );

        if (!$member->is_client) {
            $member->update(['is_client' => true]);
        }

        $membership = CompanyUser::create([
            'company_id' => $company->id,
            'user_id' => $member->id,
            'role_id' => $data['role_id'],
            'is_owner' => false,
        ]);

        AuditLogger::log('settings', 'team_member.created', "Membro da equipe \"{$member->name}\" adicionado", [
            'subject' => $member,
            'input' => ['name' => $member->name, 'email' => $member->email, 'role_id' => $data['role_id']],
        ]);

        return response()->json([
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'phone' => $member->phone,
            'role_id' => $membership->role_id,
            'role' => $membership->load('role')->role,
        ], 201);
    }

    /**
     * Only the role within this company can be changed here — name/e-mail/password belong to
     * the person's own account, which may be shared with other companies, so those are only
     * editable by the person themself via their profile.
     */
    public function update(Request $request, User $team_member)
    {
        abort_if(!$request->companyMembership()->is_owner, 403);

        $company = $request->company();
        $membership = CompanyUser::where('company_id', $company->id)->where('user_id', $team_member->id)->first();
        abort_if(!$membership, 404);

        $data = $request->validate([
            'role_id' => ['required', Rule::exists('roles', 'id')->where('company_id', $company->id)],
        ], [
            'role_id.required' => 'Selecione um perfil de acesso.',
            'role_id.exists'   => 'Perfil de acesso inválido.',
        ]);

        $membership->update(['role_id' => $data['role_id']]);

        AuditLogger::log('settings', 'team_member.updated', "Perfil de \"{$team_member->name}\" atualizado", [
            'subject' => $team_member,
            'input' => ['role_id' => $data['role_id']],
        ]);

        return response()->json([
            'id' => $team_member->id,
            'name' => $team_member->name,
            'email' => $team_member->email,
            'phone' => $team_member->phone,
            'role_id' => $membership->role_id,
            'role' => $membership->load('role')->role,
        ]);
    }

    public function destroy(Request $request, User $team_member)
    {
        abort_if(!$request->companyMembership()->is_owner, 403);

        $company = $request->company();
        $membership = CompanyUser::where('company_id', $company->id)->where('user_id', $team_member->id)->first();
        abort_if(!$membership, 404);

        $memberName = $team_member->name;
        $membership->delete();

        AuditLogger::log('settings', 'team_member.deleted', "Membro da equipe \"{$memberName}\" removido");

        return response()->json(null, 204);
    }
}
