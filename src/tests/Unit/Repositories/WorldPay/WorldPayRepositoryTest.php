<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\WorldPay;

use App\Repositories\WorldPay\WorldPayBaseRepository;
use App\Services\LogService;
use Aptive\Worldpay\CredentialsRepository\Credentials\Credentials;
use Aptive\Worldpay\CredentialsRepository\CredentialsRepository;
use GuzzleHttp\Psr7\Response as HttpResponse;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as ClientResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

abstract class WorldPayRepositoryTest extends TestCase
{
    protected const SERVICE_URL = 'https://service.example.com';
    protected const TRANSACTION_URL = 'https://transaction.example.com';
    protected const WORLDPAY_ACCOUNT_ID = '1027082';
    protected const WORLDPAY_ACCOUNT_TOKEN = '9B5D17D0F4D56D6615C4605B3F7506F792DF44F9311CA202F6DC9E463AB6E22E4D78FF01';
    protected const WORLDPAY_ACCEPTOR_ID = '4445047084090';
    protected const WORLDPAY_APPLICATION_ID = '8704';
    protected const WORLDPAY_APPLICATION_NAME = 'Aptive';
    protected const WORLDPAY_APPLICATION_VERSION = '1.00';

    protected LogService|MockObject $logServiceMock;
    protected CredentialsRepository|MockObject $credentialsRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->logServiceMock = $this->createMock(LogService::class);
        $this->credentialsRepositoryMock = $this->createMock(CredentialsRepository::class);
    }

    /**
     * @param array<string, scalar> $additionalParameters
     */
    final protected function getConfigurationRepositoryMock(array $additionalParameters = []): Repository|MockObject
    {
        $parameterKeys = array_merge(
            ['worldpay.service_url', 'worldpay.transaction_url'],
            array_keys($additionalParameters),
            [
                'worldpay.application.application_id',
                'worldpay.application.application_name',
                'worldpay.application.application_version',
                'worldpay.timeout',
            ],
        );
        $parameterValues = array_merge(
            [self::SERVICE_URL, self::TRANSACTION_URL],
            array_values($additionalParameters),
            [
                self::WORLDPAY_APPLICATION_ID,
                self::WORLDPAY_APPLICATION_NAME,
                self::WORLDPAY_APPLICATION_VERSION,
                WorldPayBaseRepository::REQUEST_TIMEOUT,
            ],
        );
        $defaultValues = [
            'worldpay.timeout' => WorldPayBaseRepository::REQUEST_TIMEOUT,
        ];

        $configMock = $this->createMock(Repository::class);
        $configMock
            ->expects(self::exactly(count($parameterKeys)))
            ->method('get')
            ->withConsecutive(
                ...array_map(
                    static fn (string $key) => [$key, $defaultValues[$key] ?? null],
                    $parameterKeys
                )
            )
            ->willReturnOnConsecutiveCalls(
                ...$parameterValues
            );

        return $configMock;
    }

    final protected function getRequestMock(string $responseXML): PendingRequest|MockObject
    {
        $httpMessage = new HttpResponse(body: $responseXML);
        $response = new ClientResponse($httpMessage);

        $requestMock = $this->createMock(PendingRequest::class);
        $requestMock
            ->expects(self::once())
            ->method('timeout')
            ->with(WorldPayBaseRepository::REQUEST_TIMEOUT)
            ->willReturnSelf();
        $requestMock
            ->expects(self::once())
            ->method('post')
            ->with(self::TRANSACTION_URL . '/?', [])
            ->willReturn($response);

        return $requestMock;
    }

    final protected function getCredentials(): Credentials
    {
        return new Credentials(
            '',
            '',
            '',
            '',
            self::WORLDPAY_ACCOUNT_ID,
            self::WORLDPAY_ACCOUNT_TOKEN,
            self::WORLDPAY_ACCEPTOR_ID,
            ''
        );
    }
}
