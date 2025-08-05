<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\Actions\Customer\ShowCustomerAction;
use App\DTO\Customer\ShowCustomerResultDTO;
use App\DTO\Customer\UpdateCommunicationPreferencesDTO;
use App\Enums\Resources;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Models\Account;
use App\Models\User;
use App\Services\AccountService;
use App\Services\CustomerService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerAutoPay;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerStatus;
use Carbon\Carbon;
use Exception;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;
use Throwable;

class CustomerControllerTest extends ApiTestCase
{
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    public MockInterface|AccountService $accountServiceMock;
    public MockInterface|ShowCustomerAction $showCustomerActionMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->instance(AccountService::class, $this->accountServiceMock);

        $this->showCustomerActionMock = Mockery::mock(ShowCustomerAction::class);
        $this->instance(ShowCustomerAction::class, $this->showCustomerActionMock);
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
                    ->where('links.self', '/api/v1/customer/' . $actionOutcome->id)
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
            ->andThrow(new AccountNotFoundException())
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
                    'self' => sprintf('/api/v1/customer/%d/communication-preferences', $this->getTestAccountNumber()),
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
            'api.customer.show',
            ['accountNumber' => $accountNumber]
        ));
    }

    private function getCommunicationPreferencesJsonResponse(array $request): TestResponse
    {
        return $this->postJson(
            route('api.customer.communication-preferences', ['accountNumber' => $this->getTestAccountNumber()]),
            $request
        );
    }
}
