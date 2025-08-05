<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Models\External\SubscriptionModel;
use App\Repositories\Mappers\PestRoutesSubscriptionToExternalModelMapper;
use Aptive\PestRoutesSDK\Entity;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Subscription;
use Illuminate\Support\Collection;

/**
 * @extends AbstractTestPestRoutesData<Subscription, SubscriptionModel>
 */
class SubscriptionData extends AbstractTestPestRoutesData
{
    protected static function getSignature(): array
    {
        return [
            'subscriptionID' => random_int(10000, 99999),
            'customerID' => random_int(1997, PHP_INT_MAX),
            'billToAccountID' => random_int(2051, PHP_INT_MAX),
            'officeID' => random_int(1, 197),
            'dateAdded' => '2022-08-23 08:33:10',
            'contractAdded' => null,
            'active' => '1',
            'activeText' => 'Active',
            'initialQuote' => '249.00',
            'initialDiscount' => '0.00',
            'initialServiceTotal' => '249.00',
            'yifDiscount' => '0.00',
            'recurringCharge' => '0.00',
            'contractValue' => '249.00',
            'billingFrequency' => '-1',
            'frequency' => '90',
            'followupService' => '90',
            'agreementLength' => '12',
            'nextService' => '2022-08-23',
            'lastCompleted' => '0000-00-00',
            'serviceID' => '1800',
            'serviceType' => 'Pro Plus',
            'soldBy' => '13894',
            'soldBy2' => '0',
            'soldBy3' => '0',
            'preferredTech' => '0',
            'addedBy' => '0',
            'initialAppointmentID' => null,
            'initialStatus' => null,
            'initialStatusText' => 'No Appointment',
            'dateCancelled' => '0000-00-00 00:00:00',
            'dateUpdated' => '2022-08-23 08:33:10',
            'cxlNotes' => '',
            'subscriptionLink' => null,
            'appointmentIDs' => null,
            'completedAppointmentIDs' => null,
            'leadID' => null,
            'leadDateAdded' => null,
            'leadUpdated' => null,
            'leadAddedBy' => null,
            'leadSourceID' => null,
            'leadSource' => null,
            'leadStatus' => null,
            'leadStageID' => null,
            'leadStage' => null,
            'leadAssignedTo' => null,
            'leadDateAssigned' => null,
            'leadValue' => null,
            'leadDateClosed' => null,
            'leadLostReason' => null,
            'sourceID' => '0',
            'source' => null,
            'annualRecurringServices' => '4',
            'regionID' => '0',
            'initialInvoice' => 'INITIAL_COMPLETION',
            'initialBillingDate' => '0000-00-00',
            'renewalFrequency' => '360',
            'renewalDate' => '0000-00-00',
            'customDate' => '0000-00-00',
            'sentriconConnected' => null,
            'sentriconSiteID' => null,
            'seasonalStart' => '0000-00-00',
            'seasonalEnd' => '0000-00-00',
            'nextBillingDate' => '2022-08-23',
            'maxMonthlyCharge' => '0.00',
            'expirationDate' => '0000-00-00',
            'lastAppointment' => '0',
            'duration' => '-1',
            'preferredDays' => '',
            'preferredStart' => '00:00:00',
            'preferredEnd' => '00:00:00',
            'unitIDs' => [
            ],
            'recurringTicket' => null,
            'addOns' => [
            ],
        ];
    }

    protected static function getRequiredEntityClass(): string
    {
        return Subscription::class;
    }

    public static function getTestTypedSubscriptions(int ...$serviceTypeIds): Collection
    {
        return self::getTestData(
            count($serviceTypeIds),
            ...array_map(fn (int $serviceTypeId) => [
                'serviceID' => $serviceTypeId,
                'serviceType' => ServiceTypeData::SERVICE_NAMES[$serviceTypeId],
            ], $serviceTypeIds)
        );
    }

    public static function getRawTestTypedSubscriptions(int ...$serviceTypeIds): Collection
    {
        return self::getRawTestData(
            count($serviceTypeIds),
            ...array_map(fn (int $serviceTypeId) => [
                'serviceID' => $serviceTypeId,
                'serviceType' => ServiceTypeData::SERVICE_NAMES[$serviceTypeId],
            ], $serviceTypeIds)
        );
    }

    public static function getRawTestTypedInactiveSubscription($serviceTypeId): Collection
    {
        return self::getRawTestData(
            1,
            [
                'serviceID' => $serviceTypeId,
                'serviceType' => ServiceTypeData::SERVICE_NAMES[$serviceTypeId],
                'active' => '0',
            ]
        );
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesSubscriptionToExternalModelMapper::class;
    }

    /**
     * @return Collection<int, SubscriptionModel>
     */
    public static function getTestTypedSubscriptionModels(int ...$serviceTypeIds): Collection
    {
        $mapper = new (static::getMapperClass());

        $pestRoutesTestData = self::getTestTypedSubscriptions(...$serviceTypeIds);

        return $pestRoutesTestData->map(fn (Entity $pestRoutesEntity) => $mapper->map($pestRoutesEntity));
    }
}
