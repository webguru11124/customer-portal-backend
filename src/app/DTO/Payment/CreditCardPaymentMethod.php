<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use Spatie\LaravelData\Attributes\MapOutputName;

class CreditCardPaymentMethod extends BasePaymentMethod
{
    public function __construct(
        #[MapOutputName('payment_method_id')]
        public string $paymentMethodId,
        #[MapOutputName('crm_account_id')]
        public string $crmAccountId,
        #[MapOutputName('type')]
        public string $type,
        #[MapOutputName('date_added')]
        public string $dateAdded,
        #[MapOutputName('is_primary')]
        public bool $isPrimary,
        #[MapOutputName('description')]
        public string|null $description = null,
        #[MapOutputName('is_expired')]
        public bool $isExpired = false,
        #[MapOutputName('is_autopay')]
        public bool $isAutoPay = false,
        #[MapOutputName('cc_type')]
        public string|null $ccType = null,
        #[MapOutputName('cc_last_four')]
        public string|null $ccLastFour = null,
        #[MapOutputName('cc_expiration_month')]
        public int|null $ccExpirationMonth = null,
        #[MapOutputName('cc_expiration_year')]
        public int|null $ccExpirationYear = null,
    ) {
        parent::__construct(
            paymentMethodId: $paymentMethodId,
            crmAccountId: $crmAccountId,
            type: $type,
            dateAdded: $dateAdded,
            description: $description,
            isPrimary: $isPrimary,
            isExpired: $isExpired,
            isAutoPay: $isAutoPay
        );
    }
}
