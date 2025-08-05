<?php

declare(strict_types=1);

namespace App\Actions\Appointment;

use App\DTO\Appointment\UpdateAppointmentDTO;
use App\Events\Appointment\AppointmentRescheduled;
use App\Exceptions\Appointment\AppointmentCanNotBeRescheduledException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Helpers\DateTimeHelper;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\EmployeeRepository;
use App\Interfaces\Repository\SpotRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Models\External\SpotModel;
use App\Services\AppointmentService;
use App\Services\LoggerAwareTrait;
use Carbon\Carbon;

class UpdateAppointmentAction
{
    use LoggerAwareTrait;
    use GetCxpSchedulerId;

    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly AppointmentRepository $appointmentRepository,
        private readonly SpotRepository $spotRepository,
        private readonly EmployeeRepository $employeeRepository
    ) {
    }

    /**
     * @throws AppointmentCanNotBeRescheduledException
     * @throws EntityNotFoundException
     */
    public function __invoke(Account $account, int $appointmentId, int $spotId, string|null $notes = null): int
    {
        /** @var AppointmentModel $appointment */
        $appointment = $this->appointmentRepository
            ->office($account->office_id)
            ->withRelated(['serviceType'])
            ->find($appointmentId);

        if (($check = $this->appointmentService->canRescheduleAppointment($appointment))->isFalse()) {
            throw new AppointmentCanNotBeRescheduledException((string) $check->getMessage());
        }

        /** @var SpotModel $spot */
        $spot = $this->spotRepository
            ->office($account->office_id)
            ->find($spotId);

        if (($check = $this->appointmentService->canAssignSpotToAppointment($spot))->isFalse()) {
            throw new AppointmentCanNotBeRescheduledException((string) $check->getMessage());
        }

        [$startTime, $endTime] = DateTimeHelper::isAmTime($spot->start)
            ? DateTimeHelper::getAmTimeRange()
            : DateTimeHelper::getPmTimeRange();

        $date = $spot->start->format(DateTimeHelper::defaultDateFormat());

        $updateAppointmentDto = new UpdateAppointmentDTO(
            officeId: $account->office_id,
            appointmentId: $appointmentId,
            routeId: $spot->routeId,
            start: Carbon::parse("$date $startTime"),
            end: Carbon::parse("$date $endTime"),
            duration: $appointment->serviceType ?
                $this->appointmentService->calculateAppointmentDuration($appointment->serviceType) : null,
            notes: $notes,
            employeeId: $this->getCxpSchedulerId($account->office_id)
        );

        $result = $this->appointmentRepository->updateAppointment($updateAppointmentDto);

        AppointmentRescheduled::dispatch($account->account_number);

        return $result;
    }

    private function getEmployeeRepository(): EmployeeRepository
    {
        return $this->employeeRepository;
    }
}
