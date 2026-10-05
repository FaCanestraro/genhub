<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Jobs\PublishToSocial;
use App\Models\Generation;
use App\Models\Publication;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PublicationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermission::class.':social,view',   only: ['show']),
            new Middleware(CheckPermission::class.':social,create', only: ['store']),
        ];
    }

    public function store(Request $request)
    {
        $companyId = $request->company()->id;

        $data = $request->validate([
            'generation_id' => 'required|integer',
            'social_account_ids' => 'required|array|min:1',
            'social_account_ids.*' => 'integer',
            'asset_ids' => 'sometimes|array|min:1',
            'asset_ids.*' => 'integer',
            'caption' => 'nullable|string|max:2200',
            'as_story' => 'sometimes|boolean',
        ]);

        $generation = Generation::where('company_id', $companyId)->with('assets')->findOrFail($data['generation_id']);
        $media = $generation->assets->whereIn('type', ['image', 'video']);
        abort_if($media->isEmpty(), 422, 'Esta geração não tem imagem ou vídeo para publicar.');

        $assetIds = isset($data['asset_ids'])
            ? $media->whereIn('id', $data['asset_ids'])->pluck('id')->values()->all()
            : $media->pluck('id')->values()->all();
        abort_if(!$assetIds, 422, 'Selecione ao menos uma mídia desta geração.');

        $accounts = SocialAccount::where('company_id', $companyId)->whereIn('id', $data['social_account_ids'])->get();
        abort_if($accounts->isEmpty(), 422, 'Selecione ao menos uma conta conectada.');

        $publications = $accounts->map(function (SocialAccount $account) use ($data, $generation, $assetIds, $companyId, $request) {
            $publication = Publication::create([
                'company_id' => $companyId,
                'social_account_id' => $account->id,
                'generation_id' => $generation->id,
                'user_id' => $request->user()->id,
                'asset_ids' => $assetIds,
                'caption' => $data['caption'] ?? null,
                'as_story' => $account->provider === 'instagram' && ($data['as_story'] ?? false),
            ]);

            PublishToSocial::dispatch($publication->id);

            return $publication->load('socialAccount');
        });

        return response()->json($publications->values(), 201);
    }

    public function show(Request $request, Publication $publication)
    {
        abort_if($publication->company_id !== $request->company()->id, 403);

        return response()->json($publication->load('socialAccount'));
    }
}
