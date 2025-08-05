<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\Actions\CreateAchTransactionSetupAction;
use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use App\Exceptions\PaymentProfile\PaymentProfileIsEmptyException;
use App\Exceptions\TransactionSetup\TransactionSetupException;
use App\Models\Account;
use App\Models\User;
use App\Services\LogService;
use Exception;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;

class AchTransactionSetupControllerTest extends ApiTestCase
{
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    public array $validData = [
        'customer_id' => '234567',
        'billing_name' => 'John Joe',
        'billing_address_line_1' => 'Aptive Street',
        'billing_address_line_2' => 'Unit #456',
        'billing_city' => 'Orlando',
        'billing_state' => 'FL',
        'billing_zip' => '32832',
        'bank_name' => 'Test Bank',
        'account_number' => '2550260',
        'account_number_confirmation' => '2550260',
        'routing_number' => '1234567',
        'check_type' => 0, //CheckType::PERSONAL,
        'account_type' => 0, //AccountType::CHECKING,
        'auto_pay' => 1,
    ];

    public MockInterface $createAchTransactionSetupActionMock;
    public MockInterface $logServiceMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->createAchTransactionSetupActionMock = Mockery::mock(CreateAchTransactionSetupAction::class);
        $this->logServiceMock = Mockery::mock(LogService::class);
        $this->instance(CreateAchTransactionSetupAction::class, $this->createAchTransactionSetupActionMock);
        $this->instance(LogService::class, $this->logServiceMock);
    }

    public function test_store_forbids_unauthorized_access(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getPostJsonResponse($this->validData)
        );
    }

    public function test_store_requires_account_number_belonging_to_logged_in_user(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this
            ->createAchTransactionSetupActionMock
            ->expects('__invoke')
            ->withAnyArgs()
            ->never();

        $user = User::factory()->create();
        $account = Account::factory()->make([
            'account_number' => $this->getTestAccountNumber() + 1,
            'office_id' => $this->getTestOfficeId(),
        ]);
        $user->accounts()->save($account);

        $this
            ->getPostJsonResponse($this->validData, $this->getTestAccountNumber() + 1)
            ->assertNotFound();
    }

    /**
     * @dataProvider createTransactionSetUpDataProvider
     */
    public function test_store_creates_ach_transaction_setup(array $requestData, array $actionArguments): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->givenActionMockReturnsValidResult($actionArguments);

        $this->getPostJsonResponse($requestData)
            ->assertOk();
    }

    public function createTransactionSetUpDataProvider(): array
    {
        return [
            'default_data' => [
                'requestData' => $this->validData,
                'actionArguments' => $this->getActionArguments(),
            ],
            'with_partial_address' => [
                'requestData' => array_diff_key($this->validData, ['billing_address_line_2' => null]),
                'actionArguments' => array_merge($this->getActionArguments(), ['billing_address_line_2' => '']),
            ],
            'without_autoPay_field' => [
                'requestData' => array_diff_key($this->validData, ['auto_pay' => null]),
                'actionArguments' => array_merge($this->getActionArguments(), ['auto_pay' => 0]),
            ],
            'without account type' => [
                'validData' => array_diff_key($this->validData, ['account_type' => null]),
                'actionArguments' => array_merge($this->getActionArguments(), ['account_type' => null]),
            ],
        ];
    }

    public function test_store_returns_error_on_invalid_input(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $invalidData = $this->validData;
        unset($invalidData['account_number_confirmation']);

        $this->getPostJsonResponse($invalidData)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The account number confirmation field is required.');
    }

    /**
     * @dataProvider exceptionsDataProvider
     */
    public function test_store_handles_exceptions($exception): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->givenActionMockThrowsException($exception);

        $this->logServiceMock->shouldReceive('logThrowable')
            ->with(LogService::CUSTOMER_ADD_ACH_INFO, $exception)
            ->once();

        $this->getPostJsonResponse($this->validData)
            ->assertServerError();
    }

    public function exceptionsDataProvider(): array
    {
        return [
            ['exception' => new TransactionSetupException()],
            ['exception' => new Exception()],
        ];
    }

    public function test_store_returns_error_on_payment_profile_is_empty_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $exception = new PaymentProfileIsEmptyException();
        $this->givenActionMockThrowsException($exception);

        $this->logServiceMock->shouldReceive('logThrowable')
            ->with(LogService::CUSTOMER_ADD_ACH_INFO, $exception)
            ->once();

        $this->getPostJsonResponse($this->validData)
            ->assertUnprocessable();
    }

    protected function getActionArguments(): array
    {
        $arguments = $this->validData;
        unset($arguments['account_number_confirmation']);
        $arguments['check_type'] = CheckType::from($arguments['check_type']);
        $arguments['account_type'] = AccountType::from($arguments['account_type']);

        return $arguments;
    }

    protected function getPostJsonResponse(array $postData, int|null $accountNumber = null): TestResponse
    {
        $accountNumber ??= $this->getTestAccountNumber();

        return $this->postJson(
            route('api.transaction-setup.ach.store', ['accountNumber' => $accountNumber]),
            $postData
        );
    }

    protected function givenActionMockReturnsValidResult($arguments): void
    {
        $this->createAchTransactionSetupActionMock->shouldReceive('__invoke')
            ->with(...(array_values($arguments)))
            ->once()
            ->andReturnNull();
    }

    protected function givenActionMockThrowsException($exception): void
    {
        $this->createAchTransactionSetupActionMock->shouldReceive('__invoke')
            ->andThrow($exception);
    }
}
