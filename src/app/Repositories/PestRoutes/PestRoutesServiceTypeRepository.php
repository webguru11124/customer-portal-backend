<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\ServiceType\SearchServiceTypesDTO;
use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Models\External\ServiceTypeModel;
use App\Repositories\Mappers\PestRoutesServiceTypeToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\ServiceTypeParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * @extends AbstractPestRoutesRepository<ServiceTypeModel, ServiceType>
 */
class PestRoutesServiceTypeRepository extends AbstractPestRoutesRepository implements ServiceTypeRepository
{
    use LoggerAwareTrait;
    /**
     * @use EntityMapperAware<ServiceType, ServiceTypeModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    protected bool $denyLazyLoad = true;

    public function __construct(
        PestRoutesServiceTypeToExternalModelMapper $entityMapper,
        ServiceTypeParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    /**
     * @return Collection<int, ServiceType>
     *
     * @throws InternalServerErrorHttpException
     * @throws OfficeNotSetException
     * @throws InvalidSearchedResourceException
     * @throws ValidationException
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchServiceTypesDTO(
            ids: $id,
            officeIds: [$this->getOfficeId()]
        );

        return $this->searchNative($searchDto);
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->serviceTypes();
    }
}
