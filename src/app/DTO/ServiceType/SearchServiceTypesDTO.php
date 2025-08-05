<?php

declare(strict_types=1);

namespace App\DTO\ServiceType;

use App\DTO\BaseDTO;
use Illuminate\Validation\ValidationException;

class SearchServiceTypesDTO extends BaseDTO
{
    /**
     * @param int[] $ids
     * @param int[] $officeIds
     *
     * @throws ValidationException
     */
    public function __construct(
        public readonly array $ids = [],
        public readonly array $officeIds = []
    ) {
        $this->validateData();
    }

    public function getRules(): array
    {
        return [
            'ids' => 'nullable|array',
            'ids.*' => 'int',
            'officeIds' => 'nullable|array',
            'officeIds.*' => 'int',
        ];
    }
}
