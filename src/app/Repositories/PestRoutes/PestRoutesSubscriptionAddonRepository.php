<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\Subscriptions\SubscriptionAddonRequestDTO;
use App\Interfaces\Repository\SubscriptionAddonRepository;
use App\Models\External\SubscriptionAddonModel;
use App\Repositories\Mappers\PestRoutesSubscriptionAddonToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\OfficeParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Services\PestRoutesClientAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\CreateSubscriptionAddonsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\SubscriptionAddon;
use Illuminate\Support\Collection;

/**
 * @extends AbstractPestRoutesRepository<SubscriptionAddonModel, SubscriptionAddon>
 */
class PestRoutesSubscriptionAddonRepository extends AbstractPestRoutesRepository implements SubscriptionAddonRepository
{
    use PestRoutesClientAwareTrait;
    use LoggerAwareTrait;

    /**
     * @use EntityMapperAware<SubscriptionAddon, SubscriptionAddonModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    public function __construct(
        PestRoutesSubscriptionAddonToExternalModelMapper $entityMapper,
        OfficeParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    public function createInitialAddon(int $subscriptionId, SubscriptionAddonRequestDTO $initialAddon): int
    {
        return $this->getPestRoutesClient()
            ->office($this->getOfficeId())
            ->subscriptions()
            ->initialAddons()
            ->create(new CreateSubscriptionAddonsParams(
                subscriptionId: $subscriptionId,
                productId: $initialAddon->productId,
                amount: $initialAddon->amount,
                description: $initialAddon->description,
                quantity: $initialAddon->quantity,
                taxable: $initialAddon->taxable,
                serviceId: $initialAddon->serviceId,
                creditTo: $initialAddon->creditTo,
                officeId: $initialAddon->officeId
            ));
    }

    protected function findManyNative(int ...$id): Collection
    {
        // There is no search option for subscription addons. Should be rewritten or left as stub.
        return new Collection();
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        // There is no search option for subscription addons. Should be rewritten or left as stub.
        return $officesResource;
    }
}
