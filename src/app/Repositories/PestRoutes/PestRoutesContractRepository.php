<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\Contract\SearchContractsDTO;
use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\ContractRepository;
use App\Models\External\ContractModel;
use App\Repositories\Mappers\PestRoutesContractToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\ContractParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Services\PestRoutesClientAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Contracts\Contract;
use Aptive\PestRoutesSDK\Resources\Contracts\ContractDocumentState;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Illuminate\Support\Collection;

/**
 * @extends AbstractPestRoutesRepository<ContractModel, Contract>
 */
class PestRoutesContractRepository extends AbstractPestRoutesRepository implements ContractRepository
{
    use PestRoutesClientAwareTrait;
    use LoggerAwareTrait;
    /**
     * @use EntityMapperAware<Contract, ContractModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    public function __construct(
        PestRoutesContractToExternalModelMapper $entityMapper,
        ContractParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    /**
     * @param SearchContractsDTO $searchContractsDTO
     *
     * @return Collection<int, Contract>
     *
     * @throws InternalServerErrorHttpException
     */
    public function searchDocuments(SearchContractsDTO $searchContractsDTO): Collection
    {
        $documents = $this->getPestRoutesClient()
            ->office($searchContractsDTO->officeId)
            ->contracts()
            ->includeData()
            ->search($this->httpParametersFactory->createSearch($searchContractsDTO))
            ->all();

        return new Collection($documents->items);
    }

    /**
     * @inheritdoc
     */
    public function getDocuments(SearchContractsDTO $searchContractsDTO): Collection
    {
        return $this
            ->searchDocuments($searchContractsDTO)
            ->filter(
                fn (Contract $document) => null !== $document->documentLink &&
                    $document->documentState === ContractDocumentState::COMPLETED
            );
    }

    /**
     * @inheritdoc
     */
    public function getDocument(int $officeId, int $documentId): Contract
    {
        return $this
            ->getPestRoutesClient()
            ->office($officeId)
            ->contracts()
            ->find($documentId, true);
    }

    /**
     * @return Collection<int, Contract>
     *
     * @throws InternalServerErrorHttpException
     * @throws InvalidSearchedResourceException
     * @throws OfficeNotSetException
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchContractsDTO(
            officeId: $this->getOfficeId(),
            accountNumbers: $id,
        );

        return $this->searchNative($searchDto);
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->contracts();
    }
}
