<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\External\SubscriptionModel;
use Tests\TestCase;

class SubscriptionModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(SubscriptionRepository::class, SubscriptionModel::getRepositoryClass());
    }
}
