<?php

declare(strict_types=1);

namespace App\Listeners;

use App\DTO\GenericFlagAssignmentsRequestDTO;
use App\Interfaces\Repository\GenericFlagAssignmentRepository;
use App\Interfaces\Subscription\SubscriptionFlagAware;
use App\Services\LoggerAwareTrait;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\GenericFlagAssignmentType;

final class AssignFlagToSubscription
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly GenericFlagAssignmentRepository $repository
    ) {
    }

    public function handle(SubscriptionFlagAware $event): void
    {
        if (null === $event->getSubscriptionFlag()) {
            return;
        }

        try {
            $this->repository
                ->office($event->getOfficeId())
                ->assignGenericFlag(new GenericFlagAssignmentsRequestDTO(
                    genericFlagId: $event->getSubscriptionFlag(),
                    entityId: $event->getSubscriptionId(),
                    type: GenericFlagAssignmentType::SUBS
                ));
        } catch (\Throwable $exception) {
            $this->getLogger()?->error(
                sprintf(
                    'Flag %d was not assigned to subscription %d after event %s due to: %s',
                    $event->getSubscriptionFlag(),
                    $event->getSubscriptionId(),
                    $event::class,
                    $exception->getMessage()
                )
            );
        }
    }
}
