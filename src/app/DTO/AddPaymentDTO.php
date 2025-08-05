<?php

namespace App\DTO;

use App\Enums\Models\Payment\PaymentMethod;
use Illuminate\Validation\Rule;

/**
 * DTO for Add Payment.
 */
class AddPaymentDTO extends BaseDTO
{
    public function __construct(
        public PaymentMethod $paymentMethod,
        public int $customerId,
        public int $amountCents,
        public int $paymentProfileId,
    ) {
        $this->validateData();
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return [
            'customerId' => ['gt:0'],
            'paymentProfileId' => ['gt:0'],
            'amountCents' => ['gt:0'],
            'paymentMethod' => Rule::in([PaymentMethod::CREDIT_CARD->value, PaymentMethod::ACH->value]),
        ];
    }

    public function getAmount(): float
    {
        return round($this->amountCents / 100, 2);
    }
}
