<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\Appointment\CreateAppointmentDTO;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Exceptions\CannotCastDate;
use Tests\Data\ServiceTypeData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use TypeError;

class CreateAppointmentDTOTest extends TestCase
{
    use RandomIntTestData;

    private const NOTES = 'Notes';
    private const START = '2022-12-01 08:00:00';
    private const END = '2022-12-01 08:29:00';
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private const DURATION = 29;
    private const SUBSCRIPTION_ID = 47;

    /**
     * @dataProvider validDataProvider
     *
     * @param array<string, mixed> $data
     *
     * @return void
     */
    public function test_it_creates_with_valid_data(array $data): void
    {
        $createAppointmentDTO = CreateAppointmentDTO::from($data);

        self::assertEquals($createAppointmentDTO->officeId, $data['officeId']);
        self::assertEquals($createAppointmentDTO->accountNumber, $data['accountNumber']);
        self::assertEquals($createAppointmentDTO->typeId, $data['typeId']);
        self::assertEquals($createAppointmentDTO->spotId, $data['spotId']);
        self::assertEquals($createAppointmentDTO->routeId, $data['routeId']);
        self::assertEquals($createAppointmentDTO->notes, $data['notes'] ?? null);
        self::assertEquals($createAppointmentDTO->start->format(self::DATE_TIME_FORMAT), self::START);
        self::assertEquals($createAppointmentDTO->end->format(self::DATE_TIME_FORMAT), self::END);
        self::assertEquals($createAppointmentDTO->duration, self::DURATION);
        self::assertEquals($createAppointmentDTO->subscriptionId, self::SUBSCRIPTION_ID);
    }

    /**
     * @return array<string, mixed>
     */
    private function getValidData(): array
    {
        return [
            'officeId' => $this->getTestOfficeId(),
            'accountNumber' => $this->getTestAccountNumber(),
            'typeId' => ServiceTypeData::PRO,
            'spotId' => $this->getTestSpotId(),
            'routeId' => $this->getTestRouteId(),
            'start' => Carbon::parse(self::START),
            'end' => Carbon::parse(self::END),
            'duration' => self::DURATION,
            'subscriptionId' => self::SUBSCRIPTION_ID,
        ];
    }

    /**
     * @return iterable<string|int, array<int, mixed>>
     */
    public function validDataProvider(): iterable
    {
        yield 'pro with notes' => [array_merge($this->getValidData(), [
            'typeId' => ServiceTypeData::PRO,
            'notes' => self::NOTES,
        ])];
        yield 'pro without notes' => [array_merge($this->getValidData(), [
            'typeId' => ServiceTypeData::PRO,
        ])];
        yield 'quarterly with notes' => [array_merge($this->getValidData(), [
            'typeId' => ServiceTypeData::QUARTERLY_SERVICE,
            'notes' => self::NOTES,
        ])];
        yield 'quarterly without notes' => [array_merge($this->getValidData(), [
            'typeId' => ServiceTypeData::QUARTERLY_SERVICE,
        ])];
        yield 'reservice with notes' => [array_merge($this->getValidData(), [
            'typeId' => ServiceTypeData::RESERVICE,
            'notes' => self::NOTES,
        ])];
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

        $searchAppointmentDTO = CreateAppointmentDTO::from($data);

        unset($searchAppointmentDTO);
    }

    /**
     * @return iterable<string|int, array<int, mixed>>
     */
    public function invalidDataProvider(): iterable
    {
        yield 'invalid office id' => [
            array_merge($this->getValidData(), [
                'officeId' => 'invalid office id',
            ]),
            TypeError::class,
        ];
        yield 'invalid account number' => [
            array_merge($this->getValidData(), [
                'accountNumber' => 'invalid account number',
            ]),
            TypeError::class,
        ];
        yield 'invalid type id' => [
            array_merge($this->getValidData(), [
                'typeId' => 'invalid type id',
            ]),
            TypeError::class,
        ];
        yield 'invalid spot id' => [
            array_merge($this->getValidData(), [
                'spotId' => 'invalid spot id',
            ]),
            TypeError::class,
        ];
        yield 'invalid start' => [
            array_merge($this->getValidData(), [
                'start' => 'invalid start',
            ]),
            CannotCastDate::class,
        ];
        yield 'invalid duration' => [
            array_merge($this->getValidData(), [
                'duration' => 'invalid duration',
            ]),
            TypeError::class,
        ];
        yield 'reservice without notes' => [
            array_merge($this->getValidData(), [
                'typeId' => ServiceTypeData::RESERVICE,
            ]),
            ValidationException::class,
        ];
        yield 'invalid subscription id' => [
            array_merge($this->getValidData(), [
                'subscriptionId' => -1,
            ]),
            ValidationException::class,
        ];
        yield 'reservice with empty notes' => [
            array_merge($this->getValidData(), [
                'typeId' => ServiceTypeData::RESERVICE,
                'notes' => '',
            ]),
            ValidationException::class,
        ];
        yield 'reservice with null notes' => [
            array_merge($this->getValidData(), [
                'typeId' => ServiceTypeData::RESERVICE,
                'notes' => null,
            ]),
            ValidationException::class,
        ];
    }
}
