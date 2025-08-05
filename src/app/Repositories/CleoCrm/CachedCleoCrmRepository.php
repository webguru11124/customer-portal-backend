<?php

declare(strict_types=1);

namespace App\Repositories\CleoCrm;

use App\Cache\AbstractCachedWrapper;
use App\DTO\CleoCrm\AccountDTO;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\CleoCrmRepository as CleoCrmRepositoryInterface;

class CachedCleoCrmRepository extends AbstractCachedWrapper implements CleoCrmRepositoryInterface
{
    public function __construct(CleoCrmRepository $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    protected function getCacheTtl(string $methodName): int
    {
        return match ($methodName) {
            default => ConfigHelper::getCleoCrmRepositoryCacheTtl()
        };
    }

    public function getAccount(int $pestRoutesCustomerAccountId): AccountDTO|null
    {
        return $this->cached(__FUNCTION__, $pestRoutesCustomerAccountId);
    }
}
