<?php

declare(strict_types=1);

namespace App\Actions\Appointment;

use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\EmployeeRepository;

trait GetCxpSchedulerId
{
    private function getCxpSchedulerId(int $officeId): int|null
    {
        try {
            $employeeId = $this->getEmployeeRepository()
                ->office($officeId)
                ->findCxpScheduler()
                ->id;
        } catch (EntityNotFoundException) {
            $employeeId = null;
        }

        return $employeeId;
    }

    abstract private function getEmployeeRepository(): EmployeeRepository;
}
