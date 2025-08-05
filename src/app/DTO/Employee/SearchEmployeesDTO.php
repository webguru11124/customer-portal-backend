<?php

declare(strict_types=1);

namespace App\DTO\Employee;

use App\DTO\BaseDTO;
use Illuminate\Validation\ValidationException;

/**
 * DTO search Employees.
 */
class SearchEmployeesDTO extends BaseDTO
{
    /**
     * @param int $officeId
     * @param int[] $ids
     * @param string|null $fname
     * @param string|null $lname
     *
     * @throws ValidationException
     */
    public function __construct(
        public readonly int $officeId,
        public readonly array $ids = [],
        public readonly string|null $fname = null,
        public readonly string|null $lname = null,
    ) {
        $this->validateData();
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return [
            'officeId' => 'gt:0',
            'ids' => 'nullable|array',
            'ids.*' => 'int',
        ];
    }
}
