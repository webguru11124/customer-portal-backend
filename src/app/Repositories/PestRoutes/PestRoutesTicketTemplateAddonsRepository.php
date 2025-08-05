<?php

namespace App\Repositories\PestRoutes;

use App\DTO\Ticket\CreateTicketTemplatesAddonRequestDTO;
use App\Interfaces\Repository\TicketTemplateAddonRepository;
use App\Models\External\TicketTemplateAddonModel;
use App\Repositories\Mappers\PestRoutesTicketTemplateAddonsToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\OfficeParametersFactory;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Aptive\PestRoutesSDK\Resources\Tickets\Params\CreateTicketsAddonParams;
use Aptive\PestRoutesSDK\Resources\Tickets\TicketAddon;
use Illuminate\Support\Collection;

/**
 * @extends AbstractPestRoutesRepository<TicketTemplateAddonModel, TicketAddon>
 */
class PestRoutesTicketTemplateAddonsRepository extends AbstractPestRoutesRepository implements TicketTemplateAddonRepository
{
    /**
     * @use EntityMapperAware<TicketAddon, TicketTemplateAddonModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    public function __construct(
        PestRoutesTicketTemplateAddonsToExternalModelMapper $entityMapper,
        OfficeParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    public function createTicketsAddon(CreateTicketTemplatesAddonRequestDTO $requestDTO): int
    {
        return $this->getPestRoutesClient()
            ->office($this->getOfficeId())
            ->tickets()
            ->addons()
            ->create(new CreateTicketsAddonParams(
                ticketId: $requestDTO->ticketId,
                description: $requestDTO->description,
                quantity: $requestDTO->quantity,
                amount: $requestDTO->amount,
                isTaxable: $requestDTO->isTaxable,
                creditTo: $requestDTO->creditTo,
                productId: $requestDTO->productId,
                serviceId: $requestDTO->serviceId,
                unitId: $requestDTO->unitId,
                officeId: $requestDTO->officeId,
            ));
    }

    protected function findManyNative(int ...$id): Collection
    {
        // There is no search option for ticket addons. Should be rewritten or left as stub.
        return new Collection();
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        // There is no search option for ticket addons. Should be rewritten or left as stub.
        return $officesResource;
    }
}
