<?php

namespace Tests\Unit\Actions;

use App\Actions\CreateCreditCardTransactionSetupAction;
use App\DTO\CreateTransactionSetupDTO;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\TransactionSetup\TransactionSetupException;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\TransactionSetup;
use App\Services\AccountService;
use App\Services\TransactionSetupService;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class CreateCreditCardTransactionSetupActionTest extends TestCase
{
    use RandomIntTestData;

    public $validData = [
        'slug' => '2Fb2wr',
        'billing_name' => 'John Joe',
        'billing_address_line_1' => 'Aptive Street',
        'billing_address_line_2' => 'Unit #456',
        'billing_city' => 'Orlando',
        'billing_state' => 'FL',
        'billing_zip' => '32832',
        'auto_pay' => 1,
    ];

    public $transactionSetupServiceMock;
    public $accountServiceMock;
    public MockInterface|CustomerRepository $customerRepositoryMock;
    public $transactionSetupRepositoryMock;
    public $transactionSetup;
    public CustomerModel $customer;
    public Account $account;

    public function setUp(): void
    {
        parent::setUp();

        $this->transactionSetupServiceMock = Mockery::mock(TransactionSetupService::class);
        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->transactionSetupRepositoryMock = Mockery::mock(TransactionSetupRepository::class);
        $this->transactionSetup = TransactionSetup::factory()->make(['account_number' => $this->getTestAccountNumber()]);
        $this->account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
        $this->customer = CustomerData::getTestEntityData(1, [
            'customerID' => $this->account->account_number,
            'officeID' => $this->account->office_id,
        ])->first();
    }

    public function test_creates_credit_card_transaction_setup()
    {
        $transactionSetupId = '7ADA6A04-814B-4E4C-9014-5085604D39E9';

        $this->setupTransactionSetupServiceMock();
        $this->setupAccountServiceMock($this->account);
        $this->setupCustomerRepositoryMock($this->customer);
        $this->transactionSetupRepositoryMock->shouldReceive('create')
            ->withArgs([CreateTransactionSetupDTO::class])
            ->andReturn($transactionSetupId)
            ->once();

        $transactionSetupAction = $this->setupTransactionSetupAction();

        self::assertEquals($transactionSetupId, ($transactionSetupAction)(...$this->validData));
    }

    public function test_throws_exception_on_itemnotfoundexception()
    {
        $this->transactionSetupServiceMock->shouldReceive('findBySlug')
            ->with($this->validData['slug'])
            ->andThrow(new ItemNotFoundException());

        $transactionSetupAction = $this->setupTransactionSetupAction();

        $this->expectException(ItemNotFoundException::class);
        ($transactionSetupAction)(...$this->validData);
    }

    public function test_throws_exception_on_emptycustomer()
    {
        $this->setupTransactionSetupServiceMock();
        $this->setupAccountServiceMock($this->account);

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();
        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->andThrow(new EntityNotFoundException());

        $transactionSetupAction = $this->setupTransactionSetupAction();

        $this->expectException(EntityNotFoundException::class);
        ($transactionSetupAction)(...$this->validData);
    }

    public function test_throws_exception_on_empty_account()
    {
        $this->setupTransactionSetupServiceMock();
        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->andThrow(new AccountNotFoundException());

        $transactionSetupAction = $this->setupTransactionSetupAction();

        $this->expectException(AccountNotFoundException::class);
        ($transactionSetupAction)(...$this->validData);
    }

    public function test_throws_validationexception_on_validationexception()
    {
        $this->setupTransactionSetupServiceMock();
        $this->setupAccountServiceMock($this->account);
        $this->setupCustomerRepositoryMock($this->customer);

        $transactionSetupAction = $this->setupTransactionSetupAction();

        $invalidData = array_merge($this->validData, ['billing_state' => 'Test']);

        $this->expectException(ValidationException::class);
        ($transactionSetupAction)(...$invalidData);
    }

    public function test_throws_transactionsetupexception_on_transactionsetupexception()
    {
        $this->setupTransactionSetupServiceMock();
        $this->setupAccountServiceMock($this->account);
        $this->setupCustomerRepositoryMock($this->customer);
        $this->transactionSetupRepositoryMock->shouldReceive('create')
            ->andThrow(TransactionSetupException::class);

        $transactionSetupAction = $this->setupTransactionSetupAction();

        $this->expectException(TransactionSetupException::class);
        ($transactionSetupAction)(...$this->validData);
    }

    protected function setupTransactionSetupAction()
    {
        return new CreateCreditCardTransactionSetupAction(
            $this->transactionSetupServiceMock,
            $this->accountServiceMock,
            $this->customerRepositoryMock,
            $this->transactionSetupRepositoryMock
        );
    }

    protected function setupTransactionSetupServiceMock()
    {
        $this->transactionSetupServiceMock->shouldReceive('findBySlug')
            ->with($this->validData['slug'])
            ->andReturn($this->transactionSetup)
            ->once();
    }

    protected function setupAccountServiceMock($return)
    {
        $this->accountServiceMock->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andReturn($return)
            ->once();
    }

    protected function setupCustomerRepositoryMock(CustomerModel $customer): void
    {
        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->with($this->account->office_id)
            ->once()
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->with($this->account->account_number)
            ->andReturn($customer)
            ->once();
    }
}
