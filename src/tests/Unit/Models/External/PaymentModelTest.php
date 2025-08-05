<?php

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\PaymentRepository;
use App\Models\External\PaymentModel;
use PHPUnit\Framework\TestCase;

class PaymentModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertSame(PaymentRepository::class, PaymentModel::getRepositoryClass());
    }
}
