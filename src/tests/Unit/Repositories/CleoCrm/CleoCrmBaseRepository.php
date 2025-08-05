<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\CleoCrm;

use App\Logging\ApiLogger;
use Aptive\Component\Http\HttpStatus;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class CleoCrmBaseRepository extends TestCase
{
    use RandomIntTestData;

    protected const API_AUTH_ACCOUNT_ID = 'c14e5a26-39a3-3f92-b89c-5c9e3dg905f4';
    protected const API_URL = 'https://test-crm.staging.com';

    protected array $headers = [
        'Aptive-Api-Account-ID' => self::API_AUTH_ACCOUNT_ID,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    /**
     * @return ConfigRepository
     */
    protected function getConfigMock(): ConfigRepository
    {
        $configMock = $this->createMock(ConfigRepository::class);
        $configMock->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['cleo_crm.api_url', null], ['cleo_crm.api_account_id', null])
            ->willReturnOnConsecutiveCalls(self::API_URL, self::API_AUTH_ACCOUNT_ID);

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

    protected function mockHttpGetRequest(string $url, array $query = [], string $responseContent = ''): HttpClient
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
            ->willReturn($this->setupStreamAndResponse($responseContent));

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
            ->willThrowException(new \Exception());

        return $clientMock;
    }

    private function setupStreamAndResponse(string $responseContent = ''): MockObject
    {
        $responseBodyMock = $this->createMock(StreamInterface::class);
        $responseBodyMock
            ->expects(self::once())
            ->method('getContents')
            ->willReturn($responseContent);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock
            ->expects(self::once())
            ->method('getBody')
            ->willReturn($responseBodyMock);
        $responseMock
            ->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(HttpStatus::OK);

        return $responseMock;
    }
}
