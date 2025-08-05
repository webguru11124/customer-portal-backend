<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use Aptive\PestRoutesSDK\Http\AbstractHttpParams;

interface PestRoutesHttpParametersFactory
{
    public function createSearch(mixed $searchDto): AbstractHttpParams;
}
