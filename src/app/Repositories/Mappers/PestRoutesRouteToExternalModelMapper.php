<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\RouteModel;
use Aptive\PestRoutesSDK\Resources\Routes\Route;

/**
 * @implements ExternalModelMapper<Route, RouteModel>
 */
class PestRoutesRouteToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Route $source
     */
    public function map(object $source): RouteModel
    {
        return RouteModel::from((array) $source);
    }
}
