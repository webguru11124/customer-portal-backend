<?php

declare(strict_types=1);

namespace App\DTO\Office;

use App\DTO\BaseDTO;
use Illuminate\Validation\ValidationException;

/**
 * DTO search Offices.
 */
class SearchOfficesDTO extends BaseDTO
{
    /**
     * @param int[]|null $ids
     *
     * @throws ValidationException
     */
    public function __construct(
        public readonly array|null $ids = null,
    ) {
        $this->validateData();
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return [
            'ids' => 'nullable|array',
            'ids.*' => 'gte:0',
        ];
    }
}
