<?php

declare(strict_types=1);

namespace App\Utilites;

use App\Exceptions\Subscription\CanNotDetermineDueSubscription;
use App\Helpers\ConfigHelper;
use App\Models\External\AppointmentModel;
use App\Models\External\CustomerModel;
use App\Models\External\SubscriptionModel;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Carbon\Carbon;

class CustomerDutyDeterminer
{
    public function getSubscriptionCustomerIsDueFor(CustomerModel $customer): SubscriptionModel|null
    {
        if (!$this->canDetermine($customer)) {
            throw new CanNotDetermineDueSubscription();
        }

        $dueSubscriptions = $customer->subscriptions->filter(
            fn (SubscriptionModel $subscription) => $subscription->isActive
                && $this->isCustomerDueForSubscription($customer, $subscription)
        );

        if ($dueSubscriptions->isEmpty()) {
            return null;
        }

        if ($dueSubscriptions->count() === 1) {
            return $dueSubscriptions->first();
        }

        $nonMosquitoSubscription = $dueSubscriptions->filter(
            fn (SubscriptionModel $subscription) => !in_array(
                $subscription->serviceType->description,
                ConfigHelper::getMosquitoServiceTypes()
            )
        )->first();

        return $nonMosquitoSubscription;
    }

    private function isCustomerDueForSubscription(CustomerModel $customer, SubscriptionModel $subscription): bool
    {
        $appointmentsCollection = $customer->appointments->filter(
            fn (AppointmentModel $appointmentModel) => $appointmentModel->status === AppointmentStatus::Completed
                && $appointmentModel->serviceType !== null
                && !$appointmentModel->isReservice()
                && $appointmentModel->subscriptionId === $subscription->id
        );

        if ($appointmentsCollection->isEmpty()) {
            return true;
        }

        $appointmentsCollection = $appointmentsCollection->sortBy(['start']);

        /** @var AppointmentModel $lastAppointment */
        $lastAppointment = $appointmentsCollection->last();

        if ($lastAppointment->start === null) {
            // @codeCoverageIgnoreStart
            return true;
            // @codeCoverageIgnoreEnd
        }

        if ($lastAppointment->serviceType
            && $lastAppointment->serviceType->isInitial
        ) {
            return Carbon::parse($lastAppointment->start)->diffInDays(Carbon::now()) > 20;
        }

        $lastAppointmentInterval = Carbon::now($lastAppointment->start->getTimezone())
            ->diffInDays(Carbon::instance($lastAppointment->start)->startOfDay());

        $reserviceInterval = $this->getReserviceInterval($subscription->serviceType->description);

        return $lastAppointmentInterval > $reserviceInterval;
    }

    private function getReserviceInterval(string $serviceType): int
    {
        $serviceTypesSummerIntervals = ConfigHelper::getSummerIntervalServiceTypes();

        if (!array_key_exists($serviceType, $serviceTypesSummerIntervals)) {
            return in_array($serviceType, ConfigHelper::getShortIntervalServiceTypes())
                ? ConfigHelper::getShortReserviceInterval()
                : ConfigHelper::getLongReserviceInterval();
        }

        $month = Carbon::now()->month;
        return $month > 3 && $month < 11 ? (int) $serviceTypesSummerIntervals[$serviceType] :
            ConfigHelper::getBasicReserviceInterval();
    }

    private function canDetermine(CustomerModel $customer): bool
    {
        $activeSubscriptions = $customer->subscriptions->filter(
            fn (SubscriptionModel $subscription) => $subscription->isActive
        );
        $subscriptionsCount = $activeSubscriptions->count();

        if ($subscriptionsCount === 1) {
            return true;
        }

        if ($subscriptionsCount > 2 || $subscriptionsCount === 0) {
            return false;
        }

        $mosquito = $activeSubscriptions->filter(
            fn (SubscriptionModel $subscription) => in_array(
                $subscription->serviceType->description,
                ConfigHelper::getMosquitoServiceTypes()
            )
        );

        return count($mosquito) === 1;
    }
}
