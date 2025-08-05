<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\AbstractExternalModel;
use App\Models\External\AppointmentModel;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment;

/**
 * @implements ExternalModelMapper<Appointment, AppointmentModel>
 */
class PestRoutesAppointmentToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Appointment $source
     *
     * @return AbstractExternalModel
     */
    public function map(object $source): AbstractExternalModel
    {
        return AppointmentModel::from((array) $source);
    }
}
