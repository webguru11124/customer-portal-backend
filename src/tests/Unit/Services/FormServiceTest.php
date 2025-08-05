<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Form\SearchFormsDTO;
use App\Interfaces\Repository\FormRepository;
use App\Models\Account;
use App\Services\FormService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Converters\DateTimeConverter;
use Aptive\PestRoutesSDK\Resources\Forms\Form;
use Carbon\Carbon;
use Mockery;
use Tests\Data\FormData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class FormServiceTest extends TestCase
{
    use RandomIntTestData;

    public function test_getDocumentsForAccount_passes_exception(): void
    {
        $repositoryMock = Mockery::mock(FormRepository::class);
        $repositoryMock
            ->expects('getDocuments')
            ->withAnyArgs()
            ->once()
            ->andThrow(new InternalServerErrorHttpException());

        $service = new FormService($repositoryMock);

        $this->expectException(InternalServerErrorHttpException::class);

        $service->getDocumentsForAccount(new Account([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]));
    }

    public function test_get_forms_for_account_returns_forms_ordered_by_date_from_newest_to_oldest(): void
    {
        $correctOrder = [
            '2023-01-10 16:00:00',
            '2023-01-05 16:00:00',
            '2023-01-01 16:00:00',
        ];

        $documentsCollection = FormData::getTestData(
            3,
            ['dateAdded' => $correctOrder[2]],
            ['dateAdded' => $correctOrder[0]],
            ['dateAdded' => $correctOrder[1]],
        );

        $repositoryMock = Mockery::mock(FormRepository::class);
        $repositoryMock
            ->expects('getDocuments')
            ->withArgs(
                fn (SearchFormsDTO $dto) => $dto->officeId === $this->getTestOfficeId()
                    && $dto->accountNumber === $this->getTestAccountNumber()
            )
            ->andReturn($documentsCollection)
            ->once();

        $service = new FormService($repositoryMock);

        $result = $service->getDocumentsForAccount(new Account([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]));

        $format = 'Y-m-d H:i:s';

        $resultOrder = $result
            ->map(
                fn (Form $document) => Carbon::instance($document->dateAdded)
                    ->setTimezone(DateTimeConverter::PEST_ROUTES_TIMEZONE)
                    ->format($format)
            )->toArray();

        self::assertEquals($correctOrder, $resultOrder);
    }
}
