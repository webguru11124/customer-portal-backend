<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\Office\SearchOfficesDTO;
use App\Helpers\ConfigHelper;
use App\Interfaces\Repository\OfficeRepository;
use App\Models\External\OfficeModel;
use App\Repositories\Mappers\PestRoutesOfficeToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\OfficeParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\PestRoutesSDK\Resources\Offices\Office;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Illuminate\Support\Collection;

/**
 * @extends AbstractPestRoutesRepository<OfficeModel, Office>
 */
class PestRoutesOfficeRepository extends AbstractPestRoutesRepository implements OfficeRepository
{
    /**
     * @use EntityMapperAware<Office, OfficeModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;
    use LoggerAwareTrait;

    protected bool $denyLazyLoad = true;

    public function __construct(
        PestRoutesOfficeToExternalModelMapper $entityMapper,
        OfficeParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    /**
     * @param int $id
     *
     * @return Collection<int, Office>
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchOfficesDTO(ids: $id);

        return $this->searchNative($searchDto);
    }

    protected function getOfficeId(): int
    {
        return ConfigHelper::getGlobalOfficeId();
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource;
    }

    /**
     * @return int[]
     */
    public function getAllOfficeIds(): array
    {
        /** @var Collection<int, OfficeModel> $officeCollection */
        $officeCollection = $this->office(ConfigHelper::getGlobalOfficeId())
            ->search(new SearchOfficesDTO());

        return $officeCollection->map(fn (OfficeModel $office) => $office->id)->toArray();
    }
}
