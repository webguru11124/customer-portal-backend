<?php

namespace Tests\Unit\Actions;

use App\Actions\CompleteCreditCardTransactionSetupAction;
use App\DTO\CreatePaymentProfileDTO;
use App\Events\PaymentMethod\CcAdded;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Exceptions\PaymentProfile\PaymentProfilesNotFoundException;
use App\Models\Account;
use App\Models\External\PaymentProfileModel;
use App\Models\TransactionSetup;
use App\Services\AccountService;
use App\Services\CreditCardService;
use App\Services\CustomerService;
use App\Services\PaymentProfileService;
use App\Services\TransactionSetupService;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Exception;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\PaymentProfileData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class CompleteCreditCardTransactionSetupActionTest extends TestCase
{
    use RandomIntTestData;

    public string $hostedPaymentStatus = 'Complete';
    public string $invalidHostedPaymentStatus = 'Error';
    public string $transactionSetupId = '7ADA6A04-814B-4E4C-9014-5085604D39E9';
    protected string $paymentAccountId;
    public Account $account;
    public PaymentProfileModel $paymentProfile;
    public TransactionSetup $transactionSetup;

    public MockInterface|AccountService $accountServiceMock;
    public MockInterface|CustomerService $customerServiceMock;
    public MockInterface|CreditCardService $creditCardServiceMock;
    public MockInterface|PaymentProfileService $paymentProfileServiceMock;
    public MockInterface|TransactionSetupService $transactionSetupServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->customerServiceMock = Mockery::mock(CustomerService::class);
        $this->creditCardServiceMock = Mockery::mock(CreditCardService::class);
        $this->paymentProfileServiceMock = Mockery::mock(PaymentProfileService::class);
        $this->transactionSetupServiceMock = Mockery::mock(TransactionSetupService::class);

        $this->paymentAccountId = Str::uuid()->toString();

        $this->account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
        $this->paymentProfile = PaymentProfileData::getTestEntityData(1, ['merchantID' => $this->paymentAccountId])->first();
        $this->transactionSetup = TransactionSetup::factory()->make([
            'account_number' => $this->getTestAccountNumber(),
            'auto_pay' => true,
        ]);
    }

    public function test_it_properly_creates_payment_profile(): void
    {
        $this->givenTransactionSetupServiceMockReturnsTransactionSetup();
        $this->givenCreditCardServiceMockCreatesPaymentProfile();
        $this->givenTransactionSetupServiceMockCompletesTransactionSetup();
        $this->givenAccountServiceMockReturnsAccount();

        $this->paymentProfileServiceMock->shouldReceive('getPaymentProfileByMerchantId')
            ->withArgs([$this->account, $this->paymentAccountId])
            ->andReturn($this->paymentProfile)
            ->once();

        $setupAction = $this->setupAction();

        Event::fake();

        self::assertEquals($this->paymentProfile, ($setupAction)(
            $this->transactionSetupId,
            $this->hostedPaymentStatus,
            $this->paymentAccountId
        ));

        Event::assertDispatched(CcAdded::class);
    }

    public function test_it_throws_credit_card_authorization_exception_on_failed_transaction()
    {
        $this->givenTransactionSetupServiceMockReturnsTransactionSetup();
        $this->transactionSetupServiceMock->shouldReceive('failAuthorization')
            ->with($this->transactionSetup)
            ->once();

        $this->expectException(CreditCardAuthorizationException::class);

        $setupAction = $this->setupAction();
        ($setupAction)($this->transactionSetupId, $this->invalidHostedPaymentStatus, $this->paymentAccountId);
    }

    public function test_it_throws_credit_card_authorization_exception_on_empty_payment_account_id()
    {
        $this->givenTransactionSetupServiceMockReturnsTransactionSetup();
        $this->transactionSetupServiceMock->shouldReceive('failAuthorization')
            ->with($this->transactionSetup)
            ->once();

        $this->expectException(CreditCardAuthorizationException::class);

        $setupAction = $this->setupAction();
        ($setupAction)(
            $this->transactionSetupId,
            $this->hostedPaymentStatus,
            null
        );
    }

    public function test_it_throws_payment_profiles_not_found_exception()
    {
        $this->givenTransactionSetupServiceMockReturnsTransactionSetup();
        $this->givenCreditCardServiceMockCreatesPaymentProfile();
        $this->givenTransactionSetupServiceMockCompletesTransactionSetup();
        $this->givenAccountServiceMockReturnsAccount();

        $this->paymentProfileServiceMock->shouldReceive('getPaymentProfileByMerchantId')
            ->andThrow(PaymentProfilesNotFoundException::class)
            ->once();

        $this->expectException(PaymentProfilesNotFoundException::class);

        $setupAction = $this->setupAction();
        ($setupAction)($this->transactionSetupId, $this->hostedPaymentStatus, $this->paymentAccountId);
    }

    public function test_it_throws_payment_profile_not_found_exception()
    {
        $this->givenTransactionSetupServiceMockReturnsTransactionSetup();
        $this->givenCreditCardServiceMockCreatesPaymentProfile();
        $this->givenTransactionSetupServiceMockCompletesTransactionSetup();
        $this->givenAccountServiceMockReturnsAccount();

        $this->paymentProfileServiceMock->shouldReceive('getPaymentProfileByMerchantId')
            ->withArgs([$this->account, $this->paymentAccountId])
            ->andReturn(null)
            ->once();

        $this->expectException(PaymentProfileNotFoundException::class);

        $setupAction = $this->setupAction();
        ($setupAction)($this->transactionSetupId, $this->hostedPaymentStatus, $this->paymentAccountId);
    }

    public function test_it_fails_payment_profile_on_credit_card_authorization_exception()
    {
        $this->givenTransactionSetupServiceMockReturnsTransactionSetup();
        $this->creditCardServiceMock->shouldReceive('createPaymentProfile')
            ->with(CreatePaymentProfileDTO::class)
            ->andThrow(CreditCardAuthorizationException::class)
            ->once();
        $this->transactionSetupServiceMock->shouldReceive('failAuthorization')
            ->with($this->transactionSetup)
            ->once();

        $this->expectException(CreditCardAuthorizationException::class);

        $setupAction = $this->setupAction();
        ($setupAction)($this->transactionSetupId, $this->hostedPaymentStatus, $this->paymentAccountId);
    }

    public function test_it_throws_exception()
    {
        $this->givenTransactionSetupServiceMockReturnsTransactionSetup();
        $this->creditCardServiceMock->shouldReceive('createPaymentProfile')
            ->with(CreatePaymentProfileDTO::class)
            ->andThrow(Exception::class)
            ->once();

        $this->expectException(Exception::class);

        $setupAction = $this->setupAction();
        ($setupAction)($this->transactionSetupId, $this->hostedPaymentStatus, $this->paymentAccountId);
    }

    protected function setupAction()
    {
        return new CompleteCreditCardTransactionSetupAction(
            $this->accountServiceMock,
            $this->customerServiceMock,
            $this->creditCardServiceMock,
            $this->paymentProfileServiceMock,
            $this->transactionSetupServiceMock
        );
    }

    protected function givenAccountServiceMockReturnsAccount()
    {
        $this->accountServiceMock->shouldReceive('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->andReturn($this->account)
            ->once();
    }

    protected function givenTransactionSetupServiceMockReturnsTransactionSetup()
    {
        $this->transactionSetupServiceMock->shouldReceive('findByTransactionSetupId')
            ->with($this->transactionSetupId)
            ->andReturn($this->transactionSetup)
            ->once();
    }

    protected function givenCreditCardServiceMockCreatesPaymentProfile()
    {
        $this
            ->creditCardServiceMock
            ->shouldReceive('createPaymentProfile')
            ->withArgs(
                fn (CreatePaymentProfileDTO $dto) => $dto->customerId === $this->getTestAccountNumber()
                    && $dto->paymentMethod === PaymentProfilePaymentMethod::AutoPayCC
                    && $dto->token === $this->paymentAccountId
                    && $dto->auto_pay === true
            )
            ->once();
    }

    protected function givenTransactionSetupServiceMockCompletesTransactionSetup()
    {
        $this->transactionSetupServiceMock->shouldReceive('complete')
            ->with($this->transactionSetup)
            ->once();
    }
}
