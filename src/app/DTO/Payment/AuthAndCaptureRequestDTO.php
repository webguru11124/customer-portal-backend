<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\DTO\BaseDTO;

final class AuthAndCaptureRequestDTO extends BaseDTO
{
    public function __construct(
        public int $amount,
        public int $customerId,
        public string $methodId,
        public int|null $recurringPaymentId = null,
        public int|null $invoiceId = null,
    ) {
    }

    public function toArray(): array
    {
        $request = [
            'amount' => $this->amount,
            'customer_id' => $this->customerId,
            'method_id' => $this->methodId,
            'recurring_payment_id' => $this->recurringPaymentId,
            'invoice_id' => $this->invoiceId,
        ];

        return array_filter($request, static fn ($item) => !(is_null($item)));
    }
}
