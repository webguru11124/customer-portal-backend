<?php

declare(strict_types=1);

namespace App\Actions\Ticket;

use App\DTO\Ticket\SearchTicketsDTO;
use App\Interfaces\Repository\TicketRepository;
use App\Models\External\TicketModel;
use App\Services\LoggerAwareTrait;
use Illuminate\Support\Collection;

class ShowCustomersTicketsAction
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly TicketRepository $ticketRepository,
    ) {
    }

    /**
     * Searches appointment filtered by given params for given account.
     *
     * @return Collection<int, TicketModel>
     */
    public function __invoke(
        int $officeId,
        int $accountNumber,
        bool $dueOnly,
    ): Collection {
        $dto = new SearchTicketsDTO(
            officeId: $officeId,
            accountNumber: $accountNumber,
            dueOnly: $dueOnly
        );

        /** @var Collection<int, TicketModel> $ticketsCollection */
        $ticketsCollection = $this->ticketRepository
            ->office($officeId)
            ->withRelated(['appointment'])
            ->search($dto);

        return $ticketsCollection
            ->sort(fn (TicketModel $left, TicketModel $right) => $right->invoiceDate <=> $left->invoiceDate)
            ->values();
    }
}
