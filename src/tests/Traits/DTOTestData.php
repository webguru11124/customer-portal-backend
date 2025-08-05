<?php

namespace Tests\Traits;

use App\DTO\Contract\SearchContractsDTO;
use App\DTO\Document\SearchDocumentsDTO;
use App\DTO\Form\SearchFormsDTO;

trait DTOTestData
{
    use RandomIntTestData;

    protected function getTestSearchDocumentDto(): SearchDocumentsDTO
    {
        return SearchDocumentsDTO::from([
            'officeId' => $this->getTestOfficeId(),
            'accountNumber' => $this->getTestAccountNumber(),
            'appointmentIds' => [$this->getTestAppointmentId()],
        ]);
    }

    protected function getTestSearchContractsDto(): SearchContractsDTO
    {
        return SearchContractsDTO::from([
            'officeId' => $this->getTestOfficeId(),
            'accountNumbers' => [$this->getTestAccountNumber()],
        ]);
    }

    protected function getTestSearchFormsDto(): SearchFormsDTO
    {
        return SearchFormsDTO::from([
            'officeId' => $this->getTestOfficeId(),
            'accountNumber' => $this->getTestAccountNumber(),
        ]);
    }
}
