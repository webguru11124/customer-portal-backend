<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\DTO\AddPaymentDTO;
use App\Enums\Models\Customer\AutoPay;
use App\Enums\Models\Payment\PaymentMethod;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Payment\PaymentNotCreatedException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\External\CustomerModel;
use App\Models\External\PaymentModel;
use App\Services\PaymentService;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfileStatus;
use Aptive\PestRoutesSDK\Resources\Payments\PaymentMethod as PestRoutesPaymentMethod;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\CustomerData;
use Tests\Data\PaymentData;
use Tests\Data\PaymentProfileData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;
use Throwable;

class PaymentControllerTest extends ApiTestCase
{
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    protected const ERROR_ACCOUNT_FROZEN = 'Account frozen';
    protected const ERROR_ACCOUNT_NOT_FOUND = 'Entity not found';

    public int $accountNumber;
    public int $officeId;
    public int $paymentId;
    public int $paymentProfileId;
    public CustomerModel $customer;

    public MockInterface|CustomerRepository $customerRepositoryMock;
    public MockInterface $paymentServiceMock;
    public MockInterface $paymentProfileServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->paymentServiceMock = Mockery::mock(PaymentService::class);
        $this->instance(CustomerRepository::class, $this->customerRepositoryMock);
        $this->instance(PaymentService::class, $this->paymentServiceMock);

        $this->accountNumber = $this->getTestAccountNumber();
        $this->officeId = $this->getTestOfficeId();
        $this->paymentId = $this->getTestPaymentId();
        $this->paymentProfileId = $this->getTestPaymentProfileId();
        $this->customer = CustomerData::getTestEntityData(1, [
            'customerID' => $this->accountNumber,
            'aPay' => AutoPay::CREDIT_CARD->value,
        ])->first();

        $paymentProfiles = PaymentProfileData::getTestEntityData(
            2,
            ['customerID' => $this->customer->id],
            [
                'customerID' => $this->customer->id,
                'paymentProfileID' => $this->paymentProfileId,
                'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH->value,
            ]
        );

        $this->customer->setRelated('paymentProfiles', $paymentProfiles);
    }

    public function test_get_payments_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getPaymentsJsonResponse()
        );
    }

    public function test_get_payments_shows_error_for_not_authorised_user(): void
    {
        $this->createAndLogInAuth0User();

        $this->getPaymentsJsonResponse()
            ->assertNotFound();
    }

    public function test_get_payments_searches_for_customer_payments(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $paymentIds = [
            123,
            456,
            789,
        ];
        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('getPaymentIds')
            ->with($this->customer)
            ->andReturn($paymentIds)
            ->once();

        $this->getPaymentsJsonResponse()
            ->assertOK()
            ->assertJson($paymentIds);
    }

    public function test_get_payments_returns404_for_existing_frozen_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new AccountFrozenException());

        $this->getPaymentsJsonResponse()
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_FROZEN);
    }

    public function test_get_payments_handles_non_existing_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new EntityNotFoundException());

        $this->getPaymentsJsonResponse()
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_NOT_FOUND);
    }

    public function test_get_payments_handles_fatal_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('getPaymentIds')
            ->with($this->customer)
            ->andThrow(new RuntimeException('Test'))
            ->once();

        $this->getPaymentsJsonResponse()
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    public function test_get_payment_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getPaymentJsonResponse()
        );
    }

    public function test_get_payment_shows_error_for_not_authorised_user(): void
    {
        $this->createAndLogInAuth0User();

        $this->getPaymentJsonResponse()
            ->assertNotFound();
    }

    public function test_get_payment_searches_for_payment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $payment = $this->getValidPayment();
        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('getPayment')
            ->with($this->customer, $payment->id)
            ->andReturn($payment)
            ->once();

        $this->getPaymentJsonResponse()
            ->assertOK()
            ->assertExactJson($payment->toArray());
    }

    public function test_get_payment_returns404_for_existing_frozen_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new AccountFrozenException());

        $this->getPaymentJsonResponse()
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_FROZEN);
    }

    public function test_get_payment_handles_non_existing_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new EntityNotFoundException());

        $this->getPaymentJsonResponse()
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_NOT_FOUND);
    }

    /**
     * @dataProvider paymentServiceExceptionProvider
     */
    public function test_get_payment_handles_fatal_error(
        Throwable $exception,
        int $expectedStatusCode,
    ): void {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('getPayment')
            ->with($this->customer, $this->paymentId)
            ->andThrow($exception)
            ->once();

        $this->getPaymentJsonResponse()
            ->assertStatus($expectedStatusCode)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    /**
     * @return iterable<array{Throwable, int}>
     */
    public function paymentServiceExceptionProvider(): iterable
    {
        yield [new RuntimeException('Test'), Response::HTTP_INTERNAL_SERVER_ERROR];
        yield [new EntityNotFoundException('Test'), Response::HTTP_NOT_FOUND];
        yield [new AccountFrozenException('Test'), Response::HTTP_NOT_FOUND];
    }

    public function test_create_payment_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getCreatePaymentJsonResponse($this->accountNumber)
        );
    }

    public function test_create_payment_shows_error_for_not_authorised_user(): void
    {
        $this->createAndLogInAuth0User();

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertNotFound();
    }

    public function test_create_payment_creates_and_searches_for_payment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();
        $payment = $this->getValidPayment();
        $this->paymentServiceMock->shouldReceive('addPayment')
            ->with($this->customer, AddPaymentDTO::class)
            ->andReturn($payment)
            ->once();

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertOk()
            ->assertExactJson($payment->toArray());
    }

    public function test_create_payment_throws_payment_not_created_exception_and_returns_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();

        $this->customer->setRelated('paymentProfiles', new Collection());

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    public function test_create_payment_throws_payment_not_created_exception_and_returns_error_on_expired_card(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();

        $paymentProfiles = PaymentProfileData::getTestEntityData(
            2,
            [
                'customerID' => $this->customer->id,
                'paymentProfileID' => $this->paymentProfileId,
                'paymentMethod' => PaymentProfilePaymentMethod::AutoPayCC->value,
                'status' => PaymentProfileStatus::Valid->value,
                'expMonth' => '01',
                'expYear' => date('y', strtotime('+1 year')),
            ]
        );

        $this->customer->setRelated('paymentProfiles', $paymentProfiles);

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    public function test_create_payment_returns_422_on_invalid_request(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $error = [
            'message' => 'The payment profile id field is required. (and 2 more errors)',
            'errors' => [
                'payment_profile_id' => [
                    'The payment profile id field is required.',
                ],
                'amount_cents' => [
                    'The amount cents field is required.',
                ],
                'payment_method' => [
                    'The payment method field is required.',
                ],
            ],
        ];

        $this->getCreatePaymentJsonResponse($this->accountNumber, [])
            ->assertUnprocessable()
            ->assertExactJson($error);
    }

    public function test_create_payment_returns404_for_existing_frozen_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new AccountFrozenException());

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_FROZEN);
    }

    public function test_create_payment_handles_non_existing_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryThrowsException(new EntityNotFoundException());

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertNotFound()
            ->assertJsonPath('message', self::ERROR_ACCOUNT_NOT_FOUND);
    }

    public function test_create_payment_handles_payment_service_fatal_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('addPayment')
            ->with($this->customer, AddPaymentDTO::class)
            ->andThrow(PaymentNotCreatedException::class)
            ->once();

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    public function test_create_payment_handles_credit_card_authorization_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenCustomerRepositoryReturnsCustomer();
        $this->paymentServiceMock->shouldReceive('addPayment')
            ->with($this->customer, AddPaymentDTO::class)
            ->andThrow(CreditCardAuthorizationException::class)
            ->once();

        $this->getCreatePaymentJsonResponse($this->accountNumber)
            ->assertStatus(Response::HTTP_PAYMENT_REQUIRED)
            ->assertJson(fn (AssertableJson $json) => $json->has('message')->etc());
    }

    protected function getPaymentsJsonResponse(): TestResponse
    {
        return $this->getJson(route(
            'api.customer.payments.get',
            ['accountNumber' => $this->accountNumber]
        ));
    }

    protected function getPaymentJsonResponse(): TestResponse
    {
        return $this->getJson(route(
            'api.customer.payments.getpayment',
            [
                'accountNumber' => $this->accountNumber,
                'paymentId' => $this->paymentId,
            ]
        ));
    }

    protected function getCreatePaymentJsonResponse($accountNumber, $postData = null): TestResponse
    {
        $postData = $postData ?? [
            'payment_profile_id' => $this->paymentProfileId,
            'amount_cents' => 12345,
            'payment_method' => PaymentMethod::CREDIT_CARD,
        ];

        return $this->postJson(
            route('api.customer.payments.add', ['accountNumber' => $accountNumber]),
            $postData
        );
    }

    protected function givenCustomerRepositoryReturnsCustomer(): void
    {
        $this->customerRepositoryMock->shouldReceive('office')
            ->with($this->officeId)
            ->once()
            ->andReturnSelf();

        $this->customerRepositoryMock->shouldReceive('withRelated')
            ->with(['paymentProfiles'])
            ->andReturnSelf();

        $this->customerRepositoryMock->shouldReceive('find')
            ->with($this->accountNumber)
            ->andReturn($this->customer)
            ->once();
    }

    protected function givenCustomerRepositoryThrowsException($exception): void
    {
        $this->customerRepositoryMock->shouldReceive('office')->andReturnSelf();
        $this->customerRepositoryMock->shouldReceive('withRelated')->andReturnSelf();
        $this->customerRepositoryMock->shouldReceive('find')
            ->with($this->accountNumber)
            ->andThrow($exception)
            ->once();
    }

    protected function getValidPayment(): PaymentModel
    {
        return PaymentData::getTestEntityData(1, [
            'paymentID' => $this->paymentId,
            'officeID' => $this->getTestOfficeId(),
            'customerID' => $this->accountNumber,
            'paymentMethod' => PestRoutesPaymentMethod::CreditCard->value,
            'date' => '2022-07-01 06:30:55',
            'amount' => 123.45,
        ])->firstOrFail();
    }
}
