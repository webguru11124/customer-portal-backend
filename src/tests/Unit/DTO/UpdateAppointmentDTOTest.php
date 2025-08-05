<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\Appointment\UpdateAppointmentDTO;
use Carbon\Carbon;
use Spatie\LaravelData\Exceptions\CannotCastDate;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use TypeError;

class UpdateAppointmentDTOTest extends TestCase
{
    use RandomIntTestData;

    private const NOTES = 'Notes';
    private const START = '2022-12-01 08:00:00';
    private const END_STANDARD = '2022-12-01 08:29:00';
    private const END_RESERVICE = '2022-12-01 08:20:00';
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private const TREATMENT_DURATION_STANDARD = 29;
    private const TREATMENT_DURATION_RESERVICE = 20;

    /**
     * @dataProvider validDataProvider
     *
     * @param array<string, mixed> $data
     * @param string $endTime
     *
     * @return void
     */
    public function test_it_creates_with_valid_data(array $data, string $endTime): void
    {
        $updateAppointmentDTO = UpdateAppointmentDTO::from($data);

        self::assertEquals($updateAppointmentDTO->officeId, $data['officeId']);
        self::assertEquals($updateAppointmentDTO->appointmentId, $data['appointmentId']);
        self::assertEquals($updateAppointmentDTO->spotId, $data['spotId']);
        self::assertEquals($updateAppointmentDTO->notes, $data['notes'] ?? null);
        self::assertEquals($updateAppointmentDTO->start->format(self::DATE_TIME_FORMAT), self::START);
        self::assertEquals($updateAppointmentDTO->end->format(self::DATE_TIME_FORMAT), self::END_STANDARD);
        self::assertEquals($updateAppointmentDTO->duration, $data['duration']);
    }

    /**
     * @return array A set of valid key => value pairs to construct a UpdateAppointmentDTO object
     */
    private function getValidData(): array
    {
        return [
            'officeId' => $this->getTestOfficeId(),
            'appointmentId' => $this->getTestAppointmentId(),
            'spotId' => $this->getTestSpotId(),
            'routeId' => $this->getTestRouteId(),
            'start' => Carbon::parse(self::START),
            'end' => Carbon::parse(self::END_STANDARD),
            'duration' => self::TREATMENT_DURATION_STANDARD,
        ];
    }

    /**
     * @return iterable<string|int, array<int, mixed>>
     */
    public function validDataProvider(): iterable
    {
        yield 'standard treatment' => [
            $this->getValidData(),
            self::END_STANDARD,
        ];
        yield 'reservice' => [
            array_merge($this->getValidData(), ['duration' => self::TREATMENT_DURATION_RESERVICE]),
            self::END_RESERVICE,
        ];
        yield 'reservice with notes' => [
            array_merge($this->getValidData(), [
                'duration' => self::TREATMENT_DURATION_RESERVICE,
                'notes' => self::NOTES,
            ]),
            self::END_RESERVICE,
        ];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param array<string, mixed> $data
     * @param string $expectedException
     *
     * @return void
     */
    public function test_it_throws_exception_with_invalid_data(array $data, string $expectedException): void
    {
        $this->expectException($expectedException);

        $searchAppointmentDTO = UpdateAppointmentDTO::from($data);

        unset($searchAppointmentDTO);
    }

    /**
     * @return iterable<string|int, array<int, mixed>>
     */
    public function invalidDataProvider(): iterable
    {
        yield 'invalid office id' => [
            array_merge($this->getValidData(), ['officeId' => 'invalid office ID']),
            TypeError::class,
        ];
        yield 'invalid appointment ID' => [
            array_merge($this->getValidData(), ['appointmentId' => 'invalid appointment ID']),
            TypeError::class,
        ];
        yield 'invalid spot ID' => [
            array_merge($this->getValidData(), ['spotId' => 'invalid spot ID']),
            TypeError::class,
        ];
        yield 'invalid start' => [
            array_merge($this->getValidData(), ['start' => self::START]),
            CannotCastDate::class,
        ];
        yield 'invalid duration' => [
            array_merge($this->getValidData(), ['duration' => 'invalid duration']),
            TypeError::class,
        ];
        yield 'invalid notes' => [
            array_merge($this->getValidData(), ['notes' => []]),
            TypeError::class,
        ];
    }
}
