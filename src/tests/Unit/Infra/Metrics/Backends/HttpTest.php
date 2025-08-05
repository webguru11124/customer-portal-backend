<?php

declare(strict_types=1);

namespace Tests\Unit\Infra\Metrics\Backends;

use App\Infra\Metrics\Backends\Http as HttpBackend;
use App\Infra\Metrics\EventPayload;
use App\Infra\Metrics\TrackedEventName;
use App\Services\LogService;
use Aptive\Component\Http\HttpStatus;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http as HttpFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InfluxDB2\Client;
use InfluxDB2\WriteApi;
use InfluxDB2\Point;
use Mockery;
use Tests\CreatesApplication;

class HttpTest extends TestCase
{
    use CreatesApplication;

    private const URL = 'https://example.com';
    private const TOKEN = 'ttt';

    public function test_it_sends_event_data(): void
    {
        $influxDBClientMock = Mockery::mock(Client::class);
        $influxDBWriteApiMock = Mockery::mock(WriteApi::class);

        Carbon::setTestNow(Carbon::createFromTimestamp(1234567890));

        $requestId = Str::freezeUuids()->toString();

        $eventPayload = new EventPayload(
            TrackedEventName::AppointmentScheduled,
            [
                'accountNumber' => 99999999,
            ]
        );

        Log::expects('debug')
            ->withArgs(function (string $message, array $context) use ($eventPayload, $requestId): bool {
                $this->assertSame(LogService::APP_METRICS_REQUEST, $message);
                $this->assertSame(self::URL, $context['url']);
                $this->assertSame('POST', $context['method']);
                $this->assertSame(
                    [sprintf('Bearer %s', self::TOKEN)],
                    $context['headers']['Authorization']
                );
                $this->assertSame(
                    json_encode([$eventPayload], JSON_THROW_ON_ERROR),
                    $context['body']
                );
                $this->assertSame($requestId, $context['request_id']);

                return true;
            })
            ->once();

        Log::expects('debug')
            ->withArgs(function (string $message, array $context) use ($requestId): bool {
                $this->assertSame(LogService::APP_METRICS_RESPONSE, $message);
                $this->assertSame($requestId, $context['request_id']);
                $this->assertSame(HttpStatus::OK, $context['status']);
                $this->assertSame('[]', $context['body']);
                $this->assertNotNull($context['duration']);

                return true;
            })
            ->once();

        HttpFacade::preventStrayRequests();
        HttpFacade::fake(function (Request $request) use ($eventPayload): PromiseInterface {
            $this->assertSame('POST', $request->method());
            $this->assertSame(self::URL, $request->url());
            $this->assertSame([sprintf('Bearer %s', self::TOKEN)], $request->header('Authorization'));
            $this->assertTrue($request->isJson());
            $this->assertEquals([$eventPayload], $request->data());

            return HttpFacade::response([]);
        });

        $influxDBWriteApiMock->shouldReceive('write')
            ->with(Mockery::type('InfluxDB2\Point'))
            ->once();

        $influxDBClientMock->shouldReceive('createWriteApi')
            ->andReturn($influxDBWriteApiMock)
            ->once();

        $backend = new HttpBackend($influxDBClientMock, self::URL, self::TOKEN);
        $backend->storeEvent($eventPayload);

        Str::createUuidsNormally();
        Carbon::setTestNow(null);
    }

    public function test_it_sends_event_data_for_payment_event(): void
    {
        $influxDBClientMock = Mockery::mock(Client::class);
        $influxDBWriteApiMock = Mockery::mock(WriteApi::class);

        Carbon::setTestNow(Carbon::createFromTimestamp(1234567890));

        $requestId = Str::freezeUuids()->toString();

        $eventPayload = new EventPayload(
            TrackedEventName::PaymentMade,
            [
                'accountNumber' => 99999999,
                'quantity' => 101.94,
            ]
        );

        Log::expects('debug')
            ->withArgs(function (string $message, array $context) use ($eventPayload, $requestId): bool {
                $this->assertSame(LogService::APP_METRICS_REQUEST, $message);
                $this->assertSame(self::URL, $context['url']);
                $this->assertSame('POST', $context['method']);
                $this->assertSame(
                    [sprintf('Bearer %s', self::TOKEN)],
                    $context['headers']['Authorization']
                );
                $this->assertSame(
                    json_encode([$eventPayload], JSON_THROW_ON_ERROR),
                    $context['body']
                );
                $this->assertSame($requestId, $context['request_id']);

                return true;
            })
            ->once();

        Log::expects('debug')
            ->withArgs(function (string $message, array $context) use ($requestId): bool {
                $this->assertSame(LogService::APP_METRICS_RESPONSE, $message);
                $this->assertSame($requestId, $context['request_id']);
                $this->assertSame(HttpStatus::OK, $context['status']);
                $this->assertSame('[]', $context['body']);
                $this->assertNotNull($context['duration']);

                return true;
            })
            ->once();

        HttpFacade::preventStrayRequests();
        HttpFacade::fake(function (Request $request) use ($eventPayload): PromiseInterface {
            $this->assertSame('POST', $request->method());
            $this->assertSame(self::URL, $request->url());
            $this->assertSame([sprintf('Bearer %s', self::TOKEN)], $request->header('Authorization'));
            $this->assertTrue($request->isJson());
            $this->assertEquals([$eventPayload], $request->data());

            return HttpFacade::response([]);
        });

        $influxDBWriteApiMock->shouldReceive('write')
            ->withArgs(function (Point $point) {
                $this->assertStringContainsString('amount=101.94', $point->toLineProtocol());
                return true;

            })
            ->once();

        $influxDBClientMock->shouldReceive('createWriteApi')
            ->andReturn($influxDBWriteApiMock)
            ->once();

        $backend = new HttpBackend($influxDBClientMock, self::URL, self::TOKEN);
        $backend->storeEvent($eventPayload);

        Str::createUuidsNormally();
        Carbon::setTestNow(null);
    }
}
