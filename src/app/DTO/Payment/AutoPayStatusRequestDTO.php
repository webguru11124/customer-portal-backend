<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\DTO\BaseDTO;

final class AutoPayStatusRequestDTO extends BaseDTO
{
    public function __construct(
        public int $customerId,
        public string $autopayMethodId,
    ) {
    }

    public function toArray(): array
    {
        return [
            'autopay_method_id' => $this->autopayMethodId,
        ];
    }
}
