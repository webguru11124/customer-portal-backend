<?php

declare(strict_types=1);

namespace App\DTO\Ticket;

final class CreateTicketTemplatesAddonRequestDTO
{
    public function __construct(
        public readonly int $ticketId,
        public readonly string $description,
        public readonly int $quantity,
        public readonly float $amount,
        public readonly bool $isTaxable,
        public readonly int $creditTo,
        public readonly int $productId = 0,
        public readonly int $serviceId = 0,
        public readonly int|null $unitId = null,
        public readonly int|null $officeId = null,
    ) {
    }
}
