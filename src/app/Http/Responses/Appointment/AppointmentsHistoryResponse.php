<?php

namespace App\Http\Responses\Appointment;

use App\Enums\Resources;
use App\Http\Responses\AbstractSearchResponse;
use App\Models\External\AppointmentModel;

class AppointmentsHistoryResponse extends AbstractSearchResponse
{
    protected function getExpectedEntityClass(): string
    {
        return AppointmentModel::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::APPOINTMENT;
    }

    /**
     * @inheritdoc
     */
    protected function getRelationships(): array
    {
        return [
            'documents' => $this->hasMany(
                fn (AppointmentModel $appointmentModel) => $appointmentModel->documents,
                Resources::DOCUMENT
            ),
        ];
    }
}
