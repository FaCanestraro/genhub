<?php

namespace App\Jobs;

use App\Models\Publication;
use App\Services\AuditLogger;
use App\Services\MetaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishToSocial implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Reels/carrossel com vídeo esperam o processamento na Meta (até ~5min por container).
    public int $timeout = 1200;
    public int $tries = 1;

    public function __construct(private int $publicationId) {}

    public function handle(MetaService $meta): void
    {
        $publication = Publication::with('socialAccount')->find($this->publicationId);
        if (!$publication || !$publication->socialAccount) return;

        $publication->update(['status' => 'processing']);
        $account = $publication->socialAccount;
        $where = ucfirst($account->provider) . " \"{$account->name}\"";

        try {
            $result = $meta->publish($publication);

            $publication->update([
                'status' => 'published',
                'external_id' => $result['id'],
                'permalink' => $result['permalink'],
                'published_at' => now(),
            ]);

            AuditLogger::log('social', 'publication.published', "Publicação enviada para {$where}", [
                'subject' => $publication,
                'company_id' => $publication->company_id,
                'causer_id' => $publication->user_id,
                'output' => $result,
            ]);
        } catch (\Throwable $e) {
            $publication->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            AuditLogger::log('social', 'publication.failed', "Falha ao publicar em {$where}", [
                'subject' => $publication,
                'company_id' => $publication->company_id,
                'causer_id' => $publication->user_id,
                'output' => ['error' => $e->getMessage()],
                'status' => 'failed',
            ]);
        }
    }
}
