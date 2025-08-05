<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\GenericFlagAssignmentRepository;
use App\Models\External\GenericFlagAssignmentModel;
use Tests\TestCase;

final class GenericFlagAssignmentModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(GenericFlagAssignmentRepository::class, GenericFlagAssignmentModel::getRepositoryClass());
    }
}
