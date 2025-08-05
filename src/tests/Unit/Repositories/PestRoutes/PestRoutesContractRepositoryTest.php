<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\Models\External\ContractModel;
use App\Repositories\Mappers\PestRoutesContractToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\ContractParametersFactory;
use App\Repositories\PestRoutes\PestRoutesContractRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Collection as PestRoutesSDKCollection;
use Aptive\PestRoutesSDK\Converters\PestRoutesTypesConverter;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Contracts\Contract;
use Aptive\PestRoutesSDK\Resources\Contracts\ContractDocumentState;
use Aptive\PestRoutesSDK\Resources\Contracts\ContractsResource;
use Aptive\PestRoutesSDK\Resources\Contracts\Params\SearchContractsParams;
use Illuminate\Support\Collection as LaravelCollection;
use Tests\Data\ContractData;
use Tests\TestCase;
use Tests\Traits\DTOTestData;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

class PestRoutesContractRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use DTOTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractExternalRepository;

    protected PestRoutesContractRepository $documentRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->documentRepository = new PestRoutesContractRepository(
            new PestRoutesContractToExternalModelMapper(),
            new ContractParametersFactory()
        );
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->documentRepository;
    }

    public function test_it_searches_contracts(): void
    {
        $documentsCollection = ContractData::getTestData(2);
        $pestRoutesClientOutcome = new PestRoutesSDKCollection($documentsCollection->toArray());

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(ContractsResource::class)
            ->callSequense('contracts', 'includeData', 'search', 'all')
            ->willReturn($pestRoutesClientOutcome)
            ->mock();

        $this->getSubject()->setPestRoutesClient($pestRoutesClientMock);

        $searchResult = $this->getSubject()->searchDocuments($this->getTestSearchContractsDto());

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

        $this->getSubject()->searchDocuments($this->getTestSearchContractsDto());
    }

    public function test_contracts_filters_out_contracts(): void
    {
        $documents = ContractData::getTestData(
            4,
            [
                'contractID' => 1,
            ],
            [
                'contractID' => 2,
                'documentLink' => null,
            ],
            [
                'contractID' => 3,
                'documentState' => ContractDocumentState::WIP->value
            ],
            [
                'contractID' => 4,
                'documentState' => ContractDocumentState::COMPLETED->value
            ],
        );

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(ContractsResource::class)
            ->methodExpectsArgs(
                'search',
                fn (SearchContractsParams $params) => $params->toArray() === [
                    'officeIDs' => [$this->getTestOfficeId()],
                    'customerIDs' => [$this->getTestAccountNumber()],
                    'includeData' => 0,
                    'includeDocumentLink' => PestRoutesTypesConverter::boolToString(true),
                ]
            )
            ->callSequense('contracts', 'includeData', 'search', 'all')
            ->willReturn(new PestRoutesSDKCollection($documents->all()))
            ->mock();

        $this->getSubject()->setPestRoutesClient($clientMock);

        $filteredDocuments = $this->getSubject()->getDocuments($this->getTestSearchContractsDto());

        $this->assertCount(2, $filteredDocuments);
        $this->assertSame(1, $filteredDocuments->first()->id);
        $this->assertSame(4, $filteredDocuments->last()->id);
    }

    public function test_get_contract_returns_contract(): void
    {
        $document = ContractData::getTestData()->first();
        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(ContractsResource::class)
            ->methodExpectsArgs('find', [$this->getTestDocumentId(), true])
            ->callSequense('contracts', 'find')
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
            ->resource(ContractsResource::class)
            ->methodExpectsArgs('find', [$this->getTestDocumentId(), true])
            ->callSequense('contracts', 'find')
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

    public function test_it_finds_single_contract(): void
    {
        /** @var ContractModel $contractModel */
        $contractModel = ContractData::getTestEntityData()->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(ContractsResource::class)
            ->callSequense('contracts', 'find')
            ->methodExpectsArgs('find', [$this->getTestDocumentId()])
            ->willReturn(
                ContractData::getTestData(
                    1,
                    ['contractID' => $contractModel->id]
                )->first()
            )
            ->mock();

        $this->getSubject()->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->getSubject()
            ->office($this->getTestOfficeId())
            ->find($this->getTestDocumentId());

        self::assertEquals($contractModel->id, $result->id);
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestAccountNumber(),
            $this->getTestAccountNumber() + 1,
        ];

        /** @var LaravelCollection<int, Contract> $documents */
        $documents = ContractData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(ContractsResource::class)
            ->callSequense('contracts', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchContractsParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === [$this->getTestOfficeId()] &&
                        $array['customerIDs'] === $ids &&
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
