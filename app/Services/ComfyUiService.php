<?php

namespace App\Services;

use App\Contracts\VideoClipRenderer;
use App\Models\Action;
use App\Models\AiCredential;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComfyUiService implements VideoClipRenderer
{
    public function generateImage(Action $action, Collection $products, ?string $extraPrompt, int $companyId): array
    {
        $baseUrl = $this->resolveBaseUrl($companyId);
        $prompt = $this->buildPrompt($action, $products, $extraPrompt);
        [$width, $height] = $this->parseResolution($action->resolution ?? '1024x1024');

        $workflow = $this->loadWorkflow('flux_schnell.json');
        $workflow['6']['inputs']['text'] = $prompt;
        $workflow['27']['inputs']['width'] = $width;
        $workflow['27']['inputs']['height'] = $height;
        $workflow['27']['inputs']['batch_size'] = min($action->quantity ?? 1, 4);
        $workflow['31']['inputs']['seed'] = random_int(0, PHP_INT_MAX - 1);

        $files = $this->runWorkflow($baseUrl, $workflow, maxWaitSeconds: 120);

        $assets = [];
        foreach ($files as $file) {
            $bytes = $this->downloadOutput($baseUrl, $file);
            if ($bytes === '') continue;

            $ext = pathinfo($file['filename'], PATHINFO_EXTENSION) ?: 'png';
            $filename = 'generations/' . uniqid() . '.' . $ext;
            Storage::disk('r2')->put($filename, $bytes);

            $assets[] = [
                'type' => 'image',
                'disk' => 'r2',
                'path' => $filename,
                'mime_type' => "image/{$ext}",
                'size' => Storage::disk('r2')->size($filename),
            ];
        }

        if (empty($assets)) {
            throw new \RuntimeException('A imagem não pôde ser gerada pelo ComfyUI. Verifique se ele está rodando e tente novamente.');
        }

        return [
            'model' => 'comfyui/flux-schnell',
            'assets' => $assets,
        ];
    }

    public function generateVideo(Action $action, Collection $products, ?string $extraPrompt, int $companyId): array
    {
        $baseUrl = $this->resolveBaseUrl($companyId);
        $prompt = $this->buildPrompt($action, $products, $extraPrompt);

        $workflow = $this->loadWorkflow('ltxv_text_to_video.json');
        $workflow['6']['inputs']['text'] = $prompt;
        $workflow['72']['inputs']['noise_seed'] = random_int(0, PHP_INT_MAX - 1);

        $files = $this->runWorkflow($baseUrl, $workflow, maxWaitSeconds: 280);

        $assets = [];
        foreach ($files as $file) {
            $bytes = $this->downloadOutput($baseUrl, $file);
            if ($bytes === '') continue;

            $ext = pathinfo($file['filename'], PATHINFO_EXTENSION) ?: 'mp4';
            $filename = 'generations/videos/' . uniqid() . '.' . $ext;
            Storage::disk('r2')->put($filename, $bytes);

            $assets[] = [
                'type' => 'video',
                'disk' => 'r2',
                'path' => $filename,
                'mime_type' => "video/{$ext}",
                'size' => Storage::disk('r2')->size($filename),
            ];
        }

        if (empty($assets)) {
            throw new \RuntimeException('O vídeo não pôde ser gerado pelo ComfyUI. Verifique se ele está rodando e tente novamente.');
        }

        return [
            'model' => 'comfyui/ltxv',
            'assets' => $assets,
        ];
    }

    /**
     * Renderiza um único clipe a partir de um prompt já pronto — usado pelo
     * StoryboardVideoService pra gerar cada corte de um storyboard. Sem suporte a imagem de
     * referência (ComfyUI/LTXV não suporta isso hoje, mesma limitação de generateVideo()).
     */
    public function renderVideoClip(string $prompt, int $companyId, array $options = []): array
    {
        $baseUrl = $this->resolveBaseUrl($companyId);

        $workflow = $this->loadWorkflow('ltxv_text_to_video.json');
        $workflow['6']['inputs']['text'] = $prompt;
        $workflow['72']['inputs']['noise_seed'] = random_int(0, PHP_INT_MAX - 1);

        $files = $this->runWorkflow($baseUrl, $workflow, maxWaitSeconds: 280);

        foreach ($files as $file) {
            $bytes = $this->downloadOutput($baseUrl, $file);
            if ($bytes === '') continue;

            $ext = pathinfo($file['filename'], PATHINFO_EXTENSION) ?: 'mp4';
            return ['bytes' => $bytes, 'mime_type' => "video/{$ext}"];
        }

        throw new \RuntimeException('O clipe não pôde ser gerado pelo ComfyUI.');
    }

    private function buildPrompt(Action $action, Collection $products, ?string $extraPrompt): string
    {
        $productContext = $products->map(fn ($p) => "- {$p->name}: {$p->description} (R$ {$p->price})")->join("\n")
            ?: 'Nenhum produto específico selecionado.';

        return trim(<<<PROMPT
        Professional commercial advertising content for {$action->platform}.
        Product: {$productContext}
        Brief: {$action->brief}
        {$extraPrompt}
        PROMPT);
    }

    private function parseResolution(string $resolution): array
    {
        if (!preg_match('/^(\d+)x(\d+)$/', $resolution, $m)) {
            return [1024, 1024];
        }

        $round64 = fn ($n) => max(64, (int) round($n / 64) * 64);

        return [$round64($m[1]), $round64($m[2])];
    }

    private function loadWorkflow(string $file): array
    {
        return json_decode(file_get_contents(resource_path("comfyui/{$file}")), true);
    }

    private function runWorkflow(string $baseUrl, array $workflow, int $maxWaitSeconds): array
    {
        $submit = Http::timeout(30)->post("{$baseUrl}/prompt", [
            'prompt' => $workflow,
            'client_id' => (string) Str::uuid(),
        ]);

        if (!$submit->successful()) {
            throw new \RuntimeException('Erro ao enviar o workflow para o ComfyUI: ' . $submit->body());
        }

        $promptId = $submit->json('prompt_id');
        if (!$promptId) {
            throw new \RuntimeException('Resposta inválida do ComfyUI: sem prompt_id.');
        }

        $deadline = time() + $maxWaitSeconds;

        while (time() < $deadline) {
            sleep(2);

            try {
                $history = Http::timeout(15)->get("{$baseUrl}/history/{$promptId}");
            } catch (\Illuminate\Http\Client\ConnectionException) {
                continue;
            }
            if (!$history->successful()) continue;

            $entry = $history->json($promptId);
            if (!$entry) continue;

            if (($entry['status']['status_str'] ?? null) === 'error') {
                throw new \RuntimeException('A geração falhou no ComfyUI. Verifique o console do ComfyUI para detalhes.');
            }

            $completed = ($entry['status']['completed'] ?? false) === true || !empty($entry['outputs']);
            if ($completed) {
                return $this->extractFiles($entry['outputs'] ?? []);
            }
        }

        throw new \RuntimeException('Tempo esgotado aguardando o ComfyUI gerar o conteúdo.');
    }

    private function extractFiles(array $outputs): array
    {
        $files = [];

        foreach ($outputs as $nodeOutput) {
            foreach ($nodeOutput as $items) {
                if (!is_array($items)) continue;

                foreach ($items as $item) {
                    if (is_array($item) && isset($item['filename'])) {
                        $files[] = $item;
                    }
                }
            }
        }

        return $files;
    }

    private function downloadOutput(string $baseUrl, array $file): string
    {
        return Http::timeout(60)->get("{$baseUrl}/view", [
            'filename' => $file['filename'],
            'subfolder' => $file['subfolder'] ?? '',
            'type' => $file['type'] ?? 'output',
        ])->body();
    }

    private function resolveBaseUrl(int $companyId): string
    {
        $url = AiCredential::where('company_id', $companyId)
            ->where('provider', 'comfyui')
            ->where('is_active', true)
            ->first()?->api_key;

        abort_if(!$url, 422, 'Cadastre a URL do ComfyUI em Configurações > Inteligência Artificial.');

        return rtrim($url, '/');
    }
}
