<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(EnsurePlatformAdmin::class),
        ];
    }

    public function index()
    {
        return response()->json(
            User::where('is_platform_admin', true)->orderBy('name')->get(['id', 'name', 'email', 'is_client', 'created_at'])
        );
    }

    public function grant(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $client = User::where('email', $data['email'])
            ->where('is_client', true)
            ->first();

        abort_if(!$client, 404, 'Nenhum cliente encontrado com esse e-mail.');
        abort_if($client->is_platform_admin, 422, 'Esse usuário já tem acesso admin.');

        $client->forceFill(['is_platform_admin' => true])->save();

        AuditLogger::log('platform_admin', 'admin_user.granted', "Acesso admin concedido a \"{$client->name}\" (cliente existente)", [
            'causer_id' => $request->user()->id,
        ]);

        return response()->json($client->only(['id', 'name', 'email', 'is_client', 'created_at']), 201);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $admin = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $admin->forceFill(['is_platform_admin' => true, 'is_client' => false])->save();

        AuditLogger::log('platform_admin', 'admin_user.created', "Usuário admin \"{$admin->name}\" criado", [
            'causer_id' => $request->user()->id,
            'input' => ['name' => $data['name'], 'email' => $data['email']],
        ]);

        return response()->json($admin->only(['id', 'name', 'email', 'created_at']), 201);
    }

    public function destroy(Request $request, User $user)
    {
        abort_if(!$user->is_platform_admin, 404);
        abort_if($user->id === $request->user()->id, 422, 'Você não pode remover seu próprio acesso admin.');
        abort_if(User::where('is_platform_admin', true)->count() <= 1, 422, 'Não é possível remover o último admin da plataforma.');

        $name = $user->name;

        // A user who also owns a client company just loses admin access (they keep using the
        // system as a normal client). A platform-admin-only user has no client data to preserve,
        // so we delete them outright instead of leaving a disabled account nobody can log into.
        if ($user->is_client) {
            $user->forceFill(['is_platform_admin' => false])->save();
        } else {
            $user->delete();
        }

        AuditLogger::log('platform_admin', 'admin_user.access_revoked', "Acesso admin de \"{$name}\" removido", [
            'causer_id' => $request->user()->id,
            'input' => ['name' => $name],
        ]);

        return response()->json(null, 204);
    }
}
