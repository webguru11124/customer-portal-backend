<?php

declare(strict_types=1);

namespace App\Actions\Appointment;

use App\DTO\FlexIVR\Appointment\RescheduleAppointment;
use App\Enums\FlexIVR\Window;
use App\Events\Appointment\AppointmentRescheduled;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Appointment\AppointmentCanNotBeRescheduledException;
use App\Exceptions\Appointment\AppointmentSpotAlreadyUsedException;
use App\Exceptions\Appointment\CannotGetCurrentAppointment;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\FlexIVRApi\AppointmentRepository as FlexIVRApiAppointmentRepository;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\External\AppointmentModel;
use App\Services\AccountService;

/**
 * @final
 */
class RescheduleAppointmentInFlexIVRAction
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly CustomerRepository $customerRepository,
        private readonly AppointmentRepository $appointmentRepository,
        private readonly FlexIVRApiAppointmentRepository $flexAppointmentRepository,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     * @throws AccountNotFoundException
     * @throws AppointmentCanNotBeCreatedException
     * @throws CannotGetCurrentAppointment
     */
    /**
     * @param int $accountNumber
     * @param int $appointmentId
     * @param int $spotId
     * @param Window $window
     * @param bool $isAroSpot
     * @param string|null $notes
     * @return AppointmentModel
     * @throws AccountNotFoundException
     * @throws AppointmentCanNotBeCreatedException
     * @throws AppointmentSpotAlreadyUsedException
     * @throws CannotGetCurrentAppointment
     * @throws EntityNotFoundException
     * @throws AppointmentCanNotBeRescheduledException
     */
    public function __invoke(
        int $accountNumber,
        int $appointmentId,
        int $spotId,
        Window $window,
        bool $isAroSpot,
        string|null $notes = null
    ): AppointmentModel {
        $account = $this->accountService->getAccountByAccountNumber($accountNumber);
        $customer = $this->customerRepository->office($account->office_id)->find($account->account_number);
        $currentAppointment = $this->flexAppointmentRepository->getCurrentAppointment($customer->id);

        if ($appointmentId !== (int) $currentAppointment->appointmentID) {
            throw new EntityNotFoundException('Current Appointment ID and request ID do not match');
        }

        $appointmentRepositoryQuery = $this->appointmentRepository
            ->office($account->office_id)
            ->withRelated(['serviceType']);

        $appointmentsCollection = $appointmentRepositoryQuery
            ->getUpcomingAppointments($account->account_number)
            ->where('id', $currentAppointment->appointmentID);
        $existingAppointment = null;
        if ($appointmentsCollection !== null) {
            $existingAppointment = $appointmentsCollection->isNotEmpty() ? $appointmentsCollection->first() : null;
        }
        // If appoinmentModel->isInitial is true, throw exception
        if ($existingAppointment !== null && $existingAppointment->isInitial) {
            throw new AppointmentCanNotBeRescheduledException('Initial appointment cannot be rescheduled');
        }
        // PR adds prefixes with - or without it before note, so we need to remove it to prevent duplication
        $updatedNotes = trim(str_replace(
            [AppointmentModel::PR_NOTE_PREFIX . ' - ', AppointmentModel::PR_NOTE_PREFIX],
            '',
            ($existingAppointment->appointmentNotes ?? '') . " " . $notes
        ));

        $dto = new RescheduleAppointment(
            officeId: $account->office_id,
            accountNumber: $account->account_number,
            subscriptionId: (int) $currentAppointment->subscriptionID,
            spotId: $spotId,
            appointmentId: (int) $currentAppointment->appointmentID,
            appointmentType: (int) $currentAppointment->type,
            window: $window,
            isAroSpot: $isAroSpot,
            notes: $updatedNotes,
        );

        $appointmentId = $this->flexAppointmentRepository->rescheduleAppointment($dto);
        $appointment = $appointmentRepositoryQuery
            ->find($appointmentId);
        AppointmentRescheduled::dispatch($account->account_number);

        return $appointment;
    }
}
