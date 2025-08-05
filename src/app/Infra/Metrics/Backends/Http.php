<?php

declare(strict_types=1);

namespace App\Infra\Metrics\Backends;

use App\Infra\Metrics\Backend;
use App\Infra\Metrics\EventPayload;
use App\Infra\Metrics\TrackedEventName;
use App\Services\LogService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http as HttpClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InfluxDB2\Client;
use InfluxDB2\Point;

final class Http implements Backend
{
    private const REQUEST_TIMEOUT = 5;

    public function __construct(
        private Client $influxDBClient,
        private readonly string $url,
        private readonly string $token,
        private readonly int $timeout = self::REQUEST_TIMEOUT,
    ) {
    }

    public function storeEvent(EventPayload $eventPayload): void
    {
        $requestId = Str::uuid()->toString();
        $start = microtime(true);

        /** @var Response $response */
        $response = HttpClient::withToken($this->token)
            ->timeout($this->timeout)
            ->beforeSending(function (Request $request) use ($requestId) {
                Log::debug(LogService::APP_METRICS_REQUEST, [
                    'request_id' => $requestId,
                    'url' => $request->url(),
                    'method' => $request->method(),
                    'headers' => $request->headers(),
                    'body' => $request->body(),
                ]);
            })
            ->asJson()
            ->post(
                $this->url,
                [$eventPayload],
            );

        $writeApi = $this->influxDBClient->createWriteApi();
        $eventName = $eventPayload->eventName->name;
        $point = Point::measurement($eventName)
            ->addField('account_id', $eventPayload->data['accountNumber']);

        if($eventPayload->eventName == TrackedEventName::PaymentMade) {
            $point->addField('amount', $eventPayload->data['quantity']);
        } else {
            $point->addField('count', 1);
        }
        $writeApi->write($point);

        Log::debug(LogService::APP_METRICS_RESPONSE, [
            'request_id' => $requestId,
            'status' => $response->status(),
            'body' => $response->body(),
            'duration' => microtime(true) - $start,
        ]);
    }
}
