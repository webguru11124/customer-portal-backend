<?php

declare(strict_types=1);

namespace App\Actions\Appointment;

use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Helpers\DateTimeHelper;
use App\Interfaces\Repository\AppointmentRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Illuminate\Support\Collection;

class ShowAppointmentsHistoryAction
{
    private const HISTORY_APPOINTMENTS_STATUSES = [
        AppointmentStatus::Completed,
        AppointmentStatus::NoShow,
        AppointmentStatus::Rescheduled,
    ];

    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
    ) {
    }

    /**
     * @return Collection<int, AppointmentModel>
     */
    public function __invoke(Account $account): Collection
    {
        $searchAppointmentsDTO = SearchAppointmentsDTO::from([
            'officeId' => $account->office_id,
            'accountNumber' => [$account->account_number],
            'dateEnd' => DateTimeHelper::today(),
            'status' => self::HISTORY_APPOINTMENTS_STATUSES,
        ]);

        /** @var Collection<int, AppointmentModel> $appointmentsCollection */
        $appointmentsCollection = $this->appointmentRepository
            ->office($account->office_id)
            ->withRelated(['documents'])
            ->search($searchAppointmentsDTO);

        return $appointmentsCollection->filter(
            fn (AppointmentModel $appointment) => $appointment->end !== null
                && !DateTimeHelper::isFutureDate($appointment->end)
        );
    }
}
