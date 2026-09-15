<?php

namespace App\Jobs;

use App\Models\Action;
use App\Models\AiCredential;
use App\Models\Asset;
use App\Models\Generation;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\ComfyUiService;
use App\Services\GeminiService;
use App\Services\MuApiService;
use App\Services\StoryboardVideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Geração de vídeo local (ComfyUI) pode levar vários minutos — bem acima do timeout
    // padrão de 60s do worker. Storyboard multi-corte (StoryboardVideoService) soma vários
    // clipes + concat com ffmpeg: pior caso 5 segmentos x até 280s de poll do Veo = ~1400s,
    // então o teto precisa de folga acima disso.
    public int $timeout = 1800;
    public int $tries = 1;

    public function __construct(
        private int $generationId,
        private string $type,
        private ?int $actionId,
        private ?array $transientAction,
        private array $productIds,
        private ?string $prompt,
        private int $companyId,
        private ?int $userId,
    ) {}

    public function handle(GeminiService $gemini, MuApiService $muapi, ComfyUiService $comfyui, StoryboardVideoService $storyboard): void
    {
        $generation = Generation::find($this->generationId);
        if (!$generation) return;

        $generation->update(['status' => 'processing']);

        $action = $this->actionId ? Action::find($this->actionId) : new Action($this->transientAction ?? []);
        if (!$action) {
            $generation->update(['status' => 'failed', 'error_message' => 'Ação não encontrada.', 'completed_at' => now()]);
            return;
        }

        $products = Product::where('company_id', $this->companyId)
            ->whereIn('id', $this->productIds)
            ->get();

        try {
            $mediaService = $this->resolveMediaService($comfyui, $muapi, $gemini);

            $result = match ($this->type) {
                'text'     => $gemini->generateCaption($action, $products, $this->prompt, $this->companyId),
                'image'    => $mediaService->generateImage($action, $products, $this->prompt, $this->companyId),
                'carousel' => $gemini->generateCarousel($action, $products, $this->prompt, $this->companyId),
                'video'    => $storyboard->generate($action, $products, $this->prompt, $this->companyId, $mediaService, $gemini),
            };

            $generation->update([
                'status' => 'completed',
                'result_text' => $result['text'] ?? null,
                'model_used' => $result['model'],
                'metadata' => $result['metadata'] ?? null,
                'completed_at' => now(),
            ]);

            foreach ($result['assets'] ?? [] as $assetData) {
                Asset::create([
                    'generation_id' => $generation->id,
                    'company_id' => $this->companyId,
                    'type' => $assetData['type'],
                    'disk' => $assetData['disk'],
                    'path' => $assetData['path'],
                    'mime_type' => $assetData['mime_type'] ?? null,
                    'size' => $assetData['size'] ?? null,
                    'width' => $assetData['width'] ?? null,
                    'height' => $assetData['height'] ?? null,
                    'duration' => $assetData['duration'] ?? null,
                ]);
            }

            if ($action->exists) {
                $action->update(['status' => 'ready']);
                if (!empty($result['caption'])) {
                    $action->update(['caption' => $result['caption'], 'hashtags' => $result['hashtags'] ?? null]);
                }
            } elseif (!empty($result['caption'])) {
                $generation->update(['result_text' => $result['caption']]);
            }

            AuditLogger::log('generate', 'generation.completed', $this->describe('concluída', $action), [
                'subject' => $generation,
                'company_id' => $this->companyId,
                'causer_id' => $this->userId,
                'input' => ['type' => $this->type, 'prompt' => $this->prompt],
                'output' => ['assets' => count($result['assets'] ?? []), 'has_caption' => !empty($result['caption'])],
                'ai_model' => $result['model'],
                'duration_ms' => (int) round($generation->started_at->diffInMilliseconds(now())),
            ]);
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();

            $userMessage = match (true) {
                str_contains($errorMsg, 'quota') || str_contains($errorMsg, 'RESOURCE_EXHAUSTED')
                    => 'Cota da API Gemini esgotada. Para imagens e vídeos, habilite o faturamento em aistudio.google.com.',
                str_contains($errorMsg, 'API_KEY_INVALID') || str_contains($errorMsg, 'API key not valid')
                    => 'Chave de API inválida. Verifique a chave cadastrada em Configurações > Inteligência Artificial (ou a GEMINI_API_KEY no .env).',
                str_contains($errorMsg, 'Tempo limite excedido') || str_contains($errorMsg, 'Tempo esgotado')
                    => $errorMsg,
                str_contains($errorMsg, 'ffmpeg')
                    => $errorMsg,
                default => 'Falha na geração: ' . $errorMsg,
            };

            $generation->update([
                'status' => 'failed',
                'error_message' => $userMessage,
                'completed_at' => now(),
            ]);

            if ($action->exists) {
                $action->update(['status' => 'failed']);
            }

            AuditLogger::log('generate', 'generation.failed', $this->describe('falhou', $action), [
                'subject' => $generation,
                'company_id' => $this->companyId,
                'causer_id' => $this->userId,
                'input' => ['type' => $this->type, 'prompt' => $this->prompt],
                'output' => ['error' => $errorMsg],
                'status' => 'failed',
                'duration_ms' => (int) round($generation->started_at->diffInMilliseconds(now())),
            ]);
        }
    }

    // Prioridade: ComfyUI (local, sem custo) > MuAPI > Gemini como fallback padrão.
    private function resolveMediaService(ComfyUiService $comfyui, MuApiService $muapi, GeminiService $gemini): ComfyUiService|MuApiService|GeminiService
    {
        foreach (['comfyui' => $comfyui, 'muapi' => $muapi] as $provider => $service) {
            $hasCredential = AiCredential::where('company_id', $this->companyId)
                ->where('provider', $provider)
                ->where('is_active', true)
                ->exists();

            if ($hasCredential) {
                return $service;
            }
        }

        return $gemini;
    }

    private function describe(string $outcome, Action $action): string
    {
        return $action->exists
            ? "Geração de {$this->type} {$outcome} para a ação \"{$action->title}\""
            : "Geração avulsa de {$this->type} {$outcome}";
    }
}
