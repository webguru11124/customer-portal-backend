<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\TicketTemplateAddonRepository;
use App\Models\External\TicketTemplateAddonModel;
use Tests\TestCase;

final class TicketTemplateAddonModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(TicketTemplateAddonRepository::class, TicketTemplateAddonModel::getRepositoryClass());
    }
}
