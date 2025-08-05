<?php

namespace App\Providers;

use App\Events\Appointment\AppointmentCanceled;
use App\Events\Appointment\AppointmentScheduled;
use App\Events\CustomerSessionStarted;
use App\Events\Payment\PaymentMade;
use App\Events\PaymentMethod\AchAdded;
use App\Events\PaymentMethod\CcAdded;
use App\Events\Subscription\SubscriptionStatusChange;
use App\Events\Subscription\SubscriptionCreated;
use App\Infra\Metrics\Listeners\TrackEvent;
use App\Infra\Metrics\TrackedEvent;
use App\Listeners\AddInitialAddonsToSubscription;
use App\Listeners\AddRecurringAddonsToSubscription;
use App\Listeners\AssignFlagToSubscription;
use App\Listeners\ForgetSubscription;
use App\Listeners\FlushSpotsCache;
use App\Listeners\ForgetCustomerData;
use App\Listeners\PreloadSpots;
use App\Listeners\QueryExecutedListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        QueryExecuted::class => [
            QueryExecutedListener::class,
        ],
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        AppointmentScheduled::class => [
            FlushSpotsCache::class,
            PreloadSpots::class,
        ],
        AppointmentCanceled::class => [
            FlushSpotsCache::class,
            PreloadSpots::class,
        ],
        CustomerSessionStarted::class => [
            PreloadSpots::class,
        ],
        AchAdded::class => [
            ForgetCustomerData::class,
        ],
        CcAdded::class => [
            ForgetCustomerData::class,
        ],
        PaymentMade::class => [
            ForgetCustomerData::class,
        ],
        TrackedEvent::class => [
            TrackEvent::class,
        ],
        SubscriptionCreated::class => [
            AssignFlagToSubscription::class,
            AddInitialAddonsToSubscription::class,
            AddRecurringAddonsToSubscription::class,
        ],
        SubscriptionStatusChange::class => [
            ForgetSubscription::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
