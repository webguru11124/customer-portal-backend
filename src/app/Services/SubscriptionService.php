<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Subscriptions\ActivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\ActivateSubscriptionResponseDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionRequestDTO;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\Account;
use App\Models\External\SubscriptionModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Carbon\Carbon;
use DateTimeInterface;

class SubscriptionService
{
    use LoggerAwareTrait;

    public function __construct(
        protected SubscriptionRepository $subscriptionRepository,
        protected AppointmentService $appointmentService
    ) {
    }

    /**
     * @param int $branchId
     * @param int $accountNumber
     *
     * @return DateTimeInterface|null
     */
    public function getNextDueDateForTheCustomer(int $branchId, int $accountNumber): DateTimeInterface|null
    {
        $subscriptions = $this->subscriptionRepository
            ->office($branchId)
            ->searchByCustomerId([$accountNumber]);

        $dueDate = null;

        foreach ($subscriptions as $subscription) {
            $nextServiceDate = $subscription->nextServiceDate;

            if (empty($dueDate) || $nextServiceDate->getTimestamp() < $dueDate->getTimestamp()) {
                $dueDate = $nextServiceDate;
            }
        }

        return !empty($dueDate) ? Carbon::createFromTimestamp($dueDate->getTimestamp()) : $dueDate;
    }

    /**
     * @throws InternalServerErrorHttpException
     * @throws OfficeNotSetException
     */
    public function activateSubscription(
        Account $account,
        SubscriptionModel $activatedSubscription
    ): ActivateSubscriptionResponseDTO {
        $subscription = $this->subscriptionRepository
            ->office($account->office_id)
            ->activateSubscription(new ActivateSubscriptionRequestDTO(
                subscriptionId: $activatedSubscription->id,
                customerId: $account->account_number,
                officeId: $account->office_id,
            ));

        $activeSubscriptions = $this->subscriptionRepository
            ->office($account->office_id)
            ->searchByCustomerId([$account->account_number]);

        if ($activeSubscriptions->isEmpty()) {
            return $subscription;
        }

        foreach ($activeSubscriptions as $activeSubscription) {
            if ($activeSubscription->id === $activatedSubscription->id) {
                continue;
            }

            try {
                $this->appointmentService->reassignSubscriptionToAppointment(
                    $activatedSubscription,
                    $activeSubscription
                );

                $this->subscriptionRepository
                    ->office($account->office_id)
                    ->deactivateSubscription(new DeactivateSubscriptionRequestDTO(
                        subscriptionId: $activeSubscription->id,
                        customerId: $account->account_number,
                        officeId: $account->office_id
                    ));
            } catch (\Throwable $exception) {
                $this->getLogger()?->error(
                    sprintf(
                        'Subscription %d was not deactivated due to: %s',
                        $activeSubscription->id,
                        $exception->getMessage()
                    )
                );
            }
        }

        return $subscription;
    }
}
