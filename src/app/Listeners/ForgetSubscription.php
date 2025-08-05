<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Interfaces\Subscription\SubscriptionStatusChangeAware;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesCustomerRepository as CustomerRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesServiceTypeRepository as ServiceTypeRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesSubscriptionRepository as SubscriptionRepository;
use App\Services\LoggerAwareTrait;
use Illuminate\Support\Facades\Cache;

final class ForgetSubscription
{
    use LoggerAwareTrait;

    public function handle(SubscriptionStatusChangeAware $event): void
    {
        try {
            Cache::tags(SubscriptionRepository::getHashTag('searchByCustomerId'))
                ->forget(SubscriptionRepository::buildKey('searchByCustomerId', [[$event->getAccountNumber()]]));

            Cache::tags(SubscriptionRepository::getHashTag('searchBy'))
                ->forget(SubscriptionRepository::buildKey('searchBy', ['customerId', [$event->getAccountNumber()]]));

            Cache::tags(CustomerRepository::getHashTag('find'))
                ->forget(CustomerRepository::buildKey('find', [$event->getAccountNumber()]));

            Cache::tags(ServiceTypeRepository::getHashTag('searchBy'))
                ->forget(ServiceTypeRepository::buildKey('searchBy', ['customerId', [$event->getAccountNumber()]]));
        } catch (\Throwable $exception) {
            $this->getLogger()?->error(sprintf(
                'Subscription cache was not flushed after event %s due to: %s',
                $event::class,
                $exception->getMessage()
            ));
        }
    }
}
