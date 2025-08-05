<?php

declare(strict_types=1);

namespace App\Traits\Repositories;

use App\Repositories\PestRoutes\ParametersFactories\PestRoutesHttpParametersFactory;

trait HttpParametersFactoryAware
{
    protected PestRoutesHttpParametersFactory $httpParametersFactory;

    protected function getHttpParametersFactory(): PestRoutesHttpParametersFactory
    {
        return $this->httpParametersFactory;
    }
}
