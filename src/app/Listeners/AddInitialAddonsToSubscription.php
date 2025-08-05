<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Interfaces\Repository\SubscriptionAddonRepository;
use App\Interfaces\Subscription\SubscriptionInitialAddonsAware;
use App\Services\LoggerAwareTrait;

final class AddInitialAddonsToSubscription
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly SubscriptionAddonRepository $subscriptionAddonRepository,
    ) {
    }

    public function handle(SubscriptionInitialAddonsAware $event): void
    {
        $addons = $event->getSubscriptionInitialAddons();

        if (0 === count($addons)) {
            return;
        }

        $this->subscriptionAddonRepository->office($event->getOfficeId());

        foreach ($addons as $addon) {
            try {
                $this->subscriptionAddonRepository->createInitialAddon($event->getSubscriptionId(), $addon);
            } catch (\Throwable $exception) {
                $this->getLogger()?->error(
                    sprintf(
                        'Initial addon %s was not assigned to subscription %d after event %s due to: %s',
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
