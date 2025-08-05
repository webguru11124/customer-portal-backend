<?php

declare(strict_types=1);

namespace App\Interfaces\Repository;

use App\Exceptions\Entity\EntityNotFoundException;
use App\Models\External\EmployeeModel;

/**
 * @extends ExternalRepository<EmployeeModel>
 */
interface EmployeeRepository extends ExternalRepository
{
    /**
     * @throws EntityNotFoundException
     */
    public function findCxpScheduler(): EmployeeModel;
}
