<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Payment;

use App\Actions\Payment\CreatePaymentActionV2;
use App\DTO\Payment\AuthAndCapture;
use App\DTO\Payment\AuthAndCaptureRequestDTO;
use App\Events\Payment\PaymentMade;
use App\Http\Requests\V2\CreatePaymentRequest;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\RandomStringTestData;

final class CreatePaymentActionV2Test extends TestCase
{
    use RandomIntTestData;
    use RandomStringTestData;

    protected AptivePaymentRepository|MockObject $paymentRepository;
    protected CreatePaymentActionV2 $action;
    protected int $accountNumber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = \Mockery::mock(AptivePaymentRepository::class);
        $this->accountNumber = $this->getTestAccountNumber();
        $this->action = new CreatePaymentActionV2($this->paymentRepository);
    }

    public function test_it_create_payment_with_payment_method_specified(): void
    {
        $accountNumber = $this->accountNumber;
        $createPaymentRequest = $this->setupCreatePaymentRequest();

        $this->paymentRepository
            ->shouldReceive('authorizeAndCapture')
            ->withArgs(
                fn (
                    AuthAndCaptureRequestDTO $requestDTO
                ) => $requestDTO->methodId === $createPaymentRequest->payment_method_id &&
                    $requestDTO->customerId === $this->accountNumber &&
                    $requestDTO->amount === $createPaymentRequest->amount_cents
            )
            ->once()
            ->andReturn($this->setupAuthAndCaptureResponseDTO());

        Event::fake();
        $this->assertEquals(
            $this->setupAuthAndCaptureResponseDTO(),
            ($this->action)($createPaymentRequest, $accountNumber)
        );
        Event::assertDispatched(PaymentMade::class);
    }

    public function test_create_payment_returns_exception_on_failure(): void
    {
        $this->paymentRepository
            ->shouldReceive('authorizeAndCapture')
            ->withAnyArgs()
            ->once()
            ->andThrow(\RuntimeException::class);

        $this->expectException(\RuntimeException::class);

        ($this->action)($this->setupCreatePaymentRequest(), $this->accountNumber);
    }

    protected function setupCreatePaymentRequest(): CreatePaymentRequest
    {
        return new CreatePaymentRequest([
            'payment_method_id' => $this->getTestPaymentMethodUuid(),
            'amount_cents' => 1000,
        ]);
    }

    protected function setupCreatePaymentRequestWithoutPaymentMethodSpecified(): CreatePaymentRequest
    {
        return new CreatePaymentRequest([
            'payment_method_id' => null,
            'amount_cents' => 1000,
        ]);
    }

    protected function setupAuthAndCaptureResponseDTO(): AuthAndCapture
    {
        return new AuthAndCapture(
            message: 'Payment has been authorized and captured.',
            status: 'CAPTURED',
            paymentId: $this->getTestPaymentMethodUuid(),
            transactionId: $this->getTestTransactionUuid(),
        );
    }
}
