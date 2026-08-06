<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load('role'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            if ($user) {
                AuditLogger::log('auth', 'auth.login_failed', "Tentativa de login com senha incorreta para \"{$user->email}\"", [
                    'account_id' => $user->accountId(),
                    'causer_id' => $user->id,
                    'status' => 'failed',
                ]);
            }

            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        AuditLogger::log('auth', 'auth.login', "Login realizado por \"{$user->email}\"", [
            'account_id' => $user->accountId(),
            'causer_id' => $user->id,
        ]);

        return response()->json([
            'user' => $user->load('role'),
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('role'));
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $request->user()->update($data);

        return response()->json($request->user()->fresh());
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return response()->json(['message' => 'Senha atual incorreta.'], 422);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Senha alterada com sucesso.']);
    }

    public function logout(Request $request)
    {
        AuditLogger::log('auth', 'auth.logout', "Logout realizado por \"{$request->user()->email}\"");

        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }
}
