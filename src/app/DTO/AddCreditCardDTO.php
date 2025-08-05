<?php

namespace App\DTO;

/**
 * DTO for Add Credit Card.
 */
class AddCreditCardDTO extends BaseDTO
{
    public function __construct(
        public string $credit_card_number,
        public string $expiration_month,
        public string $expiration_year,
        public string|null $billing_name,
        public string|null $billing_address_line_1,
        public string|null $billing_address_line_2,
        public string|null $billing_city,
        public string|null $billing_state,
        public string|null $billing_zip,
    ) {
    }
}
