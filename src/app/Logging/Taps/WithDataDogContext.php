<?php

declare(strict_types=1);

namespace App\Logging\Taps;

use Illuminate\Log\Logger;

/**
 * @codeCoverageIgnore
 */
final class WithDataDogContext
{
    public function __invoke(Logger $logger): void
    {
        if (!function_exists('\DDTrace\logs_correlation_trace_id') ||
            !function_exists('\dd_trace_peek_span_id')
        ) {
            return;
        }

        $trace = \DDTrace\logs_correlation_trace_id();
        $span =  \dd_trace_peek_span_id();

        $logger->pushProcessor(function (array $record) use ($trace, $span): array {
            $record['message'] .= sprintf(
                ' [dd.trace_id=%s dd.span_id=%s]',
                $trace,
                $span
            );

            return $record;
        });
    }
}
