<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedExternalRepositoryWrapper;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Models\External\ServiceTypeModel;
use App\Repositories\PestRoutes\PestRoutesServiceTypeRepository;

/**
 * @extends AbstractCachedExternalRepositoryWrapper<ServiceTypeModel>
 */
class CachedPestRoutesServiceTypeRepository extends AbstractCachedExternalRepositoryWrapper implements ServiceTypeRepository
{
    public function __construct(PestRoutesServiceTypeRepository $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    protected function getCacheTtl(string $methodName): int
    {
        return match ($methodName) {
            default => ConfigHelper::getServiceTypeRepositoryCacheTtl()
        };
    }
}
