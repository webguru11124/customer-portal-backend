<?php

namespace Tests\Unit\Services;

use App\DTO\AddCreditCardDTO;
use App\DTO\CreatePaymentProfileDTO;
use App\DTO\CreditCardAuthorizationDTO;
use App\Enums\Models\Customer\AutoPay;
use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileIsEmptyException;
use App\Interfaces\Repository\CreditCardAuthorizationRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Interfaces\Repository\TransactionSetupRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Repositories\PestRoutes\PestRoutesCustomerRepository;
use App\Services\AccountService;
use App\Services\CreditCardService;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class CreditCardServiceTest extends TestCase
{
    use RandomIntTestData;

    public MockInterface|CreditCardAuthorizationRepository $creditCardAuthorizationRepositoryMock;
    public MockInterface|PaymentProfileRepository $paymentProfileRepositoryMock;
    public MockInterface|CustomerRepository $customerRepositoryMock;
    public MockInterface|AccountService $accountServiceMock;
    public MockInterface|TransactionSetupRepository $transactionSetupRepositoryInterfaceMock;

    public $addCreditCardDTO;
    public $createPaymentProfileDTO;

    public $token;
    public CustomerModel $customer;
    public Account $account;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupRepositories();
        $this->setupToken();
        $this->setupCustomer();
        $this->setupAccount();
    }

    protected function setupRepositories()
    {
        $this->creditCardAuthorizationRepositoryMock = Mockery::mock(CreditCardAuthorizationRepository::class);
        $this->paymentProfileRepositoryMock = Mockery::mock(PaymentProfileRepository::class);
        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->transactionSetupRepositoryInterfaceMock = Mockery::mock(TransactionSetupRepository::class);
        $this->customerRepositoryMock = Mockery::mock(PestRoutesCustomerRepository::class);
    }

    protected function setupToken()
    {
        $this->token = Str::random(64);
    }

    protected function setupAddCreditCardDTO()
    {
        $this->addCreditCardDTO = AddCreditCardDTO::from([
            'credit_card_number' => '4242424242424242',
            'expiration_year' => '57',
            'expiration_month' => '12',
            'billing_name' => 'John Doe',
            'billing_address_line_1' => '9797 Aptive Street',
            'billing_address_line_2' => '',
            'billing_city' => 'Orlando',
            'billing_state' => 'Florida',
            'billing_zip' => '32927',
        ]);
    }

    protected function setupCreateCardPaymentProfileDTO()
    {
        $this->createPaymentProfileDTO = CreatePaymentProfileDTO::from([
            'customerId' => $this->getTestAccountNumber(),
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayCC,
            'token' => $this->token,
            'billingName' => '',
            'billingAddressLine1' => '',
            'billingAddressLine2' => '',
            'billingCity' => '',
            'billingState' => '',
            'billingZip' => '',
            'auto_pay' => true,
        ]);
    }

    protected function setupCreateACHPaymentProfileDTO()
    {
        $this->createPaymentProfileDTO = CreatePaymentProfileDTO::from([
            'customerId' => $this->getTestAccountNumber(),
            'paymentMethod' => PaymentProfilePaymentMethod::AutoPayACH,
            'billingName' => 'John Doe',
            'billingAddressLine1' => 'Aptive Street',
            'billingAddressLine2' => 'Unit 105c',
            'billingCity' => 'Orlando',
            'billingState' => 'FL',
            'billingZip' => '32832',
            'bankName' => 'Test Bank',
            'accountNumber' => '856667',
            'routingNumber' => '072403004',
            'checkType' => CheckType::PERSONAL,
            'accountType' => AccountType::SAVINGS,
            'auto_pay' => true,
        ]);
    }

    public function setupCustomer()
    {
        $this->customer = CustomerData::getTestEntityData(1, [
            'customerID' => $this->getTestAccountNumber(),
            'officeID' => $this->getTestOfficeId(),
            'aPay' => AutoPay::CREDIT_CARD->value,
            'autoPayPaymentProfileID' => $this->getTestPaymentProfileId(),
        ])->first();
    }

    private function setupAccount(): void
    {
        $this->account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
    }

    public function getCustomerWithoutAutoPay(): CustomerModel
    {
        return $this->customer = CustomerData::getTestEntityData(1, [
            'customerID' => $this->getTestAccountNumber(),
            'officeID' => $this->getTestOfficeId(),
            'aPay' => AutoPay::NO->value,
        ])->first();
    }

    public function test_create_payment_profile_creates_credit_card_payment_profile()
    {
        $this->setupCreateCardPaymentProfileDTO();
        $this->setupAccountService();
        $this->setupCustomerRepository();
        $this->setupPaymentProfileRepository();

        $this->creditCardAuthorizationRepositoryMock
            ->shouldReceive('authorize')
            ->with(CreditCardAuthorizationDTO::class, $this->customer)
            ->once();

        $createdPaymentProfileId = (new CreditCardService(
            $this->creditCardAuthorizationRepositoryMock,
            $this->paymentProfileRepositoryMock,
            $this->accountServiceMock,
            $this->transactionSetupRepositoryInterfaceMock,
            $this->customerRepositoryMock,
        ))->createPaymentProfile($this->createPaymentProfileDTO);

        $this->assertSame($this->getTestPaymentProfileId(), $createdPaymentProfileId);
    }

    public function test_create_payment_profile_creates_ach_payment_profile()
    {
        $this->setupCreateACHPaymentProfileDTO();
        $this->setupAccountService();
        $this->setupCustomerRepository();
        $this->setupPaymentProfileRepository();

        $this->creditCardAuthorizationRepositoryMock
            ->shouldNotReceive('authorize');

        $createdPaymentProfileId = (new CreditCardService(
            $this->creditCardAuthorizationRepositoryMock,
            $this->paymentProfileRepositoryMock,
            $this->accountServiceMock,
            $this->transactionSetupRepositoryInterfaceMock,
            $this->customerRepositoryMock,
        ))->createPaymentProfile($this->createPaymentProfileDTO);

        $this->assertSame($this->getTestPaymentProfileId(), $createdPaymentProfileId);
    }

    public function test_create_payment_profile_throws_payment_profile_is_empty_exception()
    {
        $this->setupCreateACHPaymentProfileDTO();
        $this->setupAccountService();
        $this->setupCustomerRepository();
        $this->paymentProfileRepositoryMock
            ->shouldReceive('addPaymentProfile')
            ->with($this->getTestOfficeId(), CreatePaymentProfileDTO::class)
            ->once()
            ->andThrow(new PaymentProfileIsEmptyException());

        $this->creditCardAuthorizationRepositoryMock
            ->shouldNotReceive('authorize');

        $this->expectException(PaymentProfileIsEmptyException::class);
        (new CreditCardService(
            $this->creditCardAuthorizationRepositoryMock,
            $this->paymentProfileRepositoryMock,
            $this->accountServiceMock,
            $this->transactionSetupRepositoryInterfaceMock,
            $this->customerRepositoryMock,
        ))->createPaymentProfile($this->createPaymentProfileDTO);
    }

    public function test_create_payment_profile_throws_exception_when_customer_not_found(): void
    {
        $this->setupAccountService();

        $this
            ->creditCardAuthorizationRepositoryMock
            ->expects('authorize')
            ->withAnyArgs()
            ->never();

        $this
            ->paymentProfileRepositoryMock
            ->expects('addPaymentProfile')
            ->withAnyArgs()
            ->never();

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->andThrow(new EntityNotFoundException());

        $this->setupCreateCardPaymentProfileDTO();

        $this->expectException(EntityNotFoundException::class);

        (new CreditCardService(
            $this->creditCardAuthorizationRepositoryMock,
            $this->paymentProfileRepositoryMock,
            $this->accountServiceMock,
            $this->transactionSetupRepositoryInterfaceMock,
            $this->customerRepositoryMock,
        ))->createPaymentProfile($this->createPaymentProfileDTO);
    }

    public function test_create_payment_profile_validates_the_payment_token()
    {
        $this->expectException(CreditCardAuthorizationException::class);

        $this->setupCreateCardPaymentProfileDTO();
        $this->setupAccountService();
        $this->setupCustomerRepository();

        $this->paymentProfileRepositoryMock
            ->shouldNotReceive('addPaymentProfile');

        $this->creditCardAuthorizationRepositoryMock
            ->shouldReceive('authorize')
            ->with(CreditCardAuthorizationDTO::class, $this->customer)
            ->andThrow(CreditCardAuthorizationException::class)
            ->once();

        (new CreditCardService(
            $this->creditCardAuthorizationRepositoryMock,
            $this->paymentProfileRepositoryMock,
            $this->accountServiceMock,
            $this->transactionSetupRepositoryInterfaceMock,
            $this->customerRepositoryMock,
        ))->createPaymentProfile($this->createPaymentProfileDTO);
    }

    protected function setupAccountService(): void
    {
        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andReturn($this->account)
            ->once();
    }

    protected function setupCustomerRepository(): void
    {
        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();
        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->with($this->account->account_number)
            ->andReturn($this->customer)
            ->once();
    }

    protected function setupPaymentProfileRepository(): void
    {
        $this->paymentProfileRepositoryMock
            ->shouldReceive('addPaymentProfile')
            ->with($this->getTestOfficeId(), CreatePaymentProfileDTO::class)
            ->once()
            ->andReturn($this->getTestPaymentProfileId());
    }

    /**
     * @return array<string, bool|array<string, int>>
     */
    protected function getPaymentProfileData(): array
    {
        return [
            'paymentProfile' => [
                'paymentProfileID' => $this->getTestPaymentProfileId(),
            ],
            'success' => true,
        ];
    }
}
