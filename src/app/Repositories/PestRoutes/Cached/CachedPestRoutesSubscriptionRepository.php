<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedExternalRepositoryWrapper;
use App\DTO\Subscriptions\ActivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\ActivateSubscriptionResponseDTO;
use App\DTO\Subscriptions\CreateSubscriptionRequestDTO;
use App\DTO\Subscriptions\CreateSubscriptionResponseDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionResponseDTO;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\External\SubscriptionModel;
use App\Repositories\PestRoutes\PestRoutesSubscriptionRepository;
use Illuminate\Support\Collection;

/**
 * @extends AbstractCachedExternalRepositoryWrapper<SubscriptionModel>
 */
class CachedPestRoutesSubscriptionRepository extends AbstractCachedExternalRepositoryWrapper implements SubscriptionRepository
{
    /** @var PestRoutesSubscriptionRepository */
    protected mixed $wrapped;

    public function __construct(PestRoutesSubscriptionRepository $wrapped)
    {

        $this->wrapped = $wrapped;
    }

    protected function getCacheTtl(string $methodName): int
    {
        return match ($methodName) {
            default => ConfigHelper::getSubscriptionRepositoryCacheTtl()
        };
    }

    public function searchByCustomerId(array $customerIds): Collection
    {
        return $this->cached(__FUNCTION__, $customerIds);
    }

    public function createSubscription(
        CreateSubscriptionRequestDTO $subscriptionRequestDTO
    ): CreateSubscriptionResponseDTO {
        return $this->wrapped->createSubscription($subscriptionRequestDTO);
    }

    public function activateSubscription(
        ActivateSubscriptionRequestDTO $activateSubscriptionRequestDTO
    ): ActivateSubscriptionResponseDTO {
        return $this->wrapped->activateSubscription($activateSubscriptionRequestDTO);
    }

    public function deactivateSubscription(
        DeactivateSubscriptionRequestDTO $deactivateSubscriptionRequestDTO
    ): DeactivateSubscriptionResponseDTO {
        return $this->wrapped->deactivateSubscription($deactivateSubscriptionRequestDTO);
    }
}
