<?php

namespace Tests\Unit\Actions;

use App\Actions\CreateAchTransactionSetupAction;
use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use App\Events\PaymentMethod\AchAdded;
use App\Exceptions\PaymentProfile\PaymentProfileIsEmptyException;
use App\Exceptions\TransactionSetup\TransactionSetupException;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Services\AccountService;
use App\Services\CreditCardService;
use App\Services\TransactionSetupService;
use Exception;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Throwable;

class CreateAchTransactionSetupActionTest extends TestCase
{
    use RandomIntTestData;

    public array $validData = [
        'customerId' => '2550260',
        'billing_name' => 'John Joe',
        'billing_address_line_1' => 'Aptive Street',
        'billing_address_line_2' => 'Unit #456',
        'billing_city' => 'Orlando',
        'billing_state' => 'FL',
        'billing_zip' => '32832',
        'bank_name' => 'Test Bank',
        'account_number' => '123456',
        'routing_number' => '1234567',
        'check_type' => CheckType::PERSONAL,
        'account_type' => AccountType::CHECKING,
        'auto_pay' => 1,
    ];

    public MockInterface|TransactionSetupService $transactionSetupServiceMock;
    public MockInterface|AccountService $accountServiceMock;
    public MockInterface|CreditCardService $creditCardServiceMock;
    public MockInterface|TransactionSetupRepository $transactionSetupRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->transactionSetupServiceMock = Mockery::mock(TransactionSetupService::class);
        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->creditCardServiceMock = Mockery::mock(CreditCardService::class);
        $this->transactionSetupRepositoryMock = Mockery::mock(TransactionSetupRepository::class);
    }

    /**
     * @dataProvider provideValidData
     */
    public function test_creates_payment_profile(array $parameters): void
    {
        $this->creditCardServiceMock
            ->shouldReceive('createPaymentProfile')
            ->andReturn($this->getTestPaymentProfileId())
            ->once();

        $transactionSetupAction = $this->setupAchTransactionSetup();

        Event::fake();

        self::assertNull(($transactionSetupAction)(...$parameters));

        Event::assertDispatched(AchAdded::class);
    }

    public function provideValidData(): array
    {
        $validDataWithoutAccountType = $this->validData;
        $validDataWithoutAccountType['account_type'] = null;

        return [
            'with account type' => [
              'parameters' => $this->validData,
            ],
            'without account type' => [
                'parameters' => $validDataWithoutAccountType,
            ],
        ];
    }

    public function test_throws_validation_exception_on_validation_exception(): void
    {
        $transactionSetupAction = $this->setupAchTransactionSetup();

        $invalidData = $this->validData;
        $invalidData['billing_state'] = 'Test';

        $this->expectException(ValidationException::class);
        ($transactionSetupAction)(...$invalidData);
    }

    /**
     * @dataProvider provideExceptionsData
     */
    public function test_it_throws_exceptions(Throwable $thrown, string $expected): void
    {
        $this->creditCardServiceMock->shouldReceive('createPaymentProfile')
            ->andThrow($thrown);

        $transactionSetupAction = $this->setupAchTransactionSetup();

        $this->expectException($expected);
        ($transactionSetupAction)(...$this->validData);
    }

    public function provideExceptionsData(): array
    {
        return [
            'transaction_setup_exception_on_transaction_setup_exception' => [
                'thrown' => new TransactionSetupException(),
                'expected' => TransactionSetupException::class,
            ],
            'exception_on_credit_card_service_exception' => [
                'thrown' => new Exception(),
                'expected' => Exception::class,
            ],
            'forwarding_PaymentProfileIsEmptyException' => [
                'thrown' => new PaymentProfileIsEmptyException(),
                'expected' => PaymentProfileIsEmptyException::class,
            ],
        ];
    }

    protected function setupAchTransactionSetup(): CreateAchTransactionSetupAction
    {
        return new CreateAchTransactionSetupAction(
            $this->transactionSetupServiceMock,
            $this->accountServiceMock,
            $this->creditCardServiceMock,
            $this->transactionSetupRepositoryMock
        );
    }
}
