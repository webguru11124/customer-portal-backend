<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\GenericFlagAssignmentsRequestDTO;
use App\Interfaces\Repository\GenericFlagAssignmentRepository;
use App\Models\External\GenericFlagAssignmentModel;
use App\Repositories\Mappers\PestRoutesGenericFlagAssignmentToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\OfficeParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Services\PestRoutesClientAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\GenericFlagAssignment;
use Aptive\PestRoutesSDK\Resources\GenericFlagAssignments\Params\CreateGenericFlagAssignmentsParams;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Illuminate\Support\Collection;

/**
 * @extends AbstractPestRoutesRepository<GenericFlagAssignmentModel, GenericFlagAssignment>
 */
class PestRoutesGenericFlagAssignmentRepository extends AbstractPestRoutesRepository implements GenericFlagAssignmentRepository
{
    use PestRoutesClientAwareTrait;
    use LoggerAwareTrait;

    /**
     * @use EntityMapperAware<GenericFlagAssignment, GenericFlagAssignmentModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    public function __construct(
        PestRoutesGenericFlagAssignmentToExternalModelMapper $entityMapper,
        OfficeParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    public function assignGenericFlag(GenericFlagAssignmentsRequestDTO $assignmentsRequestDTO): int
    {
        return $this->getPestRoutesClient()
            ->office($this->getOfficeId())
            ->genericFlagAssignments()
            ->create(new CreateGenericFlagAssignmentsParams(
                genericFlagId: $assignmentsRequestDTO->genericFlagId,
                entityId: $assignmentsRequestDTO->entityId,
                type: $assignmentsRequestDTO->type
            ));
    }

    protected function findManyNative(int ...$id): Collection
    {
        // TODO: implement search for GenericFlagAssignments

        return new Collection();
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        // TODO: implement search for GenericFlagAssignments

        return $officesResource;
    }
}
