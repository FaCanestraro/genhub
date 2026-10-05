<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Models\SocialAccount;
use App\Services\AuditLogger;
use App\Services\MetaService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Crypt;

class SocialAccountController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermission::class.':social,view',   only: ['index']),
            new Middleware(CheckPermission::class.':social,create', only: ['connectUrl', 'callback']),
            new Middleware(CheckPermission::class.':social,delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        return response()->json(
            SocialAccount::where('company_id', $request->company()->id)->orderBy('provider')->orderBy('name')->get()
        );
    }

    public function connectUrl(Request $request, MetaService $meta)
    {
        abort_if(!config('services.meta.app_id'), 422, 'Integração com a Meta não configurada (META_APP_ID).');

        // O state amarra o retorno do OAuth ao mesmo usuário + empresa que iniciou a conexão,
        // impedindo que alguém conecte as Páginas de outra pessoa na própria empresa (CSRF do OAuth).
        $state = Crypt::encryptString(json_encode([
            'company_id' => $request->company()->id,
            'user_id' => $request->user()->id,
            'exp' => now()->addMinutes(15)->timestamp,
        ]));

        return response()->json(['url' => $meta->authUrl($state)]);
    }

    public function callback(Request $request, MetaService $meta)
    {
        $data = $request->validate(['code' => 'required|string', 'state' => 'required|string']);

        try {
            $state = json_decode(Crypt::decryptString($data['state']), true);
        } catch (DecryptException) {
            $state = null;
        }

        abort_if(
            !$state
            || $state['exp'] < now()->timestamp
            || $state['company_id'] !== $request->company()->id
            || $state['user_id'] !== $request->user()->id,
            403,
            'Link de conexão inválido ou expirado. Tente conectar novamente.'
        );

        try {
            $accounts = $meta->connect($data['code']);
        } catch (\RuntimeException $e) {
            abort(422, 'A Meta recusou a conexão: ' . $e->getMessage());
        }

        abort_if(!$accounts, 422, 'Nenhuma Página do Facebook foi autorizada. Selecione ao menos uma Página ao conectar.');

        foreach ($accounts as $account) {
            SocialAccount::updateOrCreate(
                ['company_id' => $request->company()->id, 'provider' => $account['provider'], 'external_id' => $account['external_id']],
                $account
            );
        }

        AuditLogger::log('social', 'social_account.connected', 'Contas da Meta conectadas', [
            'output' => ['accounts' => collect($accounts)->map(fn ($a) => "{$a['provider']}:{$a['name']}")->all()],
        ]);

        return response()->json(['connected' => count($accounts)]);
    }

    public function destroy(Request $request, SocialAccount $socialAccount)
    {
        abort_if($socialAccount->company_id !== $request->company()->id, 403);

        $label = ucfirst($socialAccount->provider) . " \"{$socialAccount->name}\"";
        $socialAccount->delete();

        AuditLogger::log('social', 'social_account.deleted', "Conta {$label} desconectada");

        return response()->json(null, 204);
    }
}
