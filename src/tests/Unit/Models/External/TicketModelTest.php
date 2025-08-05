<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\TicketRepository;
use App\Models\External\TicketModel;
use PHPUnit\Framework\TestCase;

class TicketModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(TicketRepository::class, TicketModel::getRepositoryClass());
    }
}
