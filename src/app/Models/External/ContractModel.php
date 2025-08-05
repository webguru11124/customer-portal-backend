<?php

declare(strict_types=1);

namespace App\Models\External;

use App\Interfaces\Repository\ContractRepository;
use App\Interfaces\Repository\ExternalRepository;
use Aptive\PestRoutesSDK\Resources\Contracts\ContractDocumentState;

class ContractModel extends AbstractExternalModel
{
    public int $id;

    /**
     * @var array<int|string, int>
     */
    public array $contractIds = [];

    /**
     * @var array<int|string, int>
     */
    public array $customerIds = [];

    /**
     * @var array<int|string, int>
     */
    public array $subscriptionIds = [];
    public \DateTimeInterface|null $dateSigned = null;
    public \DateTimeInterface|null $dateAdded = null;
    public ContractDocumentState|null $documentState = null;
    public string|null $description = null;
    public string|null $documentLink = null;

    /**
     * @return class-string<ExternalRepository<self>>
     */
    public static function getRepositoryClass(): string
    {
        return ContractRepository::class;
    }
}
