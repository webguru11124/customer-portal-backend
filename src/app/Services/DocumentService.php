<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Document\SearchDocumentsDTO;
use App\Interfaces\Repository\DocumentRepository;
use App\Models\Account;
use Aptive\PestRoutesSDK\Resources\Documents\Document;
use Illuminate\Support\Collection;

/**
 * @final
 */
class DocumentService
{
    public function __construct(
        private readonly DocumentRepository $documentRepository
    ) {
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocumentsForAccount(Account $account): Collection
    {
        return $this
            ->documentRepository
            ->getDocuments(
                new SearchDocumentsDTO(
                    $account->office_id,
                    $account->account_number
                )
            )
            ->sort(fn (Document $left, Document $right) => $right->dateAdded <=> $left->dateAdded)
            ->values();
    }
}
