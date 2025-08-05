<?php

namespace Tests\Unit\Services;

use App\DTO\Appointment\CreateAppointmentDTO;
use App\DTO\Appointment\SearchAppointmentsDTO;
use App\DTO\Appointment\UpdateAppointmentDTO;
use App\DTO\Check;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Appointment\AppointmentSubscriptionCanNotBeReassigned;
use App\Exceptions\Subscription\CanNotDetermineDueSubscription;
use App\Helpers\DateTimeHelper;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\RouteRepository;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Interfaces\Repository\SpotRepository;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Models\External\CustomerModel;
use App\Models\External\RouteModel;
use App\Models\External\ServiceTypeModel;
use App\Models\External\SpotModel;
use App\Models\External\SubscriptionModel;
use App\Services\AppointmentService;
use App\Utilites\CustomerDutyDeterminer;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\AppointmentData;
use Tests\Data\CustomerData;
use Tests\Data\RouteData;
use Tests\Data\ServiceTypeData;
use Tests\Data\SpotData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\DTOTestData;
use Tests\Traits\MockFindSpot;
use Tests\Traits\PestroutesSdkExceptionProvider;

class AppointmentServiceTest extends TestCase
{
    use PestroutesSdkExceptionProvider;
    use DTOTestData;
    use MockFindSpot;

    private const RESERVICE_INTERVAL = 77;
    private const TIME_FORMAT = 'Y-m-d H:i:s';
    private const NOTES = 'Notes';
    private const DURATION = 29;

    protected AppointmentService $appointmentService;

    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;
    protected MockInterface|ServiceTypeRepository $serviceTypeRepositoryMock;
    protected MockInterface|SpotRepository $spotRepositoryMock;
    protected MockInterface|SubscriptionRepository $subscriptionRepositoryMock;
    protected MockInterface|CustomerDutyDeterminer $customerDutyDeterminerMock;
    protected MockInterface|RouteRepository $routeRepositoryMock;

    protected UpdateAppointmentDTO $updateAppointmentDTO;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->appointmentRepositoryMock = Mockery::mock(AppointmentRepository::class);
        $this->serviceTypeRepositoryMock = Mockery::mock(ServiceTypeRepository::class);
        $this->spotRepositoryMock = Mockery::mock(SpotRepository::class);
        $this->subscriptionRepositoryMock = Mockery::mock(SubscriptionRepository::class);
        $this->customerDutyDeterminerMock = Mockery::mock(CustomerDutyDeterminer::class);
        $this->routeRepositoryMock = Mockery::mock(RouteRepository::class);

        $this->appointmentService = new AppointmentService(
            $this->appointmentRepositoryMock,
            $this->serviceTypeRepositoryMock,
            $this->spotRepositoryMock,
            $this->subscriptionRepositoryMock,
            $this->customerDutyDeterminerMock,
            $this->routeRepositoryMock
        );
    }

    protected function getSpotRepositoryMock(): MockInterface|SpotRepository
    {
        return $this->spotRepositoryMock;
    }

    public function emptyNotesDataProvider(): iterable
    {
        yield 'empty notes' => [''];
        yield 'null notes' => [null];
    }

    public function notesDataProvider(): iterable
    {
        yield 'notes' => [self::NOTES];
    }

    private function getAccount(): Account
    {
        return new Account([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
    }

    /**
     * @dataProvider rescheduleCancelDataProvider
     */
    public function test_can_reschedule_and_cancel_appointment(
        array $appointmentData,
        Check $canCancel,
        Check $canReschedule
    ) {
        /** @var AppointmentModel $appointment */
        $appointment = AppointmentData::getTestEntityData(1, $appointmentData)->first();
        $serviceType = ServiceTypeData::getTestEntityDataOfTypes($appointment->serviceTypeId)->first();
        $appointment->setRelated('serviceType', $serviceType);

        $this->serviceTypeRepositoryMock
            ->shouldReceive('getServiceType')
            ->withArgs([$appointment->officeId, $appointment->serviceTypeId])
            ->andReturn(ServiceTypeData::getTestDataOfTypes($appointment->serviceTypeId)->first());

        self::assertEquals($canReschedule, $this->appointmentService->canRescheduleAppointment($appointment));
        self::assertEquals($canCancel, $this->appointmentService->canCancelAppointment($appointment));
    }

    private function getReserviceTypeId()
    {
        return ServiceTypeData::RESERVICE;
    }

    private function getNonReserviceTypeId()
    {
        $nonReserviceTypes = [
            ServiceTypeData::MOSQUITO,
            ServiceTypeData::QUARTERLY_SERVICE,
            ServiceTypeData::PREMIUM,
            ServiceTypeData::PRO,
            ServiceTypeData::PRO_PLUS,
        ];

        return $nonReserviceTypes[array_rand($nonReserviceTypes)];
    }

    public function rescheduleCancelDataProvider(): array
    {
        $yesterday = DateTimeHelper::dayBefore(1, 'Y-m-d');
        $tomorrow = DateTimeHelper::dayAfter(1, 'Y-m-d');

        $canCancelKey = 'can cancel';
        $canRescheduleKey = 'can reschedule';

        return [
            'Outdated Reservice' => [
                [
                    'date' => $yesterday,
                    'type' => $this->getReserviceTypeId(),
                ],
                $canCancelKey => Check::false('The appointment is expired.'),
                $canRescheduleKey => Check::false('The appointment is expired.'),
            ],
            'Outdated Non Reservice' => [
                [
                    'date' => $yesterday,
                    'type' => $this->getNonReserviceTypeId(),
                ],
                $canCancelKey => Check::false('The appointment is expired.'),
                $canRescheduleKey => Check::false('The appointment is expired.'),
            ],
            'Upcoming Reservice' => [
                [
                    'date' => $tomorrow,
                    'type' => $this->getReserviceTypeId(),
                ],
                $canCancelKey => Check::true(),
                $canRescheduleKey => Check::true(),
            ],
            'Upcoming Non Reservice' => [
                [
                    'date' => $tomorrow,
                    'type' => $this->getNonReserviceTypeId(),
                ],
                $canCancelKey => Check::false('Only Reservice appointment can be cancelled.'),
                $canRescheduleKey => Check::true(),
            ],
        ];
    }

    /**
     * @dataProvider assignSpotDataProvider
     */
    public function test_can_assign_spot(array $spotData, array $routeData, Check $canAssign)
    {
        /** @var SpotModel $spot */
        $spot = SpotData::getTestEntityData(1, $spotData)->first();

        $this->routeRepositoryMock
            ->shouldReceive('office')
            ->with($spot->officeId)
            ->once()
            ->andReturnSelf();

        $route = RouteData::getTestEntityData(1, $routeData)->first();

        $this->routeRepositoryMock
            ->shouldReceive('find')
            ->with($spot->routeId)
            ->once()
            ->andReturn($route);

        self::assertEquals($canAssign, $this->appointmentService->canAssignSpotToAppointment($spot));
    }

    /**
     * @return iterable<string, array<int, mixed>>
     */
    public function assignSpotDataProvider(): iterable
    {
        yield 'Upcoming' => [
            ['date' => DateTimeHelper::dayAfter(1, 'Y-m-d')],
            [
                'groupTitle' => 'Regular Routes',
                'groupID' => 0,
                'title' => 'Regular Routes',
            ],
            Check::true(),
        ];

        yield 'Initila route' => [
            ['date' => DateTimeHelper::dayAfter(1, 'Y-m-d')],
            [
                'groupTitle' => 'Initial Routes',
                'groupID' => 0,
                'title' => 'Initial Routes',
            ],
            Check::false('Scheduling in initial routes is forbidden.'),
        ];

        yield 'Outdated' => [
            ['date' => DateTimeHelper::dayBefore(1, 'Y-m-d')],
            [
                'groupTitle' => 'Regular Routes',
                'groupID' => 0,
                'title' => 'Regular Routes',
            ],
            Check::false('The spot is expired.'),
        ];
    }

    /**
     * @dataProvider resolveTypeDataProvider
     */
    public function test_it_resolves_new_appointment_type_for_customer(
        int|null $subscriptionServiceType,
        int $resolvedServiceTypeId
    ): void {
        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData()->first();

        $subscription = null;

        if ($subscriptionServiceType !== null) {
            /** @var SubscriptionModel $subscription */
            $subscription = SubscriptionData::getTestTypedSubscriptionModels($subscriptionServiceType)->first();
            $subscriptionServiceType = ServiceTypeData::getTestEntityDataOfTypes($subscriptionServiceType)->first();
            $subscription?->setRelated('serviceType', $subscriptionServiceType);
        }

        /** @var ServiceTypeModel $resolvedServiceType */
        $resolvedServiceType = ServiceTypeData::getTestEntityDataOfTypes($resolvedServiceTypeId)->first();

        $this->serviceTypeRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$customer->officeId])
            ->andReturn($this->serviceTypeRepositoryMock);

        $this->serviceTypeRepositoryMock
            ->shouldReceive('find')
            ->withArgs([$resolvedServiceType->id])
            ->andReturn($resolvedServiceType);

        $this->customerDutyDeterminerMock
            ->shouldReceive('getSubscriptionCustomerIsDueFor')
            ->withArgs([$customer])
            ->andReturn($subscription)
            ->once();

        $result = $this->appointmentService->resolveNewAppointmentTypeForCustomer($customer);

        self::assertEquals($resolvedServiceType, $result);
    }

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function resolveTypeDataProvider(): iterable
    {
        yield [
            ServiceTypeData::PRO,
            ServiceTypeData::PRO,
        ];
        yield [
            ServiceTypeData::PRO,
            ServiceTypeData::PRO,
        ];
        yield [
            ServiceTypeData::QUARTERLY_SERVICE,
            ServiceTypeData::QUARTERLY_SERVICE,
        ];
        yield [
            null,
            ServiceTypeData::RESERVICE,
        ];
    }

    public function test_resolve_type_throws_appointment_can_not_be_created_exception(): void
    {
        $massage = 'Test message';

        $this->customerDutyDeterminerMock
            ->shouldReceive('getSubscriptionCustomerIsDueFor')
            ->andThrow(new CanNotDetermineDueSubscription($massage));

        $this->expectException(AppointmentCanNotBeCreatedException::class);

        Log::shouldReceive('info')
            ->with($massage);

        $this->appointmentService->resolveNewAppointmentTypeForCustomer(CustomerData::getTestEntityData()->first());
    }

    /**
     * @dataProvider determineIfCanBeCreatedDataProvider
     */
    public function test_it_determines_if_appointment_can_be_created(
        array $spotData,
        array $routeData,
        int $upcomingAppointmentsQuantity,
        int $subscriptionsQuantity,
        DateTimeInterface $startTime,
        Check $expectedResult
    ): void {
        /** @var SpotModel $spot */
        $spot = SpotData::getTestEntityData(1, $spotData)->first();
        $upcomingAppointmentsCollection = $upcomingAppointmentsQuantity
            ? AppointmentData::getTestEntityData($upcomingAppointmentsQuantity)
            : new Collection();

        $subscriptionsCollection = $subscriptionsQuantity
            ? SubscriptionData::getTestData($subscriptionsQuantity)
            : new Collection();

        $dto = CreateAppointmentDTO::from([
            'officeId' => $spot->officeId,
            'accountNumber' => $this->getTestAccountNumber(),
            'typeId' => $this->getTestServiceTypeId(),
            'spotId' => $spot->id,
            'routeId' => $spot->routeId,
            'start' => $startTime,
            'end' => Carbon::instance($spot->start)->setTime(20, 0),
            'duration' => self::DURATION,
            'notes' => self::NOTES,
        ]);

        $this->appointmentRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$dto->officeId])
            ->andReturn($this->appointmentRepositoryMock);

        $this->appointmentRepositoryMock
            ->shouldReceive('getUpcomingAppointments')
            ->withArgs([$dto->accountNumber])
            ->andReturn($upcomingAppointmentsCollection);

        $this->subscriptionRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$dto->officeId])
            ->andReturnSelf();

        $this->subscriptionRepositoryMock
            ->shouldReceive('searchByCustomerId')
            ->withArgs([[$dto->accountNumber]])
            ->andReturn($subscriptionsCollection);

        $this->routeRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();

        /** @var RouteModel $route */
        $route = RouteData::getTestEntityData(1, $routeData)->first();

        if (!$route->isInitial()) {
            $this->mockFindSpot($dto->officeId, $dto->spotId, $spot);
        }

        $this->routeRepositoryMock
            ->shouldReceive('find')
            ->with($dto->routeId)
            ->andReturn($route);

        $result = $this->appointmentService->canCreateAppointment($dto);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return iterable<string, array<int, mixed>>
     */
    public function determineIfCanBeCreatedDataProvider(): iterable
    {
        $initialRouteData = [
            'groupTitle' => 'Initial Routes',
            'groupID' => 0,
            'title' => 'Initial Routes',
        ];

        $regularRouteData = [
            'groupTitle' => 'Regular Routes',
            'groupID' => 0,
            'title' => 'Regular Routes',
        ];

        yield 'valid data' => [
            [],
            $regularRouteData,
            0,
            1,
            Carbon::now()->addDay()->setTime(13, 0),
            Check::true(),
        ];
        yield 'outdated spot' => [
            ['date' => Carbon::now()->subDays(random_int(2, 10))->format(SpotData::DATE_FORMAT)],
            $regularRouteData,
            0,
            1,
            Carbon::now()->addDay()->setTime(13, 0),
            Check::false('The spot is expired.'),
        ];
        yield '1 appointment 2 subscriptions' => [
            [],
            $regularRouteData,
            1,
            2,
            Carbon::now()->addDay()->setTime(13, 0),
            Check::true(),
        ];
        yield '1 appointment 1 subscription' => [
            [],
            $regularRouteData,
            1,
            1,
            Carbon::now()->addDay()->setTime(13, 0),
            Check::false('The customer already has an upcoming appointment scheduled.'),
        ];
        yield '2 appointments 1 subscription' => [
            [],
            $regularRouteData,
            2,
            1,
            Carbon::now()->addDay()->setTime(13, 0),
            Check::false('The customer already has an upcoming appointment scheduled.'),
        ];
        yield 'outdated start time' => [
            [],
            $regularRouteData,
            0,
            1,
            Carbon::now()->subDay()->setTime(13, 0),
            Check::false('Date is expired.'),
        ];
        yield 'Initial route' => [
            [],
            $initialRouteData,
            0,
            1,
            Carbon::now()->addDay()->setTime(13, 0),
            Check::false('Scheduling in initial routes is forbidden.'),
        ];
    }


    public function test_resolve_new_appointment_subscription_for_customer_returns_valid_subscription(): void
    {
        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData()->first();
        /** @var SubscriptionModel $subscription */
        $subscription = SubscriptionData::getTestEntityData(1)->first();

        $this->customerDutyDeterminerMock
            ->shouldReceive('getSubscriptionCustomerIsDueFor')
            ->withArgs([$customer])
            ->andReturn($subscription)
            ->once();

        self::assertEquals(
            $subscription,
            $this->appointmentService->resolveNewAppointmentSubscriptionForCustomer($customer)
        );
    }

    public function test_resolve_new_appointment_subscription_for_customer_returns_null(): void
    {
        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData()->first();

        $this->customerDutyDeterminerMock
            ->shouldReceive('getSubscriptionCustomerIsDueFor')
            ->withArgs([$customer])
            ->andReturnNull()
            ->once();

        self::assertNull($this->appointmentService->resolveNewAppointmentSubscriptionForCustomer($customer));
    }

    public function test_resolve_new_appointment_subscription_for_customer_throws_appointment_can_not_be_created_exception(): void
    {
        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData()->first();
        $massage = 'Error Message';

        $this->customerDutyDeterminerMock
            ->shouldReceive('getSubscriptionCustomerIsDueFor')
            ->andThrow(new CanNotDetermineDueSubscription($massage));

        $this->expectException(AppointmentCanNotBeCreatedException::class);
        Log::shouldReceive('info')
            ->with($massage);

        $this->appointmentService->resolveNewAppointmentSubscriptionForCustomer($customer);
    }

    public function test_it_reassign_subscription_to_appointment(): void
    {
        $subscriptions = SubscriptionData::getTestEntityData(
            2,
            [
                'subscriptionID' => $this->getTestSubscriptionId(),
            ],
            [
                'subscriptionID' => $this->getTestSubscriptionId() + 1,
            ]
        );

        $newSubscription = $subscriptions->first();
        $oldSubscription = $subscriptions->last();
        $appointments = AppointmentData::getTestEntityData(1);

        $this->setupOfficeToReturnAppointmentRepository($oldSubscription->officeId);
        $this->setupSearchAppointmentToReturnValidAppointments($appointments, $oldSubscription);
        $this->setupOfficeToReturnAppointmentRepository($newSubscription->officeId);

        $this->appointmentRepositoryMock
            ->shouldReceive('updateAppointment')
            ->once()
            ->withArgs(
                fn (UpdateAppointmentDTO $updateAppointmentDTO) =>
                    $updateAppointmentDTO->officeId === $newSubscription->officeId &&
                    $updateAppointmentDTO->appointmentId === $appointments->first()->id &&
                    $updateAppointmentDTO->subscriptionId === $newSubscription->id &&
                    $updateAppointmentDTO->typeId === $newSubscription->serviceId
            );

        $this->appointmentService->reassignSubscriptionToAppointment($newSubscription, $oldSubscription);
    }

    public function test_it_skip_processing_subscription_to_appointment_reassign_on_empty_appointments(): void
    {
        $subscriptions = SubscriptionData::getTestEntityData(
            2,
            [
                'subscriptionID' => $this->getTestSubscriptionId(),
            ],
            [
                'subscriptionID' => $this->getTestSubscriptionId() + 1,
            ]
        );

        $newSubscription = $subscriptions->first();
        $oldSubscription = $subscriptions->last();
        $appointments = AppointmentData::getTestEntityData(0);

        $this->setupOfficeToReturnAppointmentRepository($oldSubscription->officeId);
        $this->setupSearchAppointmentToReturnValidAppointments($appointments, $oldSubscription);

        $this->appointmentRepositoryMock
            ->shouldReceive('updateAppointment')
            ->once()
            ->withAnyArgs()
            ->never();

        $this->appointmentService->reassignSubscriptionToAppointment($newSubscription, $oldSubscription);
    }

    public function test_reassign_subscription_to_appointment_thrown_an_exception(): void
    {
        $subscriptions = SubscriptionData::getTestEntityData(
            2,
            [
                'subscriptionID' => $this->getTestSubscriptionId(),
            ],
            [
                'subscriptionID' => $this->getTestSubscriptionId() + 1,
            ]
        );

        $newSubscription = $subscriptions->first();
        $oldSubscription = $subscriptions->last();
        $appointments = AppointmentData::getTestEntityData(1);

        $this->setupOfficeToReturnAppointmentRepository($oldSubscription->officeId);
        $this->setupSearchAppointmentToReturnValidAppointments($appointments, $oldSubscription);

        $this->appointmentRepositoryMock
            ->shouldReceive('updateAppointment')
            ->withArgs(
                fn (
                    UpdateAppointmentDTO $updateAppointmentDTO
                ) => $updateAppointmentDTO->officeId === $newSubscription->officeId &&
                    $updateAppointmentDTO->appointmentId === $appointments->first()->id &&
                    $updateAppointmentDTO->subscriptionId === $newSubscription->id &&
                    $updateAppointmentDTO->typeId === $newSubscription->serviceId
            )
            ->andThrow(new AppointmentSubscriptionCanNotBeReassigned(sprintf(
                'Cannot assign new subscription %d to appointment %s.',
                $appointments->first()->id,
                $newSubscription->id
            )));

        $this->expectException(AppointmentSubscriptionCanNotBeReassigned::class);

        $this->appointmentService->reassignSubscriptionToAppointment($newSubscription, $oldSubscription);
    }

    protected function setupSearchAppointmentToReturnValidAppointments(
        Collection $appointments,
        SubscriptionModel $subscription
    ): void {
        $this->appointmentRepositoryMock
            ->shouldReceive('search')
            ->once()
            ->withArgs(
                fn (SearchAppointmentsDTO $searchAppointmentsDTO) =>
                    $searchAppointmentsDTO->officeId === $subscription->officeId &&
                    $searchAppointmentsDTO->accountNumber === [$subscription->customerId] &&
                    $searchAppointmentsDTO->status === [AppointmentStatus::Pending] &&
                    $searchAppointmentsDTO->subscriptionIds === [$subscription->id]
            )
            ->andReturn($appointments);
    }

    protected function setupOfficeToReturnAppointmentRepository(int $officeId): void
    {
        $this->appointmentRepositoryMock
            ->shouldReceive('office')
            ->once()
            ->withArgs([$officeId])
            ->andReturn($this->appointmentRepositoryMock);
    }
}
