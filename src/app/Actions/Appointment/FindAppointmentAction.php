<?php

declare(strict_types=1);

namespace App\Actions\Appointment;

use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\AppointmentRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;

class FindAppointmentAction
{
    private AppointmentRepository $appointmentRepository;

    public function __construct(
        AppointmentRepository $appointmentRepository
    ) {
        $this->appointmentRepository = $appointmentRepository;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function __invoke(Account $account, int $appointmentId): AppointmentModel
    {
        /** @var AppointmentModel $entity */
        $entity = $this->appointmentRepository
            ->office($account->office_id)
            ->withRelated(['serviceType'])
            ->find($appointmentId);

        return $entity;
    }
}
