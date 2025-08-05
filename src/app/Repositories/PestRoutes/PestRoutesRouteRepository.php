<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\Route\SearchRoutesDTO;
use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\RouteRepository;
use App\Models\External\RouteModel;
use App\Repositories\Mappers\PestRoutesRouteToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\RouteParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Aptive\PestRoutesSDK\Resources\Routes\Route;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * @extends AbstractPestRoutesRepository<RouteModel, Route>
 */
class PestRoutesRouteRepository extends AbstractPestRoutesRepository implements RouteRepository
{
    use LoggerAwareTrait;
    /**
     * @use EntityMapperAware<Route, RouteModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    public function __construct(
        PestRoutesRouteToExternalModelMapper $entityMapper,
        RouteParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    /**
     * @param int ...$id
     *
     * @return Collection<int, Route>
     *
     * @throws InternalServerErrorHttpException
     * @throws InvalidSearchedResourceException
     * @throws OfficeNotSetException
     * @throws ValidationException
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchRoutesDTO(
            officeId: $this->getOfficeId(),
            ids: $id
        );

        /** @var Collection<int, Route> $result */
        $result = $this->searchNative($searchDto);

        return $result;
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->routes();
    }
}
