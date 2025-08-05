<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\DTO\BaseDTO;
use Spatie\LaravelData\Attributes\MapOutputName;

final class PaymentsListRequestDTO extends BaseDTO
{
    public function __construct(
        #[MapOutputName('customer_id')]
        public int $customerId,
        #[MapOutputName('page')]
        public int $page = 1,
        #[MapOutputName('per_page')]
        public int $perPage = 100,
    ) {
    }
}
