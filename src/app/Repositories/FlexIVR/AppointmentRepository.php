<?php

declare(strict_types=1);

namespace App\Repositories\FlexIVR;

use App\DTO\FlexIVR\Appointment\CreateAppointment;
use App\DTO\FlexIVR\Appointment\RescheduleAppointment;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Appointment\AppointmentCanNotBeRescheduledException;
use App\Exceptions\Appointment\AppointmentSpotAlreadyUsedException;
use App\Exceptions\Appointment\CannotGetCurrentAppointment;
use App\Interfaces\FlexIVRApi\AppointmentRepository as FlexIVRApiAppointmentRepository;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

/**
 * @final
 */
class AppointmentRepository extends BaseRepository implements FlexIVRApiAppointmentRepository
{
    protected const SPOT_USED_ERROR = 'is directly occupied by appointment';

    /**
     * @param int $customerId
     * @param bool $allowReservice
     *
     * @return object{appointmentID: int, officeID: int, subscriptionID: int, date: string, start: string, type: int}
     *
     * @throws CannotGetCurrentAppointment
     */
    public function getCurrentAppointment(int $customerId, bool $allowReservice = self::ALLOW_RESERVICE): object
    {
        $requestData = [
            'customerID' => $customerId,
            'executionSID' => '',
        ];

        if ($allowReservice) {
            $requestData['allowReservice'] = self::FLEX_IVR_REQUEST_ALLOW_RESERVICE_OPTION_VALUE;
        }

        try {
            /** @var object{success: bool, message: string, appointment: object{appointmentID: int, officeID: int, subscriptionID: int, date: string, start: string, type: int}} $result */
            $result = $this->sendGetRequest('appointment/current', $requestData);
        } catch (GuzzleException|JsonException $e) {
            throw new CannotGetCurrentAppointment(previous: $e);
        }

        if ($result->success !== true) {
            throw new CannotGetCurrentAppointment($result->message);
        }

        return $result->appointment;
    }

    /**
     * @param CreateAppointment $dto
     * @return int
     * @throws AppointmentCanNotBeCreatedException
     * @throws AppointmentSpotAlreadyUsedException
     */
    public function createAppointment(
        CreateAppointment $dto,
    ): int {
        try {
            /** @var object{success: bool, message: string, appointmentID: int, errorMessage: string} $result */
            $result = $this->sendPutRequest('appointment/createV2', $dto->toArray());
        } catch (GuzzleException|JsonException $e) {
            throw new AppointmentCanNotBeCreatedException(previous: $e);
        }

        if ($result->success !== true) {
            if (str_contains($result->message, self::SPOT_USED_ERROR)) {
                throw new AppointmentSpotAlreadyUsedException();
            }
            throw new AppointmentCanNotBeCreatedException($result->message ?? '');
        }

        return (int) $result->appointmentID;
    }

    /**
     * @param RescheduleAppointment $dto
     * @return int
     * @throws AppointmentCanNotBeRescheduledException
     * @throws AppointmentSpotAlreadyUsedException
     */
    public function rescheduleAppointment(
        RescheduleAppointment $dto
    ): int {
        try {
            /** @var object{success: bool, message: string, appointmentID: int, errorMessage: string} $result */
            $result = $this->sendPutRequest('appointment/rescheduleV2', $dto->toArray());
        } catch (GuzzleException|JsonException $e) {
            throw new AppointmentCanNotBeRescheduledException(previous: $e);
        }

        if ($result->success !== true) {
            if (str_contains($result->message, self::SPOT_USED_ERROR)) {
                throw new AppointmentSpotAlreadyUsedException();
            }
            throw new AppointmentCanNotBeRescheduledException($result->message ?? '');
        }

        return (int) $result->appointmentID;
    }
}
