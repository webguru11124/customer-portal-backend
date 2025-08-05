<?php

namespace Tests\Data;

use App\Models\External\SpotModel;
use App\Repositories\Mappers\PestRoutesSpotToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\Spots\Spot;
use Carbon\Carbon;

/**
 * @extends AbstractTestPestRoutesData<Spot, SpotModel>
 */
class SpotData extends AbstractTestPestRoutesData
{
    public const DATE_FORMAT = 'Y-m-d';

    protected static function getSignature(): array
    {
        return [
            'spotID' => random_int(10000, 99999),
            'officeID' => '1',
            'routeID' => '3662140',
            'date' => Carbon::now()->addDays(3)->format(self::DATE_FORMAT),
            'start' => '16:30:00',
            'end' => '16:59:00',
            'spotCapacity' => '0',
            'description' => '04:30',
            'blockReason' => 'Blocked Inside Sale',
            'currentAppointment' => null,
            'assignedTech' => '0',
            'distanceToPrevious' => '37.976855682979',
            'previousLat' => '34.112831',
            'previousLng' => '-84.673103',
            'prevCustomer' => '2373186',
            'prevSpotID' => '70949264',
            'prevAppointmentID' => '22389239',
            'apiCanSchedule' => '1',
            'open' => '0',
            'lastUpdated' => '2022-08-10 05:18:06',
            'distanceToNext' => '0',
            'nextLat' => '33.8279516',
            'nextLng' => '-84.1065249',
            'nextCustomer' => null,
            'nextSpotID' => '0',
            'nextAppointmentID' => null,
            'reserved' => '0',
            'reservationEnd' => null,
            'appointmentIDs' => [],
            'customerIDs' => [],
            'currentAppointmentDuration' => '0',
            'subscriptionID' => '0',
            'distanceToClosest' => '0',
            'officeTimeZone' => 'America/New_York',
        ];
    }

    protected static function getRequiredEntityClass(): string
    {
        return Spot::class;
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesSpotToExternalModelMapper::class;
    }
}
