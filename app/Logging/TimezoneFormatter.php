<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;

class TimezoneFormatter
{
    /**
     * Formata a data dos logs com o offset do timezone da aplicação (ex.: 2026-09-11T14:03:22-03:00).
     */
    public function __invoke($logger)
    {
        // 'Y-m-d\\TH:i:sP' é o SIMPLE_DATE do Monolog: ele ignora o dateFormat e usa
        // microssegundos, a menos que o timestamp com microssegundos seja desligado.
        $logger->getLogger()->useMicrosecondTimestamps(false);

        foreach ($logger->getHandlers() as $handler) {
            if (! $handler instanceof \Monolog\Handler\FormattableHandlerInterface) {
                continue;
            }

            $handler->setFormatter(
                tap(new LineFormatter(null, 'Y-m-d\TH:i:sP', true, true), function ($formatter) {
                    $formatter->includeStacktraces();
                })
            );
        }
    }
}
