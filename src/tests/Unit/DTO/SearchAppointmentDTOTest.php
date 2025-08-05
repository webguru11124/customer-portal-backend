<?php

namespace Tests\Unit\DTO;

use App\DTO\Appointment\SearchAppointmentsDTO;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use TypeError;

class SearchAppointmentDTOTest extends TestCase
{
    use RandomIntTestData;

    private const VALID_DATE_START = '2022-01-01';
    private const VALID_DATE_END = '2022-02-01';
    private const INVALID_DATE_START = '2022/01/01';
    private const INVALID_DATE_END = '2022/02/01';

    private function getValidData(): array
    {
        return [
            'officeId' => $this->getTestOfficeId(),
            'accountNumber' => [$this->getTestAccountNumber()],
            'dateStart' => self::VALID_DATE_START,
            'dateEnd' => self::VALID_DATE_END,
            'dateCompletedStart' => self::VALID_DATE_START,
            'dateCompletedEnd' => self::VALID_DATE_END,
            'status' => [AppointmentStatus::Pending],
            'serviceIds' => [],
            'ids' => [100, 200],
            'subscriptionIds' => [100, 200],
        ];
    }

    /**
     * @dataProvider validDataProvider
     */
    public function test_it_creates_with_valid_data(array $data): void
    {
        $searchAppointmentDTO = SearchAppointmentsDTO::from($data);

        self::assertEquals($data, $searchAppointmentDTO->toArray());
    }

    public function validDataProvider(): iterable
    {
        yield [$this->getValidData()];
        yield [array_merge($this->getValidData(), [
            'dateStart' => null,
            'dateEnd' => null,
            'dateCompletedStart' => null,
            'dateCompletedEnd' => null,
        ])];
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function test_it_throws_exception_with_invalid_data(array $data, string $expectedException): void
    {
        $this->expectException($expectedException);

        $searchAppointmentDTO = SearchAppointmentsDTO::from($data);

        unset($searchAppointmentDTO);
    }

    public function invalidDataProvider(): iterable
    {
        yield [
            array_merge($this->getValidData(), ['officeId' => null]),
            TypeError::class,
        ];
        yield [
            array_merge($this->getValidData(), ['serviceIds' => '']),
            TypeError::class,
        ];
        yield [
            array_merge($this->getValidData(), ['dateStart' => self::INVALID_DATE_START]),
            ValidationException::class,
        ];
        yield [
            array_merge($this->getValidData(), ['dateEnd' => self::INVALID_DATE_END]),
            ValidationException::class,
        ];
        yield [
            array_merge($this->getValidData(), ['dateCompletedStart' => self::INVALID_DATE_START]),
            ValidationException::class,
        ];
        yield [
            array_merge($this->getValidData(), ['dateCompletedStart' => self::INVALID_DATE_START]),
            ValidationException::class,
        ];
        yield [
            array_merge($this->getValidData(), ['dateCompletedEnd' => self::INVALID_DATE_END]),
            ValidationException::class,
        ];
        yield [
            array_merge($this->getValidData(), [
                'dateStart' => self::VALID_DATE_END,
                'dateEnd' => self::VALID_DATE_START,
            ]),
            ValidationException::class,
        ];
        yield [
            array_merge($this->getValidData(), ['ids' => ['id', 'id2']]),
            ValidationException::class,
        ];
    }

    public function test_it_represents_date_completed_start_as_carbon(): void
    {
        $expectedDate = Carbon::createFromFormat('Y-m-d', self::VALID_DATE_START)->startOfDay();
        $result = $this->getSearchAppointmentDTO()->getCarbonDateCompletedStart();

        self::assertEquals($expectedDate, $result);
    }

    public function test_it_represents_date_completed_end_as_carbon(): void
    {
        $expectedDate = Carbon::createFromFormat('Y-m-d', self::VALID_DATE_END)->endOfDay();
        $result = $this->getSearchAppointmentDTO()->getCarbonDateCompletedEnd();

        self::assertEquals($expectedDate, $result);
    }

    private function getSearchAppointmentDTO(): SearchAppointmentsDTO
    {
        return SearchAppointmentsDTO::from($this->getValidData());
    }
}
