<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Ticket\SearchTicketsDTO;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Models\External\TicketModel;
use App\Repositories\Mappers\PestRoutesTicketToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\TicketParametersFactory;
use App\Repositories\PestRoutes\PestRoutesTicketRepository;
use Aptive\PestRoutesSDK\Collection as PestRoutesSDKCollection;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Filters\DateFilter;
use Aptive\PestRoutesSDK\Resources\Tickets\Params\SearchTicketsParams;
use Aptive\PestRoutesSDK\Resources\Tickets\Ticket;
use Aptive\PestRoutesSDK\Resources\Tickets\TicketsResource;
use Illuminate\Support\Collection;
use Tests\Data\TicketData;
use Tests\TestCase;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

class PestRoutesTicketRepositoryTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;
    use RandomIntTestData;
    use ExtendsAbstractPestRoutesRepository;
    use ExtendsAbstractExternalRepository;

    protected $dto;
    protected PestRoutesTicketRepository $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->dto = SearchTicketsDTO::from([
            'officeId' => $this->getTestOfficeId(),
            'accountNumber' => $this->getTestAccountNumber(),
            'dueOnly' => true,
            'ids' => [$this->getTestTicketId()],
        ]);

        $modelMapper = new PestRoutesTicketToExternalModelMapper();
        $parametersFactory = new TicketParametersFactory();

        $this->subject = new PestRoutesTicketRepository($modelMapper, $parametersFactory);
    }

    private function getSubject(): AbstractPestRoutesRepository
    {
        return $this->subject;
    }

    public function test_it_searches_all_tickets(): void
    {
        $ticketsCollection = TicketData::getTestData(3);
        $pestRoutesClientOutcome = new PestRoutesSDKCollection($ticketsCollection->toArray());

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->dto->officeId)
            ->resource(TicketsResource::class)
            ->callSequense('tickets', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchTicketsParams $params) {
                    $array = $params->toArray();

                    return $array['ticketIDs'] === $this->dto->ids
                        && $array['officeIDs'] === [$this->dto->officeId]
                        && $array['customerIDs'] === [$this->dto->accountNumber]
                        && $array['balance']->jsonSerialize() === '{"operator":">","value":"0"}'
                        && $array['invoiceDate'] instanceof DateFilter;
                }
            )
            ->willReturn($pestRoutesClientOutcome)
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $searchResult = $this->subject
            ->office($this->dto->officeId)
            ->search($this->dto);

        self::assertInstanceOf(Collection::class, $searchResult);
        self::assertCount($ticketsCollection->count(), $searchResult);
    }

    public function test_it_finds_single_ticket(): void
    {
        /** @var Ticket $ticket */
        $ticket = TicketData::getTestData()->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->resource(TicketsResource::class)
            ->callSequense('tickets', 'find')
            ->methodExpectsArgs('find', [$ticket->id])
            ->willReturn($ticket)
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        /** @var TicketModel $result */
        $result = $this->subject
            ->office($ticket->officeId)
            ->find($ticket->id);

        self::assertEquals($ticket->id, $result->id);
    }

    public function test_get_ticket_throws_entity_not_found_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(TicketsResource::class)
            ->callSequense('tickets', 'find')
            ->willThrow(new ResourceNotFoundException())
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(EntityNotFoundException::class);

        $this->subject
            ->office($this->getTestOfficeId())
            ->find($this->getTestTicketId());
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestTicketId(),
            $this->getTestTicketId() + 1,
        ];

        /** @var Collection<int, Ticket> $tickets */
        $tickets = TicketData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(TicketsResource::class)
            ->callSequense('tickets', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchTicketsParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === [$this->getTestOfficeId()]
                        && $array['ticketIDs'] === $ids;
                }
            )
            ->willReturn(new PestRoutesSDKCollection($tickets->all()))
            ->mock();

        $this->subject->setPestRoutesClient($clientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($tickets->count(), $result);
    }
}
