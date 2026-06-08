<?php

namespace App\Console\Commands;

use App\Models\Action;
use App\Models\Template;
use App\Services\GeminiService;
use Illuminate\Console\Command;

class GenerateTemplatePreviews extends Command
{
    protected $signature = 'templates:generate-previews
        {--type=all  : Tipo a processar: image, video ou all}
        {--user=     : Processar apenas templates de um user_id específico}
        {--limit=    : Máximo de templates a processar por execução}
        {--delay=    : Segundos de espera entre requests (padrão: 4 imagem / 10 vídeo)}
        {--force     : Regerar mesmo para templates que já possuem prévia}';

    protected $description = 'Gera prévias (imagem ou vídeo) para os modelos de arte que ainda não possuem preview_path';

    public function __construct(private GeminiService $gemini)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $type = $this->option('type');

        if (! in_array($type, ['image', 'video', 'all'])) {
            $this->error("--type inválido. Use: image, video ou all.");
            return 1;
        }

        $query = Template::query()->with('user');

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if (! $this->option('force')) {
            $query->whereNull('preview_path');
        }

        if ($userId = $this->option('user')) {
            $query->where('user_id', (int) $userId);
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $templates = $query->get();

        if ($templates->isEmpty()) {
            $this->info('✓ Nenhum template pendente encontrado.');
            return 0;
        }

        $total = $templates->count();

        $this->info("Processando {$total} template(s) — tipo: {$type}");
        $this->newLine();

        $bar     = $this->output->createProgressBar($total);
        $success = 0;
        $errors  = [];

        $bar->start();

        foreach ($templates as $template) {
            // Timeout e delay variam por tipo
            $isVideo     = $template->type === 'video';
            $defaultDelay = $isVideo ? 10 : 4;
            $delay        = $this->option('delay') !== null
                ? (int) $this->option('delay')
                : $defaultDelay;

            if ($isVideo) {
                set_time_limit(360);
            }

            try {
                $action = new Action([
                    'platform'   => 'instagram',
                    'type'       => $isVideo ? 'reel' : 'post',
                    'brief'      => $template->prompt,
                    'resolution' => $isVideo ? '1080x1920' : '1080x1080',
                    'quantity'   => 1,
                ]);

                $result = $isVideo
                    ? $this->gemini->generateVideo($action, collect(), null)
                    : $this->gemini->generateImage($action, collect(), null);

                $path = $result['assets'][0]['path'] ?? null;

                if ($path) {
                    $template->update(['preview_path' => $path]);
                    $success++;
                } else {
                    $errors[] = "[{$template->title}] API retornou sem assets.";
                }
            } catch (\Throwable $e) {
                $errors[] = "[{$template->title}] " . $e->getMessage();
            }

            $bar->advance();

            if ($delay > 0) {
                sleep($delay);
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ {$success}/{$total} prévias geradas com sucesso.");

        if (! empty($errors)) {
            $this->newLine();
            $this->warn(count($errors) . ' erro(s):');
            foreach ($errors as $err) {
                $this->line("  ✗ {$err}");
            }
        }

        return empty($errors) ? 0 : 1;
    }
}
