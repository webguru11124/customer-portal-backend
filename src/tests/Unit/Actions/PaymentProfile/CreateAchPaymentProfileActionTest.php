<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\PaymentProfile;

use App\Actions\PaymentProfile\CreateAchPaymentProfileAction;
use App\DTO\CreatePaymentProfileDTO;
use App\Enums\PestRoutes\PaymentProfile\AccountType;
use App\Enums\PestRoutes\PaymentProfile\CheckType;
use App\Events\PaymentMethod\AchAdded;
use App\Models\Account;
use App\Services\CreditCardService;
use Aptive\PestRoutesSDK\Resources\PaymentProfiles\PaymentProfilePaymentMethod;
use DomainException;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\PaymentProfileData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class CreateAchPaymentProfileActionTest extends TestCase
{
    use RandomIntTestData;

    public const PAYMENT_PROFILE_DATA = [
        'billing_name' => 'John Smith',
        'billing_address_line_1' =>  '7 Lewis Circle',
        'billing_city' => 'Wilmington',
        'billing_state' => 'DE',
        'billing_zip' => '19804',
        'bank_name' => 'A Bank',
        'account_number' => '12345678',
        'routing_number' => '555123',
        'check_type' => CheckType::PERSONAL,
        'account_type' => AccountType::CHECKING,
        'auto_pay' => false,
    ];

    public function test_it_creates_ach_payment_profile(): void
    {
        $account = Account::factory()->make();
        $paymentProfiles = PaymentProfileData::getTestData(1, [
            'accountNumber' => self::PAYMENT_PROFILE_DATA['account_number'],
        ]);
        $paymentProfileId = $paymentProfiles[0]->id;

        $creditCardServiceMock = self::getCreditCardServiceMock($paymentProfileId);
        $action = new CreateAchPaymentProfileAction($creditCardServiceMock);
        $dto = self::getPaymentProfileDto($account->account_number);

        Event::fake();

        $this->assertSame($paymentProfileId, ($action)($dto));

        Event::assertDispatched(AchAdded::class);
    }

    public function test_action_does_not_catch_exception_from_services(): void
    {
        $account = Account::factory()->make();
        $creditCardServiceMock = Mockery::mock(CreditCardService::class);
        $creditCardServiceMock
            ->expects('createPaymentProfile')
            ->withAnyArgs()
            ->andThrows(new DomainException('Test'));

        $action = new CreateAchPaymentProfileAction($creditCardServiceMock);
        $dto = self::getPaymentProfileDto($account->account_number);

        $this->expectException(DomainException::class);

        ($action)($dto);
    }

    private static function getPaymentProfileDto(int $customerId): CreatePaymentProfileDTO
    {
        return new CreatePaymentProfileDTO(
            customerId: $customerId,
            paymentMethod: PaymentProfilePaymentMethod::AutoPayACH,
            token: null,
            billingName: self::PAYMENT_PROFILE_DATA['billing_name'],
            billingAddressLine1: self::PAYMENT_PROFILE_DATA['billing_address_line_1'],
            billingAddressLine2: null,
            billingCity: self::PAYMENT_PROFILE_DATA['billing_city'],
            billingState: self::PAYMENT_PROFILE_DATA['billing_state'],
            billingZip: self::PAYMENT_PROFILE_DATA['billing_zip'],
            bankName: self::PAYMENT_PROFILE_DATA['bank_name'],
            accountNumber: self::PAYMENT_PROFILE_DATA['account_number'],
            routingNumber: self::PAYMENT_PROFILE_DATA['routing_number'],
            checkType: self::PAYMENT_PROFILE_DATA['check_type'],
            accountType: self::PAYMENT_PROFILE_DATA['account_type'],
            auto_pay: self::PAYMENT_PROFILE_DATA['auto_pay']
        );
    }

    private static function getCreditCardServiceMock(int $paymentProfileId): MockInterface|CreditCardService
    {
        $creditCardServiceMock = Mockery::mock(CreditCardService::class);
        $creditCardServiceMock
            ->expects('createPaymentProfile')
            ->withArgs(function (CreatePaymentProfileDTO $dto): bool {
                return $dto->billingName === self::PAYMENT_PROFILE_DATA['billing_name']
                    && $dto->billingAddressLine1 === self::PAYMENT_PROFILE_DATA['billing_address_line_1']
                    && $dto->billingCity === self::PAYMENT_PROFILE_DATA['billing_city']
                    && $dto->billingState === self::PAYMENT_PROFILE_DATA['billing_state']
                    && $dto->billingZip === self::PAYMENT_PROFILE_DATA['billing_zip']
                    && $dto->bankName === self::PAYMENT_PROFILE_DATA['bank_name']
                    && $dto->accountNumber === self::PAYMENT_PROFILE_DATA['account_number']
                    && $dto->accountType === self::PAYMENT_PROFILE_DATA['account_type']
                    && $dto->checkType === self::PAYMENT_PROFILE_DATA['check_type']
                    && $dto->auto_pay === self::PAYMENT_PROFILE_DATA['auto_pay'];
            })
            ->once()
            ->andReturn($paymentProfileId);

        return $creditCardServiceMock;
    }
}
