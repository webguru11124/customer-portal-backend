<?php

declare(strict_types=1);

namespace App\Listeners;

use App\DTO\Ticket\CreateTicketTemplatesAddonRequestDTO;
use App\Helpers\SubscriptionAddonsConfigHelper;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Interfaces\Repository\TicketTemplateAddonRepository;
use App\Interfaces\Subscription\SubscriptionRecurringAddonsAware;
use App\Services\LoggerAwareTrait;

final class AddRecurringAddonsToSubscription
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly TicketTemplateAddonRepository $ticketAddonRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
    ) {
    }

    public function handle(SubscriptionRecurringAddonsAware $event): void
    {
        $addons = $event->getSubscriptionRecurringAddons();

        if (0 === count($addons)) {
            return;
        }

        $this->ticketAddonRepository->office($event->getOfficeId());

        foreach ($addons as $addon) {
            try {
                $ticketId = $this->subscriptionRepository
                    ->office($event->getOfficeId())
                    ->find($event->getSubscriptionId())->recurringTicket?->id;

                if (null === $ticketId) {
                    continue;
                }

                $this->ticketAddonRepository->createTicketsAddon(new CreateTicketTemplatesAddonRequestDTO(
                    ticketId: $ticketId,
                    description: $addon->description,
                    quantity: $addon->quantity,
                    amount: $addon->amount,
                    isTaxable: $addon->taxable ?? SubscriptionAddonsConfigHelper::getAddonDefaultTaxable(),
                    creditTo: SubscriptionAddonsConfigHelper::getAddonDefaultCreditTo(),
                    productId: $addon->productId,
                    serviceId: SubscriptionAddonsConfigHelper::getAddonDefaultServiceId(),
                ));
            } catch (\Throwable $exception) {
                $this->getLogger()?->error(
                    sprintf(
                        'Recurring addon %s was not assigned to subscription %d after event %s due to: %s',
                        $addon->description,
                        $event->getSubscriptionId(),
                        $event::class,
                        $exception->getMessage()
                    )
                );
            }
        }
    }
}
