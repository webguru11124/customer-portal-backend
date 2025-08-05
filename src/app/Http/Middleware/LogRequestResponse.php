<?php

namespace App\Http\Middleware;

use App\Services\LogService;
use Aptive\Illuminate\Http\JsonApi\JsonApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LogRequestResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $requestId = Str::uuid()->toString();

        $request->merge(['request_id' => $requestId]);

        Log::info(LogService::REQUEST_RECEIVED, [
            'request' => [
                'id' => $requestId,
                'location' => $request->fullUrl(),
                'query_params' => $request->query(),
                'body' => $request->all(),
                'headers' => $request->headers->all(),
            ],
        ]);

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * @param Request $request
     * @param Response $response
     *
     * @return void
     */
    public function terminate(
        Request $request,
        Response $response
    ): void {
        if ($request->method() === Request::METHOD_OPTIONS) {
            return;
        }

        $logData = [
            'response' => [
                'id' => $request->get('request_id'),
                'status' => $response->getStatusCode(),
                'body' => $this->setDataIfRequested($response),
                'headers' => $response->headers->all(),
                'response_time' => $this->getResponseTime(),
            ],
        ];

        if ($response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            Log::notice(LogService::REQUEST_PROCESSED, $logData);
        } else {
            Log::info(LogService::REQUEST_PROCESSED, $logData);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function setDataIfRequested(
        Response $response
    ): array {
        if ($response instanceof JsonResponse) {
            return $response->getData(true);
        }

        if ($response instanceof JsonApiResponse) {
            /* @noinspection JsonEncodingApiUsageInspection */
            return json_decode($response->getContent() ?: '{}', true);
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return ['data' => sprintf('Binary file %s', $response->headers->get('Content-Disposition'))];
        }

        return [];
    }

    private function getResponseTime(): float|null
    {
        // @codeCoverageIgnoreStart
        if (!defined('LARAVEL_START')) {
            return null;
        }
        // @codeCoverageIgnoreEnd

        return microtime(true) - LARAVEL_START;
    }
}
