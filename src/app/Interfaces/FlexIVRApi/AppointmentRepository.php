<?php

declare(strict_types=1);

namespace App\Interfaces\FlexIVRApi;

use App\DTO\FlexIVR\Appointment\CreateAppointment;
use App\DTO\FlexIVR\Appointment\RescheduleAppointment;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Appointment\AppointmentSpotAlreadyUsedException;
use App\Exceptions\Appointment\CannotGetCurrentAppointment;

interface AppointmentRepository
{
    public const ALLOW_RESERVICE = true;
    public const FLEX_IVR_REQUEST_ALLOW_RESERVICE_OPTION_VALUE = "True";

    /**
     * @param int $customerId
     *
     * @return object{appointmentID: int, officeID: int, subscriptionID: int, date: string, start: string, type: int}
     *
     * @throws CannotGetCurrentAppointment
     */
    public function getCurrentAppointment(int $customerId): object;

    /**
     * @param CreateAppointment $dto
     *
     * @return int appointment ID
     *
     * @throws AppointmentCanNotBeCreatedException
     * @throws AppointmentSpotAlreadyUsedException
     */
    public function createAppointment(CreateAppointment $dto): int;

    /**
     * @param RescheduleAppointment $dto
     *
     * @return int appointment ID
     *
     * @throws AppointmentCanNotBeCreatedException
     * @throws AppointmentSpotAlreadyUsedException
     */
    public function rescheduleAppointment(RescheduleAppointment $dto): int;
}
