<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use App\Logging\ApiLogger;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class ApiLoggerTest extends TestCase
{
    public function test_log_request(): void
    {
        $requestMock = $this->createMock(Request::class);
        $requestMock
            ->expects(self::once())
            ->method('method')
            ->willReturn('GET');
        $requestMock
            ->expects($this->once())
            ->method('header')
            ->willReturn([]);
        $requestMock
            ->expects($this->once())
            ->method('url')
            ->willReturn('https://example.com');
        $requestMock
            ->expects($this->once())
            ->method('post')
            ->willReturn(null);
        $requestMock
            ->expects($this->once())
            ->method('query')
            ->willReturn(null);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects(self::once())
            ->method('info')
            ->with(
                "customer-portal-api.request TEST",
                [
                    'HTTP Method' => 'GET',
                    'Headers' => [],
                    'URI' => 'https://example.com',
                    'Body' => null,
                    'Query params' => null,
                ]
            );

        $apiLogger = new ApiLogger($loggerMock);
        $apiLogger->logRequest('TEST', $requestMock);
    }

    public function test_log_external_request(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects(self::once())
            ->method('info')
            ->with(
                'MSG/TEST',
                [
                    'HTTP Method' => 'POST',
                    'URI' => 'https://example.com',
                    'Parameters' => ['foo' => 'bar'],
                ]
            );

        $apiLogger = new ApiLogger($loggerMock);
        $apiLogger->logExternalRequest('MSG/TEST', 'https://example.com', 'POST', ['foo' => 'bar']);
    }

    /**
     * @dataProvider responseStatusLogLevelProvider
     */
    public function test_log_external_response(int $status, string $logLevel): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects(self::once())
            ->method('log')
            ->with(
                $logLevel,
                'RESPONSE/TEST',
                [
                    'Headers' => ['Content-Type' => ['application/json']],
                    'Body' => 'BOOODY',
                    'HTTP Status Code' => $status,
                ]
            );

        $apiLogger = new ApiLogger($loggerMock);
        $apiLogger->logExternalResponse('RESPONSE/TEST', ['Content-Type' => ['application/json']], 'BOOODY', $status);
    }

    public static function responseStatusLogLevelProvider(): iterable
    {
        yield '200 OK' => [
            'status' => HttpStatus::OK,
            'logLevel' => LogLevel::INFO,
        ];
        yield '302 MOVED' => [
            'status' => HttpStatus::MOVED_PERMANENTLY,
            'logLevel' => LogLevel::INFO,
        ];
        yield '400 BAD REQUEST' => [
            'status' => HttpStatus::BAD_REQUEST,
            'logLevel' => LogLevel::NOTICE,
        ];
        yield '401 UNAUTHORIZED' => [
            'status' => HttpStatus::UNAUTHORIZED,
            'logLevel' => LogLevel::NOTICE,
        ];
        yield '500 INTERNAL ERROR' => [
            'status' => HttpStatus::INTERNAL_SERVER_ERROR,
            'logLevel' => LogLevel::ERROR,
        ];
        yield '501 NOT IMPLEMENTED' => [
            'status' => HttpStatus::NOT_IMPLEMENTED,
            'logLevel' => LogLevel::ERROR,
        ];
    }
}
