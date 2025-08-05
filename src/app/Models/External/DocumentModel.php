<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\DocumentRepository;
use App\Interfaces\Repository\ExternalRepository;
use DateTimeInterface;

class DocumentModel extends AbstractExternalModel
{
    public int $id;
    public int $officeId;
    public int $customerId;
    public DateTimeInterface $dateAdded;
    public int $addedBy;
    public bool $showCustomer;
    public bool $showTech;
    public int $appointmentId;
    public string|null $prefix = null;
    public string|null $description = null;
    public string|null $documentLink = null;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return DocumentRepository::class;
    }
}
