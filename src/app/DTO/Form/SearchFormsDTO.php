<?php

declare(strict_types=1);

namespace App\DTO\Form;

use App\DTO\BaseDTO;
use Illuminate\Validation\ValidationException;

class SearchFormsDTO extends BaseDTO
{
    /**
     * @param int $officeId
     * @param int|null $accountNumber
     * @param array<int|string, int>|null $formIds
     *
     * @throws ValidationException
     */
    public function __construct(
        public int $officeId,
        public int|null $accountNumber = null,
        public array|null $formIds = [],
    ) {
        $this->validateData();
    }

    public function getRules(): array
    {
        return [
            'officeId' => 'gt:0',
            'accountNumber' => 'nullable|int',
            'formIds' => 'nullable|array',
            'formIds.*' => 'int',
        ];
    }
}
