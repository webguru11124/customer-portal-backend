<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Appointment;

use App\Actions\Appointment\RescheduleAppointmentInFlexIVRAction;
use App\DTO\FlexIVR\Appointment\RescheduleAppointment;
use App\Enums\FlexIVR\Window;
use App\Events\Appointment\AppointmentRescheduled;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Appointment\AppointmentCanNotBeRescheduledException;
use App\Exceptions\Appointment\AppointmentSpotAlreadyUsedException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\FlexIVRApi\AppointmentRepository as FlexIVRApiAppointmentRepository;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Models\External\CustomerModel;
use App\Services\AccountService;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Data\AppointmentData;
use Tests\Data\CustomerData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class RescheduleAppointmentInFlexIVRActionTest extends TestCase
{
    use RandomIntTestData;

    private const TEST_NOTES = 'Test notes';

    /**
     * @dataProvider provideAppointmentData
     */
    public function test_action_throws_exception_when_current_appointment_id_does_not_match(
        string|null $appointmentNote,
        Window $window,
        bool $isAroSpot,
        string|null $notes,
    ): void {
        $wrongAppointmentId = $this->getTestAppointmentId() - 1;
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $customer->setRelated('appointments', new Collection());
        $customer->setRelated('paymentProfiles', new Collection());
        $customer->setRelated('subscriptions', new Collection([]));

        $appointmentRepositoryMock = $this->createMock(AppointmentRepository::class);
        $appointmentRepositoryMock
            ->expects($this->never())
            ->method('find');

        $flexAppointmentRepositoryMock = $this->createMock(FlexIVRApiAppointmentRepository::class);
        $flexAppointmentRepositoryMock
            ->expects($this->never())
            ->method('rescheduleAppointment');
        $flexAppointmentRepositoryMock
            ->expects($this->once())
            ->method('getCurrentAppointment')
            ->with($this->getTestAccountNumber())
            ->willReturn(new class ($wrongAppointmentId) {
                public readonly int $appointmentID;

                public function __construct(int $id)
                {
                    $this->appointmentID = $id;
                }
            });
        Event::fake();

        $action = new RescheduleAppointmentInFlexIVRAction(
            $this->getAccountServiceMock($account),
            $this->getCustomerRepositoryMock($customer),
            $appointmentRepositoryMock,
            $flexAppointmentRepositoryMock,
        );

        $this->expectException(EntityNotFoundException::class);

        $action(
            $this->getTestAccountNumber(),
            $this->getTestAppointmentId(),
            $this->getTestSpotId(),
            $window,
            $isAroSpot,
            $notes,
        );
        Event::assertNotDispatched(AppointmentRescheduled::class);
    }

    public function test_it_throws_error_when_appointment_is_initial(): void
    {
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $customer->setRelated('appointments', new Collection());
        $customer->setRelated('paymentProfiles', new Collection());
        $customer->setRelated('subscriptions', new Collection([]));

        $data = [
            'officeID' => $this->getTestOfficeId(),
            'customerID' => $this->getTestAccountNumber(),
            'status' => AppointmentStatus::Pending->value,
        ];
        $upcomingAppointment = AppointmentData::getTestEntityData(1, array_merge($data, [
            'date' => Carbon::now(AppointmentData::CUSTOMER_TIME_ZONE)
                ->addDays(random_int(5, 10))
                ->format(AppointmentData::DATE_FORMAT),
            'appointmentID' => $this->getTestAppointmentId(),
            'isInitial' => '1'
        ]))->first();

        $appointmentRepositoryMock = $this->createMock(AppointmentRepository::class);

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('office')
            ->with($this->getTestOfficeId())
            ->willReturnSelf();

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('withRelated')
            ->with(['serviceType'])
            ->willReturnSelf();

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('getUpcomingAppointments')
            ->with($this->getTestAccountNumber())
            ->willReturn(Collection::make([$upcomingAppointment]));

        $flexAppointmentRepositoryMock = $this->createMock(FlexIVRApiAppointmentRepository::class);
        $flexAppointmentRepositoryMock
                ->expects($this->once())
                ->method('getCurrentAppointment')
                ->with($this->getTestAccountNumber())
                ->willReturn(new class ($this->getTestAppointmentId(), $this->getTestSubscriptionId(), $this->getTestServiceTypeId()) {
                    public function __construct(
                        public readonly int $appointmentID,
                        public readonly int $subscriptionID,
                        public readonly int $type,
                    ) {
                    }
                });


        $action = new RescheduleAppointmentInFlexIVRAction(
            $this->getAccountServiceMock($account),
            $this->getCustomerRepositoryMock($customer),
            $appointmentRepositoryMock,
            $flexAppointmentRepositoryMock
        );

        $this->expectException(AppointmentCanNotBeRescheduledException::class);

        $action(
            $this->getTestAccountNumber(),
            $this->getTestAppointmentId(),
            $this->getTestSpotId(),
            Window::AM,
            true
        );

    }

    /**
     * @dataProvider provideAppointmentData
     */
    public function test_reschedule_creates_new_appointment(
        string|null $appointmentNote,
        Window $window,
        bool $isAroSpot,
        string|null $notes,
        string|null $finalNote,
    ): void {
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $customer->setRelated('appointments', new Collection());
        $customer->setRelated('paymentProfiles', new Collection());
        $customer->setRelated('subscriptions', new Collection([]));

        $data = [
            'officeID' => $this->getTestOfficeId(),
            'customerID' => $this->getTestAccountNumber(),
            'status' => AppointmentStatus::Pending->value,
        ];
        $upcomingAppointment = AppointmentData::getTestEntityData(1, array_merge($data, [
            'date' => Carbon::now(AppointmentData::CUSTOMER_TIME_ZONE)
                ->addDays(random_int(5, 10))
                ->format(AppointmentData::DATE_FORMAT),
            'appointmentID' => $this->getTestAppointmentId(),
            'appointmentNotes' => $appointmentNote
        ]))->first();
        $nextAppointment = AppointmentData::getTestEntityData(1, array_merge($data, [
            'date' => Carbon::now(AppointmentData::CUSTOMER_TIME_ZONE)
                ->addDays(random_int(1, 4))
                ->format(AppointmentData::DATE_FORMAT),
            'appointmentID' => $this->getTestAppointmentId() + 1,
            'appointmentNotes' => $finalNote,
        ]))->first();

        $updatedNotes = trim(str_replace(
            AppointmentModel::PR_NOTE_PREFIX,
            '',
            $appointmentNote . " " . $notes
        ));

        $action = new RescheduleAppointmentInFlexIVRAction(
            $this->getAccountServiceMock($account),
            $this->getCustomerRepositoryMock($customer),
            $this->getAppointmentRepositoryMock($nextAppointment, Collection::make([$upcomingAppointment])),
            $this->getFlexIVRApiAppointmentRepositoryMock(
                $window,
                $isAroSpot,
                $updatedNotes,
            ),
        );

        Event::fake();
        $rescheduledAppointment = $action(
            $this->getTestAccountNumber(),
            $this->getTestAppointmentId(),
            $this->getTestSpotId(),
            $window,
            $isAroSpot,
            $notes,
        );
        Event::assertDispatched(AppointmentRescheduled::class);

        $this->assertEquals($finalNote, $rescheduledAppointment->appointmentNotes);
        $this->assertInstanceOf(AppointmentModel::class, $rescheduledAppointment);
    }

    /**
     * @return iterable<string, array<int, mixed>>
     */
    public function provideAppointmentData(): iterable
    {
        yield 'old no notes new with notes' => [
            'customerNote' => self::TEST_NOTES,
            'window' => Window::AM,
            'isAroSpot' => true,
            'currentNote' => null,
            'finalNote' => AppointmentModel::PR_NOTE_PREFIX . ' ' . self::TEST_NOTES,
        ];
        yield 'old no notes new no notes' => [
            'customerNote' => null,
            'window' => Window::PM,
            'isAroSpot' => false,
            'currentNote' => null,
            'finalNote' => null,
        ];
        yield 'old with notes new with notes' => [
            'customerNote' => self::TEST_NOTES,
            'window' => Window::AM,
            'isAroSpot' => true,
            'currentNote' => AppointmentModel::PR_NOTE_PREFIX . 'old note',
            'finalNote' => AppointmentModel::PR_NOTE_PREFIX . 'old note ' . self::TEST_NOTES,
        ];
        yield 'old with notes new no notes' => [
            'customerNote' => null,
            'window' => Window::PM,
            'isAroSpot' => false,
            'currentNote' => AppointmentModel::PR_NOTE_PREFIX . 'old note',
            'finalNote' => AppointmentModel::PR_NOTE_PREFIX . 'old note',
        ];
    }

    /**
     * @dataProvider provideExceptions
     */
    public function test_reschedule_throws_repository_exceptions(string $expectedExceptionClass): void
    {
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
        $customer = CustomerData::getTestEntityData(1, [
            'officeID' => $account->office_id,
            'customerID' => $account->account_number,
        ])->first();
        $customer->setRelated('appointments', new Collection());
        $customer->setRelated('paymentProfiles', new Collection());
        $customer->setRelated('subscriptions', new Collection([]));

        $action = new RescheduleAppointmentInFlexIVRAction(
            $this->getAccountServiceMock($account),
            $this->getCustomerRepositoryMock($customer),
            $this->createMock(AppointmentRepository::class),
            $this->getFlexIVRApiAppointmentRepositoryMockThrowingException(new $expectedExceptionClass()),
        );

        $this->expectException($expectedExceptionClass);

        $action(
            $this->getTestAccountNumber(),
            $this->getTestAppointmentId(),
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
            ->expects($this->once())
            ->method('getAccountByAccountNumber')
            ->with($this->getTestAccountNumber())
            ->willReturn($account);

        return $accountServiceMock;
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
            ->method('find')
            ->with($this->getTestAccountNumber())
            ->willReturn($customer);

        return $customerRepositoryMock;
    }

    /**
     * @throws Exception if creating mock fails
     */
    private function getAppointmentRepositoryMock(
        AppointmentModel $nextAppointment,
        Collection $currentAppointmentsCollection
    ): AppointmentRepository|MockObject {
        $appointmentRepositoryMock = $this->createMock(AppointmentRepository::class);

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('office')
            ->with($this->getTestOfficeId())
            ->willReturnSelf();

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('withRelated')
            ->with(['serviceType'])
            ->willReturnSelf();

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('getUpcomingAppointments')
            ->with($this->getTestAccountNumber())
            ->willReturn($currentAppointmentsCollection);

        $appointmentRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($nextAppointment->id)
            ->willReturn($nextAppointment);

        return $appointmentRepositoryMock;
    }

    /**
     * @throws Exception if creating mock fails
     */
    public function getFlexIVRApiAppointmentRepositoryMock(
        Window $window,
        bool $isAroSpot,
        string|null $notes = null,
    ): FlexIVRApiAppointmentRepository|MockObject {
        $flexAppointmentRepositoryMock = $this->createMock(FlexIVRApiAppointmentRepository::class);
        $flexAppointmentRepositoryMock
            ->expects($this->once())
            ->method('getCurrentAppointment')
            ->with($this->getTestAccountNumber())
            ->willReturn(new class ($this->getTestAppointmentId(), $this->getTestSubscriptionId(), $this->getTestServiceTypeId()) {
                public function __construct(
                    public readonly int $appointmentID,
                    public readonly int $subscriptionID,
                    public readonly int $type,
                ) {
                }
            });
        $flexAppointmentRepositoryMock
            ->expects($this->once())
            ->method('rescheduleAppointment')
            ->with(self::callback(
                fn (RescheduleAppointment $dto) => $dto->officeId === $this->getTestOfficeId()
                    && $dto->accountNumber === $this->getTestAccountNumber()
                    && $dto->subscriptionId === $this->getTestSubscriptionId()
                    && $dto->spotId === $this->getTestSpotId()
                    && $dto->appointmentType === $this->getTestServiceTypeId()
                    && $dto->window === $window
                    && $dto->isAroSpot === $isAroSpot
                    && $dto->notes === $notes
            ))
            ->willReturn($this->getTestAppointmentId() + 1);

        return $flexAppointmentRepositoryMock;
    }

    public function getFlexIVRApiAppointmentRepositoryMockThrowingException(
        \Throwable $exception,
    ): FlexIVRApiAppointmentRepository|MockObject {
        $flexAppointmentRepositoryMock = $this->createMock(FlexIVRApiAppointmentRepository::class);
        $flexAppointmentRepositoryMock
            ->expects($this->once())
            ->method('getCurrentAppointment')
            ->with($this->getTestAccountNumber())
            ->willReturn(new class ($this->getTestAppointmentId(), $this->getTestSubscriptionId(), $this->getTestServiceTypeId()) {
                public function __construct(
                    public readonly int $appointmentID,
                    public readonly int $subscriptionID,
                    public readonly int $type,
                ) {
                }
            });
        $flexAppointmentRepositoryMock
            ->expects($this->once())
            ->method('rescheduleAppointment')
            ->willThrowException($exception);

        return $flexAppointmentRepositoryMock;
    }
}
