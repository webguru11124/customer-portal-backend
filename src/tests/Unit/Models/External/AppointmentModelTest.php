<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Exceptions\Entity\RelationNotLoadedException;
use App\Interfaces\Repository\AppointmentRepository;
use App\Models\External\AppointmentModel;
use App\Models\External\ServiceTypeModel;
use Carbon\Carbon;
use DateTimeInterface;
use Tests\Data\AppointmentData;
use Tests\Data\ServiceTypeData;
use Tests\TestCase;

class AppointmentModelTest extends TestCase
{
    private const DURATION_REPRESENTATION_SUBSTRACTION = 5;
    private const DURATION_REPRESENTATION_ADDITION = 10;
    private const RELATION_NAME_SERVICE_TYPE = 'serviceType';

    protected AppointmentModel $subject;
    protected ServiceTypeModel $serviceTypeModel;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = AppointmentData::getTestEntityData()->first();
        $this->serviceTypeModel = ServiceTypeData::getTestEntityDataOfTypes($this->subject->serviceTypeId)->first();
    }

    public function test_it_throws_exception_when_trying_to_get_non_lead_service_type(): void
    {
        $this->expectException(RelationNotLoadedException::class);

        $this->subject->serviceType;
    }

    public function test_can_set_and_get_service_type_relation(): void
    {
        $this->subject->setRelated(self::RELATION_NAME_SERVICE_TYPE, $this->serviceTypeModel);

        $result = $this->subject->serviceType;

        self::assertSame($this->serviceTypeModel, $result);
    }

    public function test_can_set_and_get_null_service_type_relation(): void
    {
        $this->subject->setRelated(self::RELATION_NAME_SERVICE_TYPE, null);

        self::assertSame(null, $this->subject->serviceType);
    }

    public function test_it_returns_service_type_name(): void
    {
        $this->subject->setRelated(self::RELATION_NAME_SERVICE_TYPE, $this->serviceTypeModel);

        $serviceTypeName = $this->serviceTypeModel->description;
        $result = $this->subject->serviceTypeName;

        self::assertEquals($serviceTypeName, $result);
    }

    public function test_it_returns_empty_string_for_appointment_without_service_type(): void
    {
        $this->subject->setRelated(self::RELATION_NAME_SERVICE_TYPE, null);

        self::assertEquals('', $this->subject->serviceTypeName);
    }

    public function test_is_reservice_returns_false_for_appointment_without_service_type(): void
    {
        $this->subject->setRelated(self::RELATION_NAME_SERVICE_TYPE, null);

        self::assertEquals(false, $this->subject->isReservice());
    }

    public function test_is_reservice_returns_true_for_appointment_with_service_type(): void
    {
        $this->subject->setRelated(self::RELATION_NAME_SERVICE_TYPE, $this->serviceTypeModel);

        self::assertEquals(true, $this->subject->isReservice());
    }

    public function test_it_returns_proper_duration_representation(): void
    {
        $this->subject->setRelated(self::RELATION_NAME_SERVICE_TYPE, $this->serviceTypeModel);

        $duration = $this->subject->duration;
        $durationRepresentation = sprintf(
            '%d-%d min (times may vary)',
            $duration - self::DURATION_REPRESENTATION_SUBSTRACTION,
            $duration + self::DURATION_REPRESENTATION_ADDITION
        );

        $result = $this->subject->durationRepresentation;

        self::assertEquals($durationRepresentation, $result);
    }

    /**
     * @dataProvider canBeCanceledDataProvider
     */
    public function test_it_can_be_canceled_if_is_upcoming_and_is_reservice_and_not_today(
        int $serviceTypeId,
        DateTimeInterface $appointmentDate,
        bool $isInitial,
        bool $expectedResult
    ): void {
        /** @var AppointmentModel $subject */
        $subject = AppointmentData::getTestEntityData(1, [
            'type' => $serviceTypeId,
            'date' => $appointmentDate->format('Y-m-d'),
            'isInitial' => $isInitial ? '1' : '0',
        ])->first();
        $serviceTypeModel = ServiceTypeData::getTestEntityDataOfTypes($subject->serviceTypeId)->first();

        $subject->setRelated(self::RELATION_NAME_SERVICE_TYPE, $serviceTypeModel);

        self::assertEquals($expectedResult, $subject->canBeCanceled());
    }

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function canBeCanceledDataProvider(): iterable
    {
        yield [ServiceTypeData::RESERVICE, Carbon::now()->addDay(), false, true];
        yield [ServiceTypeData::RESERVICE, Carbon::now()->subDay(), false, false];
        yield [ServiceTypeData::PRO, Carbon::now()->addDay(), false, false];
        yield [ServiceTypeData::PRO, Carbon::now()->subDay(), false, false];
        yield [ServiceTypeData::RESERVICE, Carbon::now()->addDay(), false, true];
        yield [ServiceTypeData::RESERVICE, Carbon::now()->addDay(), true, false];
        yield [ServiceTypeData::RESERVICE, Carbon::now(), false, false];
    }

    /**
     * @dataProvider canBeRescheduledDataProvider
     */
    public function test_it_can_be_rescheduled_if_is_upcoming_and_not_today(
        DateTimeInterface $appointmentDate,
        bool $isInitial,
        bool $expectedResult
    ): void {
        $serviceTypeArray = [
            ServiceTypeData::PRO,
            ServiceTypeData::PREMIUM,
            ServiceTypeData::MOSQUITO,
            ServiceTypeData::RESERVICE,
        ];

        /** @var AppointmentModel $subject */
        $subject = AppointmentData::getTestEntityData(1, [
            'type' => $serviceTypeArray[array_rand($serviceTypeArray)],
            'date' => $appointmentDate->format('Y-m-d'),
            'isInitial' => $isInitial ? '1' : '0',
        ])->first();
        $serviceTypeModel = ServiceTypeData::getTestEntityDataOfTypes($subject->serviceTypeId)->first();

        $subject->setRelated(self::RELATION_NAME_SERVICE_TYPE, $serviceTypeModel);

        self::assertEquals($expectedResult, $subject->canBeRescheduled());
    }

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function canBeRescheduledDataProvider(): iterable
    {
        yield [Carbon::now()->addDay(), false, true];
        yield [Carbon::now()->subDay(), false, false];
        yield [Carbon::now(), false, false];
        yield [Carbon::now()->addDay(), false, true];
        yield [Carbon::now()->addDay(), true, false];
    }

    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(AppointmentRepository::class, AppointmentModel::getRepositoryClass());
    }
}
