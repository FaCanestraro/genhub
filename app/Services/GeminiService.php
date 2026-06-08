<?php

namespace App\Services;

use App\Models\Action;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GeminiService
{
    private string $imageModel  = 'imagen-4.0-generate-001';
    private string $videoModel  = 'veo-3.0-generate-001';
    private string $textModel   = 'gemini-2.5-flash';
    private string $apiBase     = 'https://generativelanguage.googleapis.com/v1beta';

    public function generateCaption(Action $action, Collection $products, ?string $extraPrompt): array
    {
        $productContext = $this->buildProductContext($products);
        $platformRules = $this->platformRules($action->platform, $action->type);

        $prompt = <<<PROMPT
        Você é um especialista em marketing digital e copywriting para redes sociais.
        Crie uma legenda envolvente para {$action->platform} do tipo "{$action->type}".

        {$platformRules}

        PRODUTOS:
        {$productContext}

        BRIEF DA AÇÃO:
        {$action->brief}

        {$extraPrompt}

        Retorne APENAS um JSON com:
        {
          "caption": "texto da legenda",
          "hashtags": ["hashtag1", "hashtag2"],
          "cta": "chamada para ação"
        }
        PROMPT;

        $response = Gemini::generativeModel('gemini-2.5-flash')->generateContent($prompt);
        $text = $response->text();

        $json = json_decode($this->extractJson($text), true);

        return [
            'model' => $this->textModel,
            'text' => $text,
            'caption' => $json['caption'] ?? null,
            'hashtags' => $json['hashtags'] ?? [],
            'assets' => [],
        ];
    }

    public function generateImage(Action $action, Collection $products, ?string $extraPrompt): array
    {
        $productContext = $this->buildProductContext($products);
        $resolution = $action->resolution ?? '1080x1080';
        [$width, $height] = explode('x', $resolution);

        $prompt = <<<PROMPT
        Create a hyperrealistic professional commercial advertising photograph for {$action->platform}.
        Output resolution: {$resolution} px (width: {$width}px × height: {$height}px).

        PRODUCT TO FEATURE (must be clearly visible and prominently placed in the image):
        {$productContext}

        CREATIVE BRIEF (visual direction, scene, mood, persona):
        {$action->brief}

        {$extraPrompt}

        MANDATORY REQUIREMENTS:
        - The product packaging, label, and brand name must be razor-sharp and fully readable
        - Photorealistic, ultra-high detail — no illustrations, no cartoons, no CGI artifacts
        - Commercial advertising quality: perfect studio lighting or motivated natural light
        - Human skin tones must be realistic and natural (Brazilian complexion if people present)
        - Depth of field: product and person in focus, background beautifully blurred (f/1.4 quality)
        - No watermarks, no text overlays, no borders
        - 8K quality, shot on ARRI Alexa or Sony VENICE cinema camera look
        PROMPT;

        $quantity = min($action->quantity ?? 1, 4);
        $assets = [];

        for ($i = 0; $i < $quantity; $i++) {
            $imageData = $this->generateSingleImage($prompt);
            if ($imageData) {
                $assets[] = $imageData;
            }
        }

        if (empty($assets)) {
            throw new \RuntimeException('A imagem não pôde ser gerada. O conteúdo pode ter sido bloqueado pelas políticas de segurança da API (ex: pessoas reais, conteúdo protegido). Tente reformular o prompt.');
        }

        return [
            'model' => $this->imageModel,
            'assets' => $assets,
        ];
    }

    public function generateCarousel(Action $action, Collection $products, ?string $extraPrompt): array
    {
        $productContext = $this->buildProductContext($products);
        $slides = min($action->quantity ?? 5, 10);

        $captionPrompt = <<<PROMPT
        Crie {$slides} slides para um carrossel no {$action->platform}.
        Cada slide deve ter um título curto e texto de apoio.

        PRODUTOS:
        {$productContext}

        BRIEF:
        {$action->brief}

        {$extraPrompt}

        Retorne APENAS JSON:
        {
          "slides": [
            {"slide": 1, "title": "...", "text": "...", "image_prompt": "..."}
          ],
          "caption": "legenda geral do carrossel",
          "hashtags": ["..."]
        }
        PROMPT;

        $response = Gemini::geminiPro()->generateContent($captionPrompt);
        $json = json_decode($this->extractJson($response->text()), true);

        $assets = [];
        foreach ($json['slides'] ?? [] as $slide) {
            $imageData = $this->generateSingleImage(
                ($slide['image_prompt'] ?? $slide['title']) . ', estilo marketing profissional'
            );
            if ($imageData) {
                $imageData['metadata'] = ['slide' => $slide['slide'], 'title' => $slide['title']];
                $assets[] = $imageData;
            }
        }

        if (empty($assets)) {
            throw new \RuntimeException('Nenhuma imagem do carrossel pôde ser gerada. O conteúdo pode ter sido bloqueado pelas políticas de segurança da API. Tente reformular o prompt.');
        }

        return [
            'model' => $this->imageModel,
            'text' => $response->text(),
            'caption' => $json['caption'] ?? null,
            'hashtags' => $json['hashtags'] ?? [],
            'assets' => $assets,
        ];
    }

    public function generateVideo(Action $action, Collection $products, ?string $extraPrompt): array
    {
        $productContext = $this->buildProductContext($products);
        $aspectRatio    = $this->veoAspectRatio($action->resolution ?? '1080x1920');
        $duration       = 8;

        $prompt = <<<PROMPT
        Create a hyperrealistic professional commercial advertising video for {$action->platform}.
        Aspect ratio: {$aspectRatio}. Duration: {$duration} seconds.
        {$this->platformRules($action->platform, $action->type)}

        PRODUCT TO FEATURE (must be clearly visible, prominently featured throughout):
        {$productContext}

        CREATIVE BRIEF (action sequence, mood, camera direction, persona):
        {$action->brief}

        {$extraPrompt}

        MANDATORY REQUIREMENTS:
        - Photorealistic footage — no animation, no CGI look, no motion graphics
        - The product must appear clearly identifiable with readable label/packaging
        - Human actors must look like real Brazilian people — natural expressions, realistic skin
        - Cinematic camera movements: motivated push-ins, orbits, rack focus, slow motion impacts
        - Professional color grade: rich, saturated, broadcast-ready
        - Seamless continuous shot — no hard cuts, fluid motion throughout
        - Anamorphic lens quality, film grain, natural lens flares
        PROMPT;

        $key = config('gemini.api_key');

        $startResponse = Http::timeout(30)->post(
            "{$this->apiBase}/models/{$this->videoModel}:predictLongRunning?key={$key}",
            [
                'instances'  => [['prompt' => trim($prompt)]],
                'parameters' => [
                    'aspectRatio'     => $aspectRatio,
                    'durationSeconds' => $duration,
                ],
            ]
        );

        if (!$startResponse->successful()) {
            throw new \RuntimeException('Erro ao iniciar geração de vídeo: ' . $startResponse->body());
        }

        $operationName = $startResponse->json('name');
        if (!$operationName) {
            throw new \RuntimeException('Resposta inválida da API Veo: sem operation name.');
        }

        $videoData = $this->pollOperation($operationName, $key, maxWaitSeconds: 180);

        $assets = [];

        // Format 1: generateVideoResponse (URI to download)
        $samples = $videoData['response']['generateVideoResponse']['generatedSamples'] ?? [];
        foreach ($samples as $sample) {
            $uri      = $sample['video']['uri'] ?? null;
            $mimeType = $sample['video']['encoding'] ?? 'video/mp4';
            if (!$uri) continue;

            $videoBytes = Http::withHeaders(['X-Goog-Api-Key' => $key])
                ->timeout(120)
                ->get($uri)
                ->body();

            $ext      = str_replace('video/', '', explode(';', $mimeType)[0]);
            $filename = 'generations/videos/' . uniqid() . '.' . $ext;
            Storage::disk('public')->put($filename, $videoBytes);

            $assets[] = [
                'type'      => 'video',
                'disk'      => 'public',
                'path'      => $filename,
                'mime_type' => $mimeType,
                'size'      => Storage::disk('public')->size($filename),
                'duration'  => $duration,
            ];
        }

        // Format 2: predictions with inline base64 (fallback)
        if (empty($assets)) {
            foreach ($videoData['response']['predictions'] ?? [] as $prediction) {
                $bytes    = $prediction['bytesBase64Encoded'] ?? null;
                $mimeType = $prediction['mimeType'] ?? 'video/mp4';
                if (!$bytes) continue;

                $ext      = str_replace('video/', '', $mimeType);
                $filename = 'generations/videos/' . uniqid() . '.' . $ext;
                Storage::disk('public')->put($filename, base64_decode($bytes));

                $assets[] = [
                    'type'      => 'video',
                    'disk'      => 'public',
                    'path'      => $filename,
                    'mime_type' => $mimeType,
                    'size'      => Storage::disk('public')->size($filename),
                    'duration'  => $duration,
                ];
            }
        }

        return [
            'model'  => $this->videoModel,
            'assets' => $assets,
        ];
    }

    private function pollOperation(string $operationName, string $key, int $maxWaitSeconds = 180): array
    {
        $deadline = time() + $maxWaitSeconds;

        while (time() < $deadline) {
            sleep(5);

            $response = Http::timeout(15)->get(
                "{$this->apiBase}/{$operationName}?key={$key}"
            );

            if (!$response->successful()) {
                throw new \RuntimeException('Erro ao verificar status do vídeo: ' . $response->body());
            }

            $data = $response->json();

            if ($data['done'] ?? false) {
                if (isset($data['error'])) {
                    throw new \RuntimeException('Falha na geração de vídeo: ' . ($data['error']['message'] ?? 'Erro desconhecido'));
                }
                return $data;
            }
        }

        throw new \RuntimeException("Tempo limite excedido aguardando o vídeo ({$maxWaitSeconds}s). Tente novamente.");
    }

    private function generateSingleImage(string $prompt): ?array
    {
        $key = config('gemini.api_key');

        $response = Http::timeout(60)->post(
            "{$this->apiBase}/models/{$this->imageModel}:predict?key={$key}",
            [
                'instances'  => [['prompt' => $prompt]],
                'parameters' => ['sampleCount' => 1],
            ]
        );

        if (!$response->successful()) {
            throw new \RuntimeException('Erro na API Imagen: ' . $response->body());
        }

        $predictions = $response->json('predictions') ?? [];

        foreach ($predictions as $prediction) {
            $imageBase64 = $prediction['bytesBase64Encoded'] ?? null;
            $mimeType    = $prediction['mimeType'] ?? 'image/png';

            if (!$imageBase64) continue;

            $ext      = str_replace('image/', '', explode(';', $mimeType)[0]);
            $filename = 'generations/' . uniqid() . '.' . $ext;

            Storage::disk('public')->put($filename, base64_decode($imageBase64));

            return [
                'type'      => 'image',
                'disk'      => 'public',
                'path'      => $filename,
                'mime_type' => $mimeType,
                'size'      => Storage::disk('public')->size($filename),
            ];
        }

        return null;
    }

    private function resolutionToAspectRatio(string $resolution): string
    {
        if (!preg_match('/^(\d+)x(\d+)$/', $resolution, $m)) {
            return '9:16';
        }

        $w = (int) $m[1];
        $h = (int) $m[2];

        if ($w === 0 || $h === 0) return '9:16';

        $gcd = $this->gcd($w, $h);
        return ($w / $gcd) . ':' . ($h / $gcd);
    }

    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }

    // Veo only supports 9:16 and 16:9 — snap to closest
    private function veoAspectRatio(string $resolution): string
    {
        if (preg_match('/^(\d+)x(\d+)$/', $resolution, $m)) {
            return (int) $m[1] >= (int) $m[2] ? '16:9' : '9:16';
        }
        return '9:16';
    }

    private function buildProductContext(Collection $products): string
    {
        if ($products->isEmpty()) {
            return 'Nenhum produto específico selecionado.';
        }

        return $products->map(fn ($p) => "- {$p->name}: {$p->description} (R$ {$p->price})")->join("\n");
    }

    private function platformRules(string $platform, string $type): string
    {
        return match ("{$platform}_{$type}") {
            'instagram_post' => 'Legenda entre 100-300 caracteres, até 30 hashtags, tom engajador.',
            'instagram_reel' => 'Legenda curta (máx 150 chars), foco no hook das primeiras 3 linhas, emojis.',
            'instagram_story' => 'Texto muito curto (máx 80 chars), direto ao ponto, CTA claro.',
            'instagram_carousel' => 'Primeira linha é o hook, mencione "deslize para ver mais", 5-10 hashtags.',
            'tiktok_tiktok_video' => 'Legenda curta e viral, trending sounds mencionados, hashtags nicho (#fyp #foryou).',
            default => 'Tom profissional e engajador para redes sociais.',
        };
    }

    private function extractJson(string $text): string
    {
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $text, $m)) {
            return $m[1];
        }
        if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
            return $m[0];
        }

        return $text;
    }
}
