<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProxyDownloadController
{
    public function download(Request $request): BinaryFileResponse|StreamedResponse
    {
        $request->validate(['url' => 'required|url']);

        $url = $request->query('url');

        // Arquivo local: serve direto do disco, evita deadlock com o servidor single-thread
        if ($this->isLocalUrl($url)) {
            return $this->serveLocalFile($url);
        }

        // Arquivo remoto: faz proxy via stream
        return $this->streamRemoteFile($url);
    }

    private function isLocalUrl(string $url): bool
    {
        $appUrl = rtrim(config('app.url'), '/');
        $host   = parse_url($url, PHP_URL_HOST);

        return str_starts_with($url, $appUrl)
            || in_array($host, ['localhost', '127.0.0.1', '::1']);
    }

    private function serveLocalFile(string $url): BinaryFileResponse
    {
        $path      = parse_url($url, PHP_URL_PATH); // /storage/generations/file.png
        $localPath = public_path(ltrim($path, '/'));

        abort_if(!file_exists($localPath), 404, 'Arquivo não encontrado.');

        return response()->download($localPath);
    }

    private function streamRemoteFile(string $url): StreamedResponse
    {
        // Tenta HEAD para pegar o Content-Type sem baixar o corpo
        try {
            $head        = Http::timeout(10)->head($url);
            $contentType = $head->header('Content-Type') ?? 'application/octet-stream';
        } catch (\Throwable) {
            $contentType = 'application/octet-stream';
        }

        $mimeBase = explode(';', $contentType)[0];
        $ext      = $this->extensionFromMime($mimeBase);
        $filename = 'genhub-' . now()->format('YmdHis') . '.' . $ext;

        return response()->stream(function () use ($url) {
            set_time_limit(0);

            $stream = fopen($url, 'rb');
            if ($stream === false) {
                return;
            }

            while (!feof($stream)) {
                echo fread($stream, 65536);
                flush();
            }

            fclose($stream);
        }, 200, [
            'Content-Type'        => $mimeBase,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Accel-Buffering'   => 'no',
            'Cache-Control'       => 'no-cache',
        ]);
    }

    private function extensionFromMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg'      => 'jpg',
            'image/jpg'       => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'image/gif'       => 'gif',
            'video/mp4'       => 'mp4',
            'video/webm'      => 'webm',
            'video/quicktime' => 'mov',
            default           => Str::after($mime, '/') ?: 'bin',
        };
    }
}
