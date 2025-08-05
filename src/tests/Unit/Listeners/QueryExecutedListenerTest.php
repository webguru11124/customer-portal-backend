<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Listeners\QueryExecutedListener;
use App\Services\LogService;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class QueryExecutedListenerTest extends TestCase
{
    private const SQL = 'SELECT 1';
    private const BINDINGS = ['a' => 'b'];
    private const QUERY_TIME = 112.3;

    public function test_listener_logs_query(): void
    {
        $connectionMock = \Mockery::mock(Connection::class);
        $connectionMock->expects('getName')->once()->withNoArgs()->andReturn('connection');

        $event = new QueryExecuted(self::SQL, self::BINDINGS, self::QUERY_TIME, $connectionMock);

        Log::expects('debug')
            ->times(1)
            ->withArgs([
                LogService::DATABASE_QUERY_EXECUTED,
                [
                    'SQL' => self::SQL,
                    'bindings' => self::BINDINGS,
                    'execution_time' => self::QUERY_TIME . 'ms',
                ],
            ]);

        $listener = new QueryExecutedListener();
        $listener->handle($event);
    }
}
