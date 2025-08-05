<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Traits\DateFilterAware;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;

class AppointmentParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    use DateFilterAware;

    /**
     * @param SearchAppointmentsDTO $searchDto
     *
     * @return SearchAppointmentsParams
     */
    public function createSearch(mixed $searchDto): AbstractHttpParams
    {
        $this->validateInput(SearchAppointmentsDTO::class, $searchDto);

        return new SearchAppointmentsParams(
            officeId: $searchDto->officeId,
            officeIds: [$searchDto->officeId],
            ids: $searchDto->ids,
            status: $searchDto->status,
            customerIds: $searchDto->accountNumber,
            dateCompleted: $this->getDateFilter(
                $searchDto->getCarbonDateCompletedStart(),
                $searchDto->getCarbonDateCompletedEnd()
            ),
            serviceIds: $searchDto->serviceIds,
            dateStart: $searchDto->getCarbonDateStart(),
            dateEnd: $searchDto->getCarbonDateEnd()
        );
    }
}
