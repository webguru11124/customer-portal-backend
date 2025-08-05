<?php

declare(strict_types=1);

namespace App\Enums\FlexIVR;

use App\Models\External\ServiceTypeModel;
use InvalidArgumentException;

enum AppointmentType: int
{
    case RESERVICE = 0;
    case INITIAL_SERVICE = 1;
    case QUARTERLY_SERVICE = 2;
    case BASIC = 3;
    case PRO = 4;
    case PRO_PLUS = 5;
    case PREMIUM = 6;

    public static function fromServiceType(ServiceTypeModel $serviceType): self
    {
        return match ($serviceType->description) {
            'Reservice' => self::RESERVICE,
            'Initial Service' => self::INITIAL_SERVICE,
            'Quarterly Service' => self::QUARTERLY_SERVICE,
            'Basic' => self::BASIC,
            'Pro' => self::PRO,
            'Pro Plus' => self::PRO_PLUS,
            'Premium' => self::PREMIUM,
            default => throw new InvalidArgumentException('Unexpected service type'),
        };
    }
}
