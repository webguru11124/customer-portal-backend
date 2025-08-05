<?php

declare(strict_types=1);

namespace App\Http\Responses\Appointment;

use App\Models\External\AppointmentModel;

trait AppointmentAdditionalAttributes
{
    protected function additionalAttributes(): array
    {
        return [
            'serviceTypeName' => fn (AppointmentModel $appointment) => $appointment->serviceTypeName,
            'duration' => fn (AppointmentModel $appointment) => $appointment->durationRepresentation,
            'canBeCanceled' => fn (AppointmentModel $appointment) => $appointment->canBeCanceled(),
            'canBeRescheduled' => fn (AppointmentModel $appointment) => $appointment->canBeRescheduled(),
            'rescheduleMessage' => fn (AppointmentModel $appointment) => $this->getRescheduleMessage($appointment),
            'scheduledStartTime' => fn (AppointmentModel $appointment) => $appointment->start,
        ];
    }

    private function getRescheduleMessage(AppointmentModel $appointmentModel): string
    {
        if ($appointmentModel->isToday()) {
            return $this->getDenyTodayRescheduleMessage();
        }

        return '';
    }

    private function getDenyTodayRescheduleMessage(): string
    {
        return <<<'MESSAGE'
            Online rescheduling is not available for a same-day appointment.
            Please call or text 855-BUG-FREE (855-284-3733) for personal assistance.
            MESSAGE;
    }
}
