<?php

namespace App\Interfaces\Repository;

use App\DTO\Appointment\CreateAppointmentDTO;
use App\DTO\Appointment\UpdateAppointmentDTO;
use App\Models\External\AppointmentModel;
use Illuminate\Support\Collection;

/**
 * @extends ExternalRepository<AppointmentModel>
 */
interface AppointmentRepository extends ExternalRepository
{
    public function createAppointment(CreateAppointmentDTO $createAppointmentDTO): int;

    public function updateAppointment(UpdateAppointmentDTO $updateAppointmentDTO): int;

    public function cancelAppointment(AppointmentModel $appointment): void;

    /**
     * @return Collection<int, AppointmentModel>
     */
    public function getUpcomingAppointments(int $accountNumber): Collection;

    /**
     * @param int[] $customerIds
     *
     * @return Collection<int, AppointmentModel>
     */
    public function searchByCustomerId(array $customerIds): Collection;
}
