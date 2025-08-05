<?php

declare(strict_types=1);

namespace App\Http\Responses\Appointment;

use App\Enums\Resources;
use App\Http\Responses\AbstractFindResponse;
use App\Models\External\AppointmentModel;

final class FindAppointmentResponse extends AbstractFindResponse
{
    use AppointmentAdditionalAttributes;

    protected function getExpectedEntityClass(): string
    {
        return AppointmentModel::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::APPOINTMENT;
    }
}
