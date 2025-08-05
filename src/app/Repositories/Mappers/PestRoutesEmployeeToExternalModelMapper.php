<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\EmployeeModel;
use Aptive\PestRoutesSDK\Resources\Employees\Employee;

/**
 * @implements ExternalModelMapper<Employee, EmployeeModel>
 */
class PestRoutesEmployeeToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Employee $source
     *
     * @return EmployeeModel
     */
    public function map(object $source): EmployeeModel
    {
        return EmployeeModel::from((array) $source);
    }
}
