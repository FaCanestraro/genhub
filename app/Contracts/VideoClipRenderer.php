<?php

namespace App\Contracts;

interface VideoClipRenderer
{
    /**
     * Renderiza um único clipe a partir de um prompt já pronto (sem montar prompt a partir
     * de Action/produtos) e retorna os bytes crus do vídeo, sem subir pro storage.
     *
     * $options aceita: 'duration' (int, segundos), 'resolution' (string "WxH", usado só pelo
     * Gemini pra aspect ratio) e 'image_url' (string|null, foto de referência do produto).
     *
     * @return array{bytes: string, mime_type: string}
     */
    public function renderVideoClip(string $prompt, int $companyId, array $options = []): array;
}
