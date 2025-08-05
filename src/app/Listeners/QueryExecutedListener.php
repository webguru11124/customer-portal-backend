<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\LogService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

final class QueryExecutedListener
{
    /**
     * Handle the event.
     *
     * @param QueryExecuted $event
     *
     * @return void
     */
    public function handle(QueryExecuted $event): void
    {
        Log::debug(LogService::DATABASE_QUERY_EXECUTED, [
            'SQL' => $event->sql,
            'bindings' => $event->bindings,
            'execution_time' => $event->time . 'ms',
        ]);
    }
}
