<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\Enums\Models\Payment\PaymentMethod as PaymentMethodEnum;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

class BasePaymentMethod extends Data
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
        #[MapOutputName('description')]
        public string|null $description,
        #[MapOutputName('is_primary')]
        public bool $isPrimary = false,
        #[MapOutputName('is_expired')]
        public bool $isExpired = false,
        #[MapOutputName('is_autopay')]
        public bool $isAutoPay = false
    ) {
    }

    public function isAch(): bool
    {
        return PaymentMethodEnum::ACH->value === $this->type;
    }
}
