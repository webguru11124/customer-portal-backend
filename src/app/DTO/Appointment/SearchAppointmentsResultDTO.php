<?php

namespace App\DTO\Appointment;

use App\DTO\BaseDTO;
use App\Interfaces\DTO\SearchResultDto;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment;
use Aptive\PestRoutesSDK\Resources\Documents\Document;
use Illuminate\Support\Collection;

/**
 * @implements SearchResultDto<Appointment>
 */
class SearchAppointmentsResultDTO extends BaseDTO implements SearchResultDto
{
    /**
     * @param Collection<int, Appointment> $appointments
     * @param Collection<int, Document> $documents
     */
    public function __construct(
        public readonly Collection $appointments,
        public readonly Collection $documents,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getObjectsCollection(): Collection
    {
        return $this->appointments;
    }
}
