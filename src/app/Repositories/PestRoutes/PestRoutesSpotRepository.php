<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\Spot\SearchSpotsDTO;
use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\SpotRepository;
use App\Models\External\SpotModel;
use App\Repositories\Mappers\PestRoutesSpotToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\SpotParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Services\PestRoutesClientAwareTrait;
use App\Traits\DateFilterAware;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Entity;
use Aptive\PestRoutesSDK\Exceptions\PestRoutesApiException;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Aptive\PestRoutesSDK\Resources\Spots\Spot;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * @extends AbstractPestRoutesRepository<SpotModel, Spot>
 */
class PestRoutesSpotRepository extends AbstractPestRoutesRepository implements SpotRepository
{
    use PestRoutesClientAwareTrait;
    use LoggerAwareTrait;
    use DateFilterAware;
    /**
     * @use EntityMapperAware<Spot, SpotModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    public function __construct(
        PestRoutesSpotToExternalModelMapper $entityMapper,
        SpotParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    /**
     * @return Collection<int, Spot>
     *
     * @throws InternalServerErrorHttpException
     * @throws InvalidSearchedResourceException
     * @throws OfficeNotSetException
     * @throws ValidationException
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchSpotsDTO(
            officeId: $this->getOfficeId(),
            ids: $id
        );

        /** @var Collection<int, Spot> $result */
        $result = $this->searchNative($searchDto);

        return $result;
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->spots();
    }

    protected function findNative(int $id): Entity|null
    {
        try {
            return parent::findNative($id);
        } catch (PestRoutesApiException) {
            return null;
        }
    }
}
