<?php

namespace Tests\Data;

use App\Models\External\AppointmentModel;
use App\Repositories\Mappers\PestRoutesAppointmentToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment;
use Carbon\Carbon;

/**
 * @extends AbstractTestPestRoutesData<Appointment, AppointmentModel>
 */
class AppointmentData extends AbstractTestPestRoutesData
{
    public const DATE_FORMAT = 'Y-m-d';
    public const TIME_FORMAT = 'H:i:s';
    public const CUSTOMER_TIME_ZONE = 'America/New_York';

    protected static function getRequiredEntityClass(): string
    {
        return Appointment::class;
    }

    protected static function getSignature(): array
    {
        return [
            'appointmentID' => random_int(10000, 99999),
            'officeID' => '1',
            'customerID' => '2561669',
            'subscriptionID' => '2634064',
            'subscriptionRegionID' => '0',
            'routeID' => '3662041',
            'spotID' => '0',
            'date' => Carbon::now()->addDays(3)->format(self::DATE_FORMAT),
            'start' => '08:00:00',
            'end' => '13:00:00',
            'duration' => '20',
            'type' => ServiceTypeData::RESERVICE,
            'dateAdded' => '2022-07-26 04:13:50',
            'employeeID' => '354931',
            'status' => '0',
            'statusText' => 'Pending',
            'timeWindow' => 'AM',
            'callAhead' => '0',
            'isInitial' => '0',
            'subscriptionPreferredTech' => '0',
            'completedBy' => null,
            'servicedBy' => null,
            'dateCompleted' => null,
            'notes' => null,
            'officeNotes' => null,
            'timeIn' => null,
            'timeOut' => null,
            'checkIn' => null,
            'checkOut' => null,
            'windSpeed' => null,
            'windDirection' => null,
            'temperature' => null,
            'amountCollected' => null,
            'paymentMethod' => null,
            'servicedInterior' => null,
            'ticketID' => null,
            'dateCancelled' => null,
            'additionalTechs' => null,
            'appointmentNotes' => '',
            'doInterior' => '0',
            'dateUpdated' => '2022-07-28 03:18:58',
            'cancelledBy' => null,
            'assignedTech' => '0',
            'latIn' => null,
            'latOut' => null,
            'longIn' => null,
            'longOut' => null,
            'sequence' => '0',
            'lockedBy' => '0',
            'unitIDs' => [],
            'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
        ];
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesAppointmentToExternalModelMapper::class;
    }
}
