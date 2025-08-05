<?php

namespace App\DTO\Ticket;

use App\DTO\BaseDTO;
use Illuminate\Validation\ValidationException;

class SearchTicketsDTO extends BaseDTO
{
    /**
     * @param int $officeId
     * @param int|null $accountNumber
     * @param bool $dueOnly
     * @param int[] $ids
     *
     * @throws ValidationException
     */
    public function __construct(
        public readonly int $officeId,
        public readonly int|null $accountNumber = null,
        public readonly bool $dueOnly = false,
        public readonly array $ids = []
    ) {
        $this->validateData();
    }

    public function getRules(): array
    {
        return [
            'officeId' => 'required|int|gt:0',
            'accountNumber' => 'nullable|int|gt:0',
            'dueOnly' => 'bool',
            'ids.*' => 'int',
        ];
    }
}
