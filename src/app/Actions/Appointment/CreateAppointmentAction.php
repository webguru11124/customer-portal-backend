<?php

declare(strict_types=1);

namespace App\Actions\Appointment;

use App\DTO\Appointment\CreateAppointmentDTO;
use App\Events\Appointment\AppointmentScheduled;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Helpers\DateTimeHelper;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\EmployeeRepository;
use App\Interfaces\Repository\SpotRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\External\SpotModel;
use App\Services\AppointmentService;
use App\Services\LoggerAwareTrait;
use Carbon\Carbon;

class CreateAppointmentAction
{
    use LoggerAwareTrait;
    use GetCxpSchedulerId;

    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly AppointmentRepository $appointmentRepository,
        private readonly SpotRepository $spotRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly EmployeeRepository $employeeRepository,
    ) {
    }

    /**
     * Creates new appointment.
     *
     * @throws AppointmentCanNotBeCreatedException
     * @throws EntityNotFoundException
     */
    public function __invoke(Account $account, int $spotId, string|null $notes = null): int
    {
        /** @var SpotModel $spot */
        $spot = $this->spotRepository
            ->office($account->office_id)
            ->find($spotId);

        /** @var CustomerModel $customer */
        $customer = $this->customerRepository
            ->office($account->office_id)
            ->withRelated(['subscriptions.serviceType', 'appointments.serviceType'])
            ->find($account->account_number);

        $subscription = $this->appointmentService->resolveNewAppointmentSubscriptionForCustomer($customer);
        $serviceType = $this->appointmentService->resolveNewAppointmentTypeForCustomer($customer, $subscription);

        [$startTime, $endTime] = DateTimeHelper::isAmTime($spot->start)
            ? DateTimeHelper::getAmTimeRange()
            : DateTimeHelper::getPmTimeRange();

        $date = $spot->start->format(DateTimeHelper::defaultDateFormat());

        $createAppointmentDTO = new CreateAppointmentDTO(
            officeId: $account->office_id,
            accountNumber: $account->account_number,
            typeId: $serviceType->id,
            routeId: $spot->routeId,
            start: Carbon::parse("$date $startTime"),
            end: Carbon::parse("$date $endTime"),
            duration: $this->appointmentService->calculateAppointmentDuration($serviceType),
            notes: $notes,
            employeeId: $this->getCxpSchedulerId($account->office_id),
            subscriptionId: $subscription?->id
        );

        if (($check = $this->appointmentService->canCreateAppointment($createAppointmentDTO))->isFalse()) {
            throw new AppointmentCanNotBeCreatedException((string) $check->getMessage());
        }

        $result = $this->appointmentRepository->createAppointment($createAppointmentDTO);

        AppointmentScheduled::dispatch($account->account_number);

        return $result;
    }

    private function getEmployeeRepository(): EmployeeRepository
    {
        return $this->employeeRepository;
    }
}
