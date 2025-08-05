<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\OfficeRepository;
use App\Models\External\OfficeModel;
use PHPUnit\Framework\TestCase;

class OfficeModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(OfficeRepository::class, OfficeModel::getRepositoryClass());
    }
}
