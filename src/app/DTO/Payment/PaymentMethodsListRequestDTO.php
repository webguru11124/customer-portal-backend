<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\DTO\BaseDTO;

final class PaymentMethodsListRequestDTO extends BaseDTO
{
    public function __construct(
        public int $customerId,
        public string|null $expireFromDate = null,
        public string|null $expireToDate = null,
        public int|null $page = null,
        public int|null $perPage = null,
    ) {
    }

    public function toArray(): array
    {
        $request = [
            'customer_id' => $this->customerId,
            'expire_from_date' => $this->expireFromDate,
            'expire_to_date' => $this->expireToDate,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];

        return array_filter($request, static fn ($item) => !(is_null($item)));
    }
}
