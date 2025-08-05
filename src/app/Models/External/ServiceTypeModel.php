<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\ExternalRepository;
use App\Interfaces\Repository\ServiceTypeRepository;

class ServiceTypeModel extends AbstractExternalModel
{
    public int $id;
    public int $officeId;
    public string $description;
    public int $frequency;
    public float $defaultCharge;
    public string $category;
    public bool $isReservice;
    public int $defaultLength;
    public float|null $defaultInitialCharge;
    public int|null $initialId;
    public float $minimumRecurringCharge;
    public float $minimumInitialCharge;
    public bool $isRegular;
    public bool $isInitial;
    public int|null $glAccountId;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return ServiceTypeRepository::class;
    }
}
