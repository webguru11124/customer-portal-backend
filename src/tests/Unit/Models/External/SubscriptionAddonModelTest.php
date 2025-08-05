<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\SubscriptionAddonRepository;
use App\Models\External\SubscriptionAddonModel;
use Tests\TestCase;

final class SubscriptionAddonModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(SubscriptionAddonRepository::class, SubscriptionAddonModel::getRepositoryClass());
    }
}
