<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\ServiceTypeModel;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType;

/**
 * @implements ExternalModelMapper<ServiceType, ServiceTypeModel>
 */
class PestRoutesServiceTypeToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param ServiceType $source
     *
     * @return ServiceTypeModel
     */
    public function map(object $source): ServiceTypeModel
    {
        return ServiceTypeModel::from((array) $source);
    }
}
