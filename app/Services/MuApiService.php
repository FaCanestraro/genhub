<?php

namespace App\Services;

use App\Models\Action;
use App\Models\AiCredential;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MuApiService
{
    private string $imageModel = 'flux-schnell';
    private string $apiBase = 'https://api.muapi.ai/v1';

    // Domínio confirmado via catálogo público (GET /api/v1/models, sem auth). Cada variação
    // text-to-video / image-to-video é um endpoint de modelo diferente, não um parâmetro.
    private string $videoModelT2V = 'vidu-q2-turbo-text-to-video';
    private string $videoModelI2V = 'vidu-q2-turbo-image-to-video';
    private string $videoApiBase = 'https://api.muapi.ai/api/v1';

    public function generateImage(Action $action, Collection $products, ?string $extraPrompt): array
    {
        $productContext = $products->map(fn ($p) => "- {$p->name}: {$p->description} (R$ {$p->price})")->join("\n")
            ?: 'Nenhum produto específico selecionado.';

        $prompt = trim(<<<PROMPT
        Professional commercial advertising photograph for {$action->platform}.
        Product: {$productContext}
        Brief: {$action->brief}
        {$extraPrompt}
        Reproduce the product packaging/label text exactly as-is — never alter, invent, or redesign it.
        PROMPT);

        $response = Http::withToken($this->resolveApiKey())
            ->timeout(60)
            ->post("{$this->apiBase}/images/generations", [
                'model' => $this->imageModel,
                'prompt' => $prompt,
                'n' => min($action->quantity ?? 1, 4),
                'size' => $this->resolveSize($action->resolution ?? '1080x1080'),
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Erro na API MuAPI: ' . $response->body());
        }

        $assets = [];
        foreach ($response->json('data') ?? [] as $image) {
            $url = $image['url'] ?? null;
            if (!$url) continue;

            $bytes = Http::timeout(60)->get($url)->body();
            $filename = 'generations/' . uniqid() . '.png';
            Storage::disk('r2')->put($filename, $bytes);

            $assets[] = [
                'type' => 'image',
                'disk' => 'r2',
                'path' => $filename,
                'mime_type' => 'image/png',
                'size' => Storage::disk('r2')->size($filename),
            ];
        }

        if (empty($assets)) {
            throw new \RuntimeException('A imagem não pôde ser gerada pela MuAPI. Tente reformular o prompt.');
        }

        return [
            'model' => $this->imageModel,
            'assets' => $assets,
        ];
    }

    public function generateVideo(Action $action, Collection $products, ?string $extraPrompt): array
    {
        $productContext = $products->map(fn ($p) => "- {$p->name}: {$p->description} (R$ {$p->price})")->join("\n")
            ?: 'Nenhum produto específico selecionado.';
        $imageUrl = $products->first(fn ($p) => !empty($p->images))?->images[0] ?? null;
        $duration = 8;

        $prompt = trim(<<<PROMPT
        Professional commercial advertising video for {$action->platform}.
        Product: {$productContext}
        Brief: {$action->brief}
        {$extraPrompt}
        Reproduce the product packaging/label text exactly as-is — never alter, invent, or redesign it.
        PROMPT);

        $key = $this->resolveApiKey();
        $model = $imageUrl ? $this->videoModelI2V : $this->videoModelT2V;

        $body = ['prompt' => $prompt, 'duration' => $duration];
        if ($imageUrl) {
            $body['image_url'] = $imageUrl;
        }

        $startResponse = Http::withHeaders(['x-api-key' => $key])
            ->timeout(30)
            ->post("{$this->videoApiBase}/{$model}", $body);

        if (!$startResponse->successful()) {
            throw new \RuntimeException('Erro ao iniciar geração de vídeo na MuAPI: ' . $startResponse->body());
        }

        $requestId = $startResponse->json('request_id');
        if (!$requestId) {
            throw new \RuntimeException('Resposta inválida da MuAPI: sem request_id.');
        }

        $result = $this->pollVideoJob($requestId, $key);

        $assets = [];
        foreach ($result['outputs'] ?? [] as $url) {
            $bytes = Http::timeout(120)->get($url)->body();
            if ($bytes === '') continue;

            $filename = 'generations/videos/' . uniqid() . '.mp4';
            Storage::disk('r2')->put($filename, $bytes);

            $assets[] = [
                'type' => 'video',
                'disk' => 'r2',
                'path' => $filename,
                'mime_type' => 'video/mp4',
                'size' => Storage::disk('r2')->size($filename),
                'duration' => $duration,
            ];
        }

        if (empty($assets)) {
            throw new \RuntimeException('O vídeo não pôde ser gerado pela MuAPI. Tente reformular o prompt.');
        }

        return [
            'model' => $model,
            'assets' => $assets,
        ];
    }

    private function pollVideoJob(string $requestId, string $key, int $maxWaitSeconds = 180): array
    {
        $deadline = time() + $maxWaitSeconds;

        while (time() < $deadline) {
            sleep(5);

            $response = Http::withHeaders(['x-api-key' => $key])
                ->timeout(15)
                ->get("{$this->videoApiBase}/predictions/{$requestId}/result");

            if (!$response->successful()) {
                throw new \RuntimeException('Erro ao verificar status do vídeo na MuAPI: ' . $response->body());
            }

            $data = $response->json();

            if (($data['status'] ?? null) === 'completed') {
                return $data;
            }
            if (($data['status'] ?? null) === 'failed') {
                throw new \RuntimeException('A geração de vídeo falhou na MuAPI.');
            }
        }

        throw new \RuntimeException('Tempo esgotado aguardando o vídeo da MuAPI.');
    }

    private function resolveSize(string $resolution): string
    {
        if (!preg_match('/^(\d+)x(\d+)$/', $resolution, $m)) {
            return '1024x1024';
        }

        [, $w, $h] = $m;

        return match (true) {
            $w > $h => '1792x1024',
            $h > $w => '1024x1792',
            default => '1024x1024',
        };
    }

    private function resolveApiKey(): string
    {
        $company = request()?->company();

        $key = $company
            ? AiCredential::where('company_id', $company->id)
                ->where('provider', 'muapi')
                ->where('is_active', true)
                ->first()?->api_key
            : null;

        abort_if(!$key, 422, 'Cadastre uma chave da MuAPI em Configurações > Inteligência Artificial.');

        return $key;
    }
}
