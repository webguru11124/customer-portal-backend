<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Appointment;

use App\Actions\Appointment\CreateAppointmentInFlexIVRAction;
use App\DTO\Appointment\SearchAppointmentsDTO;
use App\DTO\Check;
use App\DTO\FlexIVR\Appointment\CreateAppointment;
use App\Enums\FlexIVR\AppointmentType;
use App\Enums\FlexIVR\Window;
use App\Events\Appointment\AppointmentScheduled;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Appointment\AppointmentSpotAlreadyUsedException;
use App\Exceptions\Appointment\CannotCreateAppointmentException;
use App\Exceptions\Appointment\CannotResolveAppointmentSubscriptionException;
use App\Interfaces\FlexIVRApi\AppointmentRepository as FlexIVRApiAppointmentRepository;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Models\External\CustomerModel;
use App\Models\External\ServiceTypeModel;
use App\Models\External\SubscriptionModel;
use App\Services\AccountService;
use App\Services\AppointmentService;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Data\AppointmentData;
use Tests\Data\CustomerData;
use Tests\Data\ServiceTypeData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class CreateAppointmentInFlexIVRActionTest extends TestCase
{
    use RandomIntTestData;

    private const TEST_NOTES = 'Test notes';

    /**
     * @dataProvider provideAppointmentData
     */
    public function test_it_creates_appointment(
        Window $window,
        bool $isAroSpot,
        string|null $notes,
        int $serviceTypeId,
        int $subscriptionId,
        AppointmentType $appointmentType,
    ): void {
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);

        $serviceType = ServiceTypeData::getTestEntityDataOfTypes($serviceTypeId)->first();

        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $subscriptionId,
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $subscription->setRelated('serviceType', $serviceType);

        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $customer->setRelated('appointments', new Collection());
        $customer->setRelated('paymentProfiles', new Collection());
        $customer->setRelated('subscriptions', new Collection([$subscription]));

        $appointment = AppointmentData::getTestEntityData(1, ['appointmentID' => $this->getTestAppointmentId()])->first();
        $appointmentsCollection = AppointmentData::getTestData();
        if($subscriptionId == AppointmentModel::RESERVICE_SUBSCRIPTION_ID) {
            $appointment->status = AppointmentStatus::Completed;
            $appointment->start = now()->subDays(10);
            $appointment->setRelated('serviceType', $serviceType);
            $appointment->serviceType->isInitial = true;
        }

        $appointmentServiceMock = $this->getAppointmentServiceMock($customer, $subscription, $subscription->serviceType);
        $appointmentServiceMock
            ->expects($this->once())
            ->method('hasSubscriptionWithoutPendingAppointment')
            ->withAnyParameters()
            ->willReturn(Check::true());

        $action = new CreateAppointmentInFlexIVRAction(
            $this->getAccountServiceMock($account),
            $appointmentServiceMock,
            $this->getCustomerRepositoryMock($customer),
            $this->getAppointmentRepositoryMock($appointment, Collection::make($appointmentsCollection)),
            $this->getFlexIVRApiAppointmentRepositoryMock(
                $appointmentType,
                $subscriptionId,
                $window,
                $isAroSpot,
                $notes,
            ),
        );
        Config::expects('get')->with('aptive.mosquito_service_types')->never();
        Config::expects('get')->with('aptive.default_date_format', null)->andReturn('Y-m-d');

        Event::fake();

        $this->assertSame($appointment, $action(
            $this->getTestAccountNumber(),
            $this->getTestSpotId(),
            $window,
            $isAroSpot,
            $notes,
        ));
        Event::assertDispatched(AppointmentScheduled::class);
    }

    public function test_it_throws_cannot_create_appointment_exception(): void
    {
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);

        $serviceTypeId = ServiceTypeData::PRO;
        $subscriptionId = 2799702;

        $serviceType = ServiceTypeData::getTestEntityDataOfTypes($serviceTypeId)->first();

        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $subscriptionId,
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $subscription->setRelated('serviceType', $serviceType);

        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $customer->setRelated('appointments', new Collection());
        $customer->setRelated('paymentProfiles', new Collection());
        $customer->setRelated('subscriptions', new Collection([$subscription]));

        $appointment = AppointmentData::getTestEntityData(1, ['appointmentID' => $this->getTestAppointmentId()])->first();
        $appointmentsCollection = AppointmentData::getTestData();
        if($subscription->serviceType->description == ServiceTypeData::RESERVICE) {
            $appointment->status = AppointmentStatus::Completed;
            $appointment->start = now()->subDays(10);
        }

        $appointmentServiceMock = $this->getAppointmentServiceMock($customer, $subscription, $subscription->serviceType);
        $appointmentServiceMock
            ->expects($this->once())
            ->method('hasSubscriptionWithoutPendingAppointment')
            ->withAnyParameters()
            ->willReturn(Check::false("Cannot create appointment"));
        $flexAppointmentRepositoryMock = $this->createMock(FlexIVRApiAppointmentRepository::class);
        $flexAppointmentRepositoryMock
            ->expects($this->never())
            ->method('createAppointment');

        $action = new CreateAppointmentInFlexIVRAction(
            $this->getAccountServiceMock($account),
            $appointmentServiceMock,
            $this->getCustomerRepositoryMock($customer),
            $this->getAppointmentRepositoryMockForAppointmentCannotBeCreatedException($appointment, Collection::make($appointmentsCollection)),
            $flexAppointmentRepositoryMock
        );

        $this->expectException(CannotCreateAppointmentException::class);
        $action(
            $this->getTestAccountNumber(),
            $this->getTestSpotId(),
            Window::AM,
            true
        );
    }

    /**
     * @dataProvider provideAppointmentData
     */
    public function test_it_creates_appointment_from_customer_subscription(
        Window $window,
        bool $isAroSpot,
        string|null $notes,
        int $serviceTypeId,
        int $subscriptionId,
        AppointmentType $appointmentType,
    ): void {
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);

        $serviceType = ServiceTypeData::getTestEntityDataOfTypes($serviceTypeId)->first();

        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $subscriptionId,
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $subscription->setRelated('serviceType', $serviceType);

        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $customer->setRelated('appointments', new Collection());
        $customer->setRelated('paymentProfiles', new Collection());
        $customer->setRelated('subscriptions', new Collection([$subscription]));

        $appointment = AppointmentData::getTestEntityData(1, ['appointmentID' => $this->getTestAppointmentId()])->first();
        $appointmentsCollection = AppointmentData::getTestData();
        $appointmentServiceMock = $this->getAppointmentServiceMock($customer, $subscription, $subscription->serviceType);
        $appointmentServiceMock
            ->expects($this->once())
            ->method('hasSubscriptionWithoutPendingAppointment')
            ->withAnyParameters()
            ->willReturn(Check::true());

        $action = new CreateAppointmentInFlexIVRAction(
            $this->getAccountServiceMock($account),
            $appointmentServiceMock,
            $this->getCustomerRepositoryMock($customer),
            $this->getAppointmentRepositoryMock($appointment, Collection::make($appointmentsCollection)),
            $this->getFlexIVRApiAppointmentRepositoryMock(
                $appointmentType,
                $subscriptionId,
                $window,
                $isAroSpot,
                $notes,
            ),
        );

        Config::expects('get')->with('aptive.mosquito_service_types')->never();
        Config::expects('get')->with('aptive.default_date_format', null)->andReturn('Y-m-d');

        Event::fake();

        $this->assertSame($appointment, $action(
            $this->getTestAccountNumber(),
            $this->getTestSpotId(),
            $window,
            $isAroSpot,
            $notes,
        ));
        Event::assertDispatched(AppointmentScheduled::class);
    }

    public function test_it_throws_exception_if_no_appointments(): void
    {
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);

        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $serviceType = ServiceTypeData::getTestEntityDataOfTypes(ServiceTypeData::MOSQUITO)->first();
        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId(),
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $subscription->setRelated('serviceType', $serviceType);

        $customer->setRelated('appointments', new Collection());
        $customer->setRelated('paymentProfiles', new Collection());
        $customer->setRelated('subscriptions', new Collection());

        $accountServiceMock = $this->createMock(AccountService::class);
        $accountServiceMock->method('getAccountByAccountNumber')->willReturn($account);

        $appointmentRepositoryMock = $this->createMock(AppointmentRepository::class);
        $appointmentRepositoryMock->method('search')->willReturn(new Collection());

        $flexAppointmentRepositoryMock = $this->createMock(FlexIVRApiAppointmentRepository::class);
        $flexAppointmentRepositoryMock
            ->expects($this->never())
            ->method('createAppointment');

        $action = new CreateAppointmentInFlexIVRAction(
            $accountServiceMock,
            $this->getAppointmentServiceMockForNoAppointmentsTest($customer, null, $subscription->serviceType),
            $this->getCustomerRepositoryMockForNoAppointmentsTest($customer),
            $appointmentRepositoryMock,
            $flexAppointmentRepositoryMock,
        );

        $this->expectException(CannotCreateAppointmentException::class);
        $action(
            $this->getTestAccountNumber(),
            $this->getTestSpotId(),
            Window::AM,
            true
        );
    }
    /**
     * @dataProvider provideAppointmentData
     */
    public function test_it_throws_exception_when_service_type_is_not_resolved(
        Window $window,
        bool $isAroSpot,
        string|null $notes,
    ): void {
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
        $serviceType = ServiceTypeData::getTestEntityDataOfTypes(ServiceTypeData::MOSQUITO)->first();
        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId(),
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $subscription->setRelated('serviceType', $serviceType);
        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $customer->setRelated('appointments', new Collection());
        $customer->setRelated('paymentProfiles', new Collection());
        $customer->setRelated('subscriptions', new Collection([$subscription]));
        $appointmentRepositoryMock = $this->createMock(AppointmentRepository::class);
        $appointmentRepositoryMock
            ->expects($this->never())
            ->method('find');
        $flexAppointmentRepositoryMock = $this->createMock(FlexIVRApiAppointmentRepository::class);
        $flexAppointmentRepositoryMock
            ->expects($this->never())
            ->method('createAppointment');

        $appointment = AppointmentData::getTestEntityData(1, ['appointmentID' => $this->getTestAppointmentId()])->first();
        $appointmentsCollection = AppointmentData::getTestData();

        $action = new CreateAppointmentInFlexIVRAction(
            $this->getAccountServiceMock($account),
            $this->getAppointmentServiceMock($customer, null, $subscription->serviceType),
            $this->getCustomerRepositoryMock($customer),
            $this->getAppointmentRepositoryMockForException($appointment, Collection::make($appointmentsCollection)),
            $flexAppointmentRepositoryMock,
        );
        Config::expects('get')
            ->with('aptive.mosquito_service_types')
            ->once()
            ->andReturn([ServiceTypeData::SERVICE_NAMES[ServiceTypeData::MOSQUITO]]);
        Config::expects('get')->with('app.locale')->zeroOrMoreTimes()->andReturn('en');
        Config::expects('get')->with('app.fallback_locale')->zeroOrMoreTimes()->andReturn('en');
        Config::expects('get')->with('aptive.default_date_format', null)->andReturn('Y-m-d');
        Event::fake();
        $this->expectException(CannotResolveAppointmentSubscriptionException::class);
        $action(
            $this->getTestAccountNumber(),
            $this->getTestSpotId(),
            $window,
            $isAroSpot,
            $notes,
        );
        Event::assertNotDispatched(AppointmentScheduled::class);
    }

    /**
     * @return iterable<string, array<int, mixed>>
     */
    public function provideAppointmentData(): iterable
    {
        yield 'reservice for initial appointment completed less than 20 days ago' => [
            'window' => Window::AM,
            'isAroSpot' => true,
            'notes' => self::TEST_NOTES,
            'serviceTypeId' => ServiceTypeData::RESERVICE, // Make sure this is the ID for initial service type
            'subscriptionId' => AppointmentModel::RESERVICE_SUBSCRIPTION_ID, // This should be the ID for reservice subscription
            'appointmentType' => AppointmentType::RESERVICE,
        ];
        yield 'pro with notes' => [
            'window' => Window::AM,
            'isAroSpot' => true,
            'notes' => self::TEST_NOTES,
            'serviceTypeId' => ServiceTypeData::PRO,
            'subscriptionId' => 2799702,
            'appointmentType' => AppointmentType::PRO,
        ];
        yield 'reservice with notes' => [
            'window' => Window::AM,
            'isAroSpot' => true,
            'notes' => self::TEST_NOTES,
            'serviceTypeId' => ServiceTypeData::RESERVICE,
            'subscriptionId' => AppointmentModel::RESERVICE_SUBSCRIPTION_ID,
            'appointmentType' => AppointmentType::RESERVICE,
        ];
        yield 'reservice no notes' => [
            'window' => Window::PM,
            'isAroSpot' => false,
            'notes' => null,
            'serviceTypeId' => ServiceTypeData::PRO,
            'subscriptionId' => 2799702,
            'appointmentType' => AppointmentType::PRO,
        ];
        yield 'pro no notes' => [
            'window' => Window::PM,
            'isAroSpot' => false,
            'notes' => null,
            'serviceTypeId' => ServiceTypeData::RESERVICE,
            'subscriptionId' => AppointmentModel::RESERVICE_SUBSCRIPTION_ID,
            'appointmentType' => AppointmentType::RESERVICE,
        ];
    }

    public function test_it_throws_appointment_can_not_be_created_exception_if_pending_appointment_exists(): void
    {
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);

        $serviceType = ServiceTypeData::getTestEntityDataOfTypes(ServiceTypeData::RESERVICE)->first();

        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId(),
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $subscription->setRelated('serviceType', $serviceType);

        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $customer->setRelated('appointments', new Collection());
        $customer->setRelated('paymentProfiles', new Collection());
        $customer->setRelated('subscriptions', new Collection([$subscription]));

        $appointmentServiceMock = \Mockery::mock(AppointmentService::class)->makePartial();

        $appointmentServiceMock
            ->shouldReceive('hasSubscriptionWithoutPendingAppointment')
            ->andReturn(Check::true())
            ->once();

        $appointmentServiceMock
            ->shouldReceive('resolveNewAppointmentTypeForCustomer')
            ->withArgs([$customer, $subscription])
            ->andReturn($serviceType)
            ->once();

        $appointmentServiceMock
            ->shouldReceive('resolveNewAppointmentSubscriptionForCustomer')
            ->withArgs([$customer])
            ->andReturn($subscription)
            ->once();

        $appointmentRepositoryMock = \Mockery::mock(AppointmentRepository::class);

        $appointmentRepositoryMock
            ->shouldReceive('createAppointment')
            ->never();

        $appointment = AppointmentData::getTestEntityData(1, ['appointmentID' => $this->getTestAppointmentId()])->first();
        $appointmentsCollection = AppointmentData::getTestData();

        $action = new CreateAppointmentInFlexIVRAction(
            $this->getAccountServiceMock($account),
            $appointmentServiceMock,
            $this->getCustomerRepositoryMock($customer),
            $this->getAppointmentRepositoryMockForRepositoryException($appointment, Collection::make($appointmentsCollection)),
            $this->getFlexIVRApiAppointmentRepositoryMockThrowingException(new CannotCreateAppointmentException()),
        );
        $this->expectException(CannotCreateAppointmentException::class);
        $action(
            $this->getTestAccountNumber(),
            $this->getTestSpotId(),
            Window::PM,
            false,
        );

    }

    /**
     * @dataProvider provideExceptions
     */
    public function test_it_throws_repository_exceptions(string $expectedExceptionClass): void
    {
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);

        $serviceType = ServiceTypeData::getTestEntityDataOfTypes(ServiceTypeData::RESERVICE)->first();

        $subscription = SubscriptionData::getTestEntityData(1, [
            'subscriptionID' => $this->getTestSubscriptionId(),
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $subscription->setRelated('serviceType', $serviceType);

        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $customer->setRelated('appointments', new Collection());
        $customer->setRelated('paymentProfiles', new Collection());
        $customer->setRelated('subscriptions', new Collection([$subscription]));

        $appointment = AppointmentData::getTestEntityData(1, ['appointmentID' => $this->getTestAppointmentId()])->first();
        $appointmentsCollection = AppointmentData::getTestData();
        $appointmentServiceMock = $this->getAppointmentServiceMock($customer, $subscription, $subscription->serviceType);
        $appointmentServiceMock
            ->expects($this->once())
            ->method('hasSubscriptionWithoutPendingAppointment')
            ->withAnyParameters()
            ->willReturn(Check::true());

        $action = new CreateAppointmentInFlexIVRAction(
            $this->getAccountServiceMock($account),
            $appointmentServiceMock,
            $this->getCustomerRepositoryMock($customer),
            $this->getAppointmentRepositoryMockForRepositoryException($appointment, Collection::make($appointmentsCollection)),
            $this->getFlexIVRApiAppointmentRepositoryMockThrowingException(new $expectedExceptionClass()),
        );
        $this->expectException($expectedExceptionClass);
        $action(
            $this->getTestAccountNumber(),
            $this->getTestSpotId(),
            Window::PM,
            false,
        );
    }

    /**
     * @return iterable<string, array<int, mixed>>
     */
    public function provideExceptions(): iterable
    {
        yield 'AppointmentCanNotBeCreatedException' => [
            AppointmentCanNotBeCreatedException::class,
        ];
        yield 'AppointmentSpotAlreadyUsedException' => [
            AppointmentSpotAlreadyUsedException::class,
        ];
    }

    /**
     * @throws Exception if creating mock fails
     */
    private function getAccountServiceMock(Account $account): AccountService|MockObject
    {
        $accountServiceMock = $this->createMock(AccountService::class);
        $accountServiceMock
            ->method('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->willReturn($account);

        return $accountServiceMock;
    }

    /**
     * @throws Exception if creating mock fails
     */
    private function getAppointmentServiceMock(
        CustomerModel $customer,
        SubscriptionModel|null $subscription,
        ServiceTypeModel $serviceType,
    ): AppointmentService|MockObject {
        $appointmentServiceMock = $this->createMock(AppointmentService::class);

        $appointmentServiceMock
            ->expects($this->once())
            ->method('resolveNewAppointmentSubscriptionForCustomer')
            ->with($customer)
            ->willReturn($subscription);

        $appointmentServiceMock
            ->expects($this->once())
            ->method('resolveNewAppointmentTypeForCustomer')
            ->with($customer, $subscription)
            ->willReturn($serviceType);

        return $appointmentServiceMock;
    }

    /**
     * @throws Exception if creating mock fails
     */
    private function getCustomerRepositoryMock(CustomerModel $customer): CustomerRepository|MockObject
    {
        $customerRepositoryMock = $this->createMock(CustomerRepository::class);
        $customerRepositoryMock
            ->expects($this->once())
            ->method('office')
            ->with($this->getTestOfficeId())
            ->willReturnSelf();

        $customerRepositoryMock
            ->expects($this->once())
            ->method('withRelated')
            ->with(['subscriptions.serviceType', 'appointments.serviceType'])
            ->willReturnSelf();

        $customerRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($this->getTestAccountNumber())
            ->willReturn($customer);

        return $customerRepositoryMock;
    }

    /**
     * @throws Exception if creating mock fails
     */
    private function getAppointmentRepositoryMockForException(AppointmentModel $appointment, Collection $appointmentsCollection): AppointmentRepository|MockObject
    {
        $appointmentRepositoryMock = $this->createMock(AppointmentRepository::class);
        $searchAppointmentsDto = new SearchAppointmentsDTO(
            officeId: $this->getTestOfficeId(),
            accountNumber: [$this->getTestAccountNumber()],
            dateStart: null,
            dateEnd: null,
            status: [AppointmentStatus::Pending, AppointmentStatus::Completed],
        );
        $appointmentRepositoryMock
            ->method('office')
            ->with($this->getTestOfficeId())
            ->willReturnSelf();

        $appointmentRepositoryMock
            ->method('withRelated')
            ->with(['serviceType'])
            ->willReturnSelf();

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('search')
            ->with($searchAppointmentsDto)
            ->willReturn($appointmentsCollection);

        return $appointmentRepositoryMock;
    }

    /**
     * @throws Exception if creating mock fails
     */
    private function getAppointmentRepositoryMockForRepositoryException(
        AppointmentModel $appointment,
        Collection $appointmentsCollection
    ): AppointmentRepository|MockObject {
        $appointmentRepositoryMock = $this->getAppointmentRepositoryMockForException(
            $appointment,
            $appointmentsCollection
        );

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('searchByCustomerId')
            ->with([$this->getTestAccountNumber()])
            ->willReturn(Collection::make([$appointment]));

        return $appointmentRepositoryMock;
    }

    /**
     * @throws Exception if creating mock fails
     */
    private function getAppointmentRepositoryMock(AppointmentModel $appointment, Collection $appointmentsCollection): AppointmentRepository|MockObject
    {
        $appointmentRepositoryMock = $this->createMock(AppointmentRepository::class);
        $searchAppointmentsDto = new SearchAppointmentsDTO(
            officeId: $this->getTestOfficeId(),
            accountNumber: [$this->getTestAccountNumber()],
            dateStart: null,
            dateEnd: null,
            status: [AppointmentStatus::Pending, AppointmentStatus::Completed],
        );
        $appointmentRepositoryMock
            ->method('office')
            ->with($this->getTestOfficeId())
            ->willReturnSelf();

        $appointmentRepositoryMock
            ->method('withRelated')
            ->with(['serviceType'])
            ->willReturnSelf();

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($appointment->id)
            ->willReturn($appointment);

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('search')
            ->with($searchAppointmentsDto)
            ->willReturn($appointmentsCollection);

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('searchByCustomerId')
            ->with([$this->getTestAccountNumber()])
            ->willReturn(Collection::make([$appointment]));

        return $appointmentRepositoryMock;
    }

    private function getAppointmentRepositoryMockForAppointmentCannotBeCreatedException(AppointmentModel $appointment, Collection $appointmentsCollection): AppointmentRepository|MockObject
    {
        $appointmentRepositoryMock = $this->createMock(AppointmentRepository::class);
        $searchAppointmentsDto = new SearchAppointmentsDTO(
            officeId: $this->getTestOfficeId(),
            accountNumber: [$this->getTestAccountNumber()],
            dateStart: null,
            dateEnd: null,
            status: [AppointmentStatus::Pending, AppointmentStatus::Completed],
        );
        $appointmentRepositoryMock
            ->method('office')
            ->with($this->getTestOfficeId())
            ->willReturnSelf();

        $appointmentRepositoryMock
            ->method('withRelated')
            ->with(['serviceType'])
            ->willReturnSelf();

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('search')
            ->with($searchAppointmentsDto)
            ->willReturn($appointmentsCollection);

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('searchByCustomerId')
            ->with([$this->getTestAccountNumber()])
            ->willReturn(Collection::make([$appointment]));

        return $appointmentRepositoryMock;
    }
    private function getAppointmentServiceMockForNoAppointmentsTest(
        CustomerModel $customer,
        SubscriptionModel|null $subscription,
        ServiceTypeModel $serviceType,
    ): AppointmentService|MockObject {
        $appointmentServiceMock = $this->createMock(AppointmentService::class);
        $appointmentServiceMock
            ->expects($this->never())
            ->method('resolveNewAppointmentSubscriptionForCustomer')
            ->with($customer)
            ->willReturn($subscription);

        $appointmentServiceMock
            ->expects($this->never())
            ->method('resolveNewAppointmentTypeForCustomer')
            ->with($customer, $subscription)
            ->willReturn($serviceType);

        return $appointmentServiceMock;
    }

    private function getCustomerRepositoryMockForNoAppointmentsTest(CustomerModel $customer): CustomerRepository|MockObject
    {
        $customerRepositoryMock = $this->createMock(CustomerRepository::class);
        $customerRepositoryMock
            ->expects($this->never())
            ->method('office')
            ->willReturnSelf();

        $customerRepositoryMock
            ->expects($this->never())
            ->method('find')
            ->willReturn($customer);

        return $customerRepositoryMock;
    }

    /**
     * @throws Exception if creating mock fails
     */
    public function getFlexIVRApiAppointmentRepositoryMock(
        AppointmentType $appointmentType,
        int $subscriptionId,
        Window $window,
        bool $isAroSpot,
        string|null $notes = null,
    ): FlexIVRApiAppointmentRepository|MockObject {
        $flexAppointmentRepositoryMock = $this->createMock(FlexIVRApiAppointmentRepository::class);
        $flexAppointmentRepositoryMock
            ->expects($this->once())
            ->method('createAppointment')
            ->with(self::callback(
                fn (CreateAppointment $dto) => $dto->officeId === $this->getTestOfficeId()
                    && $dto->accountNumber === $this->getTestAccountNumber()
                    && $dto->subscriptionId === $subscriptionId
                    && $dto->appointmentType === $appointmentType
                    && $dto->window === $window
                    && $dto->isAroSpot === $isAroSpot
                    && $dto->notes === $notes
            ))
            ->willReturn($this->getTestAppointmentId());

        return $flexAppointmentRepositoryMock;
    }

    public function getFlexIVRApiAppointmentRepositoryMockThrowingException(
        \Throwable $exception,
    ): FlexIVRApiAppointmentRepository|MockObject {
        $flexAppointmentRepositoryMock = $this->createMock(FlexIVRApiAppointmentRepository::class);
        $flexAppointmentRepositoryMock
            ->expects($this->once())
            ->method('createAppointment')
            ->willThrowException($exception);

        return $flexAppointmentRepositoryMock;
    }
}
