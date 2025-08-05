<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class AchPaymentMethod extends BasePaymentMethod
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
        #[MapOutputName('ach_account_last_four')]
        public string|null $achAccountLastFour = null,
        #[MapOutputName('ach_routing_number')]
        public string|null $achRoutingNumber = null,
        #[MapOutputName('ach_account_type')]
        public string|null $achAccountType = null,
        #[MapOutputName('ach_bank_name')]
        public string|null $achBankName = null,
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
