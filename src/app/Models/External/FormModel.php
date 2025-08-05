<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\ExternalRepository;
use App\Interfaces\Repository\FormRepository;
use Aptive\PestRoutesSDK\Resources\Forms\FormDocumentState;

class FormModel extends AbstractExternalModel
{
    public int $id;
    public int $customerId;
    public \DateTimeInterface|null $dateSigned;
    public \DateTimeInterface|null $dateAdded;
    public int|null $unitId;
    public int $employeeId;
    public FormDocumentState $documentState;
    public int $formTemplateId;
    public string $formDescription;
    public string|null $documentLink = null;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return FormRepository::class;
    }
}
