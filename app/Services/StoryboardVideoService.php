<?php

namespace App\Services;

use App\Contracts\VideoClipRenderer;
use App\Models\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Orquestra a geração de vídeo em múltiplos cortes: pergunta ao Gemini se o brief pede um
 * storyboard, gera cada corte via o provider já resolvido (MuAPI/Gemini/ComfyUI), concatena
 * com ffmpeg e sobe só o vídeo final — mantendo o mesmo contrato de generateVideo() (1 asset
 * de vídeo por geração). Se não houver storyboard, delega direto pra generateVideo() de sempre.
 */
class StoryboardVideoService
{
    private const MAX_SEGMENTS = 5;

    public function generate(
        Action $action,
        Collection $products,
        ?string $prompt,
        int $companyId,
        VideoClipRenderer $mediaService,
        GeminiService $planner,
    ): array {
        $segments = $planner->planVideoSegments($action, $products, $prompt, $companyId);

        if ($segments === null || count($segments) < 2) {
            return $mediaService->generateVideo($action, $products, $prompt, $companyId);
        }

        $segments = array_slice($segments, 0, self::MAX_SEGMENTS);
        $imageUrl = $products->first(fn ($p) => !empty($p->images))?->images[0] ?? null;

        $scratchDir = 'scratch/storyboard/' . Str::uuid();

        try {
            $clipPaths = [];

            foreach ($segments as $i => $segmentPrompt) {
                $clip = $mediaService->renderVideoClip($segmentPrompt, $companyId, [
                    'duration' => 8,
                    'resolution' => $action->resolution ?? '1080x1920',
                    'image_url' => $imageUrl,
                ]);

                $ext = str_replace('video/', '', explode(';', $clip['mime_type'])[0]) ?: 'mp4';
                $relativePath = "{$scratchDir}/seg_{$i}.{$ext}";
                Storage::disk('local')->put($relativePath, $clip['bytes']);
                $clipPaths[] = Storage::disk('local')->path($relativePath);
            }

            $finalPath = $this->concatWithFfmpeg($clipPaths, Storage::disk('local')->path($scratchDir));

            $filename = 'generations/videos/' . uniqid() . '.mp4';
            Storage::disk('r2')->put($filename, file_get_contents($finalPath));

            return [
                'model' => 'storyboard/' . count($segments) . 'x',
                'assets' => [[
                    'type' => 'video',
                    'disk' => 'r2',
                    'path' => $filename,
                    'mime_type' => 'video/mp4',
                    'size' => Storage::disk('r2')->size($filename),
                    'duration' => 8 * count($segments),
                ]],
                'metadata' => [
                    'storyboard' => true,
                    'segment_count' => count($segments),
                    'segments' => $segments,
                ],
            ];
        } finally {
            Storage::disk('local')->deleteDirectory($scratchDir);
        }
    }

    private function concatWithFfmpeg(array $clipPaths, string $scratchDirPath): string
    {
        $listPath = $scratchDirPath . DIRECTORY_SEPARATOR . 'concat_list.txt';
        $outPath = $scratchDirPath . DIRECTORY_SEPARATOR . 'final.mp4';

        $listContent = '';
        foreach ($clipPaths as $clipPath) {
            $escaped = str_replace("'", "'\\''", $clipPath);
            $listContent .= "file '{$escaped}'\n";
        }
        file_put_contents($listPath, $listContent);

        $result = Process::timeout(config('media.ffmpeg_timeout'))->run([
            config('media.ffmpeg_binary'), '-y', '-f', 'concat', '-safe', '0',
            '-i', $listPath, '-c', 'copy', $outPath,
        ]);

        if (!$result->successful()) {
            Log::error('ffmpeg concat falhou', ['stderr' => $result->errorOutput()]);
            throw new \RuntimeException('Não foi possível montar o vídeo final a partir dos cortes gerados. Verifique se o ffmpeg está instalado no servidor.');
        }

        return $outPath;
    }
}
