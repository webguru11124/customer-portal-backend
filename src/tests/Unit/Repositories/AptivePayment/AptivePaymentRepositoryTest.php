<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\AptivePayment;

use App\DTO\Payment\AchPaymentMethod;
use App\DTO\Payment\AuthAndCapture;
use App\DTO\Payment\AuthAndCaptureRequestDTO;
use App\DTO\Payment\AutoPayStatus;
use App\DTO\Payment\AutoPayStatusRequestDTO;
use App\DTO\Payment\CreatePaymentProfileRequestDTO;
use App\DTO\Payment\CreditCardPaymentMethod;
use App\DTO\Payment\Payment;
use App\DTO\Payment\PaymentMethodsListRequestDTO;
use App\DTO\Payment\PaymentProfile;
use App\DTO\Payment\PaymentsListRequestDTO;
use App\DTO\Payment\TokenexAuthKeys;
use App\DTO\Payment\TokenexAuthKeysRequestDTO;
use App\DTO\Payment\ValidateCreditCardTokenRequestDTO;
use App\Enums\Models\Payment\PaymentGateway;
use App\Enums\Models\PaymentProfile\CardType;
use App\Enums\Models\PaymentProfile\PaymentMethod as PaymentMethodEnum;
use App\Enums\PaymentService\PaymentProfile\AccountType;
use App\Events\Payment\PaymentMade;
use App\Exceptions\Account\CleoCrmAccountNotFoundException;
use App\Interfaces\Repository\CleoCrmRepository;
use App\Logging\ApiLogger;
use App\Exceptions\Payment\CreditCardTokenNotFoundException;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Event;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\RandomStringTestData;

class AptivePaymentRepositoryTest extends AptivePaymentBaseRepository
{
    use RandomIntTestData;
    use RandomStringTestData;

    public function test_it_returns_tokenex_auth_keys(): void
    {
        $clientMock = $this->mockHttpPostRequest(
            url: self::API_URL . '/' . AptivePaymentRepository::TOKENEX_AUTH_KEYS_ENDPOINT,
            options: $this->setupTokenexAuthKeysRequestDTO()->toArray(),
            responseContent: '{
                "_metadata": {
                    "success": true,
                    "links": {
                        "self": "https://test-api.test-payment-service.tst.com/api/v1/gateways/tokenex/authentication-keys"
                    }
                },
                "result": {
                    "message": "TokenEx Authentication Key generated successfully.",
                    "authentication_key": "YmY0RWYfi3YmY0RWYfi3YmY0RWYfi3"
                }
            }',
        );

        $result = $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->getTokenexAuthKeys($this->setupTokenexAuthKeysRequestDTO());

        $this->assertInstanceOf(TokenexAuthKeys::class, $result);
        $this->assertEquals('TokenEx Authentication Key generated successfully.', $result->message);
        $this->assertEquals('YmY0RWYfi3YmY0RWYfi3YmY0RWYfi3', $result->authenticationKey);
    }

    public function test_get_tokenex_auth_keys_returns_exception(): void
    {
        $clientMock = $this->mockHttpPostRequestToThrowException(
            url: self::API_URL . '/' . AptivePaymentRepository::TOKENEX_AUTH_KEYS_ENDPOINT,
            options: $this->setupTokenexAuthKeysRequestDTO()->toArray()
        );

        $this->expectException(\Exception::class);

        $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestOnly())
            ->getTokenexAuthKeys($this->setupTokenexAuthKeysRequestDTO());
    }

    public function test_it_update_auto_pay_status(): void
    {
        $requestDTO = new AutoPayStatusRequestDTO(
            customerId: $this->getTestAccountNumber(),
            autopayMethodId: $this->getTestPaymentMethodUuid(),
        );

        $clientMock = $this->mockHttpPatchRequest(
            url: self::API_URL . '/' . sprintf(
                AptivePaymentRepository::AUTOPAY_STATUS_ENDPOINT,
                $this->getTestCrmAccountUuid()
            ),
            options: [
                'autopay_method_id' => $requestDTO->autopayMethodId,
            ],
            responseContent: '{
                "_metadata": {
                    "success": true,
                    "links": {
                        "self": "https:\/\/api.payment-service.tst.goaptive.com\/api\/v1\/customers\/11111\/autopay-status"
                    }
                },
                "result": {
                    "message": "Autopay was updated successfully."
                }
            }',
        );

        $result = $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->updateAutoPayStatus($requestDTO);

        $this->assertInstanceOf(AutoPayStatus::class, $result);
        $this->assertEquals('Autopay was updated successfully.', $result->message);
        $this->assertTrue($result->success);
    }

    public function test_update_auto_pay_status_returns_exception(): void
    {
        $requestDTO = new AutoPayStatusRequestDTO(
            customerId: $this->getTestAccountNumber(),
            autopayMethodId: $this->getTestPaymentMethodUuid(),
        );

        $clientMock = $this->mockHttpPatchRequestToThrowException(
            url: self::API_URL . '/' . sprintf(
                AptivePaymentRepository::AUTOPAY_STATUS_ENDPOINT,
                $this->getTestCrmAccountUuid()
            ),
            options: $requestDTO->toArray(),
        );

        $this->expectException(\Exception::class);

        $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestOnly())
            ->updateAutoPayStatus($requestDTO);
    }

    public function test_update_auto_pay_status_returns_cleo_crm_missing_account_exception(): void
    {
        $requestDTO = new AutoPayStatusRequestDTO(
            customerId: $this->getTestAccountNumber(),
            autopayMethodId: $this->getTestPaymentMethodUuid(),
        );

        $clientMock = $this->createMock(HttpClient::class);
        $clientMock
            ->expects(self::never())
            ->method('patch')
            ->withAnyParameters();

        $cleoCrmRepository = \Mockery::mock(CleoCrmRepository::class);
        $cleoCrmRepository
            ->shouldReceive('getAccount')
            ->once()
            ->withArgs([$this->getTestAccountNumber()])
            ->andReturn(null);

        $this->expectException(CleoCrmAccountNotFoundException::class);

        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::never())->method('logExternalRequest');
        $loggerMock->expects(self::never())->method('logExternalResponse');

        $aptivePaymentRepository = new AptivePaymentRepository(
            guzzleClient: $clientMock,
            config: $this->getConfigMock(),
            logger: $loggerMock,
            paymentMethodValidator: $this->getPaymentMethodValidator(),
            cleoCrmRepository: $cleoCrmRepository
        );

        $aptivePaymentRepository->updateAutoPayStatus($requestDTO);
    }

    public function test_it_returns_payment_list(): void
    {
        $requestDTO = new PaymentsListRequestDTO(
            customerId: $this->getTestAccountNumber(),
        );

        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/' . AptivePaymentRepository::PAYMENTS_LIST_ENDPOINT,
            query: $requestDTO->toArray(),
            responseContent: '{
              "_metadata": {
                "success": true,
                "current_page": 1,
                "per_page": 100,
                "total_pages": 100,
                "total_results": 100,
                "links": {
                  "self": "http://localhost:8080/api/v1/payments?per_page=1&page=1",
                  "first": "http://localhost:8080/api/v1/payments?per_page=1&page=1",
                  "previous": null,
                  "next": "http://localhost:8080/api/v1/payments?per_page=1&page=2",
                  "last": "http://localhost:8080/api/v1/payments?per_page=1&page=8"
                }
              },
              "result": [
                {
                  "payment_id": "9b111657-e6e5-42d5-8427-25e78b081973",
                  "status": "AuthCapturing",
                  "amount": 23.45,
                  "created_at": "2023-06-28 23:45:00"
                }
              ]
            }',
        );

        $result = $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->getPaymentsList($requestDTO);

        $this->assertInstanceOf(Payment::class, $result[0]);
        $this->assertEquals('9b111657-e6e5-42d5-8427-25e78b081973', $result[0]->paymentId);
        $this->assertEquals('AuthCapturing', $result[0]->status);
        $this->assertEquals(23.45, $result[0]->amount);
        $this->assertEquals('2023-06-28 23:45:00', $result[0]->created_at);
    }

    public function test_get_payment_list_returns_exception(): void
    {
        $requestDTO = new PaymentsListRequestDTO(
            customerId: $this->getTestAccountNumber(),
        );

        $clientMock = $this->mockHttpGetRequestToThrowException(
            url: self::API_URL . '/' . AptivePaymentRepository::PAYMENTS_LIST_ENDPOINT,
            query: $requestDTO->toArray(),
        );

        $this->expectException(\Exception::class);

        $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestOnly())
            ->getPaymentsList($requestDTO);
    }

    public function test_it_authorize_and_capture(): void
    {
        $requestDTO = new AuthAndCaptureRequestDTO(
            amount: 1000,
            customerId: $this->getTestAccountNumber(),
            methodId: $this->getTestPaymentMethodUuid(),
        );

        $clientMock = $this->mockHttpPostRequest(
            url: self::API_URL . '/' . AptivePaymentRepository::AUTH_AND_CAPTURE_ENDPOINT,
            options: $requestDTO->toArray(),
            responseContent: '{
              "_metadata": {
                "success": true
              },
              "result": {
                "message": "Payment has been authorized and captured.",
                "status": "CAPTURED",
                "payment_id": "9b111657-e6e5-42d5-8427-25e78b081973",
                "transaction_id": "9b111658-8e9a-45ae-9df6-225c522d3f94"
              }
            }',
        );

        Event::fake();

        $result = $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->authorizeAndCapture($requestDTO);

        Event::assertDispatched(
            static fn (PaymentMade $event) => $event->getAccountNumber() === $requestDTO->customerId
                && $event->quantity === (int) round(1000 / 100)
        );

        $this->assertInstanceOf(AuthAndCapture::class, $result);
        $this->assertEquals('Payment has been authorized and captured.', $result->message);
        $this->assertEquals('CAPTURED', $result->status);
        $this->assertEquals('9b111657-e6e5-42d5-8427-25e78b081973', $result->paymentId);
        $this->assertEquals('9b111658-8e9a-45ae-9df6-225c522d3f94', $result->transactionId);
    }

    public function test_authorize_and_capture_returns_exception(): void
    {
        $requestDTO = new AuthAndCaptureRequestDTO(
            amount: 1000,
            customerId: $this->getTestAccountNumber(),
            methodId: $this->getTestPaymentMethodUuid(),
        );

        $clientMock = $this->mockHttpPostRequestToThrowException(
            url: self::API_URL . '/' . AptivePaymentRepository::AUTH_AND_CAPTURE_ENDPOINT,
            options: $requestDTO->toArray(),
        );

        $this->expectException(\Exception::class);

        $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestOnly())
            ->authorizeAndCapture($requestDTO);
    }

    public function test_it_returns_cc_payment_methods_list_without_validation(): void
    {
        $requestDTO = new PaymentMethodsListRequestDTO(
            customerId: $this->getTestAccountNumber(),
        );

        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/' . AptivePaymentRepository::PAYMENT_METHODS_ENDPOINT,
            query: $requestDTO->toArray(),
            responseContent: '{
              "_metadata": {
                "success": true,
                "current_page": 1,
                "per_page": 100,
                "total_pages": 100,
                "total_results": 100,
                "links": {
                  "self": "http://localhost:8080/api/v1/payments?per_page=1&page=1",
                  "first": "http://localhost:8080/api/v1/payments?per_page=1&page=1",
                  "previous": null,
                  "next": "http://localhost:8080/api/v1/payments?per_page=1&page=2",
                  "last": "http://localhost:8080/api/v1/payments?per_page=1&page=8"
                }
              },
              "result": [
                {
                  "payment_method_id": "9b111657-e6e5-42d5-8427-25e78b081973",
                  "account_id": "9283d55c-06f8-43e9-b723-498fc39ae04a",
                  "type": "CC",
                  "date_added": "2023-06-28 23:45:00",
                  "cc_last_four": "1234",
                  "cc_expiration_month": 12,
                  "cc_expiration_year": 2045,
                  "is_primary": true,
                  "is_autopay": true,
                  "description": "Test"
                }
              ]
            }',
        );

        $result = $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->getPaymentMethodsList($requestDTO);

        $this->assertCount(1, $result);
        /** @var CreditCardPaymentMethod $result[0] */
        $this->assertInstanceOf(CreditCardPaymentMethod::class, $result[0]);
        $this->assertEquals('9b111657-e6e5-42d5-8427-25e78b081973', $result[0]->paymentMethodId);
        $this->assertEquals('9283d55c-06f8-43e9-b723-498fc39ae04a', $result[0]->crmAccountId);
        $this->assertEquals('CC', $result[0]->type);
        $this->assertEquals('2023-06-28 23:45:00', $result[0]->dateAdded);
        $this->assertEquals('1234', $result[0]->ccLastFour);
        $this->assertEquals(12, $result[0]->ccExpirationMonth);
        $this->assertEquals(2045, $result[0]->ccExpirationYear);
        $this->assertTrue($result[0]->isPrimary);
        $this->assertEquals('Test', $result[0]->description);
        $this->assertFalse($result[0]->isExpired);
        $this->assertTrue($result[0]->isAutoPay);
    }

    public function test_it_validate_cc_payment_methods_list(): void
    {
        $requestDTO = new PaymentMethodsListRequestDTO(
            customerId: $this->getTestAccountNumber(),
        );

        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/' . AptivePaymentRepository::PAYMENT_METHODS_ENDPOINT,
            query: $requestDTO->toArray(),
            responseContent: '{
              "_metadata": {
                "success": true,
                "current_page": 1,
                "per_page": 100,
                "total_pages": 100,
                "total_results": 100,
                "links": {
                  "self": "http://localhost:8080/api/v1/payments?per_page=1&page=1",
                  "first": "http://localhost:8080/api/v1/payments?per_page=1&page=1",
                  "previous": null,
                  "next": "http://localhost:8080/api/v1/payments?per_page=1&page=2",
                  "last": "http://localhost:8080/api/v1/payments?per_page=1&page=8"
                }
              },
              "result": [
                {
                  "payment_method_id": "9b111657-e6e5-42d5-8427-25e78b081973",
                  "account_id": "9283d55c-06f8-43e9-b723-498fc39ae04a",
                  "type": "CC",
                  "date_added": "2023-06-28 23:45:00",
                  "cc_last_four": "1234",
                  "cc_expiration_month": 11,
                  "cc_expiration_year": 2000,
                  "is_primary": false,
                  "is_autopay": false,
                  "description": "Test"
                },
                {
                  "payment_method_id": "9b111657-e6e5-42d5-8427-25e78b081973",
                  "account_id": "9283d55c-06f8-43e9-b723-498fc39ae05a",
                  "type": "CC",
                  "date_added": "2023-06-30 23:45:00",
                  "cc_last_four": "1111",
                  "cc_expiration_month": 12,
                  "cc_expiration_year": 2045,
                  "is_primary": true,
                  "is_autopay": false,
                  "description": "Test"
                }
              ]
            }',
        );

        $result = $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->getPaymentMethodsList($requestDTO);

        $this->assertCount(2, $result);
        /** @var CreditCardPaymentMethod $result[0] */
        $this->assertInstanceOf(CreditCardPaymentMethod::class, $result[1]);
        $this->assertEquals('9b111657-e6e5-42d5-8427-25e78b081973', $result[1]->paymentMethodId);
        $this->assertEquals('9283d55c-06f8-43e9-b723-498fc39ae05a', $result[1]->crmAccountId);
        $this->assertEquals('CC', $result[1]->type);
        $this->assertEquals('2023-06-30 23:45:00', $result[1]->dateAdded);
        $this->assertEquals('1111', $result[1]->ccLastFour);
        $this->assertEquals(12, $result[1]->ccExpirationMonth);
        $this->assertEquals(2045, $result[1]->ccExpirationYear);
        $this->assertTrue($result[1]->isPrimary);
        $this->assertEquals('Test', $result[1]->description);
        $this->assertTrue($result[0]->isExpired);
        $this->assertFalse($result[1]->isExpired);
        $this->assertFalse($result[0]->isAutoPay);
    }

    public function test_it_returns_ach_payment_methods_list_without_validation(): void
    {
        $requestDTO = new PaymentMethodsListRequestDTO(
            customerId: $this->getTestAccountNumber(),
        );

        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/' . AptivePaymentRepository::PAYMENT_METHODS_ENDPOINT,
            query: $requestDTO->toArray(),
            responseContent: '{
              "_metadata": {
                "success": true,
                "current_page": 1,
                "per_page": 100,
                "total_pages": 100,
                "total_results": 100,
                "links": {
                  "self": "http://localhost:8080/api/v1/payments?per_page=1&page=1",
                  "first": "http://localhost:8080/api/v1/payments?per_page=1&page=1",
                  "previous": null,
                  "next": "http://localhost:8080/api/v1/payments?per_page=1&page=2",
                  "last": "http://localhost:8080/api/v1/payments?per_page=1&page=8"
                }
              },
              "result": [
                {
                  "payment_method_id": "9b111657-e6e5-42d5-8427-25e78b081973",
                  "account_id": "9283d55c-06f8-43e9-b723-498fc39ae04a",
                  "type": "ACH",
                  "date_added": "2023-06-28 23:45:00",
                  "is_primary": true,
                  "is_autopay": true,
                  "description": "Test",
                  "pestroutes_status": "Valid",
                  "ach_account_last_four": "1234",
                  "ach_routing_number": "985612814",
                  "ach_account_type": "personal_checking",
                  "ach_bank_name": "Universal Bank"
                }
              ]
            }',
        );

        $result = $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->getPaymentMethodsList($requestDTO);

        $this->assertCount(1, $result);
        /** @var AchPaymentMethod $result[0] */
        $this->assertInstanceOf(AchPaymentMethod::class, $result[0]);
        $this->assertEquals('9b111657-e6e5-42d5-8427-25e78b081973', $result[0]->paymentMethodId);
        $this->assertEquals('9283d55c-06f8-43e9-b723-498fc39ae04a', $result[0]->crmAccountId);
        $this->assertEquals('ACH', $result[0]->type);
        $this->assertEquals('2023-06-28 23:45:00', $result[0]->dateAdded);
        $this->assertTrue($result[0]->isPrimary);
        $this->assertEquals('Test', $result[0]->description);
        $this->assertEquals('1234', $result[0]->achAccountLastFour);
        $this->assertEquals('985612814', $result[0]->achRoutingNumber);
        $this->assertEquals('personal_checking', $result[0]->achAccountType);
        $this->assertEquals('Universal Bank', $result[0]->achBankName);
        $this->assertFalse($result[0]->isExpired);
        $this->assertTrue($result[0]->isAutoPay);
    }

    public function test_it_validate_ach_payment_methods_list(): void
    {
        $requestDTO = new PaymentMethodsListRequestDTO(
            customerId: $this->getTestAccountNumber(),
        );

        $clientMock = $this->mockHttpGetRequest(
            url: self::API_URL . '/' . AptivePaymentRepository::PAYMENT_METHODS_ENDPOINT,
            query: $requestDTO->toArray(),
            responseContent: '{
              "_metadata": {
                "success": true,
                "current_page": 1,
                "per_page": 100,
                "total_pages": 100,
                "total_results": 100,
                "links": {
                  "self": "http://localhost:8080/api/v1/payments?per_page=1&page=1",
                  "first": "http://localhost:8080/api/v1/payments?per_page=1&page=1",
                  "previous": null,
                  "next": "http://localhost:8080/api/v1/payments?per_page=1&page=2",
                  "last": "http://localhost:8080/api/v1/payments?per_page=1&page=8"
                }
              },
              "result": [
                {
                  "payment_method_id": "9b111657-e6e5-42d5-8427-25e78b081973",
                  "account_id": "9283d55c-06f8-43e9-b723-498fc39ae04a",
                  "type": "ACH",
                  "date_added": "2023-06-28 23:45:00",
                  "is_primary": true,
                  "is_autopay": false,
                  "description": "Test",
                  "pestroutes_status": "Valid",
                  "ach_account_last_four": "1234",
                  "ach_routing_number": "985612814",
                  "ach_account_type": "personal_checking",
                  "ach_bank_name": "Universal Bank"
                },
                {
                  "payment_method_id": "9b111657-e6e5-42d5-8427-25e78b081973",
                  "account_id": "9283d55c-06f8-43e9-b723-498fc39ae05a",
                  "type": "ACH",
                  "date_added": "2023-06-30 23:45:00",
                  "is_primary": false,
                  "is_autopay": true,
                  "description": null,
                  "pestroutes_status": "Valid",
                  "ach_account_last_four": "1234",
                  "ach_routing_number": "985612814",
                  "ach_account_type": "personal_checking",
                  "ach_bank_name": "Universal Bank"
                }
              ]
            }',
        );

        $result = $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->getPaymentMethodsList($requestDTO);

        $this->assertCount(2, $result);
        /** @var AchPaymentMethod $result[0] */
        $this->assertInstanceOf(AchPaymentMethod::class, $result[0]);
        $this->assertEquals('9b111657-e6e5-42d5-8427-25e78b081973', $result[0]->paymentMethodId);
        $this->assertEquals('9283d55c-06f8-43e9-b723-498fc39ae04a', $result[0]->crmAccountId);
        $this->assertEquals('ACH', $result[0]->type);
        $this->assertEquals('2023-06-28 23:45:00', $result[0]->dateAdded);
        $this->assertTrue($result[0]->isPrimary);
        $this->assertEquals('Test', $result[0]->description);
        $this->assertEquals('1234', $result[0]->achAccountLastFour);
        $this->assertEquals('985612814', $result[0]->achRoutingNumber);
        $this->assertEquals('personal_checking', $result[0]->achAccountType);
        $this->assertEquals('Universal Bank', $result[0]->achBankName);
        $this->assertFalse($result[0]->isExpired);
        $this->assertFalse($result[1]->isExpired);
        $this->assertFalse($result[0]->isAutoPay);
        $this->assertTrue($result[1]->isAutoPay);
    }

    public function test_get_payment_methods_list_returns_exception(): void
    {
        $requestDTO = new PaymentMethodsListRequestDTO(
            customerId: $this->getTestAccountNumber(),
        );

        $clientMock = $this->mockHttpGetRequestToThrowException(
            url: self::API_URL . '/' . AptivePaymentRepository::PAYMENT_METHODS_ENDPOINT,
            query: $requestDTO->toArray(),
        );

        $this->expectException(\Exception::class);

        $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestOnly())
            ->getPaymentMethodsList($requestDTO);
    }

    public function test_it_create_ach_payment_profile(): void
    {
        $clientMock = $this->mockHttpPostRequest(
            url: self::API_URL . '/' . AptivePaymentRepository::PAYMENT_METHODS_ENDPOINT,
            options: $this->setupACHPaymentProfile()->toArray(),
            responseContent: '{
              "_metadata": {
                "success": true,
                "links": {
                  "self": "http://localhost:8080/api/v1/payment-methods/98736"
                }
              },
              "result": {
                "message": "Payment method was successfully created",
                "payment_method_id": "9b111657-e6e5-42d5-8427-25e78b081973"
              }
            }',
        );

        $result = $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->createPaymentProfile($this->setupACHPaymentProfile());

        $this->assertInstanceOf(PaymentProfile::class, $result);
        $this->assertEquals('9b111657-e6e5-42d5-8427-25e78b081973', $result->paymentMethodId);
        $this->assertEquals('Payment method was successfully created', $result->message);
    }

    public function test_create_ach_payment_profile_returns_exception(): void
    {
        $clientMock = $this->mockHttpPostRequestToThrowException(
            url: self::API_URL . '/' . AptivePaymentRepository::PAYMENT_METHODS_ENDPOINT,
            options: $this->setupACHPaymentProfile()->toArray(),
        );

        $this->expectException(\Exception::class);

        $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestOnly())
            ->createPaymentProfile($this->setupACHPaymentProfile());
    }

    public function test_it_create_cc_payment_profile(): void
    {
        $clientMock = $this->mockHttpPostRequest(
            url: self::API_URL . '/' . AptivePaymentRepository::PAYMENT_METHODS_ENDPOINT,
            options: $this->setupCCPaymentProfile()->toArray(),
            responseContent: '{
              "_metadata": {
                "success": true,
                "links": {
                  "self": "http://localhost:8080/api/v1/payment-methods/98736"
                }
              },
              "result": {
                "message": "Payment method was successfully created",
                "payment_method_id": "9b10ebb2-aa98-4e63-bf43-dd42e7ef85f3"
              }
            }',
        );

        $result = $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse())
            ->createPaymentProfile($this->setupCCPaymentProfile());

        $this->assertInstanceOf(PaymentProfile::class, $result);
        $this->assertEquals('9b10ebb2-aa98-4e63-bf43-dd42e7ef85f3', $result->paymentMethodId);
        $this->assertEquals('Payment method was successfully created', $result->message);
    }

    public function test_create_cc_payment_profile_returns_exception(): void
    {
        $clientMock = $this->mockHttpPostRequestToThrowException(
            url: self::API_URL . '/' . AptivePaymentRepository::PAYMENT_METHODS_ENDPOINT,
            options: $this->setupCCPaymentProfile()->toArray(),
        );

        $this->expectException(\Exception::class);

        $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestOnly())
            ->createPaymentProfile($this->setupCCPaymentProfile());
    }

    public function test_it_set_payment_method_as_primary(): void
    {
        $clientMock = $this->mockHttpPatchRequest(
            url: self::API_URL . '/' . sprintf(
                AptivePaymentRepository::PAYMENT_METHOD_UPDATE_ENDPOINT,
                $this->getTestPaymentMethodUuid()
            ),
            options: [
                'is_primary' => true,
            ],
            responseContent: '{
              "_metadata": {
                "success": true,
                "links": {
                  "self": "http://localhost:8080/api/v1/payment-methods/1"
                }
              },
              "result": {
                "message": "Payment method was successfully updated"
              }
            }',
        );

        $repository = $this->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse());

        $this->assertTrue($repository->setPaymentMethodAsPrimary($this->getTestPaymentMethodUuid()));
    }

    public function test_it_set_payment_method_as_primary_returns_exception(): void
    {
        $clientMock = $this->mockHttpPatchRequestToThrowException(
            url: self::API_URL . '/' . sprintf(
                AptivePaymentRepository::PAYMENT_METHOD_UPDATE_ENDPOINT,
                $this->getTestPaymentMethodUuid()
            ),
            options: [
                'is_primary' => true,
            ],
        );

        $this->expectException(\Exception::class);

        $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestOnly())
            ->setPaymentMethodAsPrimary($this->getTestPaymentMethodUuid());
    }

    public function test_it_validate_credit_card_token(): void
    {
        $clientMock = $this->mockHttpPostRequest(
            url: self::API_URL . '/' . AptivePaymentRepository::CREDIT_CARD_TOKEN_VALIDATION_ENDPOINT,
            options: $this->setupValidateCreditCardTokenRequestDTO()->toArray(),
            responseContent: '{
              "_metadata": {
                "success": true,
                "links": {
                  "self": "http://localhost:8080/api/v1/payment-methods/validate-cc-token"
                }
              },
              "result": {
                "message": "The Credit Card was successfully validated in Gateway",
                "is_valid": true
              }
            }',
        );

        $repository = $this->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse());

        $this->assertTrue($repository->isValidCreditCardToken($this->setupValidateCreditCardTokenRequestDTO()));
    }

    public function test_it_throw_not_found_exception_on_validation_credit_card_token(): void
    {
        $clientMock = $this->mockHttpPostRequest(
            url: self::API_URL . '/' . AptivePaymentRepository::CREDIT_CARD_TOKEN_VALIDATION_ENDPOINT,
            options: $this->setupValidateCreditCardTokenRequestDTO()->toArray(),
            responseContent: '{
              "_metadata": {
                "success": false,
                "links": {
                  "self": "http://localhost:8080/api/v1/payment-methods/validate-cc-token"
                }
              },
              "result": {
                "message": "Gateway response: Error from Tokenex: 3000 : Token does not exist",
                "is_valid": false
              }
            }',
        );

        $repository = $this->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse());

        $this->expectException(CreditCardTokenNotFoundException::class);

        $repository->isValidCreditCardToken($this->setupValidateCreditCardTokenRequestDTO());
    }

    public function test_is_credit_card_token_validation_returns_exception(): void
    {
        $clientMock = $this->mockHttpPostRequestToThrowException(
            url: self::API_URL . '/' . AptivePaymentRepository::CREDIT_CARD_TOKEN_VALIDATION_ENDPOINT,
            options: $this->setupValidateCreditCardTokenRequestDTO()->toArray(),
        );

        $this->expectException(\Exception::class);

        $repository = $this->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestOnly());

        $repository->isValidCreditCardToken($this->setupValidateCreditCardTokenRequestDTO());
    }

    public function test_it_delete_payment_method(): void
    {
        $clientMock = $this->mockHttpDeleteRequest(
            url: self::API_URL . '/' . sprintf(
                AptivePaymentRepository::PAYMENT_METHOD_UPDATE_ENDPOINT,
                $this->getTestPaymentMethodUuid()
            ),
            responseContent: '{
              "_metadata": {
                "success": true,
                "links": {
                  "self": "http://localhost:8080/api/v1/payment-methods/1"
                }
              },
              "result": {
                "message": "Payment method was successfully deleted"
              }
            }',
        );

        $repository = $this->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestAndResponse());

        $this->assertTrue($repository->deletePaymentMethod($this->getTestPaymentMethodUuid()));
    }

    public function test_delete_payment_method_returns_exception(): void
    {
        $clientMock = $this->mockHttpDeleteRequestToThrowException(
            url: self::API_URL . '/' . sprintf(
                AptivePaymentRepository::PAYMENT_METHOD_UPDATE_ENDPOINT,
                $this->getTestPaymentMethodUuid()
            ),
        );

        $this->expectException(\Exception::class);

        $this
            ->setupAptivePaymentRepository($clientMock, $this->getLoggerMockLoggingRequestOnly())
            ->deletePaymentMethod($this->getTestPaymentMethodUuid());
    }

    public function test_it_throw_cleo_crm_missing_account_exception_on_get_request(): void
    {
        $requestDTO = new PaymentMethodsListRequestDTO(
            customerId: $this->getTestAccountNumber(),
        );

        $clientMock = $this->createMock(HttpClient::class);
        $clientMock
            ->expects(self::never())
            ->method('get')
            ->withAnyParameters();

        $cleoCrmRepository = \Mockery::mock(CleoCrmRepository::class);
        $cleoCrmRepository
            ->shouldReceive('getAccount')
            ->once()
            ->withArgs([$this->getTestAccountNumber()])
            ->andReturn(null);

        $this->expectException(CleoCrmAccountNotFoundException::class);

        $aptivePaymentRepository = new AptivePaymentRepository(
            guzzleClient: $clientMock,
            config: $this->getConfigMock(),
            logger: $this->getLoggerMockLoggingNothing(),
            paymentMethodValidator: $this->getPaymentMethodValidator(),
            cleoCrmRepository: $cleoCrmRepository
        );

        $aptivePaymentRepository->getPaymentMethodsList($requestDTO);
    }

    public function test_it_throw_cleo_crm_missing_account_exception_on_post_request(): void
    {
        $requestDTO = $this->setupCCPaymentProfile();

        $clientMock = $this->createMock(HttpClient::class);
        $clientMock
            ->expects(self::never())
            ->method('post')
            ->withAnyParameters();

        $cleoCrmRepository = \Mockery::mock(CleoCrmRepository::class);
        $cleoCrmRepository
            ->shouldReceive('getAccount')
            ->once()
            ->withArgs([$this->getTestAccountNumber()])
            ->andReturn(null);

        $this->expectException(CleoCrmAccountNotFoundException::class);

        $aptivePaymentRepository = new AptivePaymentRepository(
            guzzleClient: $clientMock,
            config: $this->getConfigMock(),
            logger: $this->getLoggerMockLoggingNothing(),
            paymentMethodValidator: $this->getPaymentMethodValidator(),
            cleoCrmRepository: $cleoCrmRepository
        );

        $aptivePaymentRepository->createPaymentProfile($requestDTO);
    }

    protected function setupACHPaymentProfile(): CreatePaymentProfileRequestDTO
    {
        return new CreatePaymentProfileRequestDTO(
            customerId: $this->getTestAccountNumber(),
            gatewayId: PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID,
            type: PaymentMethodEnum::ACH,
            firstName: "John",
            lastName: "Doe",
            addressLine1: "123 Main St",
            email: "john.doe.@example.com",
            city: "Provo",
            province: "UT",
            postalCode: "40207",
            countryCode: "US",
            isPrimary: true,
            isAutoPay: true,
            achAccountNumber: '0123456789',
            achRoutingNumber: '0987654321',
            achBankName: "Universal Bank",
            achAccountTypeId: AccountType::PERSONAL_CHECKING
        );
    }

    protected function setupCCPaymentProfile(): CreatePaymentProfileRequestDTO
    {
        return new CreatePaymentProfileRequestDTO(
            customerId: $this->getTestAccountNumber(),
            gatewayId: PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID,
            type: PaymentMethodEnum::CREDIT_CARD,
            firstName: "John",
            lastName: "Doe",
            addressLine1: "123 Main St",
            email: "john.doe.@example.com",
            city: "Provo",
            province: "UT",
            postalCode: "40207",
            countryCode: "US",
            isPrimary: true,
            isAutoPay: true,
            ccToken: "A95B9CA8-A975-4035-BAD4-91FF46492A40",
            ccType: CardType::VISA,
            ccExpirationMonth: 12,
            ccExpirationYear: 2030,
            ccLastFour: '0123'
        );
    }

    protected function setupTokenexAuthKeysRequestDTO(): TokenexAuthKeysRequestDTO
    {
        return new TokenexAuthKeysRequestDTO(
            tokenScheme: 'PCI',
            origins: [
                'https://localhost:8000',
            ],
            timestamp: '20231102082947'
        );
    }

    protected function setupAptivePaymentRepository(Client $clientMock, ApiLogger $loggerMock): AptivePaymentRepository
    {
        return new AptivePaymentRepository(
            guzzleClient: $clientMock,
            config: $this->getConfigMock(),
            logger: $loggerMock,
            paymentMethodValidator: $this->getPaymentMethodValidator(),
            cleoCrmRepository: $this->getCleoCrmRepositoryMock(),
        );
    }

    protected function setupValidateCreditCardTokenRequestDTO(): ValidateCreditCardTokenRequestDTO
    {
        return new ValidateCreditCardTokenRequestDTO(
            gateway: PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID,
            officeId: $this->getTestOfficeId(),
            ccToken: '4111110NfzBk1111',
            ccExpirationMonth: 10,
            ccExpirationYear: 2050,
        );
    }
}
