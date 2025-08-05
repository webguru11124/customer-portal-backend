<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\PaymentProfile;

use App\Actions\PaymentProfile\ShowCustomerPaymentProfilesActionV2;
use App\DTO\Payment\AchPaymentMethod;
use App\DTO\Payment\CreditCardPaymentMethod;
use App\DTO\Payment\PaymentMethodsListRequestDTO;
use App\Enums\Models\Payment\PaymentMethod;
use App\Http\Requests\V2\GetPaymentProfilesRequest;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use Mockery;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\RandomStringTestData;

final class ShowCustomerPaymentProfilesActionV2Test extends TestCase
{
    use RandomIntTestData;
    use RandomStringTestData;

    protected AptivePaymentRepository|MockObject $paymentRepository;
    protected GetPaymentProfilesRequest $requestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = Mockery::mock(AptivePaymentRepository::class);
        $this->requestData = new GetPaymentProfilesRequest([
            'statuses' => [
                'valid'
            ]
        ]);
    }

    public function test_it_searches_payment_profile(): void
    {
        $paymentProfiles = $this->setupPaymentMethodsList();

        $this->paymentRepository
            ->shouldReceive('getPaymentMethodsList')
            ->once()
            ->withArgs(
                fn (
                    PaymentMethodsListRequestDTO $requestDTO
                ) => $requestDTO->customerId === $this->getTestAccountNumber()
            )
            ->andReturn($paymentProfiles);

        $action = new ShowCustomerPaymentProfilesActionV2(
            paymentProfileRepository: $this->paymentRepository,
        );

        $this->assertSame(
            $paymentProfiles,
            ($action)($this->getTestAccountNumber(), $this->requestData->statusesAsEnums())
        );
    }

    /**
     * @dataProvider providePaymentMethodsListExceptions
     */
    public function test_it_thrown_an_exception_when_searches_payment_profile(
        string $throwable,
        string $expectedException
    ): void {
        $this->paymentRepository
            ->shouldReceive('getPaymentMethodsList')
            ->once()
            ->withArgs(
                fn (
                    PaymentMethodsListRequestDTO $requestDTO
                ) => $requestDTO->customerId === $this->getTestAccountNumber()
            )
            ->andThrow($throwable);

        $action = new ShowCustomerPaymentProfilesActionV2(
            paymentProfileRepository: $this->paymentRepository,
        );

        $this->expectException($expectedException);

        ($action)($this->getTestAccountNumber(), $this->requestData->statusesAsEnums());
    }

    public function setupPaymentMethodsList(): array
    {
        return [
            new CreditCardPaymentMethod(
                paymentMethodId: $this->getTestPaymentMethodUuid(),
                crmAccountId: $this->getTestCrmAccountUuid(),
                type: ucfirst(strtolower(PaymentMethod::CREDIT_CARD->value)),
                dateAdded: "2023-11-15 14:14:16",
                isPrimary: true,
                description: 'Test description',
                ccLastFour: '1111',
                ccExpirationMonth: 7,
                ccExpirationYear: 2030,
            ),
            new AchPaymentMethod(
                paymentMethodId: $this->getTestPaymentMethodUuid(),
                crmAccountId: $this->getTestCrmAccountUuid(),
                type: PaymentMethod::ACH->value,
                dateAdded: "2023-11-16 10:35:02",
                isPrimary: false,
                achAccountLastFour: '1111',
                achRoutingNumber: '985612814',
                achAccountType: 'personal_checking',
                achBankName: 'Universal Bank',
            )
        ];
    }

    protected function providePaymentMethodsListExceptions(): iterable
    {
        yield [
            TransferException::class,
            GuzzleException::class,
        ];

        yield [
            \JsonException::class,
            \JsonException::class,
        ];
    }
}
