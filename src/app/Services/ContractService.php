<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Contract\SearchContractsDTO;
use App\Interfaces\Repository\ContractRepository;
use App\Models\Account;
use Aptive\PestRoutesSDK\Resources\Contracts\Contract;
use Illuminate\Support\Collection;

/**
 * @final
 */
class ContractService
{
    public function __construct(
        private readonly ContractRepository $contractRepository
    ) {
    }

    /**
     * @return Collection<int, Contract>
     */
    public function getDocumentsForAccount(Account $account): Collection
    {
        return $this
            ->contractRepository
            ->getDocuments(
                new SearchContractsDTO(
                    officeId: $account->office_id,
                    accountNumbers: [$account->account_number]
                )
            )
            ->sort(fn (Contract $left, Contract $right) => $right->dateAdded <=> $left->dateAdded)
            ->values();
    }
}
