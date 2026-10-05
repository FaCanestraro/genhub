<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Publication;
use App\Models\SocialAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use RuntimeException;

// Publicação orgânica em Páginas do Facebook e contas profissionais do Instagram via Graph API.
// O login é feito com "Facebook Login for Business": o token da Página (que não expira quando
// vem de um token de usuário de longa duração) serve tanto para a Página quanto para o Instagram vinculado.
class MetaService
{
    public const SCOPES = [
        'pages_show_list', 'pages_read_engagement', 'pages_manage_posts',
        'business_management', 'instagram_basic', 'instagram_content_publish',
    ];

    private array $tempFiles = [];

    public function authUrl(string $state): string
    {
        return "https://www.facebook.com/{$this->version()}/dialog/oauth?" . http_build_query([
            'client_id' => config('services.meta.app_id'),
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'scope' => implode(',', self::SCOPES),
            'response_type' => 'code',
        ]);
    }

    public function redirectUri(): string
    {
        return url('/settings');
    }

    /** Troca o code do OAuth pelas Páginas (e Instagrams vinculados) que o usuário autorizou. */
    public function connect(string $code): array
    {
        $app = ['client_id' => config('services.meta.app_id'), 'client_secret' => config('services.meta.app_secret')];

        $short = $this->graph('get', 'oauth/access_token', [...$app, 'redirect_uri' => $this->redirectUri(), 'code' => $code])['access_token'];
        $long = $this->graph('get', 'oauth/access_token', [...$app, 'grant_type' => 'fb_exchange_token', 'fb_exchange_token' => $short])['access_token'];

        $pages = $this->graph('get', 'me/accounts', [
            'access_token' => $long,
            'fields' => 'id,name,access_token,picture{url},instagram_business_account{id,username,name,profile_picture_url}',
            'limit' => 100,
        ])['data'] ?? [];

        $accounts = [];
        foreach ($pages as $page) {
            $accounts[] = [
                'provider' => 'facebook',
                'external_id' => $page['id'],
                'name' => $page['name'],
                'username' => null,
                'avatar_url' => $page['picture']['data']['url'] ?? null,
                'access_token' => $page['access_token'],
            ];

            if ($ig = $page['instagram_business_account'] ?? null) {
                $accounts[] = [
                    'provider' => 'instagram',
                    'external_id' => $ig['id'],
                    'name' => $ig['name'] ?? $ig['username'],
                    'username' => $ig['username'] ?? null,
                    'avatar_url' => $ig['profile_picture_url'] ?? null,
                    'access_token' => $page['access_token'],
                ];
            }
        }

        return $accounts;
    }

    /** @return array{id: string, permalink: ?string} */
    public function publish(Publication $publication): array
    {
        $account = $publication->socialAccount;
        $ids = $publication->asset_ids;
        $assets = Asset::where('company_id', $publication->company_id)
            ->whereIn('id', $ids)
            ->whereIn('type', ['image', 'video'])
            ->get()
            ->sortBy(fn (Asset $a) => array_search($a->id, $ids))
            ->values();

        throw_if($assets->isEmpty(), RuntimeException::class, 'Nenhuma imagem ou vídeo para publicar.');

        try {
            return $account->provider === 'instagram'
                ? $this->publishInstagram($account, $assets, $publication->caption, (bool) $publication->as_story)
                : $this->publishFacebook($account, $assets, $publication->caption);
        } finally {
            foreach ($this->tempFiles as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }
            $this->tempFiles = [];
        }
    }

    private function publishFacebook(SocialAccount $account, Collection $assets, ?string $caption): array
    {
        $page = $account->external_id;
        $token = $account->access_token;

        if ($video = $assets->firstWhere('type', 'video')) {
            $id = $this->graph('post', "{$page}/videos", ['file_url' => $video->url, 'description' => $caption, 'access_token' => $token])['id'];

            return ['id' => $id, 'permalink' => "https://www.facebook.com/{$page}/videos/{$id}"];
        }

        if ($assets->count() === 1) {
            $res = $this->graph('post', "{$page}/photos", ['url' => $assets->first()->url, 'message' => $caption, 'access_token' => $token]);
            $postId = $res['post_id'] ?? $res['id'];
        } else {
            // Várias fotos num post só: sobe cada uma sem publicar e anexa todas ao post do feed.
            $media = $assets->map(fn (Asset $a) => [
                'media_fbid' => $this->graph('post', "{$page}/photos", ['url' => $a->url, 'published' => 'false', 'access_token' => $token])['id'],
            ]);
            $postId = $this->graph('post', "{$page}/feed", [
                'message' => $caption,
                'attached_media' => json_encode($media->values()),
                'access_token' => $token,
            ])['id'];
        }

        return ['id' => $postId, 'permalink' => "https://www.facebook.com/{$postId}"];
    }

    private function publishInstagram(SocialAccount $account, Collection $assets, ?string $caption, bool $asStory): array
    {
        $ig = $account->external_id;
        $token = $account->access_token;

        if ($asStory) {
            // Cada mídia vira um story separado; o Instagram não aceita legenda em story.
            foreach ($assets as $asset) {
                $result = $this->createAndPublish($ig, $token, [...$this->media($asset), 'media_type' => 'STORIES']);
            }

            return $result;
        }

        if ($assets->count() === 1) {
            $asset = $assets->first();
            $params = $asset->type === 'video' ? ['media_type' => 'REELS', 'video_url' => $asset->url] : $this->media($asset);

            return $this->createAndPublish($ig, $token, [...$params, 'caption' => $caption]);
        }

        $children = $assets->take(10)->map(fn (Asset $a) => $this->container($ig, $token, [
            ...$this->media($a),
            ...($a->type === 'video' ? ['media_type' => 'VIDEO'] : []),
            'is_carousel_item' => 'true',
        ]));

        return $this->createAndPublish($ig, $token, ['media_type' => 'CAROUSEL', 'children' => $children->implode(','), 'caption' => $caption]);
    }

    private function media(Asset $asset): array
    {
        return $asset->type === 'video' ? ['video_url' => $asset->url] : ['image_url' => $this->jpegUrl($asset)];
    }

    private function createAndPublish(string $ig, string $token, array $params): array
    {
        $creationId = $this->container($ig, $token, $params);
        $mediaId = $this->graph('post', "{$ig}/media_publish", ['creation_id' => $creationId, 'access_token' => $token])['id'];
        $permalink = $this->graph('get', $mediaId, ['fields' => 'permalink', 'access_token' => $token])['permalink'] ?? null;

        return ['id' => $mediaId, 'permalink' => $permalink];
    }

    private function container(string $ig, string $token, array $params): string
    {
        $id = $this->graph('post', "{$ig}/media", [...$params, 'access_token' => $token])['id'];

        // ponytail: polling síncrono dentro do job (5s x 60 = 5min); vídeos maiores que isso precisariam de job re-enfileirado.
        for ($i = 0; $i < 60; $i++) {
            $status = $this->graph('get', $id, ['fields' => 'status_code,status', 'access_token' => $token]);
            $code = $status['status_code'] ?? null;

            if ($code === 'FINISHED') return $id;
            if (in_array($code, ['ERROR', 'EXPIRED'], true)) {
                throw new RuntimeException('O Instagram recusou a mídia: ' . ($status['status'] ?? $code));
            }

            Sleep::for(5)->seconds();
        }

        throw new RuntimeException('Tempo esgotado aguardando o Instagram processar a mídia.');
    }

    // O Instagram só aceita JPEG; as gerações costumam sair em PNG. Converte para um arquivo
    // temporário público (fundo branco no lugar da transparência) que é apagado após publicar.
    private function jpegUrl(Asset $asset): string
    {
        if (in_array($asset->mime_type, ['image/jpeg', 'image/jpg'], true)) {
            return $asset->url;
        }

        $src = @imagecreatefromstring(Storage::disk($asset->disk)->get($asset->path));
        throw_if(!$src, RuntimeException::class, 'Não foi possível converter a imagem para JPEG.');

        $jpg = imagecreatetruecolor(imagesx($src), imagesy($src));
        imagefill($jpg, 0, 0, imagecolorallocate($jpg, 255, 255, 255));
        imagecopy($jpg, $src, 0, 0, 0, 0, imagesx($src), imagesy($src));

        ob_start();
        imagejpeg($jpg, null, 92);
        $bytes = ob_get_clean();

        $path = 'social-tmp/' . Str::uuid() . '.jpg';
        Storage::disk($asset->disk)->put($path, $bytes);
        $this->tempFiles[] = [$asset->disk, $path];

        return Storage::disk($asset->disk)->url($path);
    }

    private function graph(string $method, string $path, array $params = []): array
    {
        $response = Http::timeout(120)->asForm()
            ->{$method}("https://graph.facebook.com/{$this->version()}/" . ltrim($path, '/'), $params);

        if ($response->failed()) {
            throw new RuntimeException(
                $response->json('error.error_user_msg') ?? $response->json('error.message') ?? 'Erro ao comunicar com a Meta.'
            );
        }

        return $response->json() ?? [];
    }

    private function version(): string
    {
        return config('services.meta.graph_version');
    }
}
