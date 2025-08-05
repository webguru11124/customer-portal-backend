<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @final
 */
class ApiLogger
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }
    /**
     * @param string $apiMethod
     * @param Request $request
     *
     * @return void
     */
    public function logRequest(string $apiMethod, Request $request): void
    {
        $this->logger->info("customer-portal-api.request $apiMethod", [
            'HTTP Method' => $request->method(),
            'Headers' => $request->header(),
            'URI' => $request->url(),
            'Body' => $request->post(),
            'Query params' => $request->query(),
        ]);
    }

    /**
     * @param string $message
     * @param string $uri
     * @param string $httpMethod
     * @param array<string, mixed> $parameters
     *
     * @return void
     */
    public function logExternalRequest(
        string $message,
        string $uri,
        string $httpMethod = 'GET',
        array $parameters = []
    ): void {
        $this->logger->info($message, [
            'HTTP Method' => $httpMethod,
            'URI' => $uri,
            'Parameters' => $parameters,
        ]);
    }

    /**
     * @param string $message
     * @param string[][] $headers
     * @param mixed $body
     * @param int $statusCode
     *
     * @return void
     */
    public function logExternalResponse(string $message, array|null $headers, mixed $body, int $statusCode): void
    {
        $this->logger->log(
            self::getLogLevelFromStatusCode($statusCode),
            $message,
            [
                'Headers' => $headers,
                'Body' => $body,
                'HTTP Status Code' => $statusCode,
            ]
        );
    }

    private static function getLogLevelFromStatusCode(int $statusCode): string
    {
        if ($statusCode >= 400 && $statusCode < 500) {
            return LogLevel::NOTICE;
        }

        if ($statusCode >= 500) {
            return LogLevel::ERROR;
        }

        return LogLevel::INFO;
    }
}
