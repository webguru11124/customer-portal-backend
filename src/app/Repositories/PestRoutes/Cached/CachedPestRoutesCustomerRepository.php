<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedExternalRepositoryWrapper;
use App\DTO\Customer\UpdateCommunicationPreferencesDTO;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\External\CustomerModel;
use App\Repositories\PestRoutes\PestRoutesCustomerRepository;
use Illuminate\Support\Collection;

/**
 * @extends AbstractCachedExternalRepositoryWrapper<CustomerModel>
 */
class CachedPestRoutesCustomerRepository extends AbstractCachedExternalRepositoryWrapper implements CustomerRepository
{
    /** @var PestRoutesCustomerRepository */
    protected mixed $wrapped;

    public function __construct(PestRoutesCustomerRepository $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    protected function getCacheTtl(string $methodName): int
    {
        return match ($methodName) {
            default => ConfigHelper::getCustomerRepositoryCacheTtl()
        };
    }

    /**
     * @inheritDoc
     */
    public function updateCustomerCommunicationPreferences(UpdateCommunicationPreferencesDTO $dto): int
    {
        return $this->wrapped->updateCustomerCommunicationPreferences($dto);
    }

    /**
     * @inheritDoc
     */
    public function searchActiveCustomersByEmail(string $email, array $officeIds, bool|null $isActive = true): Collection
    {
        return $this->cached(__FUNCTION__, $email, $officeIds, $isActive);
    }
}
