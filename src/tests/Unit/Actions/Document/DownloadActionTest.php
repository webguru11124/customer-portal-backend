<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Document;

use App\Actions\Document\DownloadAction;
use App\Exceptions\Document\DocumentLinkDoesNotExist;
use App\Interfaces\Repository\DocumentRepository;
use App\Models\Account;
use Aptive\Component\Http\Exceptions\ForbiddenHttpException;
use Aptive\Component\Http\Exceptions\NotFoundHttpException;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Illuminate\Routing\Exceptions\StreamedResponseException;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Data\DocumentData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class DownloadActionTest extends TestCase
{
    use RandomIntTestData;

    private const TEST_FILE_HASH = 'ae75130a158fa601c76c86e67eaf2dbc0aa5b62b9838444d0053b3ede3f2e4a3';

    protected DocumentRepository|MockInterface|null $repositoryMock = null;
    protected DownloadAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(DocumentRepository::class);
        $this->subject = new DownloadAction($this->repositoryMock);
    }

    public function test_action_throws_not_found_when_repository_cannot_get_document(): void
    {
        $this->repositoryMock
            ->expects('getDocument')
            ->withArgs([$this->getTestOfficeId(), $this->getTestDocumentId()])
            ->once()
            ->andThrow(new ResourceNotFoundException());

        $this->expectException(NotFoundHttpException::class);

        ($this->subject)($this->getAccount(), $this->getTestDocumentId());
    }

    public function test_action_throws_forbidden_when_account_number_does_not_match(): void
    {
        $document = DocumentData::getTestData(1, [
            'customerID' => $this->getTestAccountNumber() + 1,
            'showCustomer' => '1',
            'documentLink' => null,
        ])->first();

        $this->repositoryMock
            ->expects('getDocument')
            ->withArgs([$this->getTestOfficeId(), $this->getTestDocumentId()])
            ->once()
            ->andReturn($document);

        $this->expectException(ForbiddenHttpException::class);
        $this->expectExceptionMessageMatches('/^Document "\d+" does not belong to customer "\d+"$/');

        ($this->subject)($this->getAccount(), $this->getTestDocumentId());
    }

    public function test_action_throws_exception_when_document_does_not_have_link(): void
    {
        $document = DocumentData::getTestData(1, [
            'customerID' => $this->getTestAccountNumber(),
            'showCustomer' => '1',
            'documentLink' => null,
        ])->first();

        $this->repositoryMock
            ->expects('getDocument')
            ->withArgs([$this->getTestOfficeId(), $this->getTestDocumentId()])
            ->once()
            ->andReturn($document);

        $this->expectException(DocumentLinkDoesNotExist::class);

        ($this->subject)($this->getAccount(), $this->getTestDocumentId());
    }

    public function test_action_throws_exception_with_not_an_url(): void
    {
        $document = DocumentData::getTestData(1, [
            'customerID' => $this->getTestAccountNumber(),
            'showCustomer' => '1',
            'documentLink' => 'https://x',
        ])->first();

        $this->repositoryMock
            ->expects('getDocument')
            ->withArgs([$this->getTestOfficeId(), $this->getTestDocumentId()])
            ->once()
            ->andReturn($document);

        $this->expectException(InvalidArgumentException::class);

        ($this->subject)($this->getAccount(), $this->getTestDocumentId());
    }

    public function test_action_throws_exception_with_unreachable_url(): void
    {
        $document = DocumentData::getTestData(1, [
            'customerID' => $this->getTestAccountNumber(),
            'showCustomer' => '1',
            'documentLink' => 'file:///no.pdf',
        ])->first();

        $this->repositoryMock
            ->expects('getDocument')
            ->withArgs([$this->getTestOfficeId(), $this->getTestDocumentId()])
            ->once()
            ->andReturn($document);

        $response = ($this->subject)($this->getAccount(), $this->getTestDocumentId());

        $this->expectException(StreamedResponseException::class);

        $response->sendContent();
    }

    public function test_action_returns_stream_download_response(): void
    {
        $document = DocumentData::getTestData(1, [
            'customerID' => $this->getTestAccountNumber(),
            'showCustomer' => '1',
            'documentLink' => 'file://' . realpath(__DIR__ . '/../../../Files/test.pdf'),
        ])->first();

        $this->repositoryMock
            ->expects('getDocument')
            ->withArgs([$this->getTestOfficeId(), $this->getTestDocumentId()])
            ->once()
            ->andReturn($document);

        $response = ($this->subject)($this->getAccount(), $this->getTestDocumentId());
        $this->assertInstanceOf(StreamedResponse::class, $response);

        $this->assertSame('attachment; filename=test.pdf', $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $fileContent = ob_get_clean();

        $this->assertSame(self::TEST_FILE_HASH, hash('sha256', $fileContent));
    }

    private function getAccount(): Account
    {
        return new Account([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
    }
}
