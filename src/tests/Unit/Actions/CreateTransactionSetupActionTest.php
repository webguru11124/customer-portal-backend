<?php

namespace Tests\Unit\Actions;

use App\Actions\CreateTransactionSetupAction;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\TransactionSetup;
use App\Services\TransactionSetupService;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class CreateTransactionSetupActionTest extends TestCase
{
    use RandomIntTestData;

    protected Account $account;
    protected TransactionSetup $transactionSetup;
    protected CustomerModel $customer;
    protected MockInterface|TransactionSetupService $transactionSetupServiceMock;
    protected MockInterface|CustomerRepository $customerRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->make([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
        $this->customer = CustomerData::getTestEntityData(1, [
            'customerID' => $this->account->account_number,
            'officeID' => $this->account->office_id,
        ])->first();
        $this->transactionSetup = TransactionSetup::factory()
            ->initiated()
            ->make(['account_number' => $this->account->account_number]);
        $this->transactionSetupServiceMock = Mockery::mock(TransactionSetupService::class);
        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
    }

    public function test_initiates_payment_profile()
    {
        $this->setupCustomerRepositoryMock($this->customer);
        $this->transactionSetupServiceMock->shouldReceive('initiate')
            ->andReturn($this->transactionSetup)
            ->once();
        $transactionSetupAction = $this->setupTransactionSetup();
        $this->assertEquals($this->transactionSetup, ($transactionSetupAction)($this->account));
    }

    public function test_throws_entitynotfoundexception_on_no_customer()
    {
        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();
        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->andThrow(new EntityNotFoundException());

        $transactionSetupAction = $this->setupTransactionSetup();

        $this->expectException(EntityNotFoundException::class);
        ($transactionSetupAction)($this->account);
    }

    public function test_throws_accountfrozenexception_on_frozen_account()
    {
        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();
        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->andThrow(new AccountFrozenException());
        $transactionSetupAction = $this->setupTransactionSetup();

        $this->expectException(AccountFrozenException::class);
        ($transactionSetupAction)($this->account);
    }

    public function test_throws_exception_on_transaction_setup_service_error()
    {
        $this->setupCustomerRepositoryMock($this->customer);
        $this->transactionSetupServiceMock->shouldReceive('initiate')
            ->andThrow(Exception::class)
            ->once();
        $transactionSetupAction = $this->setupTransactionSetup();

        $this->expectException(Exception::class);
        ($transactionSetupAction)($this->account);
    }

    private function setupTransactionSetup()
    {
        return new CreateTransactionSetupAction($this->customerRepositoryMock, $this->transactionSetupServiceMock);
    }

    private function setupCustomerRepositoryMock(CustomerModel $customer)
    {
        $this->customerRepositoryMock->shouldReceive('office')->andReturnSelf();
        $this->customerRepositoryMock->shouldReceive('find')
            ->with($this->account->account_number)
            ->andReturn($customer)
            ->once();
    }
}
