<?php

declare(strict_types=1);

namespace App\DTO\Subscriptions;

use App\DTO\BaseDTO;

class SearchSubscriptionsDTO extends BaseDTO
{
    /**
     * @param int[] $officeIds
     * @param int[] $ids
     * @param int[] $customerIds
     * @param bool|null $isActive
     */
    public function __construct(
        public readonly array $officeIds = [],
        public readonly array $ids = [],
        public readonly array $customerIds = [],
        public readonly bool|null $isActive = true,
    ) {
        $this->validateData();
    }

    public function getRules(): array
    {
        return [
            'officeIds.*' => 'int',
            'ids.*' => 'int',
            'customerIds.*' => 'int',
        ];
    }
}
