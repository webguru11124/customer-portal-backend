<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Ticket\CreateTicketTemplatesAddonRequestDTO;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Repositories\Mappers\PestRoutesTicketTemplateAddonsToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\OfficeParametersFactory;
use App\Repositories\PestRoutes\PestRoutesTicketTemplateAddonsRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Client;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Tickets\TicketAddon;
use Aptive\PestRoutesSDK\Resources\Tickets\TicketAddonsResource;
use Aptive\PestRoutesSDK\Resources\Tickets\TicketsResource;
use Tests\Data\TicketTemplateAddonData;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

final class PestRoutesTicketTemplateAddonsRepositoryTest extends GenericRepositoryWithoutSearchTest
{
    use RandomIntTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractExternalRepository;

    protected PestRoutesTicketTemplateAddonsRepository $ticketAddonsRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->ticketAddonsRepository = new PestRoutesTicketTemplateAddonsRepository(
            new PestRoutesTicketTemplateAddonsToExternalModelMapper(),
            new OfficeParametersFactory(),
        );
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->ticketAddonsRepository;
    }

    public function test_it_create_ticket_addons(): void
    {
        /** @var $ticketAddon TicketAddon */
        $ticketAddon = TicketTemplateAddonData::getTestEntityData(1, [
            'itemID' => $this->getTestTicketAddonsId(),
        ])->first();

        $ticketAddonResource = \Mockery::mock(TicketAddonsResource::class);
        $ticketAddonResource
            ->shouldReceive('create')
            ->andReturn($ticketAddon->id);

        $ticketResource = \Mockery::mock(TicketsResource::class);
        $ticketResource
            ->shouldReceive('addons')
            ->andReturn($ticketAddonResource);

        $officeResource = \Mockery::mock(OfficesResource::class);
        $officeResource
            ->shouldReceive('tickets')
            ->andReturn($ticketResource);

        $pestRoutesClientMock = \Mockery::mock(Client::class);
        $pestRoutesClientMock
            ->shouldReceive('office')
            ->withArgs([$this->getTestOfficeId()])
            ->andReturn($officeResource);

        $this->getSubject()->setPestRoutesClient($pestRoutesClientMock);

        self::assertEquals(
            $this->getTestTicketAddonsId(),
            $this
                ->getSubject()
                ->office($this->getTestOfficeId())
                ->createTicketsAddon($this->getTestCreateTicketsAddonRequestDTO())
        );
    }

    public function test_create_throws_internal_server_error_http_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new InternalServerErrorHttpException())
            ->mock();

        $this->getSubject()->office($this->getTestOfficeId())->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(InternalServerErrorHttpException::class);

        $this->getSubject()->createTicketsAddon($this->getTestCreateTicketsAddonRequestDTO());
    }

    public function test_create_throws_office_not_set_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new OfficeNotSetException())
            ->mock();

        $this->getSubject()->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(OfficeNotSetException::class);

        $this->getSubject()->createTicketsAddon($this->getTestCreateTicketsAddonRequestDTO());
    }

    private function getTestCreateTicketsAddonRequestDTO(): CreateTicketTemplatesAddonRequestDTO
    {
        return new CreateTicketTemplatesAddonRequestDTO(
            ticketId: $this->getTestTicketId(),
            description: 'Test Description',
            quantity: 1,
            amount: 199,
            isTaxable: true,
            creditTo: 0,
            officeId: $this->getTestOfficeId()
        );
    }
}
