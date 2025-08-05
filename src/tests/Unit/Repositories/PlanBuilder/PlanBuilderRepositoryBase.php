<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PlanBuilder;

use App\Logging\ApiLogger;
use Aptive\Component\Http\HttpStatus;
use Exception;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class PlanBuilderRepositoryBase extends TestCase
{
    use RandomIntTestData;

    protected const API_KEY = 'aobOQHS0Ea1U9KtHFTBi64oiWMUj6ICfPcpyqWJn';
    protected const API_URL = 'https://test-api-1.testapi.com/staging';

    protected array $headers = [
        'Authorization' => 'Bearer ' . self::API_KEY,
        'Content-Type' => 'application/json'
    ];

    /**
     * @return ConfigRepository
     */
    protected function getConfigMock(): ConfigRepository
    {
        $configMock = $this->createMock(ConfigRepository::class);
        $configMock->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['planbuilder.api_url', null], ['planbuilder.api_key', null])
            ->willReturnOnConsecutiveCalls(self::API_URL, self::API_KEY);

        return $configMock;
    }

    /**
     * @return ApiLogger|(ApiLogger&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLoggerMockLoggingRequestAndResponse(): ApiLogger
    {
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::once())->method('logExternalResponse');

        return $loggerMock;
    }

    /**
     * @return ApiLogger|(ApiLogger&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLoggerMockLoggingRequestOnly(): ApiLogger
    {
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::never())->method('logExternalResponse');

        return $loggerMock;
    }

    /**
     * @param string $url
     * @param array $query
     * @param string $responseContent
     * @return HttpClient
     */
    protected function mockHttpGetRequest(string $url, array $query = [], string $responseContent = ''): HttpClient
    {
        $responseBodyMock = $this->createMock(StreamInterface::class);
        $responseBodyMock->expects(self::once())
            ->method('getContents')
            ->willReturn($responseContent);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects(self::once())
            ->method('getBody')
            ->willReturn($responseBodyMock);
        $responseMock->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(HttpStatus::OK);

        $clientMock = $this->createMock(HttpClient::class);
        $clientMock->expects(self::once())
            ->method('get')
            ->with(
                $url,
                [
                    'headers' => $this->headers,
                    'query' => $query,
                ]
            )
            ->willReturn($responseMock);

        return $clientMock;
    }

    /**
     * @param string $url
     * @param array $query
     * @return HttpClient
     */
    protected function mockHttpGetRequestToThrowException(string $url, array $query = []): HttpClient
    {
        $clientMock = $this->createMock(HttpClient::class);
        $clientMock->expects(self::once())
            ->method('get')
            ->with(
                $url,
                [
                    'headers' => $this->headers,
                    'query' => $query,
                ]
            )
            ->willThrowException(new Exception());

        return $clientMock;
    }
}
