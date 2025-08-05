<?php

namespace App\Repositories\PestRoutes;

use App\DTO\Ticket\SearchTicketsDTO;
use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\TicketRepository;
use App\Models\External\TicketModel;
use App\Repositories\Mappers\PestRoutesTicketToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\TicketParametersFactory;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Aptive\PestRoutesSDK\Resources\Tickets\Ticket;
use Illuminate\Support\Collection;

/**
 * Handle PestRoutes' tickets related API calls.
 *
 * @extends AbstractPestRoutesRepository<TicketModel, Ticket>
 */
class PestRoutesTicketRepository extends AbstractPestRoutesRepository implements TicketRepository
{
    /**
     * @use EntityMapperAware<Ticket, TicketModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    public function __construct(
        PestRoutesTicketToExternalModelMapper $entityMapper,
        TicketParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    /**
     * @return Collection<int, Ticket>
     *
     * @throws InternalServerErrorHttpException
     * @throws InvalidSearchedResourceException
     * @throws OfficeNotSetException
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchTicketsDTO(
            officeId: $this->getOfficeId(),
            ids: $id
        );

        /** @var Collection<int, Ticket> $result */
        $result = $this->searchNative($searchDto);

        return $result;
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->tickets();
    }
}
