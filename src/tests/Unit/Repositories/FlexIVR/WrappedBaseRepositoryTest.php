<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\FlexIVR;

use App\Repositories\FlexIVR\BaseRepository;
use App\Repositories\FlexIVR\WrappedBaseRepository;
use Aptive\Component\Http\HttpStatus;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Traits\RandomIntTestData;

class WrappedBaseRepositoryTest extends RepositoryTest
{
    use RandomIntTestData;

    private const API_KEY_CHECK = '1234567890';
    private const API_KEY_CHECK_MD5 = 'fd85e62d9beb45428771ec688418b271';

    public function test_instance_of(): void
    {
        $this->assertInstanceOf(
            BaseRepository::class,
            $this->createMock(WrappedBaseRepository::class)
        );
    }

    protected function getConfigMock(): Repository
    {
        $configMock = $this->createMock(ConfigRepository::class);
        $configMock
            ->expects(self::exactly(5))
            ->method('get')
            ->withConsecutive(
                ['flex_ivr.api_url', null],
                ['flex_ivr.api_key', null],
                ['flex_ivr.api_wrapper_url', null],
                ['flex_ivr.api_wrapper_key_check', null],
                ['flex_ivr.api_key', null],
            )
            ->willReturnOnConsecutiveCalls(
                'https://example.com',
                self::API_KEY,
                'https://example.com',
                self::API_KEY_CHECK,
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
                        'Content-Type' => 'application/json',
                        'X-Api-Key-Check' => self::API_KEY_CHECK_MD5,
                    ],
                    'query' => $query,
                ]
            )
            ->willReturn($responseMock);

        return $clientMock;
    }
}
