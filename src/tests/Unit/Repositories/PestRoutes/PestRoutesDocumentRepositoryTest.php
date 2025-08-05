<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Document\SearchDocumentsDTO;
use App\Models\External\DocumentModel;
use App\Repositories\Mappers\PestRoutesDocumentToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\DocumentParametersFactory;
use App\Repositories\PestRoutes\PestRoutesDocumentRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Collection as PestRoutesSDKCollection;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Documents\Document;
use Aptive\PestRoutesSDK\Resources\Documents\DocumentsResource;
use Aptive\PestRoutesSDK\Resources\Documents\Params\SearchDocumentsParams;
use Illuminate\Support\Collection as LaravelCollection;
use Tests\Data\DocumentData;
use Tests\TestCase;
use Tests\Traits\DTOTestData;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

class PestRoutesDocumentRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use DTOTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractExternalRepository;

    protected PestRoutesDocumentRepository $documentRepository;

    public function setUp(): void
    {
        parent::setUp();

        $modelMapper = new PestRoutesDocumentToExternalModelMapper();
        $parametersFactory = new DocumentParametersFactory();

        $this->documentRepository = new PestRoutesDocumentRepository($modelMapper, $parametersFactory);
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->documentRepository;
    }

    public function test_it_searches_documents()
    {
        $documentsCollection = DocumentData::getTestData(2);
        $pestRoutesClientOutcome = new PestRoutesSDKCollection($documentsCollection->toArray());

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(DocumentsResource::class)
            ->callSequense('documents', 'search', 'all')
            ->willReturn($pestRoutesClientOutcome)
            ->mock();

        $this->documentRepository->setPestRoutesClient($pestRoutesClientMock);

        $searchResult = $this->documentRepository->searchDocuments($this->getTestSearchDocumentDto());

        self::assertInstanceOf(LaravelCollection::class, $searchResult);
        self::assertCount($documentsCollection->count(), $searchResult);
    }

    public function test_search_throws_internal_server_error_http_exception()
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new InternalServerErrorHttpException())
            ->mock();

        $this->documentRepository->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(InternalServerErrorHttpException::class);

        $this->documentRepository->searchDocuments($this->getTestSearchDocumentDto());
    }

    public function test_documents_passes_exception(): void
    {
        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new InternalServerErrorHttpException())
            ->mock();

        $this->documentRepository->setPestRoutesClient($clientMock);

        $this->expectException(InternalServerErrorHttpException::class);

        $this->documentRepository->getDocuments(
            new SearchDocumentsDTO(
                $this->getTestOfficeId(),
                $this->getTestAccountNumber()
            )
        );
    }

    public function test_documents_filters_out_lob_docs_and_hidden_from_customer(): void
    {
        $documents = DocumentData::getTestData(
            4,
            [
                'uploadID' => 1,
                'appointmentID' => '',
                'showCustomer' => '1',
                'documentLink' => 'https://s3.amazonaws.com/PestRoutes/lobLetters/ltr_143fb4419fa1fea0.pdf',
            ],
            [
                'uploadID' => 2,
                'appointmentID' => '1',
                'showCustomer' => '0',
                'documentLink' => 'https://s3.amazonaws.com/PestRoutes/lobLetters/ltr_243fb4419fa1fea0.pdf',
            ],
            [
                'uploadID' => 3,
                'appointmentID' => '',
                'showCustomer' => '0',
                'documentLink' => 'https://s3.amazonaws.com/PestRoutes/lobLetters/ltr_343fb4419fa1fea0.pdf',
            ],
            [
                'uploadID' => 4,
                'appointmentID' => '1',
                'showCustomer' => '1',
                'documentLink' => 'https://s3.amazonaws.com/PestRoutes/lobLetters/ltr_443fb4419fa1fea0.pdf',
            ],
            [
                'uploadID' => 5,
                'appointmentID' => '',
                'showCustomer' => '0',
                'documentLink' => 'https://s3.amazonaws.com/LOB/ltr_343fb4419fa1fea0',
            ],
        );

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(DocumentsResource::class)
            ->methodExpectsArgs(
                'search',
                fn (SearchDocumentsParams $params) => $params->toArray() === [
                    'officeIDs' => $this->getTestOfficeId(),
                    'customerID' => $this->getTestAccountNumber(),
                    'includeData' => 0,
                    'includeDocumentLink' => '1',
                ]
            )
            ->callSequense('documents', 'search', 'all')
            ->willReturn(new PestRoutesSDKCollection($documents->all()))
            ->mock();

        $this->documentRepository->setPestRoutesClient($clientMock);

        $filteredDocuments = $this->documentRepository->getDocuments(
            new SearchDocumentsDTO(
                $this->getTestOfficeId(),
                $this->getTestAccountNumber()
            )
        );

        $this->assertCount(4, $filteredDocuments);
        $this->assertSame(1, $filteredDocuments->first()->id);
        $this->assertSame(4, $filteredDocuments->last()->id);
    }

    public function test_get_document_returns_document(): void
    {
        $document = DocumentData::getTestData()->first();
        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(DocumentsResource::class)
            ->methodExpectsArgs('find', [$this->getTestDocumentId()])
            ->callSequense('documents', 'find')
            ->willReturn($document)
            ->mock();

        $this->documentRepository->setPestRoutesClient($clientMock);

        $result = $this->documentRepository
            ->getDocument($this->getTestOfficeId(), $this->getTestDocumentId());

        $this->assertSame($document, $result);
    }

    /**
     * @dataProvider getDocumentExceptionProvider
     */
    public function test_get_document_passes_exception(\Throwable $exception): void
    {
        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(DocumentsResource::class)
            ->methodExpectsArgs('find', [$this->getTestDocumentId()])
            ->callSequense('documents', 'find')
            ->willThrow($exception)
            ->mock();

        $this->documentRepository->setPestRoutesClient($clientMock);

        $this->expectException($exception::class);

        $this->documentRepository->getDocument($this->getTestOfficeId(), $this->getTestDocumentId());
    }

    public function getDocumentExceptionProvider(): iterable
    {
        yield 'Internal server error' => [new InternalServerErrorHttpException('Test')];
        yield 'Resource not found' => [new ResourceNotFoundException('Test')];
    }

    public function test_it_searches_by_appointment_id(): void
    {
        $documents = DocumentData::getTestData();
        $appointmentId = $this->getTestAppointmentId();

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(DocumentsResource::class)
            ->methodExpectsArgs(
                'search',
                fn (SearchDocumentsParams $params) => $params->toArray() === [
                        'officeID' => $this->getTestOfficeId(),
                        'appointmentIDs' => [$appointmentId],
                        'includeData' => 0,
                        'includeDocumentLink' => '1',
                    ]
            )
            ->callSequense('documents', 'includeData', 'search', 'all')
            ->willReturn(new PestRoutesSDKCollection($documents->all()))
            ->mock();

        $this->documentRepository->setPestRoutesClient($clientMock);

        $result = $this->documentRepository
            ->office($this->getTestOfficeId())
            ->searchBy('appointmentId', [$appointmentId]);

        $this->assertCount($documents->count(), $result);
    }

    public function test_it_finds_single_document()
    {
        /** @var DocumentModel $documentModel */
        $documentModel = DocumentData::getTestEntityData()->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(DocumentsResource::class)
            ->callSequense('documents', 'find')
            ->methodExpectsArgs('find', [$this->getTestDocumentId()])
            ->willReturn(
                DocumentData::getTestData(
                    1,
                    ['uploadID' => $documentModel->id]
                )->first()
            )
            ->mock();

        $this->documentRepository->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->documentRepository
            ->office($this->getTestOfficeId())
            ->find($this->getTestDocumentId());

        self::assertEquals($documentModel->id, $result->id);
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestDocumentId(),
            $this->getTestDocumentId() + 1,
        ];

        /** @var LaravelCollection<int, Document> $documents */
        $documents = DocumentData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(DocumentsResource::class)
            ->callSequense('documents', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchDocumentsParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeID'] === $this->getTestOfficeId()
                        && $array['uploadIDs'] === $ids;
                }
            )
            ->willReturn(new PestRoutesSDKCollection($documents->all()))
            ->mock();

        $this->documentRepository->setPestRoutesClient($clientMock);

        $result = $this->documentRepository
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($documents->count(), $result);
    }
}
