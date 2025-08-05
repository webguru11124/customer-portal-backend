<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Form\SearchFormsDTO;
use App\Interfaces\Repository\FormRepository;
use App\Models\Account;
use Aptive\PestRoutesSDK\Resources\Forms\Form;
use Illuminate\Support\Collection;

/**
 * @final
 */
class FormService
{
    public function __construct(
        private readonly FormRepository $formRepository
    ) {
    }

    /**
     * @return Collection<int, Form>
     */
    public function getDocumentsForAccount(Account $account): Collection
    {
        return $this
            ->formRepository
            ->getDocuments(
                new SearchFormsDTO(
                    officeId: $account->office_id,
                    accountNumber: $account->account_number
                )
            )
            ->sort(fn (Form $left, Form $right) => $right->dateAdded <=> $left->dateAdded)
            ->values();
    }
}
