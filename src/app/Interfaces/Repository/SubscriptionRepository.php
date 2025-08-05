<?php

namespace App\Interfaces\Repository;

use App\DTO\Subscriptions\ActivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\ActivateSubscriptionResponseDTO;
use App\DTO\Subscriptions\CreateSubscriptionRequestDTO;
use App\DTO\Subscriptions\CreateSubscriptionResponseDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionResponseDTO;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Models\External\SubscriptionModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Support\Collection;

/**
 * @extends ExternalRepository<SubscriptionModel>
 */
interface SubscriptionRepository extends ExternalRepository
{
    /**
     * @param int[] $customerIds
     *
     * @return Collection<int, SubscriptionModel>
     */
    public function searchByCustomerId(array $customerIds): Collection;

    /**
     * @param CreateSubscriptionRequestDTO $subscriptionRequestDTO
     *
     * @return CreateSubscriptionResponseDTO
     *
     * @throws OfficeNotSetException
     * @throws InternalServerErrorHttpException
     */
    public function createSubscription(
        CreateSubscriptionRequestDTO $subscriptionRequestDTO
    ): CreateSubscriptionResponseDTO;

    /**
     * @param ActivateSubscriptionRequestDTO $activateSubscriptionRequestDTO
     *
     * @return ActivateSubscriptionResponseDTO
     *
     * @throws OfficeNotSetException
     * @throws InternalServerErrorHttpException
     */
    public function activateSubscription(
        ActivateSubscriptionRequestDTO $activateSubscriptionRequestDTO
    ): ActivateSubscriptionResponseDTO;

    /**
     * @param DeactivateSubscriptionRequestDTO $deactivateSubscriptionRequestDTO
     *
     * @return DeactivateSubscriptionResponseDTO
     *
     * @throws OfficeNotSetException
     * @throws InternalServerErrorHttpException
     */
    public function deactivateSubscription(
        DeactivateSubscriptionRequestDTO $deactivateSubscriptionRequestDTO
    ): DeactivateSubscriptionResponseDTO;
}
