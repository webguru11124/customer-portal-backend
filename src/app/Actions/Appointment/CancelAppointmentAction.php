<?php

declare(strict_types=1);

namespace App\Actions\Appointment;

use App\Events\Appointment\AppointmentCanceled;
use App\Exceptions\Appointment\AppointmentCanNotBeCancelled;
use App\Exceptions\Appointment\AppointmentNotCancelledException;
use App\Exceptions\Authorization\UnauthorizedException;
use App\Interfaces\Repository\AppointmentRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Services\AppointmentService;

class CancelAppointmentAction
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly AppointmentRepository $appointmentRepository,
    ) {
    }

    /**
     * @throws AppointmentNotCancelledException when appointment delete fails in pestroutes.
     * @throws AppointmentCanNotBeCancelled when appointment type is not reservice.
     * @throws UnauthorizedException when appointment doesn't belong to account
     */
    public function __invoke(Account $account, int $appointmentId): void
    {
        /** @var AppointmentModel $appointment */
        $appointment = $this->appointmentRepository
            ->office($account->office_id)
            ->withRelated(['serviceType'])
            ->find($appointmentId);

        if ($appointment->customerId !== $account->account_number) {
            throw new UnauthorizedException();
        }

        if (($check = $this->appointmentService->canCancelAppointment($appointment))->isFalse()) {
            throw new AppointmentCanNotBeCancelled((string) $check->getMessage());
        }

        $this->appointmentRepository->cancelAppointment($appointment);

        AppointmentCanceled::dispatch($account->account_number);
    }
}
