<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\Actions\Document\DownloadAction;
use App\Enums\Resources;
use App\Models\Account;
use App\Services\DocumentService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Data\DocumentData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;

final class DocumentControllerTest extends ApiTestCase
{
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    public MockInterface $documentServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->documentServiceMock = Mockery::mock(DocumentService::class);
        $this->instance(DocumentService::class, $this->documentServiceMock);
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

        $this->createAndLogInAuth0UserWithAccount();

        $this->documentServiceMock
            ->expects('getDocumentsForAccount')
            ->withAnyArgs()
            ->once()
            ->andReturn($docsCollection);

        $this->getCustomerDocumentsJsonResponse($this->getTestAccountNumber())
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('links.self', sprintf('/api/v1/customer/%d/documents', $this->getTestAccountNumber()))
                    ->where('data.0.type', Resources::DOCUMENT->value)
                    ->where('data.0.id', (string) $docsCollection[0]->id)
                    ->where('data.1.type', Resources::DOCUMENT->value)
                    ->where('data.1.id', (string) $docsCollection[1]->id)
                    ->has('data', 2)
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

        $action = Mockery::mock(DownloadAction::class);
        $action
            ->expects('__invoke')
            ->withArgs(function (Account $account, int $documentId): bool {
                return $account->account_number === $this->getTestAccountNumber()
                    && $documentId === $this->getTestDocumentId();
            })
            ->once()
            ->andReturn($actionResponse);

        $this->instance(DownloadAction::class, $action);

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
        return $this->getJson(route('api.customer.documents.get', ['accountNumber' => $accountNumber]));
    }

    protected function getCustomerDocumentDownloadResponse(int $accountNumber, int $documentId): TestResponse
    {
        return $this->getJson(route(
            'api.customer.documents.download',
            [
                'accountNumber' => $accountNumber,
                'documentId' => $documentId,
            ]
        ));
    }
}
