<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\SpotModel;
use Aptive\PestRoutesSDK\Resources\Spots\Spot;

/**
 * @implements ExternalModelMapper<Spot, SpotModel>
 */
class PestRoutesSpotToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Spot $source
     *
     * @return SpotModel
     */
    public function map(object $source): SpotModel
    {
        return SpotModel::from((array) $source);
    }
}
