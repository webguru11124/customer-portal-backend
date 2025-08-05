<?php

declare(strict_types=1);

namespace App\DTO\FlexIVR\Appointment;

use App\Enums\FlexIVR\Source;
use App\Enums\FlexIVR\Window;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

final class RescheduleAppointment extends Data
{
    public function __construct(
        #[MapOutputName('officeID')]
        public readonly int $officeId,
        #[MapOutputName('customerID')]
        public readonly int $accountNumber,
        #[MapOutputName('subscriptionID')]
        public readonly int $subscriptionId,
        #[MapOutputName('spotID')]
        public readonly int $spotId,
        #[MapOutputName('currentApptID')]
        public readonly int $appointmentId,
        #[MapOutputName('currentApptType')]
        public readonly int $appointmentType,
        public readonly Window $window,
        public readonly bool $isAroSpot,
        public readonly Source $requestingSource = Source::CUSTOMER_PORTAL,
        public readonly string $executionSID = '',
        public readonly string|null $notes = null,
    ) {
    }
}
