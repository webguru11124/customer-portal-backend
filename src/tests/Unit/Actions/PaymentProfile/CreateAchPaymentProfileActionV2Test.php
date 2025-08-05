<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\PaymentProfile;

use App\Actions\PaymentProfile\CreateAchPaymentProfileActionV2;
use App\DTO\Payment\AutoPayStatus;
use App\DTO\Payment\AutoPayStatusRequestDTO;
use App\DTO\Payment\CreatePaymentProfileRequestDTO;
use App\DTO\Payment\PaymentProfile;
use App\Enums\Models\Payment\PaymentGateway;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Events\PaymentMethod\AchAdded;
use App\Models\Account;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use Exception;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\RandomStringTestData;

final class CreateAchPaymentProfileActionV2Test extends TestCase
{
    use RandomIntTestData;
    use RandomStringTestData;

    protected Account $account;
    protected PaymentProfile $paymentProfile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = Account::factory()->make();
        $this->paymentProfile = new PaymentProfile(
            $this->getTestPaymentMethodUuid(),
            'Successful'
        );
    }

    public function test_it_initializes_ach_profile_without_auto_pay_due_to_not_worldpay(): void
    {
        $aptivePaymentRepositoryMock = $this->getAptivePaymentRepositoryMock($this->paymentProfile);
        $aptivePaymentRepositoryMock
            ->expects('updateAutoPayStatus')
            ->withAnyArgs()
            ->never();

        $action = new CreateAchPaymentProfileActionV2($aptivePaymentRepositoryMock);
        $dto = $this->getPaymentProfileRequestDto($this->account->account_number);
        $dto->gatewayId = PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID;

        Event::fake();

        $this->assertSame($this->paymentProfile->paymentMethodId, ($action)($dto));

        Event::assertDispatched(AchAdded::class);
    }

    public function test_it_initializes_ach_profile_with_auto_pay_due_to_worldpay(): void
    {
        $aptivePaymentRepositoryMock = $this->getAptivePaymentRepositoryMock($this->paymentProfile);
        $aptivePaymentRepositoryMock
            ->expects('updateAutoPayStatus')
            ->withArgs(function (AutoPayStatusRequestDTO $dto): bool {
                return $dto->customerId === $this->account->account_number &&
                    $dto->autopayMethodId === $this->getTestPaymentMethodUuid();
            })
            ->once()
            ->andReturn(new AutoPayStatus(true, 'success'));

        $action = new CreateAchPaymentProfileActionV2($aptivePaymentRepositoryMock);
        $dto = $this->getPaymentProfileRequestDto($this->account->account_number, true);

        Event::fake();

        $this->assertSame($this->paymentProfile->paymentMethodId, ($action)($dto));

        Event::assertDispatched(AchAdded::class);
    }

    public function test_it_initializes_ach_profile_without_auto_pay_due_to_worldpay_and_not_primary(): void
    {
        $aptivePaymentRepositoryMock = $this->getAptivePaymentRepositoryMock($this->paymentProfile);
        $aptivePaymentRepositoryMock
            ->expects('updateAutoPayStatus')
            ->withAnyArgs()
            ->never();

        $action = new CreateAchPaymentProfileActionV2($aptivePaymentRepositoryMock);
        $dto = $this->getPaymentProfileRequestDto($this->account->account_number);
        $dto->gatewayId = PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID;
        $dto->isPrimary = false;

        Event::fake();

        $this->assertSame($this->paymentProfile->paymentMethodId, ($action)($dto));

        Event::assertDispatched(AchAdded::class);
    }

    public function test_action_does_not_catch_exception_from_services(): void
    {
        $aptivePaymentRepository = Mockery::mock(AptivePaymentRepository::class);
        $aptivePaymentRepository
            ->expects('createPaymentProfile')
            ->withAnyArgs()
            ->andThrows(Exception::class);

        $aptivePaymentRepository
            ->expects('updateAutoPayStatus')
            ->withAnyArgs()
            ->never();

        $action = new CreateAchPaymentProfileActionV2($aptivePaymentRepository);
        $dto = $this->getPaymentProfileRequestDto($this->account->account_number);

        $this->expectException(Exception::class);

        ($action)($dto);

        Event::assertNotDispatched(AchAdded::class);
    }

    private function getPaymentProfileRequestData(): array
    {
        return [
            'billing_name' => 'John Smith',
            'billing_address_line_1' =>  '7 Lewis Circle',
            'address_line_2' =>  'line 2',
            'address_line_3' =>  'line 3',
            'billing_city' => 'Wilmington',
            'billing_state' => 'DE',
            'billing_zip' => (string) $this->getTestBillingZip(),
            'email' => 'bright.nkrumah@goaptive.com',
            'bank_name' => 'A Bank',
            'country_code' => 'UT',
            'account_number' => (string) $this->getTestAccountNumber(),
            'routing_number' => (string) $this->getTestRoutingNumber(),
            'auto_pay' => true,
        ];
    }

    private function getPaymentProfileRequestDto(int $customerId, bool $isAutoPay = false): CreatePaymentProfileRequestDTO
    {
        $paymentProfileRequestData = $this->getPaymentProfileRequestData();

        $nameArray = explode(' ', $paymentProfileRequestData['billing_name'], 2);

        return new CreatePaymentProfileRequestDTO(
            customerId: $customerId,
            gatewayId: PaymentGateway::PAYMENT_GATEWAY_WORLDPAY_ID,
            type: PaymentMethod::ACH,
            firstName: $nameArray[0],
            lastName: count($nameArray) > 1 ? $nameArray[1] : '',
            addressLine1: $paymentProfileRequestData['billing_address_line_1'],
            email: $paymentProfileRequestData['email'],
            city: $paymentProfileRequestData['billing_city'],
            province: $paymentProfileRequestData['billing_state'],
            postalCode: $paymentProfileRequestData['billing_zip'],
            countryCode: $paymentProfileRequestData['country_code'],
            isPrimary: $paymentProfileRequestData['auto_pay'],
            isAutoPay: $isAutoPay,
            addressLine2: $paymentProfileRequestData['address_line_2'],
            addressLine3: $paymentProfileRequestData['address_line_3'],
            achAccountNumber: (string) $paymentProfileRequestData['account_number'],
            achRoutingNumber: (string) $paymentProfileRequestData['routing_number'],
        );
    }

    private function getAptivePaymentRepositoryMock(PaymentProfile $paymentProfile): MockInterface|AptivePaymentRepository
    {
        $paymentProfileRequestData = $this->getPaymentProfileRequestData();

        $aptivePaymentRepositoryMock = Mockery::mock(AptivePaymentRepository::class);
        $aptivePaymentRepositoryMock
            ->expects('createPaymentProfile')
            ->withArgs(function (CreatePaymentProfileRequestDTO $dto) use ($paymentProfileRequestData): bool {
                return $dto->achRoutingNumber === $paymentProfileRequestData['routing_number']
                    && $dto->achAccountNumber === $paymentProfileRequestData['account_number'];
            })
            ->once()
            ->andReturn($paymentProfile);

        return $aptivePaymentRepositoryMock;
    }
}
