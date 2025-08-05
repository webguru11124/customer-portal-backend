<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedExternalRepositoryWrapper;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\EmployeeRepository;
use App\Models\External\EmployeeModel;
use App\Repositories\PestRoutes\PestRoutesEmployeeRepository;

/**
 * @extends AbstractCachedExternalRepositoryWrapper<EmployeeModel>
 */
class CachedPestRoutesEmployeeRepository extends AbstractCachedExternalRepositoryWrapper implements EmployeeRepository
{
    public function __construct(PestRoutesEmployeeRepository $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    protected function getCacheTtl(string $methodName): int
    {
        return match ($methodName) {
            default => ConfigHelper::getEmployeeRepositoryCacheTtl()
        };
    }

    public function findCxpScheduler(): EmployeeModel
    {
        return $this->cached(__FUNCTION__, $this->getContext()->getOfficeId());
    }
}
