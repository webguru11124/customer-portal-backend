<?php

declare(strict_types=1);

namespace App\Interfaces\Repository;

use App\DTO\CleoCrm\AccountDTO;

interface CleoCrmRepository
{
    public function getAccount(int $pestRoutesCustomerAccountId): AccountDTO|null;
}
