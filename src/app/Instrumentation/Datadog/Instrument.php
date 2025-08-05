<?php

namespace App\Instrumentation\Datadog;

use Throwable;

final class Instrument
{
    /**
     * Creates a Datadog Error/Issue for Error Tracking: https://docs.datadoghq.com/tracing/error_tracking/.
     *
     * @param Throwable $throwable
     *
     * @return void
     */
    public static function error(Throwable $throwable): void
    {
        if (self::canGetActiveSpan()) {
            $span = \DDTrace\active_span();
            $span->meta['error.msg'] = $throwable->getMessage();
            $span->meta['error.type'] = get_class($throwable);
            $span->meta['error.stack'] = $throwable->getTraceAsString();
        }
    }

    private static function canGetActiveSpan(): bool
    {
        return function_exists('\DDTrace\active_span') && \DDTrace\active_span() !== null;
    }
}
