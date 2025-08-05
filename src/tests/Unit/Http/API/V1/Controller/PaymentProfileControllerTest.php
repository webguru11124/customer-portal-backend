<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\Actions\PaymentProfile\CompleteCreditCardPaymentProfileAction;
use App\Actions\PaymentProfile\CreateAchPaymentProfileAction;
use App\Actions\PaymentProfile\DeletePaymentProfileAction;
use App\Actions\PaymentProfile\InitializeCreditCardPaymentProfileAction;
use App\Actions\PaymentProfile\ShowCustomerPaymentProfilesAction;
use App\DTO\CreatePaymentProfileDTO;
use App\DTO\UpdatePaymentProfileDTO;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Enums\Models\PaymentProfile\StatusType;
use App\Enums\PaymentService\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use App\Enums\Resources;
use App\Exceptions\Authorization\UnauthorizedException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileIsEmptyException;
use App\Exceptions\PaymentProfile\PaymentProfileNotDeletedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Exceptions\PaymentProfile\TransactionSetupAlreadyCompleteException;
use App\Exceptions\TransactionSetup\TransactionSetupNotFoundException;
use App\Models\Account;
use App\Models\External\PaymentProfileModel;
use App\Services\AccountService;
use App\Services\PaymentProfileService;
use App\Services\TransactionSetupService;
use Aptive\Component\Http\HttpStatus;
use Aptive\Component\JsonApi\Exceptions\ValidationException as JsonValidationException;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\PaymentProfileData;
use Tests\Traits\ExpectedV1ResponseData;
use Tests\Traits\GetPestRoutesPaymentProfile;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;
use Tests\Unit\Actions\PaymentProfile\CompleteCreditCardPaymentProfileActionTest;
use Tests\Unit\Actions\PaymentProfile\CreateAchPaymentProfileActionTest;
use Tests\Unit\Actions\PaymentProfile\InitializeCreditCardPaymentProfileActionTest;
use Throwable;

use function array_slice;
use function count;

class PaymentProfileControllerTest extends ApiTestCase
{
    use ExpectedV1ResponseData;
    use GetPestRoutesPaymentProfile;
    use RandomIntTestData;
    use RefreshDatabase;
    use TestAuthorizationMiddleware;

    private const REDIRECT_URI = 'schema://test?id=12';
    private const VALID_REQUEST_STATUSES = 'valid,expired,failed';
    private const VALID_REQUEST_METHODS = 'CC,ACH';

    public const PAYMENT_PROFILE_DATA = [
        'billing_name' => 'John Smith',
        'billing_address_line_1' =>  '7 Lewis Circle',
        'billing_city' => 'Wilmington',
        'billing_state' => 'DE',
        'billing_zip' => '19804',
        'bank_name' => 'A Bank',
        'account_number' => '12345678',
        'account_number_confirmation' => '12345678',
        'routing_number' => '555123',
        'check_type' => CheckType::PERSONAL,
        'account_type' => AccountType::PERSONAL_CHECKING,
        'auto_pay' => false,
    ];

    public int $paymentProfileId;
    public Account $account;
    public TransactionSetupService|MockInterface|null $transactionSetupServiceMock;
    public AccountService|MockInterface|null $accountServiceMock;
    public PaymentProfileService|MockInterface|null $paymentProfileServiceMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->paymentProfileServiceMock = Mockery::mock(PaymentProfileService::class);
        $this->instance(PaymentProfileService::class, $this->paymentProfileServiceMock);
        $this->instance(AccountService::class, $this->accountServiceMock);

        $this->paymentProfileId = $this->getTestPaymentProfileId();

        $this->account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
    }

    public function test_payment_profiles_get_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getUserAccountPaymentProfilesJsonResponse()
        );
    }

    public function test_payment_profiles_get_shows_error_when_account_does_not_belong_to_customer(): void
    {
        $this->createAndLogInAuth0User();

        $this->getUserAccountPaymentProfilesJsonResponse()
            ->assertNotFound();
    }

    /**
     * @param Collection<int, PaymentProfileModel> $paymentProfiles
     * @param StatusType[] $statuses
     * @param PaymentMethod[] $paymentMethods
     *
     * @return MockInterface|ShowCustomerPaymentProfilesAction
     */
    private function mockShowCustomerPaymentProfilesAction(
        Collection $paymentProfiles,
        array $inputStatuses = [],
        array $inputPaymentMethods = [],
    ): MockInterface|ShowCustomerPaymentProfilesAction {
        $actionMock = Mockery::mock(ShowCustomerPaymentProfilesAction::class);
        $actionMock->shouldReceive('__invoke')
            ->withArgs(function (
                Account $account,
                array $statuses,
                array $paymentMethods
            ) use ($inputStatuses, $inputPaymentMethods) {
                return $account->account_number === $this->getTestAccountNumber()
                    && $statuses === $inputStatuses
                    && $paymentMethods === $inputPaymentMethods;
            })
            ->once()
            ->andReturn($paymentProfiles);
        $this->instance(ShowCustomerPaymentProfilesAction::class, $actionMock);

        return $actionMock;
    }

    public function test_payment_profiles_get_shows_payment_profiles(): void
    {
        $paymentProfiles = PaymentProfileData::getTestEntityData();
        /** @var PaymentProfileModel $paymentProfile */
        $paymentProfile = $paymentProfiles->first();

        $this->createAndLogInAuth0UserWithAccount();
        $this->mockShowCustomerPaymentProfilesAction($paymentProfiles);

        $response = $this->getUserAccountPaymentProfilesJsonResponse();

        $response
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where(
                        'links.self',
                        '/api/v1/user/accounts/' . $this->account->account_number . '/paymentprofiles'
                    )
                    ->where('data.0.id', (string) $paymentProfile->id)
                    ->where('data.0.type', 'PaymentProfile')
                    ->where('data.0.attributes.customerId', $paymentProfile->customerId)
            );
    }

    /**
     * @dataProvider provideInvalidRequestData
     */
    public function test_payment_profiles_get_throws_validation_error_on_invalid_request(
        string|null $status,
        string|null $paymentMethod,
        string $error,
    ): void {
        $this->createAndLogInAuth0UserWithAccount();

        $action = Mockery::mock(ShowCustomerPaymentProfilesAction::class);
        $action->shouldReceive('__invoke')
            ->andReturn(PaymentProfileData::getTestEntityData());
        $this->instance(ShowCustomerPaymentProfilesAction::class, $action);

        $response = $this->getUserAccountPaymentProfilesJsonResponse($status, $paymentMethod);
        $response->assertUnprocessable()
            ->assertJsonPath('message', $error);
    }

    public function test_payment_profiles_delete_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getDeletePaymentProfileJsonResponse($this->paymentProfileId)
        );
    }

    public function test_payment_profiles_delete_shows_error_when_account_does_not_belong_to_customer(): void
    {
        $this->createAndLogInAuth0User();

        $this->getDeletePaymentProfileJsonResponse($this->paymentProfileId)
            ->assertNotFound();
    }

    /**
     * @dataProvider deleteFailureDataProvider
     */
    public function test_payment_profiles_delete_shows_error_when_delete_fails(
        Throwable $deleteException,
        int $expectedStatus
    ): void {
        $itemId = $this->paymentProfileId;

        $this->createAndLogInAuth0UserWithAccount();

        $actionMock = Mockery::mock(DeletePaymentProfileAction::class);
        $this->instance(DeletePaymentProfileAction::class, $actionMock);

        $actionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                function (Account $account, int $argumentPaymentProfileId) use ($itemId): bool {
                    return $account->account_number === $this->getTestAccountNumber()
                        && $argumentPaymentProfileId === $itemId;
                }
            )
            ->once()
            ->andThrow($deleteException);

        $this->getDeletePaymentProfileJsonResponse($itemId)
            ->assertStatus($expectedStatus);
    }

    public function test_payment_profiles_delete_deletes_payment_profile(): void
    {
        $itemId = $this->paymentProfileId;

        $this->createAndLogInAuth0UserWithAccount();

        $actionMock = Mockery::mock(DeletePaymentProfileAction::class);
        $this->instance(DeletePaymentProfileAction::class, $actionMock);

        $actionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                function (Account $account, int $argumentPaymentProfileId) use ($itemId): bool {
                    return $account->account_number === $this->getTestAccountNumber()
                        && $argumentPaymentProfileId === $itemId;
                }
            )
            ->once()
            ->andReturnNull();

        $this->getDeletePaymentProfileJsonResponse($itemId)
            ->assertNoContent();
    }

    public function test_get_payment_profiles_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getGetPaymentProfilesJsonResponse()
        );
    }

    public function test_get_payment_profiles_shows_error_when_account_does_not_belong_to_customer(): void
    {
        $this->createAndLogInAuth0User();

        $this->getGetPaymentProfilesJsonResponse()
            ->assertNotFound();
    }

    public function test_get_payment_profiles_searches_for_customer_payment_profiles(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $paymentProfiles = PaymentProfileData::getTestEntityData();

        /** @var PaymentProfileModel $paymentProfile */
        $paymentProfile = $paymentProfiles->first();

        $statuses = array_map(
            fn (string $status) => StatusType::from($status),
            explode(',', self::VALID_REQUEST_STATUSES)
        );

        $paymentMethods = array_map(
            fn (string $paymentMethod) => PaymentMethod::from($paymentMethod),
            explode(',', self::VALID_REQUEST_METHODS)
        );

        $this->mockShowCustomerPaymentProfilesAction($paymentProfiles, $statuses, $paymentMethods);

        $response = $this->getGetPaymentProfilesJsonResponse();

        $response
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where($paymentProfile->id . '.customerID', $paymentProfile->customerId)
                    ->where($paymentProfile->id . '.billingAddress1', $paymentProfile->primaryBillingAddress)
                    ->where($paymentProfile->id . '.billingAddress2', $paymentProfile->secondaryBillingAddress)
                    ->where($paymentProfile->id . '.lastFour', $paymentProfile->cardLastFour)
                    ->where($paymentProfile->id . '.merchantID', $paymentProfile->merchantId)
                    ->where($paymentProfile->id . '.description', $paymentProfile->description)
                    ->where($paymentProfile->id . '.billingName', $paymentProfile->billingName)
                    ->where($paymentProfile->id . '.billingCity', $paymentProfile->billingCity)
                    ->where($paymentProfile->id . '.billingState', $paymentProfile->billingState)
                    ->where($paymentProfile->id . '.billingZip', $paymentProfile->billingZip)
                    ->where($paymentProfile->id . '.billingPhone', $paymentProfile->billingPhone)
                    ->where($paymentProfile->id . '.billingEmail', $paymentProfile->billingEmail)
                    ->where($paymentProfile->id . '.bankName', $paymentProfile->bankName)
                    ->where($paymentProfile->id . '.accountNumber', $paymentProfile->accountNumber)
                    ->where($paymentProfile->id . '.routingNumber', $paymentProfile->routingNumber)
                    ->where($paymentProfile->id . '.paymentMethod', $paymentProfile->paymentMethod->value)
                    ->where($paymentProfile->id . '.accountType', $paymentProfile->accountType->value)
                    ->where($paymentProfile->id . '.checkType', $paymentProfile->checkType->value)
                    ->where($paymentProfile->id . '.status', $paymentProfile->status->value)
                    ->where($paymentProfile->id . '.cardType', $paymentProfile->cardType)
                    ->where($paymentProfile->id . '.expMonth', $paymentProfile->cardExpirationMonth)
                    ->where($paymentProfile->id . '.expYear', $paymentProfile->cardExpirationYear)
                    ->where($paymentProfile->id . '.id', $paymentProfile->id)
                    ->where($paymentProfile->id . '.isValid', $paymentProfile->isValid)
            );
    }

    public function test_get_payment_profiles_handles_fatal_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $actionMock = Mockery::mock(ShowCustomerPaymentProfilesAction::class);
        $this->instance(ShowCustomerPaymentProfilesAction::class, $actionMock);
        $actionMock->shouldReceive('__invoke')->andThrow(new Exception('Error'));

        $response = $this->getGetPaymentProfilesJsonResponse();

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJsonPath('message', 'Error');
    }

    public function test_get_payment_profiles_returns404_for_not_existing_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $accountNumber = $this->getTestAccountNumber() + 1;

        $response = $this->getGetPaymentProfilesJsonResponse($accountNumber);

        $response->assertNotFound()
            ->assertJsonPath('message', 'Account number not found');
    }

    /**
     * @dataProvider provideInvalidRequestData
     */
    public function test_get_payment_profiles_throws_validation_error_on_invalid_request(
        string|null $status,
        string|null $paymentMethod,
        string $error,
    ): void {
        $this->createAndLogInAuth0UserWithAccount();

        $action = Mockery::mock(ShowCustomerPaymentProfilesAction::class);
        $this->instance(ShowCustomerPaymentProfilesAction::class, $action);
        $action->shouldReceive('__invoke')->andReturn(PaymentProfileData::getTestEntityData());

        $response = $this->getGetPaymentProfilesInvalidJsonResponse($status, $paymentMethod);
        $response->assertUnprocessable()
            ->assertJsonPath('message', $error);
    }

    public function test_update_payment_profile_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getPatchPaymentProfileJsonResponse()
        );
    }

    public function test_update_payment_profile_shows_error_when_account_does_not_belong_to_customer(): void
    {
        $this->createAndLogInAuth0User();

        $this->getPatchPaymentProfileJsonResponse()
            ->assertNotFound();
    }

    public function test_update_payment_profile_updates_payment_profile(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->paymentProfileServiceMock->expects('updatePaymentProfile')
            ->with(UpdatePaymentProfileDTO::class)
            ->once()
            ->andReturn($this->getPestRoutesPaymentProfile(billingName: 'John Doe Smith'));

        $this->getPatchPaymentProfileJsonResponse()
            ->assertOk()
            ->assertExactJson($this->getResourceUpdatedExpectedResponse(
                'customer/' . $this->getTestAccountNumber() . '/paymentprofiles/' . $this->paymentProfileId,
                Resources::PAYMENT_PROFILE->value,
                $this->paymentProfileId
            ));
    }

    /**
     * @dataProvider provideUpdatePaymentProfileExceptionsData
     */
    public function test_update_payment_profile_returns_valid_responses_on_exceptions(
        $exception,
        $status,
        string $message
    ): void {
        $this->createAndLogInAuth0UserWithAccount();

        $this->paymentProfileServiceMock->expects('updatePaymentProfile')
            ->with(UpdatePaymentProfileDTO::class)
            ->andThrow($exception)
            ->once();

        $this->getPatchPaymentProfileJsonResponse()
            ->assertStatus($status)
            ->assertJsonPath('errors.0.title', $message);
    }

    public function test_update_payment_profile_returns_valid_response_on_validation_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->getPatchPaymentProfileJsonResponse(['expMonth' => 14])
            ->assertUnprocessable()
            ->assertJsonPath('errors.expMonth.0', 'The exp month must be between 1 and 12.');
    }

    public function test_creating_ach_payment_profile_calls_action(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $createAchActionMock = Mockery::mock(CreateAchPaymentProfileAction::class);
        $createAchActionMock
            ->expects('__invoke')
            ->withArgs(function (CreatePaymentProfileDTO $dto): bool {
                return $dto->customerId === $this->getTestAccountNumber();
            })
            ->once()
            ->andReturn($this->getTestPaymentProfileId());

        $this->instance(CreateAchPaymentProfileAction::class, $createAchActionMock);

        $requestData = array_merge(CreateAchPaymentProfileActionTest::PAYMENT_PROFILE_DATA, [
            'account_number_confirmation' => CreateAchPaymentProfileActionTest::PAYMENT_PROFILE_DATA['account_number'],
        ]);

        $response = $this->postJson(
            route('api.customer.paymentprofiles.ach.create', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.id', (string) $this->getTestPaymentProfileId());
    }

    public function test_creating_ach_payment_profile_returns_empty_payment_profile_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $createAchActionMock = Mockery::mock(CreateAchPaymentProfileAction::class);
        $createAchActionMock
            ->expects('__invoke')
            ->withAnyArgs()
            ->once()
            ->andThrows(new PaymentProfileIsEmptyException('Empty payment profile was created'));

        $this->instance(CreateAchPaymentProfileAction::class, $createAchActionMock);

        $requestData = array_merge(CreateAchPaymentProfileActionTest::PAYMENT_PROFILE_DATA, [
            'account_number_confirmation' => CreateAchPaymentProfileActionTest::PAYMENT_PROFILE_DATA['account_number'],
        ]);

        $response = $this->postJson(
            route('api.customer.paymentprofiles.ach.create', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response->assertStatus(HttpStatus::PAYMENT_REQUIRED);
    }

    public function test_creating_ach_payment_profile_returns_credit_card_authorization_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $createAchActionMock = Mockery::mock(CreateAchPaymentProfileAction::class);
        $createAchActionMock
            ->expects('__invoke')
            ->withAnyArgs()
            ->once()
            ->andThrows(new CreditCardAuthorizationException('Credit card authorization exception'));

        $this->instance(CreateAchPaymentProfileAction::class, $createAchActionMock);

        $requestData = array_merge(CreateAchPaymentProfileActionTest::PAYMENT_PROFILE_DATA, [
            'account_number_confirmation' => CreateAchPaymentProfileActionTest::PAYMENT_PROFILE_DATA['account_number'],
        ]);

        $response = $this->postJson(
            route('api.customer.paymentprofiles.ach.create', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response->assertStatus(HttpStatus::PAYMENT_REQUIRED);
    }

    public function test_creating_ach_payment_profile_returns_error_from_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $createAchActionMock = Mockery::mock(CreateAchPaymentProfileAction::class);
        $createAchActionMock
            ->expects('__invoke')
            ->withAnyArgs()
            ->once()
            ->andThrows(new Exception('Test'));

        $this->instance(CreateAchPaymentProfileAction::class, $createAchActionMock);

        $requestData = array_merge(CreateAchPaymentProfileActionTest::PAYMENT_PROFILE_DATA, [
            'account_number_confirmation' => CreateAchPaymentProfileActionTest::PAYMENT_PROFILE_DATA['account_number'],
        ]);

        $response = $this->postJson(
            route('api.customer.paymentprofiles.ach.create', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_creating_ach_payment_profile_validates_request(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $createAchActionMock = Mockery::mock(CreateAchPaymentProfileAction::class);
        $createAchActionMock
            ->expects('__invoke')
            ->withAnyArgs()
            ->never();

        $this->instance(CreateAchPaymentProfileAction::class, $createAchActionMock);

        $response = $this->postJson(
            route('api.customer.paymentprofiles.ach.create', ['accountNumber' => $this->getTestAccountNumber()]),
        );

        $response
            ->assertUnprocessable()
            ->assertJsonCount(10, 'errors');
    }

    public function test_initialize_credit_card_payment_profile(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $initializeActionMock = Mockery::mock(InitializeCreditCardPaymentProfileAction::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withArgs(function (...$args): bool {
                /** @var Account $accountArgument */
                $accountArgument = $args[count($args) - 1];

                return
                    array_slice($args, 0, -1) === array_values(
                        InitializeCreditCardPaymentProfileActionTest::PAYMENT_PROFILE_DATA
                    )
                    && $accountArgument->account_number === $this->getTestAccountNumber();
            })
            ->andReturn(self::REDIRECT_URI);

        $this->instance(InitializeCreditCardPaymentProfileAction::class, $initializeActionMock);

        $response = $this->postJson(
            route('api.customer.paymentprofiles.creditcard.create', ['accountNumber' => $this->getTestAccountNumber()]),
            InitializeCreditCardPaymentProfileActionTest::PAYMENT_PROFILE_DATA
        );

        $response
            ->assertCreated()
            ->assertExactJson(['uri' => self::REDIRECT_URI]);
    }

    public function test_creating_credit_card_payment_profile_returns_credit_card_authorization_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $initializeActionMock = Mockery::mock(InitializeCreditCardPaymentProfileAction::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withAnyArgs()
            ->andThrows(new CreditCardAuthorizationException('Credit card authorization exception'));

        $this->instance(InitializeCreditCardPaymentProfileAction::class, $initializeActionMock);

        $response = $this->postJson(
            route('api.customer.paymentprofiles.creditcard.create', ['accountNumber' => $this->getTestAccountNumber()]),
            InitializeCreditCardPaymentProfileActionTest::PAYMENT_PROFILE_DATA
        );

        $response->assertStatus(HttpStatus::PAYMENT_REQUIRED);
    }

    public function test_initialize_credit_card_payment_profile_error_from_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $initializeActionMock = Mockery::mock(InitializeCreditCardPaymentProfileAction::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withAnyArgs()
            ->andThrows(new Exception('Test'));

        $this->instance(InitializeCreditCardPaymentProfileAction::class, $initializeActionMock);

        $response = $this->postJson(
            route('api.customer.paymentprofiles.creditcard.create', ['accountNumber' => $this->getTestAccountNumber()]),
            InitializeCreditCardPaymentProfileActionTest::PAYMENT_PROFILE_DATA
        );

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
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
                'api.customer.paymentprofiles.creditcard.complete',
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

    /**
     * @dataProvider provideCompleteCreditCardActionExceptions
     */
    public function test_complete_credit_card_payment_profile_returns_error_on_exception(
        Throwable $exception,
        int $status,
    ): void {
        $this->createAndLogInAuth0UserWithAccount();
        $transactionSetupId = Str::uuid()->toString();

        $initializeActionMock = Mockery::mock(CompleteCreditCardPaymentProfileAction::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withAnyArgs()
            ->andThrows($exception);

        $this->instance(CompleteCreditCardPaymentProfileAction::class, $initializeActionMock);

        $response = $this->postJson(
            route(
                'api.customer.paymentprofiles.creditcard.complete',
                [
                    'accountNumber' => $this->getTestAccountNumber(),
                    'transactionSetupId' => $transactionSetupId,
                ]
            ),
            CompleteCreditCardPaymentProfileActionTest::PAYMENT_PROFILE_DATA
        );

        $response->assertStatus($status);
    }

    public function provideCompleteCreditCardActionExceptions(): array
    {
        return [
            [
                'exception' => new Exception('Test'),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR
            ],
            [
                'exception' => new TransactionSetupNotFoundException(),
                'status' => Response::HTTP_NOT_FOUND
            ],
            [
                'exception' => new JsonValidationException(),
                'status' => HttpStatus::UNPROCESSABLE_ENTITY
            ],
            [
                'exception' => new PaymentProfileNotFoundException(),
                'status' => Response::HTTP_NOT_FOUND
            ],
        ];
    }

    public function test_complete_credit_card_payment_profile_returns_205_when_setup_is_already_complete(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $transactionSetupId = Str::uuid()->toString();
        $accountNumber = $this->getTestAccountNumber();

        $initializeActionMock = Mockery::mock(CompleteCreditCardPaymentProfileAction::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withAnyArgs()
            ->andThrows(new TransactionSetupAlreadyCompleteException());

        $this->instance(CompleteCreditCardPaymentProfileAction::class, $initializeActionMock);

        $response = $this->postJson(
            route(
                'api.customer.paymentprofiles.creditcard.complete',
                [
                    'accountNumber' => $accountNumber,
                    'transactionSetupId' => $transactionSetupId,
                ]
            ),
            CompleteCreditCardPaymentProfileActionTest::PAYMENT_PROFILE_DATA
        );

        $response->assertStatus(Response::HTTP_RESET_CONTENT);
    }

    public function provideInvalidRequestData(): array
    {
        return [
            [
                'statuses' => 'all',
                'paymentMethods' => null,
                'error' => 'The selected statuses.0 is invalid.',
            ],
            [
                'statuses' => 'valid,valid',
                'paymentMethods' => null,
                'error' => 'The statuses.0 field has a duplicate value. (and 1 more error)',
            ],
            [
                'statuses' => null,
                'paymentMethods' => 'NO',
                'error' => 'The selected paymentMethods.0 is invalid.',
            ],
            [
                'statuses' => null,
                'paymentMethods' => 'ACH,ACH',
                'error' => 'The paymentMethods.0 field has a duplicate value. (and 1 more error)',
            ],
        ];
    }

    public function deleteFailureDataProvider(): array
    {
        return [
            [
                'deleteException' => new PaymentProfileNotFoundException(),
                'expectedStatus' => Response::HTTP_NOT_FOUND,
            ],
            'api error' => [
                'deleteException' => new PaymentProfileNotDeletedException(),
                'expectedStatus' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ],
            'PP is autopay' => [
                'deleteException' => new PaymentProfileNotDeletedException(
                    'Can not delete a payment profile because it is set for autopay.',
                    PaymentProfileNotDeletedException::STATUS_LOCKED
                ),
                'expectedStatus' => Response::HTTP_CONFLICT,
            ],
            [
                'deleteException' => new UnauthorizedException(),
                'expectedStatus' => Response::HTTP_UNAUTHORIZED,
            ],
            [
                'abstractHttpException' => new PaymentProfileNotFoundException(),
                'expectedStatus' => Response::HTTP_NOT_FOUND,
            ],
        ];
    }

    public function provideUpdatePaymentProfileExceptionsData(): array
    {
        return [
            [
                'exception' => new Exception(),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => '500 Internal Server Error',
            ],
            [
                'exception' => new PaymentProfileNotFoundException(),
                'status' => Response::HTTP_NOT_FOUND,
                'message' => '404 Not Found',
            ]
        ];
    }

    protected function getUserAccountPaymentProfilesJsonResponse($status = null, $paymentMethod = null): TestResponse
    {
        return $this->getJson(
            route('api.user.accounts.paymentprofiles.get', $this->getRequestData($status, $paymentMethod))
        );
    }

    protected function getGetPaymentProfilesInvalidJsonResponse($status = null, $paymentMethod = null): TestResponse
    {
        return $this->getJson(
            route('api.customer.getpaymentprofiles', $this->getRequestData($status, $paymentMethod))
        );
    }

    protected function getRequestData($status = null, $paymentMethod = null): array
    {
        $request['accountNumber'] = $this->getTestAccountNumber();

        if ($status) {
            $request['statuses'] = $status;
        }

        if ($paymentMethod) {
            $request['paymentMethods'] = $paymentMethod;
        }

        return $request;
    }

    protected function getGetPaymentProfilesJsonResponse(int|null $accountNumber = null): TestResponse
    {
        $accountNumber = $accountNumber ?? $this->getTestAccountNumber();

        return $this->getJson(route(
            'api.customer.getpaymentprofiles',
            [
                'accountNumber' => $accountNumber,
                'statuses' => self::VALID_REQUEST_STATUSES,
                'paymentMethods' => self::VALID_REQUEST_METHODS,
            ]
        ));
    }

    protected function getPatchPaymentProfileJsonResponse(array $request = []): TestResponse
    {
        $request = array_merge(
            [
                'billingFName' => 'Jonh',
                'billingLName' => 'Doe',
                'expMonth' => 12,
            ],
            $request
        );

        $route = route(
            'api.customer.paymentprofiles.update',
            [
                'accountNumber' => $this->getTestAccountNumber(),
                'paymentProfileId' =>  $this->paymentProfileId,
            ]
        );

        return $this->patchJson($route, $request);
    }

    protected function getDeletePaymentProfileJsonResponse(int $paymentProfileId): TestResponse
    {
        return $this->deleteJson(route(
            'api.user.accounts.paymentprofiles.delete',
            [
                'accountNumber' => $this->getTestAccountNumber(),
                'paymentProfileId' => $paymentProfileId,
            ]
        ));
    }

    public function test_creating_ach_payment_profile_returns_abstract_http_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $createAchActionMock = Mockery::mock(CreateAchPaymentProfileAction::class);
        $createAchActionMock
            ->expects('__invoke')
            ->withAnyArgs()
            ->once()
            ->andThrows(new PaymentProfileNotFoundException());

        $this->instance(CreateAchPaymentProfileAction::class, $createAchActionMock);

        $requestData = array_merge(CreateAchPaymentProfileActionTest::PAYMENT_PROFILE_DATA, [
            'account_number_confirmation' => CreateAchPaymentProfileActionTest::PAYMENT_PROFILE_DATA['account_number'],
        ]);

        $response = $this->postJson(
            route('api.customer.paymentprofiles.ach.create', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_creating_credit_card_payment_profile_returns_abstract_http_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $initializeActionMock = Mockery::mock(InitializeCreditCardPaymentProfileAction::class);
        $initializeActionMock
            ->expects('__invoke')
            ->once()
            ->withAnyArgs()
            ->andThrows(new paymentprofileNotFoundException());

        $this->instance(InitializeCreditCardPaymentProfileAction::class, $initializeActionMock);

        $response = $this->postJson(
            route('api.customer.paymentprofiles.creditcard.create', ['accountNumber' => $this->getTestAccountNumber()]),
            InitializeCreditCardPaymentProfileActionTest::PAYMENT_PROFILE_DATA
        );

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }
}
