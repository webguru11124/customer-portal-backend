<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes;

use App\DTO\Form\SearchFormsDTO;
use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\FormRepository;
use App\Models\External\FormModel;
use App\Repositories\Mappers\PestRoutesFormToExternalModelMapper;
use App\Repositories\PestRoutes\ParametersFactories\FormParametersFactory;
use App\Services\LoggerAwareTrait;
use App\Services\PestRoutesClientAwareTrait;
use App\Traits\Repositories\EntityMapperAware;
use App\Traits\Repositories\HttpParametersFactoryAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Forms\Form;
use Aptive\PestRoutesSDK\Resources\Forms\FormDocumentState;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Resource;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * @extends AbstractPestRoutesRepository<FormModel, Form>
 */
class PestRoutesFormRepository extends AbstractPestRoutesRepository implements FormRepository
{
    use PestRoutesClientAwareTrait;
    use LoggerAwareTrait;
    /**
     * @use EntityMapperAware<Form, FormModel>
     */
    use EntityMapperAware;
    use HttpParametersFactoryAware;

    public function __construct(
        PestRoutesFormToExternalModelMapper $entityMapper,
        FormParametersFactory $httpParametersFactory
    ) {
        $this->entityMapper = $entityMapper;
        $this->httpParametersFactory = $httpParametersFactory;

        parent::__construct();
    }

    /**
     * @param SearchFormsDTO $searchFormsDTO
     *
     * @return Collection<int, Form>
     *
     * @throws InternalServerErrorHttpException
     */
    public function searchDocuments(SearchFormsDTO $searchFormsDTO): Collection
    {
        $documents = $this->getPestRoutesClient()
            ->office($searchFormsDTO->officeId)
            ->forms()
            ->includeData()
            ->search($this->httpParametersFactory->createSearch($searchFormsDTO))
            ->all();

        return new Collection($documents->items);
    }

    /**
     * @inheritdoc
     */
    public function getDocuments(SearchFormsDTO $searchFormsDTO): Collection
    {
        return $this
            ->searchDocuments($searchFormsDTO)
            ->filter(
                fn (Form $document) => null !== $document->documentLink &&
                    $document->documentState === FormDocumentState::COMPLETED
            );
    }

    /**
     * @inheritdoc
     */
    public function getDocument(int $officeId, int $documentId): Form
    {
        return $this
            ->getPestRoutesClient()
            ->office($officeId)
            ->forms()
            ->find($documentId, true);
    }

    /**
     * @return Collection<int, Form>
     *
     * @throws InternalServerErrorHttpException
     * @throws InvalidSearchedResourceException
     * @throws ValidationException
     * @throws OfficeNotSetException
     */
    protected function findManyNative(int ...$id): Collection
    {
        $searchDto = new SearchFormsDTO(
            officeId: $this->getOfficeId(),
            formIds: $id
        );

        return $this->searchNative($searchDto);
    }

    protected function getSearchedResource(OfficesResource $officesResource): Resource
    {
        return $officesResource->forms();
    }
}
