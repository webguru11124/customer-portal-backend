<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Employee\SearchEmployeesDTO;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;

class EmployeeParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    /**
     * @param SearchEmployeesDTO $searchDto
     *
     * @return SearchEmployeesParams
     */
    public function createSearch(mixed $searchDto): SearchEmployeesParams
    {
        $this->validateInput(SearchEmployeesDTO::class, $searchDto);

        return new SearchEmployeesParams(
            officeIds: [$searchDto->officeId],
            isActive: true,
            employeeIds: $searchDto->ids,
            lastName: $searchDto->lname,
            firstName: $searchDto->fname
        );
    }
}
