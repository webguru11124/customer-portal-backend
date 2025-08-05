<?php

namespace Tests\Unit\Http\Responses;

use App\Http\Requests\SearchAppointmentsRequest;
use App\Http\Responses\Appointment\SearchAppointmentsResponse;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Models\External\AppointmentModel;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\AppointmentData;
use Tests\Data\ServiceTypeData;
use Tests\TestCase;

class SearchAppointmentsResponseTest extends TestCase
{
    private const QUARTERLY_SERVICE_NAME = 'Standard Service';
    private const DURATION = 20;
    private const DURATION_SUBSTRACTION = 5;
    private const DURATION_ADDITION = 10;

    protected MockInterface|ServiceTypeRepository $serviceTypeRepositoryMock;
    protected MockInterface|AppointmentService $appointmentServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->serviceTypeRepositoryMock = Mockery::mock(ServiceTypeRepository::class);
        $this->instance(ServiceTypeRepository::class, $this->serviceTypeRepositoryMock);

        $this->appointmentServiceMock = Mockery::mock(AppointmentService::class);
        $this->instance(AppointmentService::class, $this->appointmentServiceMock);
    }

    public function test_response_is_correct(): void
    {
        $requestMock = Mockery::mock(SearchAppointmentsRequest::class);
        $requestUri = 'http://request_uri';
        $duration = 20;
        $requestMock->shouldReceive('getRequestUri')->andReturn($requestUri);
        $appointmentsCollection = AppointmentData::getTestEntityData(
            2,
            [
                'type' => ServiceTypeData::QUARTERLY_SERVICE,
                'duration' => $duration,
            ],
            [
                'type' => ServiceTypeData::RESERVICE,
                'date' => Carbon::now()->format('Y-m-d'),
            ]
        );

        $appointmentsCollection = $appointmentsCollection->map(
            function (AppointmentModel $appointment) {
                $serviceType = ServiceTypeData::getTestEntityDataOfTypes($appointment->serviceTypeId)->first();

                return $appointment->setRelated('serviceType', $serviceType);
            }
        );

        /** @var SearchAppointmentsResponse $subject */
        $subject = SearchAppointmentsResponse::make($requestMock, $appointmentsCollection);

        $content = json_decode($subject->getContent(), true);

        self::assertEquals($requestUri, $content['links']['self']);
        self::assertEquals(self::QUARTERLY_SERVICE_NAME, $content['data'][0]['attributes']['serviceTypeName']);
        self::assertFalse($content['data'][0]['attributes']['canBeCanceled']);
        self::assertTrue($content['data'][0]['attributes']['canBeRescheduled']);
        self::assertEmpty($content['data'][0]['attributes']['rescheduleMessage']);

        self::assertFalse($content['data'][1]['attributes']['canBeRescheduled']);
        self::assertNotEmpty($content['data'][1]['attributes']['rescheduleMessage']);

        $bottom = self::DURATION - self::DURATION_SUBSTRACTION;
        $top = self::DURATION + self::DURATION_ADDITION;
        $expectedDurationPhrase = "$bottom-$top min (times may vary)";

        self::assertEquals($expectedDurationPhrase, $content['data'][0]['attributes']['duration']);
    }
}
