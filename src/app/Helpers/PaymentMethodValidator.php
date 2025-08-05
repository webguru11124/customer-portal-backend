<?php

declare(strict_types=1);

namespace App\Helpers;

use App\DTO\Payment\PaymentMethod;
use App\Enums\Models\Payment\PaymentMethod as PaymentMethodEnum;

class PaymentMethodValidator
{
    public function isPaymentMethodExpired(PaymentMethod $paymentMethod): bool
    {
        $basePaymentMethod = $paymentMethod->basePaymentMethod;
        if ($basePaymentMethod->type === PaymentMethodEnum::ACH->value) {
            return false;
        }

        //@phpstan-ignore-next-line
        if (null === $basePaymentMethod->ccExpirationMonth || null === $basePaymentMethod->ccExpirationYear) {
            return true;
        }

        $ccExpirationDate = (new \DateTime())
            ->setDate($basePaymentMethod->ccExpirationYear, $basePaymentMethod->ccExpirationMonth, 1)
            ->setTime(0, 0)
            ->modify('last day of this month');

        return !DateTimeHelper::isTodayOrFutureDate($ccExpirationDate);
    }
}
