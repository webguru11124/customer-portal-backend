<?php

declare(strict_types=1);

namespace App\DTO\Contract;

use App\DTO\BaseDTO;
use Illuminate\Validation\ValidationException;

class SearchContractsDTO extends BaseDTO
{
    /**
     * @param int $officeId
     * @param array<int|string, int>|null $accountNumbers
     *
     * @throws ValidationException
     */
    public function __construct(
        public int $officeId,
        public array|null $accountNumbers = null,
    ) {
        $this->validateData();
    }

    public function getRules(): array
    {
        return [
            'officeId' => 'gt:0',
            'accountNumbers' => 'nullable|array',
            'accountNumbers.*' => 'int',
        ];
    }
}
