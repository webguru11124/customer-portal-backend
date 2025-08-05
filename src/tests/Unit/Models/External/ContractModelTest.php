<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\ContractRepository;
use App\Models\External\ContractModel;
use Tests\TestCase;

class ContractModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(ContractRepository::class, ContractModel::getRepositoryClass());
    }
}
