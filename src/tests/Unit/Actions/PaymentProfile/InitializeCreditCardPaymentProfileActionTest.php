<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\PaymentProfile;

use App\Actions\PaymentProfile\InitializeCreditCardPaymentProfileAction;
use App\DTO\CreateTransactionSetupDTO;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\TestCase;

final class InitializeCreditCardPaymentProfileActionTest extends TestCase
{
    use RefreshDatabase;

    public const PAYMENT_PROFILE_DATA = [
        'billing_name' => 'John Doe',
        'billing_address_line_1' => '7 Lewis Circle',
        'billing_address_line_2' => 'STE 1516178',
        'billing_city' => 'Wilmington',
        'billing_state' => 'DE',
        'billing_zip' => '19804',
        'auto_pay' => true,
    ];

    public function test_it_initializes_credit_card_profile(): void
    {
        Config::set('worldpay.transaction_setup_url', 'schema://test?ts={{TransactionSetupID}}');

        $account = Account::factory()->make();
        $customer = CustomerData::getTestEntityData(1, [
            'customerID' => $account->account_number,
            'officeID' => $account->office_id,
        ])->first();
        $transactionSetupId = Str::uuid()->toString();

        $customerRepositoryMock = self::getCustomerRepositoryMock($account, $customer);
        $transactionSetupRepositoryMock = self::getTransactionSetupRepositoryMock($customer, $transactionSetupId);

        $action = new InitializeCreditCardPaymentProfileAction($customerRepositoryMock, $transactionSetupRepositoryMock);
        $actionArguments = array_merge(
            array_values(self::PAYMENT_PROFILE_DATA),
            [$account]
        );

        $this->assertSame(
            sprintf('schema://test?ts=%s', $transactionSetupId),
            ($action)(...$actionArguments)
        );
        $this->assertDatabaseHas('transaction_setups', [
            'account_number' => $account->account_number,
            'transaction_setup_id' => $transactionSetupId,
            'auto_pay' => self::PAYMENT_PROFILE_DATA['auto_pay'],
        ]);
    }

    private static function getCustomerRepositoryMock(
        Account $account,
        CustomerModel $customer
    ): MockInterface|CustomerRepository {
        $customerRepositoryMock = Mockery::mock(CustomerRepository::class);

        $customerRepositoryMock
            ->shouldReceive('office')
            ->with($account->office_id)
            ->andReturnSelf();

        $customerRepositoryMock
            ->shouldReceive('find')
            ->with($account->account_number)
            ->once()
            ->andReturn($customer);

        return $customerRepositoryMock;
    }

    private static function getTransactionSetupRepositoryMock(
        CustomerModel $customer,
        string $transactionSetupId
    ): MockInterface|TransactionSetupRepository {
        $transactionSetupRepositoryMock = Mockery::mock(TransactionSetupRepository::class);
        $transactionSetupRepositoryMock
            ->expects('create')
            ->withArgs(function (CreateTransactionSetupDTO $dto) use ($customer): bool {
                return $dto->officeId === $customer->officeId
                    && $dto->email === $customer->email
                    && $dto->phone_number === $customer->getFirstPhone()
                    && $dto->billing_name === self::PAYMENT_PROFILE_DATA['billing_name']
                    && $dto->billing_address_line_1 === self::PAYMENT_PROFILE_DATA['billing_address_line_1']
                    && $dto->billing_address_line_2 === self::PAYMENT_PROFILE_DATA['billing_address_line_2']
                    && $dto->billing_city === self::PAYMENT_PROFILE_DATA['billing_city']
                    && $dto->billing_state === self::PAYMENT_PROFILE_DATA['billing_state']
                    && $dto->billing_zip === self::PAYMENT_PROFILE_DATA['billing_zip']
                    && $dto->auto_pay === self::PAYMENT_PROFILE_DATA['auto_pay'];
            })
            ->once()
            ->andReturn($transactionSetupId);

        return $transactionSetupRepositoryMock;
    }
}
