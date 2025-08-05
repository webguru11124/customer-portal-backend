<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\Appointment\ShowAppointmentsHistoryAction;
use App\Actions\Customer\ShowCustomerActionV2;
use App\Actions\Subscription\ShowSubscriptionsAction;
use App\Actions\Ticket\ShowCustomersTicketsAction;
use App\DTO\Customer\AutoPayResponseDTO;
use App\DTO\Customer\ShowCustomerSubscriptionResultDTO;
use App\DTO\Customer\UpdateCommunicationPreferencesDTO;
use App\DTO\Customer\V2\ShowCustomerResultDTO;
use App\Enums\Resources;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Http\Responses\GetAccountsResponse;
use App\Http\Responses\SearchSubscriptionsResponse;
use App\Http\Responses\SearchTicketsResponse;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\OfficeRepository;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Models\Account;
use App\Models\External\SubscriptionModel;
use App\Models\External\TicketModel;
use App\Models\User;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use App\Services\AccountService;
use App\Services\CustomerService;
use App\Services\PlanBuilderService;
use App\Services\SubscriptionUpgradeService;
use App\Services\UserService;
use App\Utilites\CustomerDutyDeterminer;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAutoPay;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerStatus;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\CustomerData;
use Tests\Data\ServiceTypeData;
use Tests\Data\SubscriptionData;
use Tests\Data\TicketData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;
use Throwable;
use DateTimeImmutable;

class CustomerControllerTest extends ApiTestCase
{
    use RandomIntTestData;
    use TestAuthorizationMiddleware;
    use RefreshDatabase;
    public Account $account;
    private const SEARCH_ROUTE_NAME = 'api.v2.customer.invoices.get';
    protected string $userAccountsRouteName = 'api.v2.user.accounts';
    protected string $userAccountsRouteURL = '/api/v2/user/accounts';
    public MockInterface|AccountService $accountServiceMock;
    public MockInterface|ShowCustomerActionV2 $showCustomerActionMock;
    public MockInterface|ShowCustomersTicketsAction $showCustomersTicketsActionMock;
    public MockInterface|ShowSubscriptionsAction $showSubscriptionsActionMock;
    public MockInterface|CustomerService $customerServiceMock;
    public MockInterface|GetAccountsResponse $getAccountsResponseMock;
    public MockInterface|SearchTicketsResponse $searchTicketsResponseMock;
    public MockInterface|SearchSubscriptionsResponse $searchSubscriptionsResponseMock;
    public MockInterface|UserService $userServiceMock;

    /*
     * Mock Repository for the ShowCustomerActionV2
     */
    protected MockInterface|OfficeRepository $officeRepositoryMock;
    protected MockInterface|CustomerRepository $customerRepositoryMock;
    protected MockInterface|PaymentProfileRepository $paymentProfileRepositoryMock;
    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;
    protected MockInterface|ServiceTypeRepository $serviceTypeRepositoryMock;
    protected MockInterface|CustomerDutyDeterminer $customerDutyDeterminerMock;
    protected MockInterface|SubscriptionUpgradeService $subscriptionUpgradeServiceMock;
    protected MockInterface|AptivePaymentRepository $paymentRepositoryMock;
    protected MockInterface|PlanBuilderService $planBuilderServiceMock;

    public MockInterface|ShowAppointmentsHistoryAction $showAppointmentsHistoryActionMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->instance(AccountService::class, $this->accountServiceMock);

        $this->officeRepositoryMock = Mockery::mock(OfficeRepository::class);
        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->paymentProfileRepositoryMock = Mockery::mock(PaymentProfileRepository::class);
        $this->customerDutyDeterminerMock = Mockery::mock(CustomerDutyDeterminer::class);
        $this->subscriptionUpgradeServiceMock = Mockery::mock(SubscriptionUpgradeService::class);
        $this->paymentRepositoryMock = Mockery::mock(AptivePaymentRepository::class);
        $this->planBuilderServiceMock = Mockery::mock(PlanBuilderService::class);
        $this->appointmentRepositoryMock = Mockery::mock(AppointmentRepository::class);
        $this->showAppointmentsHistoryActionMock = Mockery::mock(ShowAppointmentsHistoryAction::class);
        $this->showCustomerActionMock = Mockery::mock(ShowCustomerActionV2::class, [
            $this->customerRepositoryMock,
            $this->paymentProfileRepositoryMock,
            $this->customerDutyDeterminerMock,
            $this->subscriptionUpgradeServiceMock,
            $this->paymentRepositoryMock,
            $this->planBuilderServiceMock,
            $this->appointmentRepositoryMock,
            $this->showAppointmentsHistoryActionMock
        ])->makePartial();
        $this->showCustomerActionMock->shouldAllowMockingProtectedMethods();
        $this->instance(ShowCustomerActionV2::class, $this->showCustomerActionMock);


        $this->showCustomersTicketsActionMock = Mockery::mock(ShowCustomersTicketsAction::class);
        $this->instance(ShowCustomersTicketsAction::class, $this->showCustomersTicketsActionMock);

        $this->showSubscriptionsActionMock = Mockery::mock(ShowSubscriptionsAction::class);
        $this->instance(ShowSubscriptionsAction::class, $this->showSubscriptionsActionMock);

        $this->customerServiceMock = Mockery::mock(CustomerService::class);
        $this->instance(CustomerService::class, $this->customerServiceMock);

        $this->account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
    }

    protected function getUserSubscriptionsJsonResponse(): TestResponse
    {
        return $this->getJson(
            route('api.v2.customer.subscriptions.get', ['accountNumber' => $this->getTestAccountNumber()])
        );
    }

    public function test_get_customer_data_returns_subscription_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->createAndLogInAuth0UserWithAccount();
        $this->showSubscriptionsActionMock = Mockery::mock(ShowSubscriptionsAction::class);

        $subscriptions = SubscriptionData::getTestEntityData(
            1,
            ['nextService' => '2022-08-13'],
            ['nextService' => '2022-09-30'],
        );
        $serviceType = ServiceTypeData::getTestEntityData()->first();
        $subscriptions->each(
            fn (SubscriptionModel $subscriptions) => $subscriptions->setRelated('serviceType', $serviceType)
        );
        $this->instance(ShowSubscriptionsAction::class, $this->showSubscriptionsActionMock);
        $this->showSubscriptionsActionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (Account $account) => $account->office_id === $this->getTestOfficeId()
                    && $account->account_number === $this->getTestAccountNumber()
            )->once()
            ->andReturn($subscriptions);
        $subscriptionsArray = $subscriptions->toArray();
        $response = $this->getJson($this->getCustomerDataRoute());
        $response->assertOk();
        $response->assertJsonPath('data.subscription.data.0.id', $subscriptionsArray[0]['id']);
        $response->assertJsonPath('data.subscription.data.0.customerId', $subscriptionsArray[0]['customerId']);
        $response->assertJsonPath('data.subscription.status', 'ok');
        $response->assertJsonPath('data.autoPay.status', 'error');
        $response->assertJsonPath('data.invoice.status', 'error');
        $response->assertJsonPath('data.account.status', 'error');
    }


    public function test_get_customer_data_returns_invoice_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->createAndLogInAuth0UserWithAccount();

        $tickets = TicketData::getTestEntityData(2);
        $tickets->each(
            fn (TicketModel $ticket) => $ticket->setRelated('appointment', null)
        );

        $this->showCustomersTicketsActionMock = Mockery::mock(ShowCustomersTicketsAction::class);
        $this
            ->showCustomersTicketsActionMock
            ->expects('__invoke')
            ->withArgs([
                $this->account->office_id,
                $this->account->account_number,
                true,
            ])
            ->once()
            ->andReturn($tickets);
        $this->instance(ShowCustomersTicketsAction::class, $this->showCustomersTicketsActionMock);

        $this
            ->getJson($this->getCustomerDataRoute())
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json->has('data')->has('links')->etc())
            ->assertJsonPath('data.invoice.status', 'ok')
            ->assertJsonPath('data.autoPay.status', 'error')
            ->assertJsonPath('data.account.status', 'error')
            ->assertJsonPath('data.subscription.status', 'error')
            ->assertJsonCount(4, 'data');
    }

    public function test_customer_data_handles_exceptions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->createAndLogInAuth0UserWithAccount();

        $this->showSubscriptionsActionMock = Mockery::mock(ShowSubscriptionsAction::class);
        $this->showSubscriptionsActionMock
            ->shouldReceive('__invoke')
            ->andThrow(new Exception('Subscription exception'));
        $this->instance(ShowSubscriptionsAction::class, $this->showSubscriptionsActionMock);

        $this->showCustomersTicketsActionMock = Mockery::mock(ShowCustomersTicketsAction::class);
        $this->showCustomersTicketsActionMock
            ->shouldReceive('__invoke')
            ->andThrow(new Exception('Invoice exception'));
        $this->instance(ShowCustomersTicketsAction::class, $this->showCustomersTicketsActionMock);

        $this->customerServiceMock = Mockery::mock(CustomerService::class);
        $this->customerServiceMock
            ->shouldReceive('getCustomerAutoPayData')
            ->andThrow(new Exception('Auto-pay exception'));
        $this->instance(CustomerService::class, $this->customerServiceMock);

        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->accountServiceMock
            ->shouldReceive('getAccountData')
            ->andThrow(new Exception('Account data exception'));
        $this->instance(AccountService::class, $this->accountServiceMock);

        $response = $this->getJson($this->getCustomerDataRoute());

        $response->assertOk();
        $response->assertJsonPath('data.subscription.status', 'error');
        $response->assertJsonPath('data.invoice.status', 'error');
        $response->assertJsonPath('data.autoPay.status', 'error');
        $response->assertJsonPath('data.account.status', 'error'); // New check for account data
        $response->assertJson([
                "links" => [
                    "self" => sprintf("/api/v2/customer/%d/data", $this->getTestAccountNumber())
                ],
                "data" => [
                    "subscription" => [
                        "status" => "error",
                        "data" => []
                    ],
                    "account" => [
                        "status" => "error",
                        "data" => []
                    ],
                    "invoice" => [
                        "status" => "error",
                        "data" => []
                    ],
                    "autoPay" => [
                        "status" => "error",
                        "data" => []
                    ]
                ]
            ]);
    }

    public function test_get_customer_data_returns_auto_pay_data(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $nextPaymentDate = new DateTimeImmutable('tomorrow');
        $autoPayData = new AutoPayResponseDTO(
            id: 19997,
            isEnabled: true,
            cardType: 'Visa',
            cardLastFour: '1111',
            planName: 'VIP',
            nextPaymentAmount: 197.97,
            nextPaymentDate: $nextPaymentDate,
            preferredBillingDate: 'January 1st'
        );
        $this->instance(CustomerService::class, $this->customerServiceMock);

        $this->customerServiceMock
            ->shouldReceive('getCustomerAutoPayData')
            ->withArgs(fn (Account $account) => $account->account_number === $this->getTestAccountNumber())
            ->once()
            ->andReturn([$autoPayData]);

        $response = $this->getJson($this->getCustomerDataRoute());
        $response->assertOk();
        $expectedAutoPayData = [
            "id" => 19997,
            "isEnabled" => true,
            "planName" => 'VIP',
            "nextPaymentAmount" => 197.97,
            "nextPaymentDate" => $response['data']['autoPay']['data'][0]['nextPaymentDate'],
            "cardType" => 'Visa',
            "cardLastFour" => '1111',
            "preferredBillingDate" => 'January 1st'
        ];
        $response->assertJsonPath('data.autoPay.data.0', $expectedAutoPayData);
        $response->assertJsonPath('data.subscription.status', 'error');
        $response->assertJsonPath('data.invoice.status', 'error');
        $response->assertJsonPath('data.account.status', 'error');
    }

    /**
     * Recursively convert an object and its nested objects to an array.
     *
     * @param array|object $object The object to convert to an array.
     * @return array|object
     */
    private function convertObjectToArray($object)
    {
        if (is_object($object)) {
            $array = [];
            foreach ($object as $key => $value) {
                if (is_object($value) || is_array($value)) {
                    $array[$key] = $this->convertObjectToArray($value);
                } else {
                    $array[$key] = $value;
                }
            }
            return $array;
        } elseif (is_array($object)) {
            return array_map([$this, 'convertObjectToArray'], $object);
        }
        return $object;
    }

    public function test_get_customer_data_returns_accounts_data(): void
    {
        $user = $this->createAndLogInAuth0UserWithAccount();
        $customersCollection = CustomerData::getTestEntityData(1);
        $this->customerServiceMock = Mockery::mock(CustomerService::class);
        $this->userServiceMock = Mockery::mock(UserService::class, [
            $this->customerRepositoryMock,
            $this->officeRepositoryMock
        ])->makePartial();

        $this->instance(CustomerService::class, $this->customerServiceMock);
        $this->instance(UserService::class, $this->userServiceMock);

        $offices = [$user->accounts->first()->office_id];
        $this->officeRepositoryMock
            ->shouldReceive('getAllOfficeIds')
            ->once()
            ->andReturn($offices);

        $this->customerRepositoryMock
            ->shouldReceive('office')->andReturnSelf()
            ->shouldReceive('searchActiveCustomersByEmail')
            ->withArgs([$user->email, $offices])
            ->once()
            ->andReturn($customersCollection);

        $this->customerServiceMock
            ->shouldReceive('getActiveCustomersCollectionForUser')
            ->withArgs([$user])
            ->once()
            ->andReturn($customersCollection);

        $this->userServiceMock
            ->shouldReceive('findUserByEmailAndExtId')
            ->with($user->email, $user->external_id, User::AUTH0COLUMN)
            ->once()
            ->andReturn($user);
        $this->userServiceMock
            ->shouldReceive('syncUserAccounts')
            ->withArgs([$user])
            ->once();


        $accountNumber = $this->getTestAccountNumber();
        $response = $this->getJson($this->getCustomerDataRoute($accountNumber));
        $account = $customersCollection->first();
        $response
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->where('links.self', '/api/v2/customer/' . $accountNumber . '/data')
                    ->where('data.account.data.0.id', $account->id)
                    ->where('data.account.data.0.firstName', $account->firstName)
                    ->where('data.account.data.0.lastName', $account->lastName)
                    ->where('data.account.data.0.email', $account->email)
                    ->where('data.account.data.0.phones', $this->convertObjectToArray($account->phones))
                    ->where('data.account.data.0.address', $this->convertObjectToArray($account->address))
                    ->where('data.subscription.status', 'error')
                    ->where('data.invoice.status', 'error')
                    ->where('data.autoPay.status', 'error')
                    ->where('data.account.data.0.billingInformation', $this->convertObjectToArray($account->billingInformation))
            );

    }

    public function test_show_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getCustomerShowJsonResponse()
        );
    }

    public function test_show_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();
        $this->getCustomerShowJsonResponse()->assertNotFound();
    }

    public function test_show_requires_account_number_belonging_to_user(): void
    {
        $this->createAndLogInAuth0User();

        $user = User::factory()->create();
        $account = Account::factory()->make([
            'account_number' => $this->getTestAccountNumber() + 1,
            'office_id' => $this->getTestOfficeId(),
        ]);
        $user->accounts()->save($account);

        $this
            ->getCustomerShowJsonResponse($this->getTestAccountNumber() + 1)
            ->assertNotFound();
    }

    public function test_show_searches_for_existing_customer_without_autopay(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->checkShowReturnsValidCustomer();
    }

    protected function checkShowReturnsValidCustomer()
    {
        /** @var Account $account */
        $account = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);

        $subscription = SubscriptionData::getTestEntityData()->first();
        $subscription->setRelated('serviceType', ServiceTypeData::getTestEntityData()->first());

        $subscriptionUpgradeService = \Mockery::mock(SubscriptionUpgradeService::class);
        $subscriptionUpgradeService
            ->shouldReceive('isUpgradeAvailable')
            ->withArgs([$subscription])
            ->once()
            ->andReturn(true);

        $subscriptionUpgradeService
            ->shouldReceive('getPlanBuilderPlanSpecialtyPestsProducts')
            ->withArgs([$subscription])
            ->once()
            ->andReturn([]);

        $actionOutcome = new ShowCustomerResultDTO(
            id: $account->account_number,
            officeId: $account->office_id,
            firstName: 'FirstName',
            lastName: 'LastName',
            email: 'email@test.com',
            phoneNumber: (string) random_int(10000000, 99999999),
            balanceCents: random_int(10, 100),
            isOnMonthlyBilling: true,
            dueDate: Carbon::now()->addDays(random_int(1, 10))->format('Y-m-d'),
            paymentProfileId: random_int(100, 99999),
            autoPayProfileLastFour: (string) random_int(1000, 9999),
            isDueForStandardTreatment: true,
            lastTreatmentDate: Carbon::now()->subDays(random_int(1, 10))->format('Y-m-d'),
            status: CustomerStatus::Active,
            autoPayMethod: CustomerAutoPay::AutoPayCC,
            subscription: new ShowCustomerSubscriptionResultDTO(
                subscription: $subscription,
                subscriptionUpgradeService: $subscriptionUpgradeService
            ),
        );
        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andReturn($account)
            ->once();

        $this->showCustomerActionMock
            ->shouldReceive('__invoke')
            ->andReturn($actionOutcome);

        $this->getCustomerShowJsonResponse()
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('links.self', '/api/v2/customer/' . $actionOutcome->id)
                    ->where('data.id', (string) $actionOutcome->id)
                    ->where('data.type', 'Customer')
                    ->where('data.attributes.officeId', $actionOutcome->officeId)
                    ->where('data.attributes.firstName', $actionOutcome->firstName)
                    ->where('data.attributes.lastName', $actionOutcome->lastName)
                    ->where('data.attributes.name', $actionOutcome->name)
                    ->where('data.attributes.statusName', $actionOutcome->statusName)
                    ->where('data.attributes.email', $actionOutcome->email)
                    ->where('data.attributes.isEmailValid', $actionOutcome->isEmailValid)
                    ->where('data.attributes.phoneNumber', $actionOutcome->phoneNumber)
                    ->where('data.attributes.isPhoneNumberValid', $actionOutcome->isPhoneNumberValid)
                    ->where('data.attributes.autoPay', $actionOutcome->autoPay)
                    ->where('data.attributes.balanceCents', $actionOutcome->balanceCents)
                    ->where('data.attributes.isOnMonthlyBilling', $actionOutcome->isOnMonthlyBilling)
                    ->where('data.attributes.dueDate', $actionOutcome->dueDate)
                    ->where('data.attributes.paymentProfileId', $actionOutcome->paymentProfileId)
                    ->where('data.attributes.autoPayProfileLastFour', $actionOutcome->autoPayProfileLastFour)
                    ->where('data.attributes.isDueForStandardTreatment', $actionOutcome->isDueForStandardTreatment)
                    ->where('data.attributes.lastTreatmentDate', $actionOutcome->lastTreatmentDate)
                    ->where('data.attributes.subscription.id', $actionOutcome->subscription->id)
                    ->where('data.attributes.subscription.agreementDate', $actionOutcome->subscription->agreementDate)
                    ->where('data.attributes.subscription.agreementLength', $actionOutcome->subscription->agreementLength)
                    ->where('data.attributes.subscription.serviceType', $actionOutcome->subscription->serviceType)
                    ->where('data.attributes.subscription.specialtyPests', $actionOutcome->subscription->specialtyPests)
                    ->where('data.attributes.subscription.isUpgradeAvailable', $actionOutcome->subscription->isUpgradeAvailable)
            );
    }

    public function test_show_searches_for_existing_frozen_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andThrow(new AccountFrozenException())
            ->once();

        $this->getCustomerShowJsonResponse()->assertNotFound();
    }

    public function test_show_handles_non_existing_customer(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andThrow(new ModelNotFoundException())
            ->once();

        $this->getCustomerShowJsonResponse()->assertNotFound();
    }

    public function test_show_handles_fatal_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andThrow(new Exception('Error'))
            ->once();

        $this->getCustomerShowJsonResponse()
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_show_handles_payment_profile_not_found_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andReturn(Account::factory()->makeOne())
            ->once();

        $this->showCustomerActionMock
            ->shouldReceive('__invoke')
            ->andThrow(new PaymentProfileNotFoundException());

        $this->getCustomerShowJsonResponse()
            ->assertNotFound();
    }

    public function test_update_communication_preferences_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();
        $this->getCommunicationPreferencesJsonResponse([
            'smsReminders' => '1',
            'emailReminders' => '1',
            'phoneReminders' => '1',
        ])
            ->assertNotFound();
    }

    /**
     * @dataProvider updateCommunicationPreferencesExceptionProvider
     */
    public function test_update_communication_preferences_shows_error_when_exception_occurs_during_contact_preferences_update(
        Throwable $exception
    ): void {
        $this->createAndLogInAuth0UserWithAccount();

        $customerServiceMock = Mockery::mock(CustomerService::class);
        $customerServiceMock
            ->expects('updateCommunicationPreferences')
            ->withAnyArgs()
            ->once()
            ->andThrow($exception);

        $this->instance(CustomerService::class, $customerServiceMock);

        $this->getCommunicationPreferencesJsonResponse([
            'smsReminders' => '0',
            'emailReminders' => '0',
            'phoneReminders' => '0',
        ])
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_update_communication_preferences_shows_validation_error_when_some_data_is_missing(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->getCommunicationPreferencesJsonResponse(['smsReminders' => 'a'])
            ->assertUnprocessable()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->has('message')
                    ->has('errors', 3)
                    ->whereAllType([
                        'errors.smsReminders.0' => 'string',
                        'errors.phoneReminders.0' => 'string',
                        'errors.emailReminders.0' => 'string',
                    ])
            );
    }

    public function test_update_communication_preferences_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->getCommunicationPreferencesJsonResponse([
            'smsReminders' => '0',
            'emailReminders' => '0',
            'phoneReminders' => '0',
        ]));
    }

    public function updateCommunicationPreferencesExceptionProvider(): array
    {
        return [
            [new ResourceNotFoundException()],
            [new InternalServerErrorHttpException()],
        ];
    }

    public function test_update_communication_preferences_updates_communication_preferences(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $customerServiceMock = Mockery::mock(CustomerService::class);
        $customerServiceMock
            ->expects('updateCommunicationPreferences')
            ->withArgs(
                fn ($dto) => $this->validateCommunicationPreferencesDtoValues($dto, true, false, true)
            )
            ->once()
            ->andReturn($this->getTestAccountNumber());

        $this->instance(CustomerService::class, $customerServiceMock);

        $this->getCommunicationPreferencesJsonResponse([
            'smsReminders' => '1',
            'emailReminders' => '0',
            'phoneReminders' => '1',
        ])
            ->assertOk()
            ->assertExactJson([
                'links' => [
                    'self' => sprintf('/api/v2/customer/%d/communication-preferences', $this->getTestAccountNumber()),
                ],
                'data' => [
                    'type' => Resources::CUSTOMER->value,
                    'id' => (string) $this->getTestAccountNumber(),
                ],
            ]);
    }

    private function validateCommunicationPreferencesDtoValues(
        UpdateCommunicationPreferencesDTO $dto,
        bool $smsReminders,
        bool $emailReminders,
        bool $phoneReminders
    ): bool {
        return $dto->accountNumber === $this->getTestAccountNumber()
            && $dto->officeId === $this->getTestOfficeId()
            && $dto->smsReminders === $smsReminders
            && $dto->phoneReminders === $phoneReminders
            && $dto->emailReminders === $emailReminders;
    }

    private function getCustomerShowJsonResponse(int|null $accountNumber = null): TestResponse
    {
        $accountNumber ??= $this->getTestAccountNumber();

        return $this->getJson(route(
            'api.v2.customer.show',
            ['accountNumber' => $accountNumber]
        ));
    }

    private function getCommunicationPreferencesJsonResponse(array $request): TestResponse
    {
        return $this->postJson(
            route('api.v2.customer.communication-preferences', ['accountNumber' => $this->getTestAccountNumber()]),
            $request
        );
    }


    private function makeResponseAssertion(TestResponse $response, Collection $customersCollection): void
    {
        $response
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($customersCollection) {
                $json = $json
                    ->where('links.self', $this->userAccountsRouteURL)
                    ->count('data', $customersCollection->count());

                foreach ($customersCollection as $key => $customer) {
                    $json = $json
                        ->where("data.$key.id", (string)$customersCollection->get($key)->id)
                        ->where("data.$key.type", 'Customer');
                }

                return $json;
            });
    }

    private function getUserAccountsRoute(): string
    {
        return route($this->userAccountsRouteName);
    }

    protected function getTicketsRoute(int $accountNumber = 0, ?bool $dueOnly = null): string
    {
        $routeParams = ['accountNumber' => $accountNumber];

        if ($dueOnly !== null) {
            $routeParams['dueOnly'] = $dueOnly;
        }

        return route(self::SEARCH_ROUTE_NAME, $routeParams);
    }

    protected function getCustomerDataRoute($accountNumber = null): string
    {
        return route('api.v2.customer.data', ['accountNumber' => $accountNumber ?? $this->getTestAccountNumber()]);
    }
}
