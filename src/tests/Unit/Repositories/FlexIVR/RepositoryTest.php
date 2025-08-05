<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\FlexIVR;

use Aptive\Component\Http\HttpStatus;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

abstract class RepositoryTest extends TestCase
{
    use RandomIntTestData;

    protected const API_KEY = '1234567890';

    protected function getConfigMock(): Repository
    {
        $configMock = $this->createMock(ConfigRepository::class);
        $configMock
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['flex_ivr.api_url', null],
                ['flex_ivr.api_key', null],
            )
            ->willReturnOnConsecutiveCalls(
                'https://example.com',
                self::API_KEY,
            );

        return $configMock;
    }

    protected function mockGetHttpRequest(string $url, array $query, string $responseContent): HttpClient
    {
        $responseBodyMock = $this->createMock(StreamInterface::class);
        $responseBodyMock->expects(self::once())->method('getContents')->willReturn($responseContent);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects(self::once())->method('getBody')->willReturn($responseBodyMock);
        $responseMock->expects(self::once())->method('getStatusCode')->willReturn(HttpStatus::OK);

        $clientMock = $this->createMock(HttpClient::class);
        $clientMock
            ->expects(self::once())
            ->method('get')
            ->with(
                $url,
                [
                    'headers' => [
                        'X-API-KEY' => self::API_KEY,
                        'Content-Type' => 'application/json'
                    ],
                    'query' => $query,
                ]
            )
            ->willReturn($responseMock);

        return $clientMock;
    }

    protected function mockPutHttpRequest(string $url, array $data, string $responseContent): HttpClient
    {
        $responseBodyMock = $this->createMock(StreamInterface::class);
        $responseBodyMock->expects(self::once())->method('getContents')->willReturn($responseContent);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects(self::once())->method('getBody')->willReturn($responseBodyMock);
        $responseMock->expects(self::once())->method('getStatusCode')->willReturn(HttpStatus::CREATED);

        $clientMock = $this->createMock(HttpClient::class);
        $clientMock
            ->expects(self::once())
            ->method('put')
            ->with(
                $url,
                [
                    'headers' => [
                        'X-API-KEY' => self::API_KEY,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $data,
                ]
            )
            ->willReturn($responseMock);

        return $clientMock;
    }
}
