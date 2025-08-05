<?php

namespace Tests\Unit\Services;

use App\Services\LogService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class LogServiceTest extends TestCase
{
    protected $start;
    protected $end;
    protected $formattedStart;
    protected $formattedEnd;
    protected $sessionId = 234242343;

    public function setUp(): void
    {
        parent::setUp();

        $this->start = Carbon::create(2022, 03, 14, 17, 26, 16);
        $this->end = Carbon::create(2022, 03, 14, 17, 26, 19);
        $this->formattedStart = '2022-03-14 17:26:16.000';
        $this->formattedEnd = '2022-03-14 17:26:19.000';
    }

    public function test_it_gets_current_formated_date_time(): void
    {
        $getCurrentFormattedDateTime = $this->callProtectedMethod(LogService::class, 'getCurrentFormattedDateTime');
        $logService = new LogService();

        Carbon::setTestNow($this->start);
        $formattedDateTime = $getCurrentFormattedDateTime->invoke($logService);

        $this->assertEquals($this->formattedStart, $formattedDateTime);
    }

    public function test_it_gets_time_info_when_start_time_is_empty(): void
    {
        $getCurrentFormattedDateTime = $this->callProtectedMethod(LogService::class, 'getTimeInfo');
        $logService = new LogService();

        Carbon::setTestNow($this->start);
        $timeInfo = $getCurrentFormattedDateTime->invoke($logService);

        $this->assertEquals([
            'start' => $this->formattedStart,
            'end' => null,
            'elapsed' => null,
        ], $timeInfo);
    }

    public function test_it_gets_time_info_when_start_time_is_not_empty(): void
    {
        $getCurrentFormattedDateTime = $this->callProtectedMethod(LogService::class, 'getTimeInfo');
        $logService = new LogService();

        Carbon::setTestNow($this->end);
        $timeInfo = $getCurrentFormattedDateTime->invoke($logService, $this->formattedStart);

        $this->assertEquals([
            'start' => $this->formattedStart,
            'end' => $this->formattedEnd,
            'elapsed' => 3000,
        ], $timeInfo);
    }

    public function test_it_logs_info(): void
    {
        $payload = [
            'foo' => 'bar',
        ];
        Session::shouldReceive('getId')->andReturn($this->sessionId);
        Carbon::setTestNow($this->start);
        Log::shouldReceive('info')
            ->with(
                LogService::CREATE_TRANSACTION_SETUP_PAYLOAD,
                [
                    'session_id' => $this->sessionId,
                    'payload' => $payload,
                    'start' => $this->formattedStart,
                    'end' => null,
                    'elapsed' => null,
                ]
            );

        $logService = new LogService();

        $this->assertSame(
            $this->formattedStart,
            $logService->logInfo(LogService::CREATE_TRANSACTION_SETUP_PAYLOAD, $payload)
        );
    }

    public function test_it_logs_a_throable(): void
    {
        $code = 550;
        $message = 'This is and exception';

        $exception = new Exception($message, $code);
        Session::shouldReceive('getId')->andReturn($this->sessionId);
        Carbon::setTestNow($this->end);
        Log::shouldReceive('error')
            ->with(
                LogService::CREATE_TRANSACTION_SETUP_PAYLOAD,
                [
                'session_id' => $this->sessionId,
                'error' => [
                    'message' => $message,
                    'code' => $code,
                ],
                'start' => $this->formattedStart,
                'end' => $this->formattedEnd,
                'elapsed' => 3000,
            ]
            );

        $logService = new LogService();

        $this->assertSame(
            $this->formattedStart,
            $logService->logThrowable(LogService::CREATE_TRANSACTION_SETUP_PAYLOAD, $exception, $this->formattedStart)
        );
    }
}
