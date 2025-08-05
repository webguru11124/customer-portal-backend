<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\Document\SearchDocumentsDTO;
use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Exceptions\Entity\RelationNotFoundException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\DocumentRepository;
use App\Models\External\DocumentModel;
use App\Repositories\Mappers\PestRoutesDocumentToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\DocumentParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Services\PestRoutesClientAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Documents\Document;
use Aptive\PestRoutesSDK\Resources\Documents\Params\SearchDocumentsParams;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Illuminate\Support\Collection;

/**
 * @extends AbstractPestRoutesRepository<DocumentModel, Document>
 */
class PestRoutesDocumentRepository extends AbstractPestRoutesRepository implements DocumentRepository
{
    use PestRoutesClientAwareTrait;
    use LoggerAwareTrait;
    /**
     * @use EntityMapperAware<Document, DocumentModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    public function __construct(
        PestRoutesDocumentToExternalModelMapper $entityMapper,
        DocumentParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    private const LOB_BUCKET_URI = 'https://s3.amazonaws.com/LOB/';

    /**
     * @param SearchDocumentsDTO $searchDocumentDTO
     *
     * @return Collection<int, Document>
     *
     * @throws InternalServerErrorHttpException
     */
    public function searchDocuments(SearchDocumentsDTO $searchDocumentDTO): Collection
    {
        $documents = $this->getPestRoutesClient()
            ->office($searchDocumentDTO->officeId)
            ->documents()
            ->search($this->buildSearchDocumentsParams($searchDocumentDTO))
            ->all();

        return new Collection($documents->items);
    }

    /**
     * @param SearchDocumentsDTO $searchDocumentDTO
     *
     * @return Collection<int, Document>
     */
    public function getDocuments(
        SearchDocumentsDTO $searchDocumentDTO
    ): Collection {
        return $this
            ->searchDocuments($searchDocumentDTO)
            ->filter(
                fn (Document $document) => !str_starts_with((string) $document->documentLink, self::LOB_BUCKET_URI)
            );
    }

    /**
     * @inheritdoc
     */
    public function getDocument(int $officeId, int $documentId): Document
    {
        return $this
            ->getPestRoutesClient()
            ->office($officeId)
            ->documents()
            ->find($documentId);
    }

    private function buildSearchDocumentsParams(SearchDocumentsDTO $searchDocumentDTO): SearchDocumentsParams
    {
        return new SearchDocumentsParams(
            officeIds: $searchDocumentDTO->officeId,
            customerId: $searchDocumentDTO->accountNumber,
            appointmentIds: $searchDocumentDTO->appointmentIds,
        );
    }

    /**
     * @return Collection<int, Document>
     *
     * @throws InternalServerErrorHttpException
     * @throws InvalidSearchedResourceException
     * @throws OfficeNotSetException
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchDocumentsDTO(
            officeId: $this->getOfficeId(),
            ids: $id
        );

        return $this->searchNative($searchDto);
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->documents();
    }

    /**
     * @param int[] $appointmentIds
     *
     * @return Collection<int, DocumentModel>
     *
     * @throws RelationNotFoundException
     */
    public function searchByAppointmentId(array $appointmentIds): Collection
    {
        $searchDto = new SearchDocumentsDTO(
            officeId: $this->getOfficeId(),
            appointmentIds: $appointmentIds
        );

        /** @var Collection<int, DocumentModel> $result */
        $result = $this->search($searchDto);

        return $result;
    }
}
