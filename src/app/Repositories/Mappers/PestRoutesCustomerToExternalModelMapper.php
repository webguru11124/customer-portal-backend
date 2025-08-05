<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\AbstractExternalModel;
use App\Models\External\CustomerModel;
use Aptive\PestRoutesSDK\Resources\Customers\Customer;

/**
 * @implements ExternalModelMapper<Customer, CustomerModel>
 */
class PestRoutesCustomerToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Customer $source
     *
     * @return CustomerModel
     */
    public function map(object $source): AbstractExternalModel
    {
        return CustomerModel::from((array) $source);
    }
}
