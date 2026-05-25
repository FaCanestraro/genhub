<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Asset;
use App\Models\Generation;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class GenerationController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

    public function generate(Request $request, Action $action)
    {
        abort_if($action->user_id !== $request->user()->id, 403);

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
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'status' => 'processing',
            'prompt' => $request->prompt,
            'started_at' => now(),
        ]);

        try {
            $products = $request->user()->products()
                ->whereIn('id', $action->product_ids ?? [])
                ->get();

            $result = match ($request->type) {
                'text'     => $this->gemini->generateCaption($action, $products, $request->prompt),
                'image'    => $this->gemini->generateImage($action, $products, $request->prompt),
                'carousel' => $this->gemini->generateCarousel($action, $products, $request->prompt),
                'video'    => $this->gemini->generateVideo($action, $products, $request->prompt),
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
                        'user_id' => $request->user()->id,
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

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();

            $userMessage = match(true) {
                str_contains($errorMsg, 'quota') || str_contains($errorMsg, 'RESOURCE_EXHAUSTED')
                    => 'Cota da API Gemini esgotada. Para imagens e vídeos, habilite o faturamento em aistudio.google.com. Para texto, aguarde alguns segundos.',
                str_contains($errorMsg, 'API_KEY') || str_contains($errorMsg, 'INVALID_ARGUMENT')
                    => 'Chave de API inválida. Verifique a GEMINI_API_KEY no .env.',
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
            'user_id'    => $request->user()->id,
            'type'       => $request->type,
            'status'     => 'processing',
            'prompt'     => $request->brief . ($request->prompt ? "\n\n" . $request->prompt : ''),
            'started_at' => now(),
        ]);

        try {
            $products = $request->user()->products()
                ->whereIn('id', $request->product_ids ?? [])
                ->get();

            $result = match ($request->type) {
                'text'     => $this->gemini->generateCaption($action, $products, $request->prompt),
                'image'    => $this->gemini->generateImage($action, $products, $request->prompt),
                'carousel' => $this->gemini->generateCarousel($action, $products, $request->prompt),
                'video'    => $this->gemini->generateVideo($action, $products, $request->prompt),
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
                        'user_id'       => $request->user()->id,
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

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();

            $userMessage = match(true) {
                str_contains($errorMsg, 'quota') || str_contains($errorMsg, 'RESOURCE_EXHAUSTED')
                    => 'Cota da API Gemini esgotada. Para imagens e vídeos, habilite o faturamento em aistudio.google.com.',
                str_contains($errorMsg, 'API_KEY') || str_contains($errorMsg, 'INVALID_ARGUMENT')
                    => 'Chave de API inválida. Verifique a GEMINI_API_KEY no .env.',
                str_contains($errorMsg, 'Tempo limite excedido')
                    => $errorMsg . ' O Veo pode demorar até 3 minutos.',
                default => 'Falha na geração: ' . $errorMsg,
            };

            $generation->update([
                'status'        => 'failed',
                'error_message' => $errorMsg,
                'completed_at'  => now(),
            ]);

            return response()->json(['message' => $userMessage], 500);
        }

        return response()->json($generation->load('assets'));
    }

    public function renameSession(Request $request, string $sessionId)
    {
        $request->validate(['title' => 'required|string|max:100']);

        $request->user()
            ->generations()
            ->where('session_id', $sessionId)
            ->update(['session_title' => $request->title]);

        return response()->json(null, 204);
    }

    public function destroySession(Request $request, string $sessionId)
    {
        $generations = $request->user()
            ->generations()
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
        $query = $request->user()
            ->generations()
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
        abort_if($generation->user_id !== $request->user()->id, 403);

        $generation->update(['action_id' => null]);

        return response()->json(null, 204);
    }

    public function destroy(Request $request, Generation $generation)
    {
        abort_if($generation->user_id !== $request->user()->id, 403);

        $generation->assets()->each(function ($asset) {
            \Illuminate\Support\Facades\Storage::disk($asset->disk)->delete($asset->path);
            $asset->delete();
        });

        $generation->delete();

        return response()->json(null, 204);
    }

    public function show(Request $request, Generation $generation)
    {
        abort_if($generation->user_id !== $request->user()->id, 403);

        return response()->json($generation->load('assets'));
    }

    public function index(Request $request)
    {
        $query = $request->user()
            ->generations()
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
