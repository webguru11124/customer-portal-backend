<?php

declare(strict_types=1);

namespace App\Actions\Appointment;

use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Interfaces\Repository\AppointmentRepository;
use App\Models\External\AppointmentModel;
use App\Services\LoggerAwareTrait;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Support\Collection;

class SearchAppointmentsAction
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
    ) {
    }

    /**
     * Searches appointment filtered by given params for given account.
     *
     * @return Collection<int, AppointmentModel>
     *
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SearchAppointmentsDTO $searchAppointmentDTO): Collection
    {
        /** @var Collection<int, AppointmentModel> $result */
        $result = $this->appointmentRepository
            ->office($searchAppointmentDTO->officeId)
            ->withRelated(['serviceType'])
            ->search($searchAppointmentDTO);

        return $result;
    }
}
