<?php

namespace App\DTO;

/**
 * DTO for Create a Transaction Setup.
 */
class CreateTransactionSetupDTO extends BaseDTO
{
    public const SLUG_LENGTH = 6;

    public function __construct(
        public string $slug,
        public int $officeId,
        public string|null $email,
        public string|null $phone_number,
        public string $billing_name,
        public string $billing_address_line_1,
        public string|null $billing_address_line_2,
        public string $billing_city,
        public string $billing_state,
        public string $billing_zip,
        public bool|null $auto_pay,
    ) {
        $this->validateData();
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return [
            'slug' => sprintf('required|size:%d', self::SLUG_LENGTH),
            'email' => 'nullable|email|max:128',
            'phone_number' => 'sometimes|max:14',
            'billing_name' => 'required|max:128',
            'billing_address_line_1' => 'required|max:128',
            'billing_address_line_2' => 'sometimes|max:128',
            'billing_city' => 'required|max:64',
            'billing_state' => 'required|max:2|alpha',
            'billing_zip' => 'required|max:5',
        ];
    }
}
