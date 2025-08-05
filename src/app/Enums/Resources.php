<?php

namespace App\Enums;

enum Resources: string
{
    case APPOINTMENT = 'Appointment';
    case AUTOPAY = 'Autopay';
    case CUSTOMER = 'Customer';
    case DOCUMENT = 'Document';
    case CONTRACT = 'Contract';
    case FORM = 'Form';
    case PAYMENT_PROFILE = 'PaymentProfile';
    case SPOT = 'Spot';
    case SUBSCRIPTION = 'Subscription';
    case TICKET = 'Ticket';
    case PLAN = 'Plan';
    case PRODUCT = 'Product';
    case ADDON = 'Addon';
}
