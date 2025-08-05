<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\FormRepository;
use App\Models\External\FormModel;
use Tests\TestCase;

class FormModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(FormRepository::class, FormModel::getRepositoryClass());
    }
}
