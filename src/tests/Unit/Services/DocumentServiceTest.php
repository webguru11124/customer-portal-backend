<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Document\SearchDocumentsDTO;
use App\Interfaces\Repository\DocumentRepository;
use App\Models\Account;
use App\Services\DocumentService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Converters\DateTimeConverter;
use Aptive\PestRoutesSDK\Resources\Documents\Document;
use Carbon\Carbon;
use Mockery;
use Tests\Data\DocumentData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class DocumentServiceTest extends TestCase
{
    use RandomIntTestData;

    public function test_get_documents_for_account_passes_exception(): void
    {
        $repositoryMock = Mockery::mock(DocumentRepository::class);
        $repositoryMock
            ->expects('getDocuments')
            ->withAnyArgs()
            ->once()
            ->andThrow(new InternalServerErrorHttpException());

        $service = new DocumentService($repositoryMock);

        $this->expectException(InternalServerErrorHttpException::class);

        $service->getDocumentsForAccount(new Account([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]));
    }

    public function test_get_documents_for_account_returns_documents_ordered_by_date_from_newest_to_oldest(): void
    {
        $correctOrder = [
            '2022-01-10 16:00:00',
            '2022-01-05 16:00:00',
            '2022-01-01 16:00:00',
        ];

        $documentsCollection = DocumentData::getTestData(
            3,
            ['dateAdded' => $correctOrder[2]],
            ['dateAdded' => $correctOrder[0]],
            ['dateAdded' => $correctOrder[1]],
        );

        $repositoryMock = Mockery::mock(DocumentRepository::class);
        $repositoryMock
            ->expects('getDocuments')
            ->withArgs(
                fn (SearchDocumentsDTO $dto) => $dto->officeId === $this->getTestOfficeId()
                    && $dto->accountNumber === $this->getTestAccountNumber()
                    && $dto->appointmentIds === null
            )
            ->andReturn($documentsCollection)
            ->once();

        $service = new DocumentService($repositoryMock);

        $result = $service->getDocumentsForAccount(new Account([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]));

        $format = 'Y-m-d H:i:s';

        $resultOrder = $result
            ->map(
                fn (Document $document) => Carbon::instance($document->dateAdded)
                    ->setTimezone(DateTimeConverter::PEST_ROUTES_TIMEZONE)
                    ->format($format)
            )->toArray();

        self::assertEquals($correctOrder, $resultOrder);
    }
}
