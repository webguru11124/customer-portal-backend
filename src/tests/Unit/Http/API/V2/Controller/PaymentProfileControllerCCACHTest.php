<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\PaymentProfile\CompleteCreditCardPaymentProfileAction;
use App\Actions\PaymentProfile\CreateAchPaymentProfileActionV2;
use App\DTO\Payment\CreatePaymentProfileRequestDTO;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Actions\PaymentProfile\InitializeCreditCardPaymentProfileActionV2;
use App\DTO\Payment\PaymentProfile;
use App\Http\Requests\V2\InitializeCreditCardPaymentProfileRequest;
use App\Models\Account;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesCustomerRepository;
use Aptive\Component\Http\HttpStatus;
use Exception;
use Illuminate\Support\Str;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\CustomerData;
use Tests\Traits\RandomStringTestData;
use Tests\Traits\TestAuthorizationMiddleware;
use Tests\Unit\Actions\PaymentProfile\CompleteCreditCardPaymentProfileActionTest;

use Tests\Unit\Actions\PaymentProfile\InitializeCreditCardPaymentProfileActionV2Test;
use Tests\Unit\Http\API\V1\Controller\PaymentProfileControllerTest;

class PaymentProfileControllerCCACHTest extends PaymentProfileController
{
    use TestAuthorizationMiddleware;
    use RandomStringTestData;

    public function test_v2_creating_ach_payment_profile_calls_action_for_v2(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $customerData = CustomerData::getTestEntityData(1, [
            'customerID' => $this->getTestAccountNumber(),
        ])->first();

        $cachedPestRoutesCustomerRepository = Mockery::mock(CachedPestRoutesCustomerRepository::class);
        $cachedPestRoutesCustomerRepository
            ->expects('office')
            ->andReturnSelf()
            ->once();
        $cachedPestRoutesCustomerRepository
            ->expects('find')
            ->with($this->getTestAccountNumber())
            ->andReturn($customerData)
            ->once();

        $this->instance(CustomerRepository::class, $cachedPestRoutesCustomerRepository);

        $createAchActionMock = Mockery::mock(CreateAchPaymentProfileActionV2::class);
        $createAchActionMock
            ->expects('__invoke')
            ->withArgs(function (CreatePaymentProfileRequestDTO $dto): bool {
                return $dto->customerId === $this->getTestAccountNumber();
            })
            ->once()
            ->andReturn($this->getTestPaymentProfileId());

        $this->instance(CreateAchPaymentProfileActionV2::class, $createAchActionMock);

        $requestData = array_merge(PaymentProfileControllerTest::PAYMENT_PROFILE_DATA, [
            'account_number' => PaymentProfileControllerTest::PAYMENT_PROFILE_DATA['account_number'],
            'account_number_confirmation' => PaymentProfileControllerTest::PAYMENT_PROFILE_DATA['account_number'],
        ]);

        $response = $this->postJson(
            route('api.v2.customer.paymentprofiles.ach.create', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.id', (string) $this->getTestPaymentProfileId());
    }

    public function test_creating_ach_payment_profile_returns_error_from_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $customerData = CustomerData::getTestEntityData(1, [
            'customerID' => $this->getTestAccountNumber(),
        ])->first();

        $cachedPestRoutesCustomerRepository = Mockery::mock(CachedPestRoutesCustomerRepository::class);
        $cachedPestRoutesCustomerRepository
            ->expects('office')
            ->andReturnSelf()
            ->once();
        $cachedPestRoutesCustomerRepository
            ->expects('find')
            ->with($this->getTestAccountNumber())
            ->andReturn($customerData)
            ->once();

        $this->instance(CustomerRepository::class, $cachedPestRoutesCustomerRepository);

        $createAchActionMock = Mockery::mock(CreateAchPaymentProfileActionV2::class);
        $createAchActionMock
            ->expects('__invoke')
            ->withAnyArgs()
            ->once()
            ->andThrows(new Exception('Test'));

        $this->instance(CreateAchPaymentProfileActionV2::class, $createAchActionMock);

        $requestData = array_merge(PaymentProfileControllerTest::PAYMENT_PROFILE_DATA, [
            'account_number' => PaymentProfileControllerTest::PAYMENT_PROFILE_DATA['account_number'],
            'account_number_confirmation' => PaymentProfileControllerTest::PAYMENT_PROFILE_DATA['account_number'],
        ]);

        $response = $this->postJson(
            route('api.v2.customer.paymentprofiles.ach.create', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_creating_ach_payment_profile_returns_credit_card_authorization_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $customerData = CustomerData::getTestEntityData(1, [
            'customerID' => $this->getTestAccountNumber(),
        ])->first();

        $cachedPestRoutesCustomerRepository = Mockery::mock(CachedPestRoutesCustomerRepository::class);
        $cachedPestRoutesCustomerRepository
            ->expects('office')
            ->andReturnSelf()
            ->once();
        $cachedPestRoutesCustomerRepository
            ->expects('find')
            ->with($this->getTestAccountNumber())
            ->andReturn($customerData)
            ->once();

        $this->instance(CustomerRepository::class, $cachedPestRoutesCustomerRepository);

        $createAchActionMock = Mockery::mock(CreateAchPaymentProfileActionV2::class);
        $createAchActionMock
            ->expects('__invoke')
            ->withAnyArgs()
            ->once()
            ->andThrows(new CreditCardAuthorizationException('Credit card authorization exception'));

        $this->instance(CreateAchPaymentProfileActionV2::class, $createAchActionMock);

        $requestData = array_merge(PaymentProfileControllerTest::PAYMENT_PROFILE_DATA, [
            'account_number' => PaymentProfileControllerTest::PAYMENT_PROFILE_DATA['account_number'],
            'account_number_confirmation' => PaymentProfileControllerTest::PAYMENT_PROFILE_DATA['account_number'],
        ]);

        $response = $this->postJson(
            route('api.v2.customer.paymentprofiles.ach.create', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response->assertStatus(HttpStatus::PAYMENT_REQUIRED);
    }

    public function test_creating_ach_payment_profile_returns_abstract_http_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $customerData = CustomerData::getTestEntityData(1, [
            'customerID' => $this->getTestAccountNumber(),
        ])->first();

        $cachedPestRoutesCustomerRepository = Mockery::mock(CachedPestRoutesCustomerRepository::class);
        $cachedPestRoutesCustomerRepository
            ->expects('office')
            ->andReturnSelf()
            ->once();
        $cachedPestRoutesCustomerRepository
            ->expects('find')
            ->with($this->getTestAccountNumber())
            ->andReturn($customerData)
            ->once();

        $this->instance(CustomerRepository::class, $cachedPestRoutesCustomerRepository);

        $createAchActionMock = Mockery::mock(CreateAchPaymentProfileActionV2::class);
        $createAchActionMock
            ->expects('__invoke')
            ->withAnyArgs()
            ->once()
            ->andThrows(new PaymentProfileNotFoundException());

        $this->instance(CreateAchPaymentProfileActionV2::class, $createAchActionMock);

        $requestData = array_merge(PaymentProfileControllerTest::PAYMENT_PROFILE_DATA, [
            'account_number' => PaymentProfileControllerTest::PAYMENT_PROFILE_DATA['account_number'],
            'account_number_confirmation' => PaymentProfileControllerTest::PAYMENT_PROFILE_DATA['account_number'],
        ]);

        $response = $this->postJson(
            route('api.v2.customer.paymentprofiles.ach.create', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_creating_ach_payment_profile_validates_request(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $createAchActionMock = Mockery::mock(CreateAchPaymentProfileActionV2::class);
        $createAchActionMock
            ->expects('__invoke')
            ->withAnyArgs()
            ->never();

        $this->instance(CreateAchPaymentProfileActionV2::class, $createAchActionMock);

        $response = $this->postJson(
            route('api.v2.customer.paymentprofiles.ach.create', ['accountNumber' => $this->getTestAccountNumber()]),
        );

        $response
            ->assertUnprocessable()
            ->assertJsonCount(10, 'errors');
    }

    public function test_initialize_credit_card_payment_profile(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $paymentProfile = new PaymentProfile(
            $this->getTestPaymentMethodUuid(),
            'Test message'
        );

        $accountNumber = $this->getTestAccountNumber();

        $initializeActionMock = Mockery::mock(InitializeCreditCardPaymentProfileActionV2::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withArgs(static function (InitializeCreditCardPaymentProfileRequest $request, Account $account) use ($accountNumber) {
                return $account->account_number === $accountNumber &&
                    $request->billing_address_line_1 === InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC['billing_address_line_1'] &&
                    $request->billing_city === InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC['billing_city'] &&
                    $request->billing_state === InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC['billing_state'] &&
                    $request->billing_zip === InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC['billing_zip'] &&
                    $request->card_type === InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC['card_type'] &&
                    $request->cc_token === InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC['cc_token'] &&
                    $request->cc_expiration_month === InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC['cc_expiration_month'] &&
                    $request->cc_expiration_year === InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC['cc_expiration_year'] &&
                    $request->cc_last_four === InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC['cc_last_four'] &&
                    $request->cc_type === InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC['cc_type'];
            })
            ->andReturn($paymentProfile);

        $this->instance(InitializeCreditCardPaymentProfileActionV2::class, $initializeActionMock);

        $this->postJson(
            route('api.v2.customer.paymentprofiles.creditcard.create', ['accountNumber' => $this->getTestAccountNumber()]),
            InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC
        )
            ->assertCreated()
            ->assertExactJson($paymentProfile->toArray());
    }

    public function test_initialize_credit_card_payment_profile_returns_error_on_validation(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $initializeActionMock = Mockery::mock(InitializeCreditCardPaymentProfileActionV2::class);
        $initializeActionMock
            ->expects('__invoke')
            ->never()
            ->withAnyArgs();

        $this->instance(InitializeCreditCardPaymentProfileActionV2::class, $initializeActionMock);

        $this->postJson(
            route('api.v2.customer.paymentprofiles.creditcard.create', ['accountNumber' => $this->getTestAccountNumber()]),
            [
                'cc_token' => 1,
            ]
        )
            ->assertUnprocessable();
    }

    public function test_initialize_credit_card_payment_profile_returns_error_on_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $initializeActionMock = Mockery::mock(InitializeCreditCardPaymentProfileActionV2::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withAnyArgs()
            ->andThrows(new Exception());

        $this->instance(InitializeCreditCardPaymentProfileActionV2::class, $initializeActionMock);

        $response = $this->postJson(
            route('api.v2.customer.paymentprofiles.creditcard.create', ['accountNumber' => $this->getTestAccountNumber()]),
            InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC
        );

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_create_credit_card_payment_profile_returns_abstract_http_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $initializeActionMock = Mockery::mock(InitializeCreditCardPaymentProfileActionV2::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withAnyArgs()
            ->andThrows(new PaymentProfileNotFoundException());

        $this->instance(InitializeCreditCardPaymentProfileActionV2::class, $initializeActionMock);

        $response = $this->postJson(
            route('api.v2.customer.paymentprofiles.creditcard.create', ['accountNumber' => $this->getTestAccountNumber()]),
            InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC
        );

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_creating_credit_card_payment_profile_returns_credit_card_authorization_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $initializeActionMock = Mockery::mock(InitializeCreditCardPaymentProfileActionV2::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withAnyArgs()
            ->andThrows(new CreditCardAuthorizationException('Credit card authorization exception'));

        $this->instance(InitializeCreditCardPaymentProfileActionV2::class, $initializeActionMock);

        $response = $this->postJson(
            route('api.v2.customer.paymentprofiles.creditcard.create', ['accountNumber' => $this->getTestAccountNumber()]),
            InitializeCreditCardPaymentProfileActionV2Test::PAYMENT_PROFILE_DATA_CC
        );

        $response->assertStatus(HttpStatus::PAYMENT_REQUIRED);
    }

    public function test_complete_credit_card_payment_profile(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $transactionSetupId = Str::uuid()->toString();

        $initializeActionMock = Mockery::mock(CompleteCreditCardPaymentProfileAction::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withArgs(function (
                Account $account,
                string $paymentAccountId,
                string $status,
                string $transactionId
            ) use ($transactionSetupId): bool {
                return
                    $account->account_number === $this->getTestAccountNumber()
                    && $paymentAccountId === CompleteCreditCardPaymentProfileActionTest::PAYMENT_PROFILE_DATA['PaymentAccountID']
                    && $status === CompleteCreditCardPaymentProfileActionTest::PAYMENT_PROFILE_DATA['HostedPaymentStatus']
                    && $transactionId === $transactionSetupId;
            })
            ->andReturn($this->getTestPaymentProfileId());

        $this->instance(CompleteCreditCardPaymentProfileAction::class, $initializeActionMock);

        $response = $this->postJson(
            route(
                'api.v2.customer.paymentprofiles.creditcard.complete',
                [
                    'accountNumber' => $this->getTestAccountNumber(),
                    'transactionSetupId' => $transactionSetupId,
                ]
            ),
            CompleteCreditCardPaymentProfileActionTest::PAYMENT_PROFILE_DATA
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.id', (string) $this->getTestPaymentProfileId());
    }

    public function test_complete_credit_card_payment_profile_returns_error_on_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $transactionSetupId = Str::uuid()->toString();

        $initializeActionMock = Mockery::mock(CompleteCreditCardPaymentProfileAction::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withAnyArgs()
            ->andThrows(new Exception('Test'));

        $this->instance(CompleteCreditCardPaymentProfileAction::class, $initializeActionMock);

        $response = $this->postJson(
            route(
                'api.v2.customer.paymentprofiles.creditcard.complete',
                [
                    'accountNumber' => $this->getTestAccountNumber(),
                    'transactionSetupId' => $transactionSetupId,
                ]
            ),
            CompleteCreditCardPaymentProfileActionTest::PAYMENT_PROFILE_DATA
        );

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
