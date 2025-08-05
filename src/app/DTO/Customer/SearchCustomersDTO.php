<?php

namespace App\DTO\Customer;

use App\DTO\BaseDTO;

final class SearchCustomersDTO extends BaseDTO
{
    /**
     * @param int[] $officeIds
     * @param int[]|null $accountNumbers
     * @param string|null $email
     * @param bool|null $isActive
     * @param bool $includeCancellationReason
     * @param bool $includeSubscriptions
     * @param bool $includeCustomerFlag
     * @param bool $includeAdditionalContacts
     * @param bool $includePortalLogin
     */
    public function __construct(
        public readonly array $officeIds,
        public readonly array|null $accountNumbers = null,
        public readonly string|null $email = null,
        public readonly bool|null $isActive = null,
        public readonly bool $includeCancellationReason = true,
        public readonly bool $includeSubscriptions = true,
        public readonly bool $includeCustomerFlag = true,
        public readonly bool $includeAdditionalContacts = true,
        public readonly bool $includePortalLogin = true,
    ) {
    }
}
