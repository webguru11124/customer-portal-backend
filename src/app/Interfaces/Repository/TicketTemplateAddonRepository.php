<?php

namespace App\Interfaces\Repository;

use App\DTO\Ticket\CreateTicketTemplatesAddonRequestDTO;
use App\Models\External\TicketTemplateAddonModel;

/**
 * @extends ExternalRepository<TicketTemplateAddonModel>
 */
interface TicketTemplateAddonRepository extends ExternalRepository
{
    public function createTicketsAddon(CreateTicketTemplatesAddonRequestDTO $requestDTO): int;
}
