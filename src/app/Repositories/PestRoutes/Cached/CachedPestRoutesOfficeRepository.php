<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedExternalRepositoryWrapper;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\OfficeRepository;
use App\Models\External\OfficeModel;
use App\Repositories\PestRoutes\PestRoutesOfficeRepository;

/**
 * @extends AbstractCachedExternalRepositoryWrapper<OfficeModel>
 */
class CachedPestRoutesOfficeRepository extends AbstractCachedExternalRepositoryWrapper implements OfficeRepository
{
    public function __construct(PestRoutesOfficeRepository $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    protected function getCacheTtl(string $methodName): int
    {
        return match ($methodName) {
            default => ConfigHelper::getOfficeRepositoryCacheTtl()
        };
    }

    /**
     * @return int[]
     */
    public function getAllOfficeIds(): array
    {
        return $this->cached(__FUNCTION__);
    }
}
