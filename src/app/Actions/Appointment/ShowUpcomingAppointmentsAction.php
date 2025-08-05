<?php

declare(strict_types=1);

namespace App\Actions\Appointment;

use App\Interfaces\Repository\AppointmentRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Support\Collection;

class ShowUpcomingAppointmentsAction
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
    ) {
    }

    /**
     * @return Collection<int, AppointmentModel>
     *
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(Account $account, int|null $limit = null): Collection
    {
        $appointmentsCollection = $this->appointmentRepository
            ->office($account->office_id)
            ->withRelated(['serviceType'])
            ->getUpcomingAppointments($account->account_number);

        $appointmentsCollection = $appointmentsCollection->sortBy(['start']);

        if ($limit) {
            $appointmentsCollection = $appointmentsCollection->slice(0, $limit);
        }
        return $appointmentsCollection->values();
    }
}
