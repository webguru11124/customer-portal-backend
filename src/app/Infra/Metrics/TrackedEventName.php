<?php

declare(strict_types=1);

namespace App\Infra\Metrics;

enum TrackedEventName: string
{
    case AppointmentScheduled = 'appointment/scheduled';
    case AppointmentCanceled = 'appointment/canceled';
    case AppointmentRescheduled = 'appointment/rescheduled';
    case AchPaymentMethodAdded = 'billing/added_method_ach';
    case CcPaymentMethodAdded = 'billing/added_method_cc';
    case PaymentMade = 'billing/made_one_time_payment';
    case SubscriptionCreated = 'subscription/created';
    case SubscriptionStatusChange = 'subscription/status_change';
    case SpotSearched = 'spot/searched';
}
