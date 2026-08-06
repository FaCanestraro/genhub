<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Models\AiCredential;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class AiCredentialController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermission::class.':ai_providers,view',   only: ['index']),
            new Middleware(CheckPermission::class.':ai_providers,create', only: ['store']),
            new Middleware(CheckPermission::class.':ai_providers,edit',   only: ['update']),
            new Middleware(CheckPermission::class.':ai_providers,delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $credentials = AiCredential::where('user_id', $request->user()->accountId())
            ->get()
            ->keyBy('provider');

        $catalog = collect(config('ai_providers'))->map(function ($provider, $slug) use ($credentials) {
            $credential = $credentials->get($slug);

            return [
                'provider' => $slug,
                'label' => $provider['label'],
                'capabilities' => $provider['capabilities'],
                'available' => $provider['available'],
                'id' => $credential?->id,
                'is_active' => $credential?->is_active ?? false,
                'has_key' => (bool) $credential,
                'updated_at' => $credential?->updated_at,
            ];
        })->values();

        return response()->json($catalog);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(array_keys(config('ai_providers')))],
            'api_key' => 'required|string|max:1000',
        ]);

        $provider = config("ai_providers.{$data['provider']}");
        abort_if(!$provider['available'], 422, 'Este provedor ainda não está disponível.');

        $accountId = $request->user()->accountId();

        $credential = AiCredential::updateOrCreate(
            ['user_id' => $accountId, 'provider' => $data['provider']],
            [
                'label' => $provider['label'],
                'api_key' => $data['api_key'],
                'capabilities' => $provider['capabilities'],
                'is_active' => true,
            ]
        );

        AuditLogger::log('ai_providers', 'ai_credential.saved', "Chave de IA \"{$provider['label']}\" cadastrada", [
            'subject' => $credential,
            'input' => ['provider' => $data['provider']],
        ]);

        return response()->json(['provider' => $credential->provider, 'is_active' => $credential->is_active], 201);
    }

    public function update(Request $request, AiCredential $ai_provider)
    {
        abort_if($ai_provider->user_id !== $request->user()->accountId(), 403);

        $data = $request->validate([
            'is_active' => 'sometimes|boolean',
            'api_key' => 'sometimes|string|max:1000',
        ]);

        $ai_provider->update($data);

        AuditLogger::log('ai_providers', 'ai_credential.updated', "Chave de IA \"{$ai_provider->label}\" atualizada", [
            'subject' => $ai_provider,
            'input' => ['fields' => array_keys($data)],
        ]);

        return response()->json(['provider' => $ai_provider->provider, 'is_active' => $ai_provider->is_active]);
    }

    public function destroy(Request $request, AiCredential $ai_provider)
    {
        abort_if($ai_provider->user_id !== $request->user()->accountId(), 403);

        $label = $ai_provider->label;
        $provider = $ai_provider->provider;
        $ai_provider->delete();

        AuditLogger::log('ai_providers', 'ai_credential.deleted', "Chave de IA \"{$label}\" removida", [
            'input' => ['provider' => $provider],
        ]);

        return response()->json(null, 204);
    }
}
