<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Services\ContractService;
use App\Services\DocumentService;
use App\Services\FormService;
use App\Services\GenericDocumentService;
use Aptive\PestRoutesSDK\Converters\DateTimeConverter;
use Aptive\PestRoutesSDK\Resources\Documents\Document;
use Carbon\Carbon;
use Tests\Data\DocumentData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class GenericDocumentServiceTest extends TestCase
{
    use RandomIntTestData;

    public function test_get_documents_for_account_returns_documents_from_all_services(): void
    {
        $correctOrder = [
            '2024-02-10 16:00:00',
            '2024-01-10 16:00:00',
            '2023-06-05 16:30:00',
            '2023-06-05 16:10:00',
            '2022-11-01 22:00:00',
            '2022-03-01 22:33:44',
        ];

        $documentsCollection = DocumentData::getTestData(
            3,
            ['dateAdded' => $correctOrder[4] . '.UTC'],
            ['dateAdded' => $correctOrder[2] . '.UTC'],
            ['dateAdded' => $correctOrder[0] . '.UTC'],
        );
        $contractsCollection = DocumentData::getTestData(
            1,
            ['dateAdded' => $correctOrder[3] . '.UTC'],
        );
        $formsCollection = DocumentData::getTestData(
            2,
            ['dateAdded' => $correctOrder[5] . '.UTC'],
            ['dateAdded' => $correctOrder[1] . '.UTC'],
        );

        $service = new GenericDocumentService(
            documentService: $this->createConfiguredMock(DocumentService::class, [
                'getDocumentsForAccount' => $documentsCollection,
            ]),
            contractsService: $this->createConfiguredMock(ContractService::class, [
                'getDocumentsForAccount' => $contractsCollection,
            ]),
            formService: $this->createConfiguredMock(FormService::class, [
                'getDocumentsForAccount' => $formsCollection,
            ]),
        );

        $result = $service->getDocumentsForAccount(new Account([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]));
        self::assertEquals(6, $result->count());

        $resultOrder = $result->map(
            fn (Document $document) => Carbon::instance($document->dateAdded)
                    ->setTimezone(DateTimeConverter::CLIENT_TIMEZONE)
                    ->format('Y-m-d H:i:s')
        )->toArray();

        self::assertEquals($correctOrder, array_values($resultOrder));
    }
}
