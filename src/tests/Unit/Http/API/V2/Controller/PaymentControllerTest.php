<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\Payment\CreatePaymentActionV2;
use App\DTO\Payment\AuthAndCapture;
use App\Http\Requests\V2\CreatePaymentRequest;
use Tests\Traits\RandomStringTestData;
use Tests\Traits\TestAuthorizationMiddleware;

final class PaymentControllerTest extends PaymentController
{
    use TestAuthorizationMiddleware;
    use RandomStringTestData;

    public function test_create_payment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $createPaymentRequest = $this->setupCreatePaymentRequest();

        $createPaymentActionMock = \Mockery::mock(CreatePaymentActionV2::class);
        $createPaymentActionMock
            ->shouldReceive('__invoke')
            ->once()
            ->withArgs(
                fn (
                    CreatePaymentRequest $request,
                    int $accountNumber
                ) => $request->payment_method_id === $createPaymentRequest->payment_method_id &&
                    $accountNumber === $this->accountNumber &&
                    $request->amount_cents === $createPaymentRequest->amount_cents
            )
            ->andReturn($this->setupAuthAndCaptureResponseDTO());

        $this->instance(CreatePaymentActionV2::class, $createPaymentActionMock);

        $this
            ->getCreatePaymentJsonResponse(
                $this->accountNumber,
                [
                    'payment_method_id' => $createPaymentRequest->payment_method_id,
                    'amount_cents' => $createPaymentRequest->amount_cents,
                ]
            )
            ->assertOk()
            ->assertExactJson($this->setupAuthAndCaptureResponseDTO()->toArray());
    }

    public function test_create_payment_returns_error_on_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $createPaymentRequest = $this->setupCreatePaymentRequest();

        $createPaymentActionMock = \Mockery::mock(CreatePaymentActionV2::class);
        $createPaymentActionMock
            ->shouldReceive('__invoke')
            ->once()
            ->withAnyArgs()
            ->andThrow(new \Exception());

        $this->instance(CreatePaymentActionV2::class, $createPaymentActionMock);

        $this
            ->getCreatePaymentJsonResponse(
                $this->accountNumber,
                [
                    'payment_method_id' => $createPaymentRequest->payment_method_id,
                    'amount_cents' => $createPaymentRequest->amount_cents,
                ]
            )
            ->assertServerError();
    }

    public function test_create_payment_returns_validation_exception_on_negative_amount(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->instance(CreatePaymentActionV2::class, \Mockery::mock(CreatePaymentActionV2::class));

        $this
            ->getCreatePaymentJsonResponse(
                $this->accountNumber,
                [
                    'payment_method_id' => $this->getTestPaymentMethodUuid(),
                    'amount_cents' => -1000,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The amount cents must be greater than 0.');
    }

    public function test_create_payment_returns_validation_exception_on_wrong_payment_method_id_format(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->instance(CreatePaymentActionV2::class, \Mockery::mock(CreatePaymentActionV2::class));

        $this
            ->getCreatePaymentJsonResponse(
                $this->accountNumber,
                [
                    'payment_method_id' => "1",
                    'amount_cents' => 1000,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The payment method id must be a valid UUID.');
    }

    protected function setupCreatePaymentRequest(): CreatePaymentRequest
    {
        return new CreatePaymentRequest([
            'payment_method_id' => $this->getTestPaymentMethodUuid(),
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
