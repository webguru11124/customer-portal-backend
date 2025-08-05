<?php

namespace Tests\Unit\Actions\Appointment;

use App\Actions\Appointment\CreateAppointmentAction;
use App\DTO\Appointment\CreateAppointmentDTO;
use App\DTO\Check;
use App\Events\Appointment\AppointmentScheduled;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\EmployeeRepository;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Interfaces\Repository\SpotRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\External\SpotModel;
use App\Services\AppointmentService;
use App\Utilites\CustomerDutyDeterminer;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\Data\ServiceTypeData;
use Tests\Data\SpotData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\GenerateDate;
use Tests\Traits\MockFindCxpScheduler;
use Tests\Traits\MockFindSpot;
use Tests\Traits\RandomIntTestData;
use Throwable;

class CreateAppointmentActionTest extends TestCase
{
    use RandomIntTestData;
    use GenerateDate;
    use MockFindSpot;
    use MockFindCxpScheduler;

    private const DURATION_STANDARD = 29;
    private const DURATION_RESERVICE = 20;
    private const TEST_NOTES = 'Test notes';

    protected MockInterface|AppointmentService $appointmentServiceMock;
    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;
    protected MockInterface|ServiceTypeRepository $serviceTypeRepositoryMock;
    protected MockInterface|SpotRepository $spotRepositoryMock;
    protected MockInterface|CustomerRepository $customerRepositoryMock;
    protected MockInterface|EmployeeRepository $employeeRepositoryMock;
    protected MockInterface|CustomerDutyDeterminer $customerDutyDeterminerMock;

    protected Account $accountModel;
    protected CreateAppointmentAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->appointmentServiceMock = Mockery::mock(AppointmentService::class)->makePartial();
        $this->appointmentRepositoryMock = Mockery::mock(AppointmentRepository::class);
        $this->spotRepositoryMock = Mockery::mock(SpotRepository::class);
        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->employeeRepositoryMock = Mockery::mock(EmployeeRepository::class);

        $this->subject = new CreateAppointmentAction(
            $this->appointmentServiceMock,
            $this->appointmentRepositoryMock,
            $this->spotRepositoryMock,
            $this->customerRepositoryMock,
            $this->employeeRepositoryMock,
        );

        $this->accountModel = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
    }

    protected function getSpotRepositoryMock(): MockInterface|SpotRepository
    {
        return $this->spotRepositoryMock;
    }

    private function mockFindCustomer(CustomerModel|Throwable $expectedResult): void
    {
        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$this->accountModel->office_id])
            ->andReturn($this->customerRepositoryMock)
            ->once();

        $this->customerRepositoryMock
            ->shouldReceive('withRelated')
            ->withArgs([['subscriptions.serviceType', 'appointments.serviceType']])
            ->andReturn($this->customerRepositoryMock)
            ->once();

        $expectation = $this->customerRepositoryMock
            ->shouldReceive('find')
            ->withArgs([$this->accountModel->account_number])
            ->once();

        if ($expectedResult instanceof Throwable) {
            $expectation->andThrow($expectedResult);
        } else {
            $expectation->andReturn($expectedResult);
        }
    }

    /**
     * @dataProvider createAppointmentDataProvider
     */
    public function test_it_creates_appointment(
        int $serviceTypeId,
        int $duration,
        string|null $notes,
        int|null $employeeId
    ): void {
        $date = $this->generateFutureDate();
        $time = '08:00:00';

        $serviceType = ServiceTypeData::getTestEntityDataOfTypes($serviceTypeId)->first();
        $subscription = SubscriptionData::getTestEntityData(1)->first();
        $subscription->setRelated('serviceType', $serviceType);

        /** @var SpotModel $spot */
        $spot = SpotData::getTestEntityData(1, [
            'date' => $date,
            'start' => $time,
        ])->first();

        $this->mockFindSpot($this->getTestOfficeId(), $this->getTestSpotId(), $spot);

        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $this->getTestOfficeId(),
            'customerID' => $this->getTestAccountNumber(),
        ])->first();

        $this->mockFindCustomer($customer);

        $this->appointmentServiceMock
            ->shouldReceive('resolveNewAppointmentTypeForCustomer')
            ->withArgs([$customer, $subscription])
            ->andReturn($serviceType)
            ->once();

        $this->appointmentServiceMock
            ->shouldReceive('resolveNewAppointmentSubscriptionForCustomer')
            ->withArgs([$customer])
            ->andReturn($subscription)
            ->once();

        $this->mockFindCxpScheduler(
            $this->employeeRepositoryMock,
            $this->accountModel->office_id,
            $employeeId
        );

        $dtoArgs = fn (CreateAppointmentDTO $dto) => $dto->officeId === $this->accountModel->office_id
            && $dto->accountNumber === $this->accountModel->account_number
            && $dto->spotId === null
            && $dto->typeId === $serviceType->id
            && $dto->start->format('Y-m-d H:i:s') === "$date 08:00:00"
            && $dto->end->format('Y-m-d H:i:s') === "$date 13:00:00"
            && $dto->notes === $notes
            && $dto->duration === $duration
            && $dto->employeeId === $employeeId
            && $dto->subscriptionId === $subscription->id;

        $this->appointmentServiceMock
            ->shouldReceive('canCreateAppointment')
            ->andReturn(Check::true())
            ->once();

        $this->appointmentRepositoryMock
            ->shouldReceive('createAppointment')
            ->withArgs($dtoArgs)
            ->andReturn($this->getTestAppointmentId())
            ->once();

        Event::fake();

        $result = ($this->subject)($this->accountModel, $this->getTestSpotId(), $notes);

        Event::assertDispatched(AppointmentScheduled::class);

        self::assertSame($this->getTestAppointmentId(), $result);
    }

    /**
     * @return iterable<string, array<int, mixed>>
     */
    public function createAppointmentDataProvider(): iterable
    {
        yield 'pro service type' => [
            ServiceTypeData::PRO,
            self::DURATION_STANDARD,
            null,
            $this->getTestEmployeeId(),
        ];

        yield 'reservice service type' => [
            ServiceTypeData::RESERVICE,
            self::DURATION_RESERVICE,
            self::TEST_NOTES,
            $this->getTestEmployeeId(),
        ];

        yield 'employee not found' => [
            ServiceTypeData::RESERVICE,
            self::DURATION_RESERVICE,
            self::TEST_NOTES,
            null,
        ];
    }

    public function test_it_throws_appointment_can_not_be_created_exception(): void
    {
        $this->mockFindSpot(
            $this->accountModel->office_id,
            $this->getTestSpotId(),
            SpotData::getTestEntityData()->first()
        );

        $this->mockFindCustomer(CustomerData::getTestEntityData()->first());

        $this->appointmentServiceMock
            ->shouldReceive('resolveNewAppointmentTypeForCustomer')
            ->andReturn(ServiceTypeData::getTestEntityDataOfTypes(ServiceTypeData::PRO)->first());

        $this->mockAppointmentServiceToReturnSubscription();

        $this->appointmentServiceMock
            ->shouldReceive('canCreateAppointment')
            ->andReturn(Check::false('reason'));

        $this->mockFindCxpScheduler(
            $this->employeeRepositoryMock,
            $this->getTestOfficeId(),
            $this->getTestEmployeeId()
        );

        $this->appointmentRepositoryMock
            ->shouldReceive('createAppointment')
            ->never();
        Event::fake();

        $this->expectException(AppointmentCanNotBeCreatedException::class);

        ($this->subject)($this->accountModel, $this->getTestSpotId());
        Event::assertNotDispatched(AppointmentScheduled::class);
    }

    /**
     * @dataProvider emptyNotesDataProvider
     */
    public function test_it_throws_validation_exception_if_try_to_create_reservice_without_notes(string|null $notes): void
    {
        $this->mockFindSpot(
            $this->accountModel->office_id,
            $this->getTestSpotId(),
            SpotData::getTestEntityData()->first()
        );

        $this->mockFindCustomer(CustomerData::getTestEntityData()->first());

        $this->appointmentServiceMock
            ->shouldReceive('resolveNewAppointmentTypeForCustomer')
            ->andReturn(ServiceTypeData::getTestEntityDataOfTypes(ServiceTypeData::RESERVICE)->first());

        $this->mockAppointmentServiceToReturnSubscription();

        $this->mockFindCxpScheduler(
            $this->employeeRepositoryMock,
            $this->getTestOfficeId(),
            $this->getTestEmployeeId()
        );

        $this->appointmentRepositoryMock
            ->shouldReceive('createAppointment')
            ->never();
        Event::fake();

        $this->expectException(ValidationException::class);

        ($this->subject)($this->accountModel, $this->getTestSpotId(), $notes);
        Event::assertNotDispatched(AppointmentScheduled::class);
    }

    /**
     * @return iterable<int, string|null>
     */
    public function emptyNotesDataProvider(): iterable
    {
        yield [''];
        yield [null];
    }

    /**
     * @dataProvider spotRepoExceptionsDataProvider
     */
    public function test_it_passes_spot_repository_exceptions(string $exceptionClass): void
    {
        $this->mockFindSpot(
            $this->accountModel->office_id,
            $this->getTestSpotId(),
            new $exceptionClass()
        );

        $this->expectException($exceptionClass);
        Event::fake();

        ($this->subject)($this->accountModel, $this->getTestSpotId());
        Event::assertNotDispatched(AppointmentScheduled::class);
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function spotRepoExceptionsDataProvider(): iterable
    {
        yield [InternalServerErrorHttpException::class];
        yield [EntityNotFoundException::class];
    }

    /**
     * @dataProvider spotRepoExceptionsDataProvider
     */
    public function test_it_passes_customer_repository_exceptions(string $exceptionClass): void
    {
        $this->mockFindSpot(
            $this->accountModel->office_id,
            $this->getTestSpotId(),
            SpotData::getTestEntityData()->first()
        );

        $this->mockFindCustomer(new $exceptionClass());
        Event::fake();

        $this->expectException($exceptionClass);

        ($this->subject)($this->accountModel, $this->getTestSpotId());
        Event::assertNotDispatched(AppointmentScheduled::class);
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function customerRepoExceptionsDataProvider(): iterable
    {
        yield [InternalServerErrorHttpException::class];
        yield [AccountFrozenException::class];
    }

    /**
     * @dataProvider appointmentServiceExceptionsDataProvider
     */
    public function test_it_passes_appointment_service_exceptions(string $exceptionClass): void
    {
        $this->mockFindSpot(
            $this->accountModel->office_id,
            $this->getTestSpotId(),
            SpotData::getTestEntityData()->first()
        );

        $this->mockFindCustomer(CustomerData::getTestEntityData()->first());

        $this->appointmentServiceMock
            ->shouldReceive('resolveNewAppointmentSubscriptionForCustomer')
            ->andThrow(new $exceptionClass());
        Event::fake();

        $this->expectException($exceptionClass);

        ($this->subject)($this->accountModel, $this->getTestSpotId());
        Event::assertNotDispatched(AppointmentScheduled::class);
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function appointmentServiceExceptionsDataProvider(): iterable
    {
        yield [InternalServerErrorHttpException::class];
        yield [EntityNotFoundException::class];
        yield [AppointmentCanNotBeCreatedException::class];
    }

    /**
     * @dataProvider appointmentRepoExceptionsDataProvider
     */
    public function test_it_passes_appointment_repository_exceptions(string $exceptionClass): void
    {
        $this->mockFindSpot(
            $this->accountModel->office_id,
            $this->getTestSpotId(),
            SpotData::getTestEntityData()->first()
        );

        $this->mockFindCustomer(CustomerData::getTestEntityData()->first());

        $this->appointmentServiceMock
            ->shouldReceive('resolveNewAppointmentTypeForCustomer')
            ->andReturn(ServiceTypeData::getTestEntityDataOfTypes(ServiceTypeData::PRO)->first());

        $this->appointmentServiceMock
            ->shouldReceive('canCreateAppointment')
            ->andReturn(Check::true());

        $this->mockAppointmentServiceToReturnSubscription();

        $this->mockFindCxpScheduler(
            $this->employeeRepositoryMock,
            $this->getTestOfficeId(),
            $this->getTestEmployeeId()
        );

        $this->appointmentRepositoryMock
            ->shouldReceive('createAppointment')
            ->andThrow(new $exceptionClass());
        Event::fake();

        $this->expectException($exceptionClass);

        ($this->subject)($this->accountModel, $this->getTestSpotId(), self::TEST_NOTES);
        Event::assertNotDispatched(AppointmentScheduled::class);
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function appointmentRepoExceptionsDataProvider(): iterable
    {
        yield [InternalServerErrorHttpException::class];
    }

    protected function mockAppointmentServiceToReturnSubscription(): void
    {
        $subscription = SubscriptionData::getTestEntityData(1)->first();
        $this->appointmentServiceMock
            ->shouldReceive('resolveNewAppointmentSubscriptionForCustomer')
            ->andReturn($subscription)
            ->once();
    }
}
