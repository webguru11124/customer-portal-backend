<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\OfficeModel;
use Aptive\PestRoutesSDK\Resources\Offices\Office;

/**
 * @implements ExternalModelMapper<Office, OfficeModel>
 */
class PestRoutesOfficeToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Office $source
     *
     * @return OfficeModel
     */
    public function map(object $source): OfficeModel
    {
        return OfficeModel::from((array) $source);
    }
}
