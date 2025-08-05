<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\DTO\Payment\AuthAndCapture;
use App\DTO\Payment\AuthAndCaptureRequestDTO;
use App\Events\Payment\PaymentMade;
use App\Http\Requests\V2\CreatePaymentRequest;
use App\Repositories\AptivePayment\AptivePaymentRepository;
use GuzzleHttp\Exception\GuzzleException;

class CreatePaymentActionV2
{
    public function __construct(
        private readonly AptivePaymentRepository $paymentRepository,
    ) {
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function __invoke(
        CreatePaymentRequest $request,
        int $accountNumber
    ): AuthAndCapture {
        $amount = $request->amount_cents;
        $payment = $this->paymentRepository->authorizeAndCapture(new AuthAndCaptureRequestDTO(
            amount: $amount,
            customerId: $accountNumber,
            methodId: $request->payment_method_id
        ));

        PaymentMade::dispatch($accountNumber, (int) round($amount / 100));

        return $payment;
    }
}
