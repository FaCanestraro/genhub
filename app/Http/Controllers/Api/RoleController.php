<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        abort_if(!$request->user()->isOwner(), 403);

        Role::firstOrCreate(
            ['user_id' => $request->user()->accountId(), 'is_default' => true],
            ['name' => 'Administrador', 'description' => 'Acesso completo a todas as áreas do sistema.', 'permissions' => $this->fullPermissions()]
        );

        $roles = Role::where('user_id', $request->user()->accountId())
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        abort_if(!$request->user()->isOwner(), 403);

        $data = $request->validate([
            'name'                     => 'required|string|max:255',
            'description'              => 'nullable|string|max:255',
            'permissions'              => 'nullable|array',
            'permissions.*.view'       => 'boolean',
            'permissions.*.create'     => 'boolean',
            'permissions.*.edit'       => 'boolean',
            'permissions.*.delete'     => 'boolean',
        ]);

        $role = Role::create([
            'user_id'     => $request->user()->accountId(),
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'permissions' => $this->mergePermissions($data['permissions'] ?? []),
        ]);

        AuditLogger::log('settings', 'role.created', "Perfil de acesso \"{$role->name}\" criado", [
            'subject' => $role,
            'input' => ['name' => $data['name'], 'description' => $data['description'] ?? null],
        ]);

        return response()->json($role, 201);
    }

    public function update(Request $request, Role $role)
    {
        abort_if(!$request->user()->isOwner(), 403);
        abort_if($role->user_id !== $request->user()->accountId(), 403);
        abort_if($role->is_default, 422, 'Não é possível alterar o perfil padrão.');

        $data = $request->validate([
            'name'                     => 'required|string|max:255',
            'description'              => 'nullable|string|max:255',
            'permissions'              => 'nullable|array',
            'permissions.*.view'       => 'boolean',
            'permissions.*.create'     => 'boolean',
            'permissions.*.edit'       => 'boolean',
            'permissions.*.delete'     => 'boolean',
        ]);

        $role->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'permissions' => $this->mergePermissions($data['permissions'] ?? []),
        ]);

        AuditLogger::log('settings', 'role.updated', "Perfil de acesso \"{$role->name}\" atualizado", [
            'subject' => $role,
            'input' => ['name' => $data['name'], 'description' => $data['description'] ?? null],
        ]);

        return response()->json($role);
    }

    public function destroy(Request $request, Role $role)
    {
        abort_if(!$request->user()->isOwner(), 403);
        abort_if($role->user_id !== $request->user()->accountId(), 403);
        abort_if($role->is_default, 422, 'Não é possível excluir o perfil padrão.');
        abort_if(User::where('role_id', $role->id)->exists(), 422, 'Não é possível excluir um perfil atribuído a membros da equipe. Reatribua-os primeiro.');

        $roleName = $role->name;
        $role->delete();

        AuditLogger::log('settings', 'role.deleted', "Perfil de acesso \"{$roleName}\" excluído");

        return response()->json(['message' => 'Perfil excluído com sucesso.']);
    }

    private function mergePermissions(array $permissions): array
    {
        $merged = $this->defaultPermissions();

        foreach ($merged as $slug => $actions) {
            if (isset($permissions[$slug])) {
                $merged[$slug] = [
                    'view'   => (bool) ($permissions[$slug]['view'] ?? false),
                    'create' => (bool) ($permissions[$slug]['create'] ?? false),
                    'edit'   => (bool) ($permissions[$slug]['edit'] ?? false),
                    'delete' => (bool) ($permissions[$slug]['delete'] ?? false),
                ];
            }
        }

        return $merged;
    }

    private function defaultPermissions(): array
    {
        return collect(config('menus'))
            ->mapWithKeys(fn ($label, $slug) => [$slug => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false]])
            ->toArray();
    }

    private function fullPermissions(): array
    {
        return collect(config('menus'))
            ->mapWithKeys(fn ($label, $slug) => [$slug => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true]])
            ->toArray();
    }
}
