<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\PaymentProfile;

use App\Actions\PaymentProfile\CompleteCreditCardPaymentProfileAction;
use App\DTO\CreatePaymentProfileDTO;
use App\Enums\Models\TransactionSetupStatus;
use App\Events\PaymentMethod\CcAdded;
use App\Exceptions\PaymentProfile\CreditCardAuthorizationException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Exceptions\PaymentProfile\TransactionSetupAlreadyCompleteException;
use App\Exceptions\TransactionSetup\TransactionSetupNotFoundException;
use App\Models\Account;
use App\Models\External\PaymentProfileModel;
use App\Models\TransactionSetup;
use App\Services\CreditCardService;
use App\Services\PaymentProfileService;
use App\Services\TransactionSetupService;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfile;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\PaymentProfileData;
use Tests\TestCase;

final class CompleteCreditCardPaymentProfileActionTest extends TestCase
{
    use RefreshDatabase;

    public const PAYMENT_PROFILE_DATA = [
        'HostedPaymentStatus' => 'Complete',
        'PaymentAccountID' => '56a1099e-897b-4af4-9b9e-9ee337c358d6',
        'ValidationCode' => '10122B2D83EE4868',
    ];

    public function test_it_creates_payment_profile_after_successful_authorization(): void
    {
        $account = Account::factory()->make();
        $paymentProfile = PaymentProfileData::getTestEntityData(1, [
            'accountNumber' => (string) $account->account_number,
            'merchantID' => self::PAYMENT_PROFILE_DATA['PaymentAccountID'],
        ])->first();

        $transactionSetupId = Str::uuid()->toString();
        $transactionSetup = TransactionSetup::factory()
            ->generated()
            ->withAddress()
            ->create([
                'transaction_setup_id' => $transactionSetupId,
                'account_number' => $account->account_number,
            ]);

        $creditCardServiceMock = self::getCreditCardServiceMock(
            $transactionSetup,
            self::PAYMENT_PROFILE_DATA['PaymentAccountID']
        );
        $paymentProfileServiceMock = self::getPaymentProfileServiceMock(
            $account,
            self::PAYMENT_PROFILE_DATA['PaymentAccountID'],
            $paymentProfile
        );
        $transactionSetupServiceMock = self::getTransactionSetupServiceMock(
            $account->account_number,
            $transactionSetupId,
            $transactionSetup
        );
        $transactionSetupServiceMock
            ->expects('transactionSetupIdIsComplete')
            ->andReturn(false);

        $action = new CompleteCreditCardPaymentProfileAction(
            $creditCardServiceMock,
            $paymentProfileServiceMock,
            $transactionSetupServiceMock
        );

        Event::fake();

        $this->assertSame($paymentProfile->id, ($action)(
            $account,
            self::PAYMENT_PROFILE_DATA['PaymentAccountID'],
            self::PAYMENT_PROFILE_DATA['HostedPaymentStatus'],
            $transactionSetupId
        ));

        Event::assertDispatched(CcAdded::class);

        $this->assertDatabaseHas($transactionSetup, [
            'account_number' => $transactionSetup->account_number,
            'transaction_setup_id' => $transactionSetupId,
            'status' => TransactionSetupStatus::COMPLETE,
        ]);
    }

    /**
     * @dataProvider errorRequestDataProvider
     *
     * @param array<string, string> $errorRequestData
     */
    public function test_it_throws_exception_when_hosted_payment_status_is_not_complete(array $errorRequestData): void
    {
        $account = Account::factory()->make();

        $transactionSetupServiceMock = Mockery::mock(TransactionSetupService::class);
        $transactionSetupServiceMock
            ->expects('findGeneratedByAccountNumberAndSetupId')
            ->withAnyArgs()
            ->never();

        $this->expectException(CreditCardAuthorizationException::class);

        $action = new CompleteCreditCardPaymentProfileAction(
            Mockery::mock(CreditCardService::class),
            Mockery::mock(PaymentProfileService::class),
            $transactionSetupServiceMock
        );

        ($action)(
            $account,
            $errorRequestData['PaymentAccountID'],
            $errorRequestData['HostedPaymentStatus'],
            'A'
        );
    }

    public function test_it_throws_exception_when_payment_profile_cannot_be_found(): void
    {
        $account = Account::factory()->make();
        $transactionSetupId = Str::uuid()->toString();
        $transactionSetup = TransactionSetup::factory()
            ->generated()
            ->withAddress()
            ->create([
                'transaction_setup_id' => $transactionSetupId,
                'account_number' => $account->account_number,
            ]);

        $creditCardServiceMock = self::getCreditCardServiceMock(
            $transactionSetup,
            self::PAYMENT_PROFILE_DATA['PaymentAccountID']
        );
        $paymentProfileServiceMock = self::getPaymentProfileServiceMock(
            $account,
            self::PAYMENT_PROFILE_DATA['PaymentAccountID'],
            null
        );
        $transactionSetupServiceMock = self::getTransactionSetupServiceMock(
            $account->account_number,
            $transactionSetupId,
            $transactionSetup
        );
        $transactionSetupServiceMock
            ->expects('transactionSetupIdIsComplete')
            ->andReturn(false);

        $action = new CompleteCreditCardPaymentProfileAction(
            $creditCardServiceMock,
            $paymentProfileServiceMock,
            $transactionSetupServiceMock
        );

        $this->expectException(PaymentProfileNotFoundException::class);

        ($action)(
            $account,
            self::PAYMENT_PROFILE_DATA['PaymentAccountID'],
            self::PAYMENT_PROFILE_DATA['HostedPaymentStatus'],
            $transactionSetupId
        );
    }

    public function test_it_throws_exception_when_transaction_setup_is_already_complete(): void
    {
        $account = Account::factory()->make();
        $transactionSetupId = Str::uuid()->toString();

        $creditCardServiceMock = Mockery::mock(CreditCardService::class);
        $creditCardServiceMock
            ->expects('createPaymentProfile')
            ->withAnyArgs()
            ->never();

        $transactionSetupServiceMock = Mockery::mock(TransactionSetupService::class);
        $transactionSetupServiceMock
            ->expects('findGeneratedByAccountNumberAndSetupId')
            ->withAnyArgs()
            ->never();
        $transactionSetupServiceMock
            ->expects('transactionSetupIdIsComplete')
            ->andReturn(true);

        $action = new CompleteCreditCardPaymentProfileAction(
            $creditCardServiceMock,
            Mockery::mock(PaymentProfileService::class),
            $transactionSetupServiceMock
        );

        $this->expectException(TransactionSetupAlreadyCompleteException::class);

        ($action)(
            $account,
            self::PAYMENT_PROFILE_DATA['PaymentAccountID'],
            self::PAYMENT_PROFILE_DATA['HostedPaymentStatus'],
            $transactionSetupId
        );
    }

    public function test_it_throws_exception_when_transaction_setup_cannot_be_found(): void
    {
        $account = Account::factory()->make();
        $transactionSetupId = Str::uuid()->toString();

        $creditCardServiceMock = Mockery::mock(CreditCardService::class);
        $creditCardServiceMock
            ->expects('createPaymentProfile')
            ->withAnyArgs()
            ->never();

        $transactionSetupServiceMock = Mockery::mock(TransactionSetupService::class);
        $transactionSetupServiceMock
            ->expects('findGeneratedByAccountNumberAndSetupId')
            ->withArgs([$account->account_number, $transactionSetupId])
            ->once()
            ->andThrow(new ModelNotFoundException());
        $transactionSetupServiceMock
            ->expects('transactionSetupIdIsComplete')
            ->andReturn(false);

        $action = new CompleteCreditCardPaymentProfileAction(
            $creditCardServiceMock,
            Mockery::mock(PaymentProfileService::class),
            $transactionSetupServiceMock
        );

        $this->expectException(TransactionSetupNotFoundException::class);

        ($action)(
            $account,
            self::PAYMENT_PROFILE_DATA['PaymentAccountID'],
            self::PAYMENT_PROFILE_DATA['HostedPaymentStatus'],
            $transactionSetupId
        );
    }

    /**
     * @return iterable<string, array<string, string>>
     */
    public function errorRequestDataProvider(): iterable
    {
        yield 'Invalid status' => [array_merge(self::PAYMENT_PROFILE_DATA, ['HostedPaymentStatus' => 'Error'])];
        yield 'Invalid payment account id' => [array_merge(self::PAYMENT_PROFILE_DATA, ['PaymentAccountID' => ''])];
        yield 'payment account id is null' => [array_merge(self::PAYMENT_PROFILE_DATA, ['PaymentAccountID' => null])];
    }

    private static function getCreditCardServiceMock(
        TransactionSetup $transactionSetup,
        string $paymentAccountId
    ): MockInterface|CreditCardService {
        $creditCardServiceMock = Mockery::mock(CreditCardService::class);
        $creditCardServiceMock
            ->expects('createPaymentProfile')
            ->withArgs(function (CreatePaymentProfileDTO $dto) use ($transactionSetup, $paymentAccountId): bool {
                return $dto->billingName === $transactionSetup->billing_name
                    && $dto->billingAddressLine1 === $transactionSetup->billing_address_line_1
                    && $dto->billingAddressLine2 === $transactionSetup->billing_address_line_2
                    && $dto->billingCity === $transactionSetup->billing_city
                    && $dto->billingState === $transactionSetup->billing_state
                    && $dto->billingZip === $transactionSetup->billing_zip
                    && $dto->token === $paymentAccountId
                    && $dto->paymentMethod === PaymentProfilePaymentMethod::AutoPayCC
                    && $dto->bankName === null
                    && $dto->accountNumber === null
                    && $dto->accountType === null
                    && $dto->checkType === null
                    && $dto->auto_pay === $transactionSetup->auto_pay;
            })
            ->once();

        return $creditCardServiceMock;
    }

    /**
     * @param Account $account
     * @param PaymentProfile[] $paymentProfiles
     *
     * @return MockInterface|PaymentProfileService
     */
    private static function getPaymentProfileServiceMock(
        Account $account,
        string $paymentAccountId,
        PaymentProfileModel|null $paymentProfile
    ): MockInterface|PaymentProfileService {
        $paymentProfileServiceMock = Mockery::mock(PaymentProfileService::class);
        $paymentProfileServiceMock
            ->expects('getPaymentProfileByMerchantId')
            ->with($account, $paymentAccountId)
            ->once()
            ->andReturn($paymentProfile);

        return $paymentProfileServiceMock;
    }

    private static function getTransactionSetupServiceMock(
        int $accountNumber,
        string $transactionSetupId,
        TransactionSetup $transactionSetup
    ): MockInterface|TransactionSetupService {
        $transactionSetupServiceMock = Mockery::mock(TransactionSetupService::class);
        $transactionSetupServiceMock
            ->expects('findGeneratedByAccountNumberAndSetupId')
            ->withArgs([$accountNumber, $transactionSetupId])
            ->once()
            ->andReturn($transactionSetup);

        return $transactionSetupServiceMock;
    }
}
