<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Document;

use App\Actions\Document\DownloadActionV2;
use App\Enums\Resources;
use App\Exceptions\Document\DocumentLinkDoesNotExist;
use App\Interfaces\Repository\ContractRepository;
use App\Interfaces\Repository\DocumentRepository;
use App\Interfaces\Repository\FormRepository;
use App\Models\Account;
use Aptive\Component\Http\Exceptions\ForbiddenHttpException;
use Aptive\Component\Http\Exceptions\NotFoundHttpException;
use Aptive\PestRoutesSDK\Converters\PestRoutesTypesConverter;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Illuminate\Routing\Exceptions\StreamedResponseException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Data\ContractData;
use Tests\Data\DocumentData;
use Tests\Data\FormData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class DownloadActionV2Test extends TestCase
{
    use RandomIntTestData;

    private const TEST_FILE_HASH = 'ae75130a158fa601c76c86e67eaf2dbc0aa5b62b9838444d0053b3ede3f2e4a3';

    protected DocumentRepository|MockInterface|null $repositoryMock = null;
    protected ContractRepository|MockInterface|null $contractRepositoryMock = null;
    protected FormRepository|MockInterface|null $formRepositoryMock = null;
    protected DownloadActionV2 $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->documentRepositoryMock = Mockery::mock(DocumentRepository::class);
        $this->contractRepositoryMock = Mockery::mock(ContractRepository::class);
        $this->formRepositoryMock = Mockery::mock(FormRepository::class);
        $this->subject = new DownloadActionV2(
            $this->documentRepositoryMock,
            $this->contractRepositoryMock,
            $this->formRepositoryMock
        );
    }

    /**
     * @dataProvider provideRepositoryData
     */
    public function test_action_throws_not_found_when_repository_cannot_get_document(
        string $repositoryClass,
        string $documentType
    ): void {
        $this->getRelevantRepository($repositoryClass)
            ->expects('getDocument')
            ->withArgs([$this->getTestOfficeId(), $this->getTestDocumentId()])
            ->once()
            ->andThrow(new ResourceNotFoundException());

        $this->expectException(NotFoundHttpException::class);

        ($this->subject)($this->getAccount(), $this->getTestDocumentId(), $documentType);
    }

    /**
     * @dataProvider provideInvalidDocumentData
     */
    public function test_action_throws_exceptions(
        string $repositoryClass,
        string $documentType,
        Collection $collection,
        int $accountNumber,
        string $expectedException,
        string|null $expectedExceptionMessage = null
    ): void {
        $entity = $collection->first();
        $this->getRelevantRepository($repositoryClass)
            ->expects('getDocument')
            ->withArgs([$this->getTestOfficeId(), $this->getTestDocumentId()])
            ->once()
            ->andReturn($entity);

        $this->expectException($expectedException);

        if (null !== $expectedExceptionMessage) {
            $this->expectExceptionMessageMatches($expectedExceptionMessage);
        }

        ($this->subject)($this->getAccount($accountNumber), $this->getTestDocumentId(), $documentType);
    }

    /**
     * @dataProvider provideDocumentUnreachableLinkData
     */
    public function test_action_throws_exception_with_unreachable_url(
        string $repositoryClass,
        string $documentType,
        Collection $collection
    ): void {
        $entity = $collection->first();
        $this->getRelevantRepository($repositoryClass)
            ->expects('getDocument')
            ->withArgs([$this->getTestOfficeId(), $this->getTestDocumentId()])
            ->once()
            ->andReturn($entity);

        $customerId = $entity->customerId ?? current($entity->customerIds);
        $response = ($this->subject)($this->getAccount($customerId), $this->getTestDocumentId(), $documentType);

        $this->expectException(StreamedResponseException::class);

        $response->sendContent();
    }

    /**
     * @dataProvider provideDocumentStreamLinkData
     */
    public function test_action_returns_stream_download_response(
        string $repositoryClass,
        string $documentType,
        Collection $collection
    ): void {
        $entity = $collection->first();
        $this->getRelevantRepository($repositoryClass)
            ->expects('getDocument')
            ->withArgs([$this->getTestOfficeId(), $this->getTestDocumentId()])
            ->once()
            ->andReturn($entity);

        $customerId = $entity->customerId ?? current($entity->customerIds);
        $response = ($this->subject)($this->getAccount($customerId), $this->getTestDocumentId(), $documentType);
        $this->assertInstanceOf(StreamedResponse::class, $response);

        $this->assertSame('attachment; filename=test.pdf', $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $fileContent = ob_get_clean();

        $this->assertSame(self::TEST_FILE_HASH, hash('sha256', $fileContent));
    }

    private function getAccount(int|null $accountNumber = null): Account
    {
        return new Account([
            'account_number' => $accountNumber ?? $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
    }

    private function provideRepositoryData(): array
    {
        return [
            [
                DocumentRepository::class,
                Resources::DOCUMENT->value,
            ],
            [
                ContractRepository::class,
                Resources::CONTRACT->value,
            ],
            [
                FormRepository::class,
                Resources::FORM->value,
            ],
        ];
    }

    private function provideInvalidDocumentData(): iterable
    {
        $documentLink = 'https://x';
        $message = '/^Document "\d+" does not belong to customer "\d+"$/';

        yield 'should throw forbidden when account number does not match with document customer id' => [
            DocumentRepository::class,
            Resources::DOCUMENT->value,
            DocumentData::getTestData(1, [
                'customerID' => $this->getTestAccountNumber() + 1,
                'documentLink' => null,
            ]),
            $this->getTestAccountNumber(),
            ForbiddenHttpException::class,
            $message,
        ];

        yield 'should throw forbidden when account number does not match with contract customer ids' => [
            ContractRepository::class,
            Resources::CONTRACT->value,
            ContractData::getTestData(1, [
                'customerIDs' => implode(',', [$this->getTestAccountNumber() + 1]),
                'documentLink' => null,
            ]),
            $this->getTestAccountNumber(),
            ForbiddenHttpException::class,
            $message,
        ];

        yield 'should throw forbidden when account number does not match with form customer id' => [
            FormRepository::class,
            Resources::FORM->value,
            FormData::getTestData(1, [
                'customerID' => $this->getTestAccountNumber() + 1,
                'documentLink' => null,
            ]),
            $this->getTestAccountNumber(),
            ForbiddenHttpException::class,
            $message,
        ];

        yield 'should throw exception when document does not have link' => [
            DocumentRepository::class,
            Resources::DOCUMENT->value,
            DocumentData::getTestData(1, [
                'customerID' => $this->getTestAccountNumber(),
                'documentLink' => null,
            ]),
            $this->getTestAccountNumber(),
            DocumentLinkDoesNotExist::class,
        ];

        yield 'should throw exception when contract does not have link' => [
            ContractRepository::class,
            Resources::CONTRACT->value,
            ContractData::getTestData(1, [
                'customerIDs' => PestRoutesTypesConverter::arrayToString([$this->getTestAccountNumber()]),
                'documentLink' => null,
            ]),
            $this->getTestAccountNumber(),
            DocumentLinkDoesNotExist::class,
        ];

        yield 'should throw exception when form does not have link' => [
            FormRepository::class,
            Resources::FORM->value,
            FormData::getTestData(1, [
                'customerID' => $this->getTestAccountNumber(),
                'documentLink' => null,
            ]),
            $this->getTestAccountNumber(),
            DocumentLinkDoesNotExist::class,
        ];

        yield 'should throw exception for document without correct url' => [
            DocumentRepository::class,
            Resources::DOCUMENT->value,
            DocumentData::getTestData(1, [
                'customerID' => $this->getTestAccountNumber(),
                'documentLink' => $documentLink,
            ]),
            $this->getTestAccountNumber(),
            InvalidArgumentException::class,
        ];

        yield 'should throw exception for contract without correct url' => [
            ContractRepository::class,
            Resources::CONTRACT->value,
            ContractData::getTestData(1, [
                'customerIDs' => PestRoutesTypesConverter::arrayToString([$this->getTestAccountNumber()]),
                'documentLink' => $documentLink,
            ]),
            $this->getTestAccountNumber(),
            InvalidArgumentException::class,
        ];

        yield 'should throw exception for form without correct url' => [
            FormRepository::class,
            Resources::FORM->value,
            FormData::getTestData(1, [
                'customerID' => $this->getTestAccountNumber(),
                'documentLink' => $documentLink,
            ]),
            $this->getTestAccountNumber(),
            InvalidArgumentException::class,
        ];
    }

    private function provideDocumentUnreachableLinkData(): iterable
    {
        $documentLink = 'file:///no.pdf';
        yield 'should throw exception for document with unreachable link' => [
            DocumentRepository::class,
            Resources::DOCUMENT->value,
            DocumentData::getTestData(1, [
                'customerID' => $this->getTestAccountNumber(),
                'documentLink' => $documentLink,
            ]),
        ];

        yield 'should throw exception for contract with unreachable link' => [
            ContractRepository::class,
            Resources::CONTRACT->value,
            ContractData::getTestData(1, [
                'customerIDs' => PestRoutesTypesConverter::arrayToString([$this->getTestAccountNumber()]),
                'documentLink' => $documentLink,
            ]),
        ];

        yield 'should throw exception for form with unreachable link' => [
            FormRepository::class,
            Resources::FORM->value,
            FormData::getTestData(1, [
                'customerID' => $this->getTestAccountNumber(),
                'documentLink' => $documentLink,
            ]),
        ];
    }

    private function provideDocumentStreamLinkData(): iterable
    {
        $documentLink = 'file://' . realpath(__DIR__ . '/../../../Files/test.pdf');
        yield 'should return stream response for document' => [
            DocumentRepository::class,
            Resources::DOCUMENT->value,
            DocumentData::getTestData(1, [
                'customerID' => $this->getTestAccountNumber(),
                'documentLink' => $documentLink,
            ]),
        ];

        yield 'should return stream response for contract' => [
            ContractRepository::class,
            Resources::CONTRACT->value,
            ContractData::getTestData(1, [
                'customerIDs' => PestRoutesTypesConverter::arrayToString([$this->getTestAccountNumber()]),
                'documentLink' => $documentLink,
            ]),
        ];

        yield 'should return stream response for form' => [
            FormRepository::class,
            Resources::FORM->value,
            FormData::getTestData(1, [
                'customerID' => $this->getTestAccountNumber(),
                'documentLink' => $documentLink,
            ]),
        ];
    }

    private function getRelevantRepository(
        string $repositoryClass
    ): DocumentRepository|ContractRepository|FormRepository|MockInterface {
        return match ($repositoryClass) {
            DocumentRepository::class => $this->documentRepositoryMock,
            ContractRepository::class => $this->contractRepositoryMock,
            FormRepository::class => $this->formRepositoryMock,
        };
    }
}
