<?php

namespace App\Http\Responses;

use App\Enums\Resources;
use App\Models\External\TicketModel;
use App\Traits\ObjectToResource;
use App\Traits\ValidateObjectClass;

class SearchTicketsResponse extends AbstractSearchResponse
{
    use ValidateObjectClass;
    use ObjectToResource;

    protected function getExpectedEntityClass(): string
    {
        return TicketModel::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::TICKET;
    }

    protected function additionalAttributes(): array
    {
        return [
            'appointmentDate' => fn (TicketModel $ticket) => $ticket->appointment?->start,
        ];
    }
}
