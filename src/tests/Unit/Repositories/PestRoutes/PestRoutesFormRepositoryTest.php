<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\Models\External\FormModel;
use App\Repositories\Mappers\PestRoutesFormToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\FormParametersFactory;
use App\Repositories\PestRoutes\PestRoutesFormRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Collection as PestRoutesSDKCollection;
use Aptive\PestRoutesSDK\Converters\PestRoutesTypesConverter;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Forms\Form;
use Aptive\PestRoutesSDK\Resources\Forms\FormDocumentState;
use Aptive\PestRoutesSDK\Resources\Forms\FormsResource;
use Aptive\PestRoutesSDK\Resources\Forms\Params\SearchFormsParams;
use Illuminate\Support\Collection as LaravelCollection;
use Tests\Data\FormData;
use Tests\TestCase;
use Tests\Traits\DTOTestData;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

class PestRoutesFormRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use DTOTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractExternalRepository;

    protected PestRoutesFormRepository $documentRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->documentRepository = new PestRoutesFormRepository(
            new PestRoutesFormToExternalModelMapper(),
            new FormParametersFactory()
        );
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->documentRepository;
    }

    public function test_it_searches_forms(): void
    {
        $documentsCollection = FormData::getTestData(2);
        $pestRoutesClientOutcome = new PestRoutesSDKCollection($documentsCollection->toArray());

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(FormsResource::class)
            ->callSequense('forms', 'includeData', 'search', 'all')
            ->willReturn($pestRoutesClientOutcome)
            ->mock();

        $this->getSubject()->setPestRoutesClient($pestRoutesClientMock);

        $searchResult = $this->getSubject()->searchDocuments($this->getTestSearchFormsDto());

        self::assertInstanceOf(LaravelCollection::class, $searchResult);
        self::assertCount($documentsCollection->count(), $searchResult);
    }

    public function test_search_throws_internal_server_error_http_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new InternalServerErrorHttpException())
            ->mock();

        $this->getSubject()->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(InternalServerErrorHttpException::class);

        $this->getSubject()->searchDocuments($this->getTestSearchFormsDto());
    }

    public function test_form_filters_out_forms(): void
    {
        $documents = FormData::getTestData(
            4,
            [
                'formID' => 1,
            ],
            [
                'formID' => 2,
                'documentLink' => null,
            ],
            [
                'formID' => 3,
                'documentState' => FormDocumentState::WIP->value
            ],
            [
                'formID' => 4,
                'documentState' => FormDocumentState::COMPLETED->value
            ],
        );

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(FormsResource::class)
            ->methodExpectsArgs(
                'search',
                fn (SearchFormsParams $params) => $params->toArray() === [
                    'officeIDs' => [$this->getTestOfficeId()],
                    'customerID' => $this->getTestAccountNumber(),
                    'includeData' => 0,
                    'includeDocumentLink' => PestRoutesTypesConverter::boolToString(true),
                ]
            )
            ->callSequense('forms', 'includeData', 'search', 'all')
            ->willReturn(new PestRoutesSDKCollection($documents->all()))
            ->mock();

        $this->getSubject()->setPestRoutesClient($clientMock);

        $filteredDocuments = $this->getSubject()->getDocuments($this->getTestSearchFormsDto());

        $this->assertCount(2, $filteredDocuments);
        $this->assertSame(1, $filteredDocuments->first()->id);
        $this->assertSame(4, $filteredDocuments->last()->id);
    }

    public function test_get_form_returns_form(): void
    {
        $document = FormData::getTestData()->first();
        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(FormsResource::class)
            ->methodExpectsArgs('find', [$this->getTestDocumentId(), true])
            ->callSequense('forms', 'find')
            ->willReturn($document)
            ->mock();

        $this->getSubject()->setPestRoutesClient($clientMock);

        $result = $this->getSubject()->getDocument($this->getTestOfficeId(), $this->getTestDocumentId());

        $this->assertSame($document, $result);
    }

    /**
     * @dataProvider getDocumentExceptionProvider
     */
    public function test_get_document_passes_exception(\Throwable $exception): void
    {
        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(FormsResource::class)
            ->methodExpectsArgs('find', [$this->getTestDocumentId(), true])
            ->callSequense('forms', 'find')
            ->willThrow($exception)
            ->mock();

        $this->getSubject()->setPestRoutesClient($clientMock);

        $this->expectException($exception::class);

        $this->getSubject()->getDocument($this->getTestOfficeId(), $this->getTestDocumentId());
    }

    public function getDocumentExceptionProvider(): iterable
    {
        yield 'Internal server error' => [new InternalServerErrorHttpException('Test')];
        yield 'Resource not found' => [new ResourceNotFoundException('Test')];
    }

    public function test_it_finds_single_form(): void
    {
        /** @var FormModel $formModel */
        $formModel = FormData::getTestEntityData()->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(FormsResource::class)
            ->callSequense('forms', 'find')
            ->methodExpectsArgs('find', [$this->getTestDocumentId()])
            ->willReturn(
                FormData::getTestData(
                    1,
                    ['formID' => $formModel->id]
                )->first()
            )
            ->mock();

        $this->getSubject()->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->getSubject()
            ->office($this->getTestOfficeId())
            ->find($this->getTestDocumentId());

        self::assertEquals($formModel->id, $result->id);
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestFormId(),
            $this->getTestFormId() + 1,
        ];

        /** @var LaravelCollection<int, Form> $documents */
        $documents = FormData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(FormsResource::class)
            ->callSequense('forms', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchFormsParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === [$this->getTestOfficeId()] &&
                        $array['formIDs'] === $ids &&
                        $array['includeData'] === 0 &&
                        $array['includeDocumentLink'] === PestRoutesTypesConverter::boolToString(true);
                }
            )
            ->willReturn(new PestRoutesSDKCollection($documents->all()))
            ->mock();

        $this->getSubject()->setPestRoutesClient($clientMock);

        $result = $this->getSubject()
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($documents->count(), $result);
    }
}
