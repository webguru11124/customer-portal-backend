<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use Aptive\PestRoutesSDK\Resources\Documents\Document;
use Illuminate\Support\Collection;

/**
 * @final
 */
class GenericDocumentService
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly ContractService $contractsService,
        private readonly FormService $formService
    ) {
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocumentsForAccount(Account $account): Collection
    {
        return $this->documentService->getDocumentsForAccount($account)
            ->merge($this->contractsService->getDocumentsForAccount($account))
            ->merge($this->formService->getDocumentsForAccount($account))
            ->sortBy([['dateAdded', 'desc']]);
    }
}
