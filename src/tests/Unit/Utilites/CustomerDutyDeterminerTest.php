<?php

declare(strict_types=1);

namespace Tests\Unit\Utilites;

use App\Exceptions\Subscription\CanNotDetermineDueSubscription;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Models\External\AppointmentModel;
use App\Models\External\CustomerModel;
use App\Models\External\SubscriptionModel;
use App\Utilites\CustomerDutyDeterminer;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\AppointmentData;
use Tests\Data\CustomerData;
use Tests\Data\ServiceTypeData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class CustomerDutyDeterminerTest extends TestCase
{
    use RandomIntTestData;

    private const RESERVICE_INTERVAL_DUE = 63;
    private const RESERVICE_INTERVAL_MOSQUITO_DUE = 28;

    private const RESERVICE_INTERVAL_NOT_DUE = 60;
    private const RESERVICE_INTERVAL_MOSQUITO_NOT_DUE = 25;

    private const RESERVICE_INTERVAL_MOSQUITO_THRESHOLD = 26;

    private const RESERVICE_INTERVAL_BASIC_DUE = 39;
    private const RESERVICE_INTERVAL_SUMMER_PRO_DUE = 24;
    private const RESERVICE_INTERVAL_SUMMER_PREMIUM_DUE = 14;
    private const PRIMARY_SUBSCRIPTION_ID = 2634064;
    private const SECONDARY_SUBSCRIPTION_ID = 2634000;

    private const CUSTOMER_TIME_ZONE = 'America/New_York';
    private const DAY_START = '00:00:00';
    private const DAY_END = '23:59:59';
    private const DATE_FORMAT = 'Y-m-d';
    private const SUMMER_DAY = '2023-06-06 11:00:00';
    private const WINTER_DAY = '2023-12-12 11:00:00';

    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;
    protected CustomerDutyDeterminer $customerDutyDeterminer;

    public function setUp(): void
    {
        parent::setUp();

        $this->appointmentRepositoryMock = Mockery::mock(AppointmentRepository::class);
        $this->instance(ServiceTypeRepository::class, $this->appointmentRepositoryMock);

        $this->customerDutyDeterminer = new CustomerDutyDeterminer($this->appointmentRepositoryMock);
    }

    /**
     * @dataProvider resolverDataProvider
     */
    public function test_it_determines_proper_subscription(
        array $subscriptionsData,
        array $appointmentsData,
        int|null $expectedSubscriptionServiceTypeId,
        string $testDate = self::SUMMER_DAY,
    ) {
        Carbon::setTestNow($testDate);
        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData()->first();

        $subscriptions = new Collection();

        foreach ($subscriptionsData as $datum) {
            /** @var SubscriptionModel $subscription */
            $subscription = SubscriptionData::getTestEntityData(1, $datum)->first();
            $serviceType = ServiceTypeData::getTestEntityDataOfTypes($subscription->serviceId)->first();
            $subscription->setRelated('serviceType', $serviceType);
            $subscriptions->add($subscription);
        }
        $appointments = new Collection();

        foreach ($appointmentsData as $datum) {
            $datum['date'] = Carbon::now()->subDays($datum['interval'])->format(self::DATE_FORMAT);
            /** @var AppointmentModel $appointment */
            $appointment = AppointmentData::getTestEntityData(1, $datum)->first();
            $serviceType = ServiceTypeData::getTestEntityDataOfTypes($appointment->serviceTypeId)->first();
            if (isset($datum['isInitial']) && $datum['isInitial']) {
                $daysDifference = isset($datum) ? $datum['startDateDifference'] : 0;
                $serviceType->isInitial = true;
                $appointment->start = Carbon::now()->subDays($daysDifference);
            }
            $appointment->setRelated('serviceType', $serviceType);
            $appointments->add($appointment);
        }

        $customer->setRelated('subscriptions', $subscriptions);
        $customer->setRelated('appointments', $appointments);

        $expectedSubscription = $expectedSubscriptionServiceTypeId
            ? SubscriptionData::getTestTypedSubscriptionModels($expectedSubscriptionServiceTypeId)->first()
            : null;

        $result = $this->customerDutyDeterminer->getSubscriptionCustomerIsDueFor($customer);

        self::assertEquals($expectedSubscription?->serviceId, $result?->serviceId);
    }

    public function resolverDataProvider()
    {
        yield 'Single subscription and no completed appointments' => [
            [
                [
                    'serviceID' => ServiceTypeData::QUARTERLY_SERVICE,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ]
            ],
            [],
            ServiceTypeData::QUARTERLY_SERVICE,
        ];

        yield 'Several completed appointments due and not due' => [
            [
                [
                    'serviceID' => ServiceTypeData::QUARTERLY_SERVICE,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::QUARTERLY_SERVICE,
                    'interval' => self::RESERVICE_INTERVAL_DUE + 10,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'type' => ServiceTypeData::QUARTERLY_SERVICE,
                    'interval' => self::RESERVICE_INTERVAL_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'type' => ServiceTypeData::QUARTERLY_SERVICE,
                    'interval' => self::RESERVICE_INTERVAL_DUE + 5,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
            ],
            null,
        ];

        yield 'QUARTERLY_SERVICE due' => [
            [['serviceID' => ServiceTypeData::QUARTERLY_SERVICE]],
            [
                [
                    'type' => ServiceTypeData::QUARTERLY_SERVICE,
                    'interval' => self::RESERVICE_INTERVAL_DUE,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::QUARTERLY_SERVICE,
        ];

        yield 'summer PRO due' => [
            [['serviceID' => ServiceTypeData::PRO]],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::PRO,
            self::SUMMER_DAY,
        ];

        yield 'winter PRO due' => [
            [['serviceID' => ServiceTypeData::PRO]],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::PRO,
            self::WINTER_DAY,
        ];

        yield 'summer PRO not due' => [
            [
                [
                'serviceID' => ServiceTypeData::PRO,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
            ],
            null,
            self::SUMMER_DAY,
        ];

        yield 'winter PRO not due' => [
            [
                [
                'serviceID' => ServiceTypeData::PRO,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ],
            ],
            null,
            self::WINTER_DAY,
        ];

        yield 'summer PRO_PLUS due' => [
            [['serviceID' => ServiceTypeData::PRO_PLUS]],
            [
                [
                    'type' => ServiceTypeData::PRO_PLUS,
                    'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::PRO_PLUS,
            self::SUMMER_DAY,
        ];

        yield 'winter PRO_PLUS due' => [
            [['serviceID' => ServiceTypeData::PRO_PLUS]],
            [
                [
                    'type' => ServiceTypeData::PRO_PLUS,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::PRO_PLUS,
            self::WINTER_DAY,
        ];

        yield 'summer PRO_PLUS not due' => [
            [
                [
                    'serviceID' => ServiceTypeData::PRO_PLUS,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::PRO_PLUS,
                    'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
            ],
            null,
            self::SUMMER_DAY,
        ];

        yield 'winter PRO_PLUS not due' => [
            [
                [
                    'serviceID' => ServiceTypeData::PRO_PLUS,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::PRO_PLUS,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ],
            ],
            null,
            self::WINTER_DAY,
        ];

        yield 'summer PREMIUM due' => [
            [['serviceID' => ServiceTypeData::PREMIUM]],
            [
                [
                    'type' => ServiceTypeData::PREMIUM,
                    'interval' => self::RESERVICE_INTERVAL_SUMMER_PREMIUM_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::PREMIUM,
            self::SUMMER_DAY,
        ];

        yield 'summer PREMIUM not due' => [
            [
                [
                    'serviceID' => ServiceTypeData::PREMIUM,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::PREMIUM,
                    'interval' => self::RESERVICE_INTERVAL_SUMMER_PREMIUM_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
            ],
            null,
            self::SUMMER_DAY,
        ];

        yield 'winter PREMIUM due' => [
            [['serviceID' => ServiceTypeData::PREMIUM]],
            [
                [
                    'type' => ServiceTypeData::PREMIUM,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::PREMIUM,
            self::WINTER_DAY,
        ];

        yield 'winter PREMIUM not due' => [
            [
                [
                    'serviceID' => ServiceTypeData::PREMIUM,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::PREMIUM,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ],
            ],
            null,
            self::WINTER_DAY,
        ];

        yield 'summer BASIC due' => [
            [['serviceID' => ServiceTypeData::BASIC]],
            [
                [
                    'type' => ServiceTypeData::BASIC,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::BASIC,
            self::SUMMER_DAY,
        ];

        yield 'summer BASIC not due' => [
            [
                [
                    'serviceID' => ServiceTypeData::BASIC,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::BASIC,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ],
            ],
            null,
            self::SUMMER_DAY,
        ];

        yield 'winter BASIC due' => [
            [['serviceID' => ServiceTypeData::BASIC]],
            [
                [
                    'type' => ServiceTypeData::BASIC,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::BASIC,
            self::WINTER_DAY,
        ];

        yield 'winter BASIC not due' => [
            [
                [
                    'serviceID' => ServiceTypeData::BASIC,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::BASIC,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ],
            ],
            null,
            self::WINTER_DAY,
        ];

        yield 'MOSQUITO due' => [
            [['serviceID' => ServiceTypeData::MOSQUITO]],
            [
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_DUE,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::MOSQUITO,
        ];

        yield 'MOSQUITO not due' => [
            [
                [
                    'serviceID' => ServiceTypeData::MOSQUITO,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
            ],
            null,
        ];

        yield 'The customer is due for their QUARTERLY standard treatment but not due for the MOSQUITO treatment' => [
            [
                ['serviceID' => ServiceTypeData::QUARTERLY_SERVICE],
                ['serviceID' => ServiceTypeData::MOSQUITO],
            ],
            [
                [
                    'type' => ServiceTypeData::QUARTERLY_SERVICE,
                    'interval' => self::RESERVICE_INTERVAL_DUE,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::QUARTERLY_SERVICE,
        ];

        yield 'The customer is NOT due for their QUARTERLY standard treatment but IS due for the MOSQUITO treatment' => [
            [
                [
                    'serviceID' => ServiceTypeData::QUARTERLY_SERVICE,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ],
                [
                    'serviceID' => ServiceTypeData::MOSQUITO,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID,
                ],
            ],
            [
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ],
                [
                    'type' => ServiceTypeData::QUARTERLY_SERVICE,
                    'interval' => self::RESERVICE_INTERVAL_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID,
                ],
            ],
            ServiceTypeData::MOSQUITO,
        ];

        yield 'The customer is due for their QUARTERLY standard treatment and is due for the MOSQUITO treatment' => [
            [
                ['serviceID' => ServiceTypeData::QUARTERLY_SERVICE],
                ['serviceID' => ServiceTypeData::MOSQUITO],
            ],
            [
                [
                    'type' => ServiceTypeData::QUARTERLY_SERVICE,
                    'interval' => self::RESERVICE_INTERVAL_DUE,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_DUE,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::QUARTERLY_SERVICE,
        ];

        yield 'The customer is not due for QUATERLY and MOSQUITO treatments' => [
            [
                [
                    'serviceID' => ServiceTypeData::QUARTERLY_SERVICE,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'serviceID' => ServiceTypeData::MOSQUITO,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID
                ],
            ],
            [
                [
                    'type' => ServiceTypeData::QUARTERLY_SERVICE,
                    'interval' => self::RESERVICE_INTERVAL_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID
                ],
            ],
            null,
        ];

        yield 'The customer is not due if initial appointment scheduled passed days < 20' => [
            [
                [
                    'serviceID' => ServiceTypeData::QUARTERLY_SERVICE,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::QUARTERLY_SERVICE,
                    'interval' => self::RESERVICE_INTERVAL_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'isInitial' => '1',
                    'startDateDifference' => 10,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ]
            ],
            null,
            Carbon::now()->toDateTimeString()
        ];

        yield 'The customer is due if initial appointment scheduled passed days > 20' => [
            [
                [
                    'serviceID' => ServiceTypeData::QUARTERLY_SERVICE,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ]
            ],
            [
                [
                    'type' => ServiceTypeData::QUARTERLY_SERVICE,
                    'interval' => self::RESERVICE_INTERVAL_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'isInitial' => '1',
                    'startDateDifference' => 21,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
                ]
            ],
            ServiceTypeData::QUARTERLY_SERVICE,
            Carbon::now()->toDateTimeString()
        ];

        yield 'Summer. The customer is due for their Pro standard treatment but not due for the MOSQUITO treatment' => [
            [
                ['serviceID' => ServiceTypeData::PRO],
                ['serviceID' => ServiceTypeData::MOSQUITO],
            ],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::PRO,
            self::SUMMER_DAY,
        ];

        yield 'Winter. The customer is due for their Pro standard treatment but not due for the MOSQUITO treatment' => [
            [
                ['serviceID' => ServiceTypeData::PRO],
                ['serviceID' => ServiceTypeData::MOSQUITO],
            ],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::PRO,
            self::SUMMER_DAY,
        ];

        yield 'Summer. The customer is NOT due for their Pro standard treatment but IS due for the MOSQUITO treatment' => [
            [
                [
                    'serviceID' => ServiceTypeData::PRO,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'serviceID' => ServiceTypeData::MOSQUITO,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID
                ],
            ],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID
                ],
            ],
            ServiceTypeData::MOSQUITO,
            self::SUMMER_DAY,
        ];

        yield 'Winter. The customer is NOT due for their Pro standard treatment but IS due for the MOSQUITO treatment' => [
            [
                [
                    'serviceID' => ServiceTypeData::PRO,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'serviceID' => ServiceTypeData::MOSQUITO,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID
                ],
            ],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID
                ],
            ],
            ServiceTypeData::MOSQUITO,
            self::WINTER_DAY,
        ];

        yield 'Summer. The customer is due for their Pro standard treatment and IS due for the MOSQUITO treatment' => [
            [
                ['serviceID' => ServiceTypeData::PRO],
                ['serviceID' => ServiceTypeData::MOSQUITO],
            ],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_DUE,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::PRO,
            self::SUMMER_DAY,
        ];

        yield 'Winter. The customer is due for their Pro standard treatment and IS due for the MOSQUITO treatment' => [
            [
                ['serviceID' => ServiceTypeData::PRO],
                ['serviceID' => ServiceTypeData::MOSQUITO],
            ],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE + 1,
                    'status' => AppointmentStatus::Completed->value,
                ],
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_DUE,
                    'status' => AppointmentStatus::Completed->value,
                ],
            ],
            ServiceTypeData::PRO,
            self::WINTER_DAY,
        ];

        yield 'Summer. The customer is not due for PRO and MOSQUITO treatments' => [
            [
                [
                    'serviceID' => ServiceTypeData::PRO,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'serviceID' => ServiceTypeData::MOSQUITO,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID
                ],
            ],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID
                ],
            ],
            null,
            self::SUMMER_DAY,
        ];

        yield 'Winter. The customer is not due for PRO and MOSQUITO treatments' => [
            [
                [
                    'serviceID' => ServiceTypeData::PRO,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'serviceID' => ServiceTypeData::MOSQUITO,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID
                ],
            ],
            [
                [
                    'type' => ServiceTypeData::PRO,
                    'interval' => self::RESERVICE_INTERVAL_BASIC_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
                ],
                [
                    'type' => ServiceTypeData::MOSQUITO,
                    'interval' => self::RESERVICE_INTERVAL_MOSQUITO_NOT_DUE,
                    'status' => AppointmentStatus::Completed->value,
                    'subscriptionID' => self::SECONDARY_SUBSCRIPTION_ID
                ],
            ],
            null,
            self::WINTER_DAY,
        ];

        yield 'Customer has two subscriptions and no previous appointments' => [
            [
                ['serviceID' => ServiceTypeData::PRO],
                ['serviceID' => ServiceTypeData::MOSQUITO],
            ],
            [],
            ServiceTypeData::PRO,
        ];

        yield 'Customer has two subscriptions, one inactive and no previous appointments' => [
            [
                ['serviceID' => ServiceTypeData::PRO],
                [
                    'serviceID' => ServiceTypeData::MOSQUITO,
                    'active' => '0',
                ],
            ],
            [],
            ServiceTypeData::PRO,
        ];

        yield 'Summer. Test thresholds: 24 days start day' => [
            [[
                'serviceID' => ServiceTypeData::PRO,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
            ]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::PRO,
                'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE,
                'start' => self::DAY_START,
                'status' => AppointmentStatus::Completed->value,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
            ]],
            null,
            self::SUMMER_DAY,
        ];

        yield 'Summer. Test thresholds: 24 days end day' => [
            [[
                'serviceID' => ServiceTypeData::PRO,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
            ]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::PRO,
                'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE,
                'start' => self::DAY_END,
                'status' => AppointmentStatus::Completed->value,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
            ]],
            null,
            self::SUMMER_DAY,
        ];

        yield 'Summer. Test thresholds: 25 days start day' => [
            [['serviceID' => ServiceTypeData::PRO]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::PRO,
                'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE + 1,
                'start' => self::DAY_START,
                'status' => AppointmentStatus::Completed->value,
            ]],
            ServiceTypeData::PRO,
            self::SUMMER_DAY,
        ];

        yield 'Summer. Test thresholds: 25 days end day' => [
            [['serviceID' => ServiceTypeData::PRO]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::PRO,
                'interval' => self::RESERVICE_INTERVAL_SUMMER_PRO_DUE + 1,
                'start' => self::DAY_END,
                'status' => AppointmentStatus::Completed->value,
            ]],
            ServiceTypeData::PRO,
            self::SUMMER_DAY,
        ];

        yield 'Winter. Test thresholds: 39 days start day' => [
            [[
                'serviceID' => ServiceTypeData::PRO,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
            ]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::PRO,
                'interval' => self::RESERVICE_INTERVAL_BASIC_DUE,
                'start' => self::DAY_START,
                'status' => AppointmentStatus::Completed->value,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
            ]],
            null,
            self::WINTER_DAY,
        ];

        yield 'Winter. Test thresholds: 39 days end day' => [
            [[
                'serviceID' => ServiceTypeData::PRO,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
            ]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::PRO,
                'interval' => self::RESERVICE_INTERVAL_BASIC_DUE,
                'start' => self::DAY_END,
                'status' => AppointmentStatus::Completed->value,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
            ]],
            null,
            self::WINTER_DAY,
        ];

        yield 'Winter. Test thresholds: 40 days start day' => [
            [['serviceID' => ServiceTypeData::PRO]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::PRO,
                'interval' => self::RESERVICE_INTERVAL_BASIC_DUE + 1,
                'start' => self::DAY_START,
                'status' => AppointmentStatus::Completed->value,
            ]],
            ServiceTypeData::PRO,
            self::WINTER_DAY,
        ];

        yield 'Winter. Test thresholds: 40 days end day' => [
            [['serviceID' => ServiceTypeData::PRO]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::PRO,
                'interval' => self::RESERVICE_INTERVAL_BASIC_DUE + 1,
                'start' => self::DAY_END,
                'status' => AppointmentStatus::Completed->value,
            ]],
            ServiceTypeData::PRO,
            self::WINTER_DAY,
        ];

        yield 'Test thresholds: 26 days start day' => [
            [[
                'serviceID' => ServiceTypeData::MOSQUITO,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
            ]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::MOSQUITO,
                'interval' => self::RESERVICE_INTERVAL_MOSQUITO_THRESHOLD,
                'start' => self::DAY_START,
                'status' => AppointmentStatus::Completed->value,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID,
            ]],
            null,
        ];

        yield 'Test thresholds: 26 days end day' => [
            [[
                'serviceID' => ServiceTypeData::MOSQUITO,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
            ]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::MOSQUITO,
                'interval' => self::RESERVICE_INTERVAL_MOSQUITO_THRESHOLD,
                'start' => self::DAY_END,
                'status' => AppointmentStatus::Completed->value,
                'subscriptionID' => self::PRIMARY_SUBSCRIPTION_ID
            ]],
            null,
        ];

        yield 'Test thresholds: 27 days start day' => [
            [['serviceID' => ServiceTypeData::MOSQUITO]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::MOSQUITO,
                'interval' => self::RESERVICE_INTERVAL_MOSQUITO_THRESHOLD + 1,
                'start' => self::DAY_START,
                'status' => AppointmentStatus::Completed->value,
            ]],
            ServiceTypeData::MOSQUITO,
        ];

        yield 'Test thresholds: 27 days end day' => [
            [['serviceID' => ServiceTypeData::MOSQUITO]],
            [[
                'officeTimeZone' => self::CUSTOMER_TIME_ZONE,
                'type' => ServiceTypeData::MOSQUITO,
                'interval' => self::RESERVICE_INTERVAL_MOSQUITO_THRESHOLD + 1,
                'start' => self::DAY_END,
                'status' => AppointmentStatus::Completed->value,
            ]],
            ServiceTypeData::MOSQUITO,
        ];
    }

    /**
     * @dataProvider unresolvableDataProvider
     */
    public function test_it_throws_an_exception_when_can_not_determine_subscription(array $subscriptionsData)
    {
        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData()->first();

        $subscriptions = new Collection();

        foreach ($subscriptionsData as $datum) {
            /** @var SubscriptionModel $subscription */
            $subscription = SubscriptionData::getTestEntityData(1, $datum)->first();
            $serviceType = ServiceTypeData::getTestEntityDataOfTypes($subscription->serviceId)->first();
            $subscription->setRelated('serviceType', $serviceType);
            $subscriptions->add($subscription);
        }

        $customer->setRelated('subscriptions', $subscriptions);

        $this->expectException(CanNotDetermineDueSubscription::class);

        $this->customerDutyDeterminer->getSubscriptionCustomerIsDueFor($customer);
    }

    public function unresolvableDataProvider(): array
    {
        return [
            'Customer has no subscriptions' => [[]],
            'Customer has two non mosquito subscriptions' => [[
                ['serviceID' => ServiceTypeData::PRO],
                ['serviceID' => ServiceTypeData::PRO_PLUS],
            ]],
            'Customer has two mosquito subscriptions' => [[
                ['serviceID' => ServiceTypeData::MOSQUITO],
                ['serviceID' => ServiceTypeData::MOSQUITO],
            ]],
            'Customer has three subscriptions' => [[
                ['serviceID' => ServiceTypeData::MOSQUITO],
                ['serviceID' => ServiceTypeData::PRO],
                ['serviceID' => ServiceTypeData::PRO_PLUS],
            ]],
            'Customer has four subscriptions' => [[
                ['serviceID' => ServiceTypeData::MOSQUITO],
                ['serviceID' => ServiceTypeData::PRO],
                ['serviceID' => ServiceTypeData::PRO_PLUS],
                ['serviceID' => ServiceTypeData::QUARTERLY_SERVICE],
            ]],
        ];
    }
}
