<?php

declare(strict_types=1);

namespace App\DTO\Document;

use App\DTO\BaseDTO;

/**
 * DTO search Documents.
 */
class SearchDocumentsDTO extends BaseDTO
{
    /**
     * @param int $officeId
     * @param int|null $accountNumber
     * @param array<int, int>|null $appointmentIds
     * @param array<int|string, int>|null $ids
     * @param bool $includeDocumentLink
     */
    public function __construct(
        public int $officeId,
        public int|null $accountNumber = null,
        public array|null $appointmentIds = null,
        public array|null $ids = null,
        public bool $includeDocumentLink = true,
    ) {
        $this->validateData();
    }

    public function getRules(): array
    {
        return [
            'officeId' => 'gt:0',
            'accountNumber' => 'nullable|int',
            'appointmentIds' => 'nullable|array',
            'ids' => 'nullable|array',
            'appointmentIds.*' => 'int',
            'ids.*' => 'int',
        ];
    }
}
