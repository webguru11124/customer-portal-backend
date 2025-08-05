<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\PaymentProfile;

use App\Actions\PaymentProfile\DeletePaymentProfileActionV2;
use App\DTO\Payment\CreditCardPaymentMethod;
use App\DTO\Payment\PaymentMethod;
use App\DTO\Payment\PaymentMethodsListRequestDTO;
use App\Enums\Models\Payment\PaymentMethod as PaymentMethodEnum;
use App\Exceptions\Authorization\UnauthorizedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotDeletedException;
use App\Exceptions\PaymentProfile\PaymentProfileNotFoundException;
use App\Models\Account;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\RandomStringTestData;

final class DeletePaymentProfileActionV2Test extends TestCase
{
    use RandomIntTestData;
    use RandomStringTestData;

    protected Account $account;
    protected AptivePaymentRepository|MockInterface $paymentRepository;
    protected DeletePaymentProfileActionV2 $subject;

    protected int $accountNumber;

    public function setUp(): void
    {
        parent::setUp();

        $this->accountNumber = $this->getTestAccountNumber();
        $this->paymentRepository = Mockery::mock(AptivePaymentRepository::class);

        $this->subject = new DeletePaymentProfileActionV2(
            $this->paymentRepository
        );
    }

    public function test_it_deletes_payment_profile(): void
    {
        $this->paymentRepository
            ->shouldReceive('getPaymentMethodsList')
            ->once()
            ->withArgs(fn (PaymentMethodsListRequestDTO $requestDTO) => $requestDTO->customerId === $this->accountNumber)
            ->andReturn($this->setupPaymentMethodList($this->getTestPaymentMethodUuid()));

        $this->paymentRepository
            ->shouldReceive('deletePaymentMethod')
            ->withArgs(
                fn (string $paymentProfileId) => $paymentProfileId === $this->getTestPaymentMethodUuid()
            )
            ->once()
            ->andReturn(true);

        ($this->subject)($this->accountNumber, $this->getTestPaymentMethodUuid());
    }

    /**
     * @dataProvider provideInvalidPaymentMethodData
     */
    public function test_delete_payment_profile_throw_an_exception(
        array $paymentMethodsList,
        string $paymentProfileId,
        string $exception
    ): void {
        $this->paymentRepository
            ->shouldReceive('getPaymentMethodsList')
            ->once()
            ->withArgs(fn (PaymentMethodsListRequestDTO $requestDTO) => $requestDTO->customerId === $this->accountNumber)
            ->andReturn($paymentMethodsList);

        $this->paymentRepository
            ->shouldReceive('deletePaymentMethod')
            ->withAnyArgs()
            ->never();

        $this->expectException($exception);
        ($this->subject)($this->accountNumber, $paymentProfileId);
    }

    /**
     * @param string $paymentProfileId
     * @param bool $isPrimary
     *
     * @return PaymentMethod[]
     */
    protected function setupPaymentMethodList(string $paymentProfileId, bool $isPrimary = false): array
    {
        return [
            new CreditCardPaymentMethod(
                paymentMethodId: $paymentProfileId,
                crmAccountId: $this->getTestCrmAccountUuid(),
                type: PaymentMethodEnum::CREDIT_CARD->value,
                dateAdded: "2023-11-16 10:48:58",
                isPrimary: $isPrimary
            ),
        ];
    }

    protected function provideInvalidPaymentMethodData(): iterable
    {
        $paymentProfileId = $this->getTestPaymentMethodUuid();

        yield 'primary_payment_method_cannot_been_removed' => [
            $this->setupPaymentMethodList($paymentProfileId, true),
            $paymentProfileId,
            PaymentProfileNotDeletedException::class
        ];

        yield 'empty_payment_methods_list' => [
            [],
            $paymentProfileId,
            PaymentProfileNotFoundException::class
        ];

        yield 'invalid_customer_payment_method' => [
            $this->setupPaymentMethodList(sprintf('%s%d', $paymentProfileId, 1)),
            $paymentProfileId,
            UnauthorizedException::class
        ];
    }
}
