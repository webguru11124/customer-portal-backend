<?php

namespace App\DTO;

/**
 * DTO for Create a Transaction Setup.
 */
class CreditCardAuthorizationDTO extends BaseDTO
{
    public function __construct(
        public string $paymentAccountID,
        public float $transactionAmount,
    ) {
    }
}
