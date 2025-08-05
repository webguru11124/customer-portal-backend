<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedExternalRepositoryWrapper;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\RouteRepository;
use App\Models\External\RouteModel;
use App\Repositories\PestRoutes\PestRoutesRouteRepository;

/**
 * @extends AbstractCachedExternalRepositoryWrapper<RouteModel>
 */
class CachedPestRoutesRouteRepository extends AbstractCachedExternalRepositoryWrapper implements RouteRepository
{
    public function __construct(PestRoutesRouteRepository $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    protected function getCacheTtl(string $methodName): int
    {
        return match ($methodName) {
            default => ConfigHelper::getRouteRepositoryCacheTtl()
        };
    }
}
