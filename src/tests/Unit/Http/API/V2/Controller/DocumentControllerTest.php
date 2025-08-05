<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\Document\DownloadActionV2;
use App\Enums\Resources;
use App\Models\Account;
use App\Services\GenericDocumentService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Data\ContractData;
use Tests\Data\DocumentData;
use Tests\Data\FormData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;

final class DocumentControllerTest extends ApiTestCase
{
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    private const DOCUMENT_TYPE = 'Document';

    public MockInterface $documentServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->documentServiceMock = Mockery::mock(GenericDocumentService::class);
        $this->instance(GenericDocumentService::class, $this->documentServiceMock);
    }

    public function test_get_customer_document_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getCustomerDocumentsJsonResponse($this->getTestAccountNumber())
        );
    }

    public function test_get_customer_document_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->getCustomerDocumentsJsonResponse($this->getTestAccountNumber())
            ->assertNotFound();
    }

    public function test_get_customer_documents_returns_error_when_account_number_does_not_belong_to_user(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->documentServiceMock
            ->expects('getDocumentsForAccount')
            ->withAnyArgs()
            ->never();

        $this->getCustomerDocumentsJsonResponse($this->getTestAccountNumber() + 1)
            ->assertNotFound();
    }

    public function test_get_customer_documents_returns_error_on_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->documentServiceMock
            ->expects('getDocumentsForAccount')
            ->withAnyArgs()
            ->once()
            ->andThrow(new InternalServerErrorHttpException());

        $this->getCustomerDocumentsJsonResponse($this->getTestAccountNumber())
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_get_customer_documents_return_collection(): void
    {
        $docsCollection = DocumentData::getTestData(2);
        $contractsCollection = ContractData::getTestData(2);
        $formsCollection = FormData::getTestData(2);

        $this->createAndLogInAuth0UserWithAccount();

        $this->documentServiceMock
            ->expects('getDocumentsForAccount')
            ->withAnyArgs()
            ->once()
            ->andReturn($docsCollection->merge($contractsCollection)->merge($formsCollection));

        $this->getCustomerDocumentsJsonResponse($this->getTestAccountNumber())
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('links.self', sprintf('/api/v2/customer/%d/documents', $this->getTestAccountNumber()))
                    ->where('data.0.type', Resources::DOCUMENT->value)
                    ->where('data.0.id', (string) $docsCollection[0]->id)
                    ->where('data.1.type', Resources::DOCUMENT->value)
                    ->where('data.1.id', (string) $docsCollection[1]->id)
                    ->where('data.2.type', Resources::CONTRACT->value)
                    ->where('data.2.id', (string) $contractsCollection[0]->id)
                    ->where('data.3.type', Resources::CONTRACT->value)
                    ->where('data.3.id', (string) $contractsCollection[1]->id)
                    ->where('data.4.type', Resources::FORM->value)
                    ->where('data.4.id', (string) $formsCollection[0]->id)
                    ->where('data.5.type', Resources::FORM->value)
                    ->where('data.5.id', (string) $formsCollection[1]->id)
                    ->has('data', 6)
                    ->etc()
            );
    }

    public function test_document_download_requires_authorization(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getCustomerDocumentDownloadResponse(
                $this->getTestAccountNumber(),
                $this->getTestDocumentId()
            )
        );
    }

    public function test_document_download_returns_stream_response(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $callbackMock = Mockery::mock(\stdClass::class);
        $callbackMock
            ->expects('callback')
            ->withNoArgs()
            ->once();

        $actionResponse = new StreamedResponse(
            function () use ($callbackMock): void {
                $callbackMock->callback();
            },
            headers: ['Content-Disposition' => 'attachment; filename=test.pdf']
        );

        $action = Mockery::mock(DownloadActionV2::class);
        $action
            ->expects('__invoke')
            ->withArgs(function (Account $account, int $documentId, string $documentType): bool {
                return $account->account_number === $this->getTestAccountNumber() &&
                    $documentId === $this->getTestDocumentId() &&
                    $documentType === self::DOCUMENT_TYPE;
            })
            ->once()
            ->andReturn($actionResponse);

        $this->instance(DownloadActionV2::class, $action);

        $this->getCustomerDocumentDownloadResponse(
            $this->getTestAccountNumber(),
            $this->getTestDocumentId()
        )
            ->assertOk()
            ->assertDownload('test.pdf')
            ->sendContent();
    }

    protected function getCustomerDocumentsJsonResponse(int $accountNumber): TestResponse
    {
        return $this->getJson(route('api.v2.customer.documents.get', ['accountNumber' => $accountNumber]));
    }

    protected function getCustomerDocumentDownloadResponse(int $accountNumber, int $documentId): TestResponse
    {
        return $this->getJson(route(
            'api.v2.customer.documents.download',
            [
                'accountNumber' => $accountNumber,
                'documentId' => $documentId,
                'documentType' => self::DOCUMENT_TYPE,
            ]
        ));
    }
}
