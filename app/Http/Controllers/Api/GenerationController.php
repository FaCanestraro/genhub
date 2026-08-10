<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Models\Action;
use App\Models\Asset;
use App\Models\Generation;
use App\Models\Product;
use App\Models\AiCredential;
use App\Services\AuditLogger;
use App\Services\GeminiService;
use App\Services\MuApiService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class GenerationController extends Controller implements HasMiddleware
{
    public function __construct(private GeminiService $gemini, private MuApiService $muapi) {}

    // ponytail: only 2 real providers exist, so "active muapi credential wins" is enough —
    // add a proper provider registry/config if a 3rd provider shows up.
    private function hasMuApiCredential(): bool
    {
        return AiCredential::where('company_id', request()->company()->id)
            ->where('provider', 'muapi')
            ->where('is_active', true)
            ->exists();
    }

    private function generateImage($action, $products, ?string $prompt): array
    {
        return $this->hasMuApiCredential()
            ? $this->muapi->generateImage($action, $products, $prompt)
            : $this->gemini->generateImage($action, $products, $prompt);
    }

    private function generateVideo($action, $products, ?string $prompt): array
    {
        return $this->hasMuApiCredential()
            ? $this->muapi->generateVideo($action, $products, $prompt)
            : $this->gemini->generateVideo($action, $products, $prompt);
    }

    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermission::class.':generate,view',   only: ['index', 'show', 'standaloneHistory']),
            new Middleware(CheckPermission::class.':generate,create', only: ['generate', 'generateStandalone']),
            new Middleware(CheckPermission::class.':generate,edit',   only: ['renameSession', 'detach']),
            new Middleware(CheckPermission::class.':generate,delete', only: ['destroy', 'destroySession']),
        ];
    }

    public function generate(Request $request, Action $action)
    {
        abort_if($action->company_id !== $request->company()->id, 403);

        $request->validate([
            'type'   => 'required|in:image,text,carousel,video',
            'prompt' => 'nullable|string|max:2000',
        ]);

        // Vídeo pode levar até 3 min — aumenta o limite de execução do PHP
        if ($request->type === 'video') {
            set_time_limit(300);
        }

        $action->update(['status' => 'generating']);

        $generation = Generation::create([
            'action_id' => $action->id,
            'company_id' => $request->company()->id,
            'type' => $request->type,
            'status' => 'processing',
            'prompt' => $request->prompt,
            'started_at' => now(),
        ]);

        try {
            $products = Product::where('company_id', $request->company()->id)
                ->whereIn('id', $action->product_ids ?? [])
                ->get();

            $result = match ($request->type) {
                'text'     => $this->gemini->generateCaption($action, $products, $request->prompt),
                'image'    => $this->generateImage($action, $products, $request->prompt),
                'carousel' => $this->gemini->generateCarousel($action, $products, $request->prompt),
                'video'    => $this->generateVideo($action, $products, $request->prompt),
            };

            $generation->update([
                'status' => 'completed',
                'result_text' => $result['text'] ?? null,
                'model_used' => $result['model'],
                'metadata' => $result['metadata'] ?? null,
                'completed_at' => now(),
            ]);

            if (!empty($result['assets'])) {
                foreach ($result['assets'] as $assetData) {
                    Asset::create([
                        'generation_id' => $generation->id,
                        'company_id' => $request->company()->id,
                        'type' => $assetData['type'],
                        'disk' => $assetData['disk'],
                        'path' => $assetData['path'],
                        'mime_type' => $assetData['mime_type'] ?? null,
                        'size' => $assetData['size'] ?? null,
                        'width'    => $assetData['width'] ?? null,
                        'height'   => $assetData['height'] ?? null,
                        'duration' => $assetData['duration'] ?? null,
                    ]);
                }
            }

            $action->update(['status' => 'ready']);

            if (!empty($result['caption'])) {
                $action->update([
                    'caption' => $result['caption'],
                    'hashtags' => $result['hashtags'] ?? null,
                ]);
            }

            AuditLogger::log('generate', 'generation.completed', "Geração de {$request->type} concluída para a ação \"{$action->title}\"", [
                'subject' => $generation,
                'input' => ['type' => $request->type, 'prompt' => $request->prompt],
                'output' => ['assets' => count($result['assets'] ?? []), 'has_caption' => !empty($result['caption'])],
                'ai_model' => $result['model'],
                'duration_ms' => (int) round($generation->started_at->diffInMilliseconds(now())),
            ]);

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();

            $userMessage = match(true) {
                str_contains($errorMsg, 'quota') || str_contains($errorMsg, 'RESOURCE_EXHAUSTED')
                    => 'Cota da API Gemini esgotada. Para imagens e vídeos, habilite o faturamento em aistudio.google.com. Para texto, aguarde alguns segundos.',
                str_contains($errorMsg, 'API_KEY_INVALID') || str_contains($errorMsg, 'API key not valid')
                    => 'Chave de API inválida. Verifique a chave cadastrada em Configurações > Inteligência Artificial (ou a GEMINI_API_KEY no .env).',
                str_contains($errorMsg, 'Tempo limite excedido')
                    => $errorMsg . ' O Veo pode demorar até 3 minutos.',
                default => 'Falha na geração: ' . $errorMsg,
            };

            $generation->update([
                'status' => 'failed',
                'error_message' => $errorMsg,
                'completed_at' => now(),
            ]);
            $action->update(['status' => 'failed']);

            AuditLogger::log('generate', 'generation.failed', "Falha na geração de {$request->type} para a ação \"{$action->title}\"", [
                'subject' => $generation,
                'input' => ['type' => $request->type, 'prompt' => $request->prompt],
                'output' => ['error' => $errorMsg],
                'status' => 'failed',
                'duration_ms' => (int) round($generation->started_at->diffInMilliseconds(now())),
            ]);

            return response()->json(['message' => $userMessage], 500);
        }

        return response()->json($generation->load('assets'));
    }

    public function generateStandalone(Request $request)
    {
        $request->validate([
            'type'         => 'required|in:image,text,carousel,video',
            'platform'     => 'required|in:instagram,tiktok,facebook,youtube',
            'content_type' => 'required|in:post,reel,carousel,story,tiktok_video',
            'brief'        => 'nullable|string|max:5000',
            'prompt'       => 'nullable|string|max:2000',
            'resolution'   => ['nullable', 'string', 'regex:/^\d+x\d+$/'],
            'quantity'     => 'nullable|integer|min:1|max:10',
            'product_ids'  => 'nullable|array',
            'session_id'   => 'nullable|string|max:36',
        ]);

        if ($request->type === 'video') {
            set_time_limit(300);
        }

        $action = new \App\Models\Action([
            'platform'   => $request->platform,
            'type'       => $request->content_type,
            'brief'      => $request->brief,
            'resolution' => $request->resolution ?? '1080x1080',
            'quantity'   => $request->quantity ?? 1,
        ]);

        $generation = Generation::create([
            'action_id'  => null,
            'session_id' => $request->session_id,
            'company_id'    => $request->company()->id,
            'type'       => $request->type,
            'status'     => 'processing',
            'prompt'     => $request->brief . ($request->prompt ? "\n\n" . $request->prompt : ''),
            'started_at' => now(),
        ]);

        try {
            $products = Product::where('company_id', $request->company()->id)
                ->whereIn('id', $request->product_ids ?? [])
                ->get();

            $result = match ($request->type) {
                'text'     => $this->gemini->generateCaption($action, $products, $request->prompt),
                'image'    => $this->generateImage($action, $products, $request->prompt),
                'carousel' => $this->gemini->generateCarousel($action, $products, $request->prompt),
                'video'    => $this->generateVideo($action, $products, $request->prompt),
            };

            $generation->update([
                'status'       => 'completed',
                'result_text'  => $result['text'] ?? null,
                'model_used'   => $result['model'],
                'metadata'     => $result['metadata'] ?? null,
                'completed_at' => now(),
            ]);

            if (!empty($result['assets'])) {
                foreach ($result['assets'] as $assetData) {
                    Asset::create([
                        'generation_id' => $generation->id,
                        'company_id'       => $request->company()->id,
                        'type'          => $assetData['type'],
                        'disk'          => $assetData['disk'],
                        'path'          => $assetData['path'],
                        'mime_type'     => $assetData['mime_type'] ?? null,
                        'size'          => $assetData['size'] ?? null,
                        'width'         => $assetData['width'] ?? null,
                        'height'        => $assetData['height'] ?? null,
                        'duration'      => $assetData['duration'] ?? null,
                    ]);
                }
            }

            if (!empty($result['caption'])) {
                $generation->update(['result_text' => $result['caption']]);
            }

            AuditLogger::log('generate', 'generation.completed', "Geração avulsa de {$request->type} concluída", [
                'subject' => $generation,
                'input' => ['type' => $request->type, 'platform' => $request->platform, 'brief' => $request->brief, 'prompt' => $request->prompt],
                'output' => ['assets' => count($result['assets'] ?? []), 'has_caption' => !empty($result['caption'])],
                'ai_model' => $result['model'],
                'duration_ms' => (int) round($generation->started_at->diffInMilliseconds(now())),
            ]);

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();

            $userMessage = match(true) {
                str_contains($errorMsg, 'quota') || str_contains($errorMsg, 'RESOURCE_EXHAUSTED')
                    => 'Cota da API Gemini esgotada. Para imagens e vídeos, habilite o faturamento em aistudio.google.com.',
                str_contains($errorMsg, 'API_KEY_INVALID') || str_contains($errorMsg, 'API key not valid')
                    => 'Chave de API inválida. Verifique a chave cadastrada em Configurações > Inteligência Artificial (ou a GEMINI_API_KEY no .env).',
                str_contains($errorMsg, 'Tempo limite excedido')
                    => $errorMsg . ' O Veo pode demorar até 3 minutos.',
                default => 'Falha na geração: ' . $errorMsg,
            };

            $generation->update([
                'status'        => 'failed',
                'error_message' => $errorMsg,
                'completed_at'  => now(),
            ]);

            AuditLogger::log('generate', 'generation.failed', "Falha na geração avulsa de {$request->type}", [
                'subject' => $generation,
                'input' => ['type' => $request->type, 'platform' => $request->platform, 'brief' => $request->brief, 'prompt' => $request->prompt],
                'output' => ['error' => $errorMsg],
                'status' => 'failed',
                'duration_ms' => (int) round($generation->started_at->diffInMilliseconds(now())),
            ]);

            return response()->json(['message' => $userMessage], 500);
        }

        return response()->json($generation->load('assets'));
    }

    public function renameSession(Request $request, string $sessionId)
    {
        $request->validate(['title' => 'required|string|max:100']);

        Generation::where('company_id', $request->company()->id)
            ->where('session_id', $sessionId)
            ->update(['session_title' => $request->title]);

        return response()->json(null, 204);
    }

    public function destroySession(Request $request, string $sessionId)
    {
        $generations = Generation::where('company_id', $request->company()->id)
            ->where('session_id', $sessionId)
            ->with('assets')
            ->get();

        foreach ($generations as $generation) {
            $generation->assets->each(function ($asset) {
                \Illuminate\Support\Facades\Storage::disk($asset->disk)->delete($asset->path);
                $asset->delete();
            });
            $generation->delete();
        }

        return response()->json(null, 204);
    }

    public function standaloneHistory(Request $request)
    {
        $query = Generation::where('company_id', $request->company()->id)
            ->with('assets')
            ->latest();

        if ($request->filled('session_id')) {
            // Busca por sessão independente de estar vinculada a uma ação
            $query->where('session_id', $request->session_id);
        } else {
            // Sem filtro de sessão: retorna apenas gerações avulsas
            $query->whereNull('action_id');
        }

        return response()->json($query->get());
    }

    public function detach(Request $request, Generation $generation)
    {
        abort_if($generation->company_id !== $request->company()->id, 403);

        $generation->update(['action_id' => null]);

        return response()->json(null, 204);
    }

    public function destroy(Request $request, Generation $generation)
    {
        abort_if($generation->company_id !== $request->company()->id, 403);

        $generation->assets()->each(function ($asset) {
            \Illuminate\Support\Facades\Storage::disk($asset->disk)->delete($asset->path);
            $asset->delete();
        });

        $generation->delete();

        return response()->json(null, 204);
    }

    public function show(Request $request, Generation $generation)
    {
        abort_if($generation->company_id !== $request->company()->id, 403);

        return response()->json($generation->load('assets'));
    }

    public function index(Request $request)
    {
        $query = Generation::where('company_id', $request->company()->id)
            ->with('assets', 'action.campaign')
            ->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(24));
    }
}
