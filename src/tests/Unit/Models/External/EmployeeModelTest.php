<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\EmployeeRepository;
use App\Models\External\EmployeeModel;
use PHPUnit\Framework\TestCase;

class EmployeeModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(EmployeeRepository::class, EmployeeModel::getRepositoryClass());
    }
}
