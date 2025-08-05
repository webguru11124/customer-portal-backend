<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\SpotRepository;
use App\Models\External\SpotModel;
use Tests\TestCase;

class SpotModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(SpotRepository::class, SpotModel::getRepositoryClass());
    }
}
