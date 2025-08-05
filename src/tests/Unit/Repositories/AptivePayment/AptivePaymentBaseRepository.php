<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\AptivePayment;

use App\DTO\CleoCrm\AccountDTO;
use App\Helpers\PaymentMethodValidator;
use App\Interfaces\Repository\CleoCrmRepository;
use App\Logging\ApiLogger;
use Aptive\Component\Http\HttpStatus;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\RandomStringTestData;

class AptivePaymentBaseRepository extends TestCase
{
    use RandomIntTestData;
    use RandomStringTestData;

    protected const API_KEY = 'zymHHWdAfuK3LuE9zPMo';
    protected const API_URL = 'https://test-api.test-payment-service.tst.com';

    protected array $headers = [
        'Api-Key' => self::API_KEY,
        'Content-Type' => 'application/json',
    ];

    protected function getConfigMock(): ConfigRepository
    {
        $configMock = $this->createMock(ConfigRepository::class);
        $configMock
            ->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['payment.api_url', null], ['payment.api_key', null])
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
     * @return ApiLogger|(ApiLogger&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLoggerMockLoggingNothing(): ApiLogger
    {
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::never())->method('logExternalRequest');
        $loggerMock->expects(self::never())->method('logExternalResponse');

        return $loggerMock;
    }

    protected function mockHttpGetRequest(string $url, array $query = [], string $responseContent = ''): HttpClient
    {
        if (array_key_exists('customer_id', $query)) {
            $query['account_id'] = $this->getTestCrmAccountUuid();
            unset($query['customer_id']);
        }

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

    protected function mockHttpPostRequest(string $url, array $options = [], string $responseContent = ''): HttpClient
    {
        if (array_key_exists('customer_id', $options)) {
            $options['account_id'] = $this->getTestCrmAccountUuid();
            unset($options['customer_id']);
        }

        $clientMock = $this->createMock(HttpClient::class);
        $clientMock->expects(self::once())
            ->method('post')
            ->with(
                $url,
                [
                    'headers' => $this->headers,
                    'json' => $options,
                ]
            )
            ->willReturn($this->setupStreamAndResponse($responseContent));

        return $clientMock;
    }

    protected function mockHttpPatchRequest(string $url, array $options = [], string $responseContent = ''): HttpClient
    {
        if (array_key_exists('customer_id', $options)) {
            $options['account_id'] = $this->getTestCrmAccountUuid();
            unset($options['customer_id']);
        }

        $clientMock = $this->createMock(HttpClient::class);
        $clientMock->expects(self::once())
            ->method('patch')
            ->with(
                $url,
                [
                    'headers' => $this->headers,
                    'json' => $options,
                ]
            )
            ->willReturn($this->setupStreamAndResponse($responseContent));

        return $clientMock;
    }

    protected function mockHttpDeleteRequest(string $url, string $responseContent = ''): HttpClient
    {
        $clientMock = $this->createMock(HttpClient::class);
        $clientMock->expects(self::once())
            ->method('delete')
            ->with(
                $url,
                [
                    'headers' => $this->headers,
                ]
            )
            ->willReturn($this->setupStreamAndResponse($responseContent));

        return $clientMock;
    }

    protected function mockHttpGetRequestToThrowException(string $url, array $query = []): HttpClient
    {
        if (array_key_exists('customer_id', $query)) {
            $query['account_id'] = $this->getTestCrmAccountUuid();
            unset($query['customer_id']);
        }

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

    protected function mockHttpPostRequestToThrowException(string $url, array $options = []): HttpClient
    {
        if (array_key_exists('customer_id', $options)) {
            $options['account_id'] = $this->getTestCrmAccountUuid();
            unset($options['customer_id']);
        }

        $clientMock = $this->createMock(HttpClient::class);
        $clientMock->expects(self::once())
            ->method('post')
            ->with(
                $url,
                [
                    'headers' => $this->headers,
                    'json' => $options,
                ]
            )
            ->willThrowException(new \Exception());

        return $clientMock;
    }

    protected function mockHttpPatchRequestToThrowException(string $url, array $options = []): HttpClient
    {
        if (array_key_exists('customer_id', $options)) {
            $options['account_id'] = $this->getTestCrmAccountUuid();
            unset($options['customer_id']);
        }

        $clientMock = $this->createMock(HttpClient::class);
        $clientMock->expects(self::once())
            ->method('patch')
            ->with(
                $url,
                [
                    'headers' => $this->headers,
                    'json' => $options,
                ]
            )
            ->willThrowException(new \Exception());

        return $clientMock;
    }

    protected function mockHttpDeleteRequestToThrowException(string $url): HttpClient
    {
        $clientMock = $this->createMock(HttpClient::class);
        $clientMock->expects(self::once())
            ->method('delete')
            ->with(
                $url,
                [
                    'headers' => $this->headers,
                ]
            )
            ->willThrowException(new \Exception());

        return $clientMock;
    }

    /**
     * @return PaymentMethodValidator
     */
    protected function getPaymentMethodValidator(): PaymentMethodValidator
    {
        return new PaymentMethodValidator();
    }

    protected function getCleoCrmRepositoryMock(): CleoCrmRepository
    {
        $cleoCrmRepositoryMock = $this->createMock(CleoCrmRepository::class);
        $cleoCrmRepositoryMock
            ->method('getAccount')
            ->with($this->getTestAccountNumber())
            ->willReturn(new AccountDTO(
                id: $this->getTestCrmAccountUuid(),
                externalRefId: $this->getTestAccountNumber(),
                areaId: 1,
                dealerId: 1,
                contactId: '1',
                billingContactId: '1',
                serviceAddressId: '',
                billingAddressId: '',
                isActive: true,
                paidInFull: true,
                balanceAge: 1,
                responsibleBalanceAge: 1,
                preferredBillingDayOfMonth: 1,
                smsReminders: false,
                phoneReminders: false,
                emailReminders: false,
                createdAt: '',
                updatedAt: ''
            ));

        return $cleoCrmRepositoryMock;
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
