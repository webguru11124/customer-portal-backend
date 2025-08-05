<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\TicketModel;
use Aptive\PestRoutesSDK\Resources\Tickets\Ticket;

/**
 * @implements ExternalModelMapper<Ticket, TicketModel>
 */
class PestRoutesTicketToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Ticket $source
     *
     * @return TicketModel
     */
    public function map(object $source): TicketModel
    {
        return TicketModel::from((array) $source);
    }
}
