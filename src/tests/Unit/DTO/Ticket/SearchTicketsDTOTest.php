<?php

namespace Tests\Unit\DTO\Ticket;

use App\DTO\Ticket\SearchTicketsDTO;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SearchTicketsDTOTest extends TestCase
{
    /**
     * @dataProvider provideDTOData
     */
    public function test_it_set_up_valid_properties(int $officeId, int $accountNumber, bool $dueOnly): void
    {
        $dto = $this->getSearchTicketsDTO(officeId: $officeId, accountNumber: $accountNumber, dueOnly: $dueOnly);
        $this->assertEquals($officeId, $dto->officeId);
        $this->assertEquals($accountNumber, $dto->accountNumber);
        $this->assertSame($dueOnly, $dto->dueOnly);
    }

    /**
     * @dataProvider provideInvalidDTOData
     */
    public function test_it_throws_exception_on_invalid_data(int $officeId, int $accountNumber, bool $dueOnly): void
    {
        $this->expectException(ValidationException::class);
        $this->getSearchTicketsDTO(officeId: $officeId, accountNumber: $accountNumber, dueOnly: $dueOnly);
    }

    protected function getSearchTicketsDTO(int $officeId, int $accountNumber, bool $dueOnly)
    {
        return new SearchTicketsDTO(officeId: $officeId, accountNumber: $accountNumber, dueOnly: $dueOnly);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function provideDTOData(): array
    {
        return [
            [
                'officeId' => 1,
                'accountNumber' => 1,
                'dueOnly' => false,
            ],
            [
                'officeId' => 3,
                'accountNumber' => 2,
                'dueOnly' => true,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function provideInvalidDTOData(): array
    {
        return [
            [
                'officeId' => 0,
                'accountNumber' => 1,
                'dueOnly' => false,
            ],
            [
                'officeId' => 1,
                'accountNumber' => 0,
                'dueOnly' => true,
            ],
        ];
    }
}
