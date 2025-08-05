<?php

declare(strict_types=1);

namespace App\Http\Responses\Appointment;

use App\Enums\Resources;
use App\Http\Responses\AbstractSearchResponse;
use App\Models\External\AppointmentModel;

class SearchAppointmentsResponse extends AbstractSearchResponse
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
