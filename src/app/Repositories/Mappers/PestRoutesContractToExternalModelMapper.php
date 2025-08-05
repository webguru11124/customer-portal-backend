<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\AbstractExternalModel;
use App\Models\External\ContractModel;
use Aptive\PestRoutesSDK\Resources\Contracts\Contract;

/**
 * @implements ExternalModelMapper<Contract, ContractModel>
 */
class PestRoutesContractToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Contract $source
     *
     * @return ContractModel
     */
    public function map(object $source): AbstractExternalModel
    {
        return ContractModel::from((array) $source);
    }
}
