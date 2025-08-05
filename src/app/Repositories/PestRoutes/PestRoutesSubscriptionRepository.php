<?php

namespace App\Repositories\PestRoutes;

use App\DTO\Subscriptions\ActivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\ActivateSubscriptionResponseDTO;
use App\DTO\Subscriptions\CreateSubscriptionRequestDTO;
use App\DTO\Subscriptions\CreateSubscriptionResponseDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionResponseDTO;
use App\DTO\Subscriptions\SearchSubscriptionsDTO;
use App\Enums\SubscriptionStatus;
use App\Events\Subscription\SubscriptionStatusChange;
use App\Events\Subscription\SubscriptionCreated;
use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Exceptions\Entity\RelationNotFoundException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\External\SubscriptionModel;
use App\Repositories\Mappers\PestRoutesSubscriptionToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\SubscriptionParametersFactory;
use App\Services\LoggerAwareTrait as PsrLoggerAwareTrait;
use App\Services\PestRoutesClientAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\CreateSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\UpdateSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Subscription;
use Illuminate\Support\Collection;

/**
 * Handle PestRoutes's subscription related API calls.
 *
 * @extends AbstractPestRoutesRepository<SubscriptionModel, Subscription>
 */
class PestRoutesSubscriptionRepository extends AbstractPestRoutesRepository implements SubscriptionRepository
{
    use PestRoutesClientAwareTrait;
    use PsrLoggerAwareTrait;
    /**
     * @use EntityMapperAware<Subscription, SubscriptionModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    public function __construct(
        PestRoutesSubscriptionToExternalModelMapper $entityMapper,
        SubscriptionParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    /**
     * @return Collection<int, Subscription>
     *
     * @throws InternalServerErrorHttpException
     * @throws InvalidSearchedResourceException
     * @throws OfficeNotSetException
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchSubscriptionsDTO(
            officeIds: [$this->getOfficeId()],
            ids: $id
        );

        /** @var Collection<int, Subscription> $result */
        $result = $this->searchNative($searchDto);

        return $result;
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->subscriptions();
    }

    /**
     * @param int[] $customerIds
     *
     * @return Collection<int, SubscriptionModel>
     *
     * @throws RelationNotFoundException
     */
    public function searchByCustomerId(array $customerIds): Collection
    {
        $searchDto = new SearchSubscriptionsDTO(
            officeIds: [$this->getOfficeId()],
            customerIds: $customerIds
        );

        /** @var Collection<int, SubscriptionModel> $result */
        $result = $this->search($searchDto);

        return $result;
    }

    public function createSubscription(
        CreateSubscriptionRequestDTO $subscriptionRequestDTO
    ): CreateSubscriptionResponseDTO {
        $subscriptionResponseDTO = new CreateSubscriptionResponseDTO(
            subscriptionId: $this->getPestRoutesClient()
                ->office($this->getOfficeId())
                ->subscriptions()
                ->create(new CreateSubscriptionsParams(
                    serviceId: $subscriptionRequestDTO->serviceId,
                    customerId: $subscriptionRequestDTO->customerId,
                    followupDelay: $subscriptionRequestDTO->followupDelay,
                    agreementLength: $subscriptionRequestDTO->agreementLength,
                    serviceCharge: $subscriptionRequestDTO->serviceCharge,
                    initialCharge: $subscriptionRequestDTO->initialCharge,
                    isActive: $subscriptionRequestDTO->isActive
                ))
        );

        SubscriptionCreated::dispatch(
            $subscriptionResponseDTO->subscriptionId,
            $this->getOfficeId(),
            $subscriptionRequestDTO->flag,
            $subscriptionRequestDTO->initialAddons,
            $subscriptionRequestDTO->addOns
        );

        return $subscriptionResponseDTO;
    }

    public function activateSubscription(
        ActivateSubscriptionRequestDTO $activateSubscriptionRequestDTO
    ): ActivateSubscriptionResponseDTO {
        $subscription = new ActivateSubscriptionResponseDTO(
            subscriptionId: $this->getPestRoutesClient()
            ->office($this->getOfficeId())
            ->subscriptions()
            ->update(new UpdateSubscriptionsParams(
                subscriptionId: $activateSubscriptionRequestDTO->subscriptionId,
                customerId: $activateSubscriptionRequestDTO->customerId,
                isActive: (bool) SubscriptionStatus::ACTIVE->value
            ))
        );

        SubscriptionStatusChange::dispatch(
            $subscription->subscriptionId,
            $activateSubscriptionRequestDTO->customerId,
            $this->getOfficeId()
        );

        return $subscription;
    }

    public function deactivateSubscription(
        DeactivateSubscriptionRequestDTO $deactivateSubscriptionRequestDTO
    ): DeactivateSubscriptionResponseDTO {
        $subscription = new DeactivateSubscriptionResponseDTO(
            subscriptionId: $this->getPestRoutesClient()
                ->office($this->getOfficeId())
                ->subscriptions()
                ->update(new UpdateSubscriptionsParams(
                    subscriptionId: $deactivateSubscriptionRequestDTO->subscriptionId,
                    isActive: (bool) SubscriptionStatus::FROZEN->value
                ))
        );

        SubscriptionStatusChange::dispatch(
            $subscription->subscriptionId,
            $deactivateSubscriptionRequestDTO->customerId,
            $this->getOfficeId()
        );

        return $subscription;
    }
}
