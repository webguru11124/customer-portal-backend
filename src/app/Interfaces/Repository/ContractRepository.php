<?php

declare(strict_types=1);

namespace App\Interfaces\Repository;

use App\DTO\Contract\SearchContractsDTO;
use App\Models\External\ContractModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Contracts\Contract;
use Illuminate\Support\Collection;

/**
 * @extends ExternalRepository<ContractModel>
 */
interface ContractRepository extends ExternalRepository
{
    public function searchDocuments(SearchContractsDTO $searchContractsDTO): mixed;

    /**
     * @return Collection<int, Contract>
     */
    public function getDocuments(SearchContractsDTO $searchContractsDTO): Collection;

    /**
     * @param int $officeId
     * @param int $documentId
     *
     * @return Contract
     *
     * @throws ResourceNotFoundException
     * @throws InternalServerErrorHttpException
     */
    public function getDocument(int $officeId, int $documentId): Contract;
}
