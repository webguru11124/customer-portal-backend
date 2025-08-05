<?php

declare(strict_types=1);

namespace App\Interfaces\Repository;

use App\DTO\CreditCardAuthorizationDTO;
use App\Models\External\CustomerModel;

/**
 * Handles credit card related features.
 */
interface CreditCardAuthorizationRepository
{
    /**
     * Authorize a given amount to a credit card (Payment Account).
     *
     * @param CreditCardAuthorizationDTO $dto
     * @return mixed
     */
    public function authorize(CreditCardAuthorizationDTO $dto, CustomerModel $customer): mixed;
}
