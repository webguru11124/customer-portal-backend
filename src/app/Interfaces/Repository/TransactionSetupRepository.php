<?php

namespace App\Interfaces\Repository;

use App\DTO\CreateTransactionSetupDTO;

/**
 * Handles transaction setup related features.
 */
interface TransactionSetupRepository
{
    /**
     * Creates new Transaction Setup.
     *
     * @param CreateTransactionSetupDTO $dto
     *
     * @return string
     */
    public function create(CreateTransactionSetupDTO $dto): string;
}
