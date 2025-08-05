<?php

namespace App\DTO;

/**
 * DTO for Create a Transaction Setup.
 */
class InitiateTransactionSetupDTO extends BaseDTO
{
    public function __construct(
        public int $accountNumber,
        public string|null $email,
        public string|null $phoneNumber
    ) {
        $this->validateData();
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return [
            'accountNumber' => 'required|max:128',
        ];
    }
}
