<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\DTO\Subscriptions\ActivateSubscriptionResponseDTO;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\Account;
use App\Services\SubscriptionService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;

class ActivateSubscriptionAction
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     * @throws OfficeNotSetException
     * @throws EntityNotFoundException
     */
    public function __invoke(
        Account $account,
        int $subscriptionId
    ): ActivateSubscriptionResponseDTO {
        return $this->subscriptionService->activateSubscription(
            $account,
            $this->subscriptionRepository->office($account->office_id)->find($subscriptionId)
        );
    }
}
