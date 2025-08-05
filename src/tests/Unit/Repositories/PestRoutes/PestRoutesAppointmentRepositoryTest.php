<?php

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Appointment\CreateAppointmentDTO;
use App\DTO\Appointment\SearchAppointmentsDTO;
use App\DTO\Appointment\UpdateAppointmentDTO;
use App\Exceptions\Appointment\AppointmentNotCancelledException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Entity\RelationNotFoundException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\DocumentRepository;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Models\External\AppointmentModel;
use App\Repositories\Mappers\PestRoutesAppointmentToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\AppointmentParametersFactory;
use App\Repositories\PestRoutes\PestRoutesAppointmentRepository;
use App\Repositories\PestRoutes\PestRoutesDocumentRepository;
use App\Repositories\PestRoutes\PestRoutesServiceTypeRepository;
use App\Repositories\RepositoryContext;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Collection;
use Aptive\PestRoutesSDK\Collection as PestRoutesSDKCollection;
use Aptive\PestRoutesSDK\Converters\DateTimeConverter;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentsResource;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Carbon\Carbon;
use Illuminate\Support\Collection as LaravelCollection;
use Mockery;
use Tests\Data\AppointmentData;
use Tests\Data\DocumentData;
use Tests\Data\ServiceTypeData;
use Tests\TestCase;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

class PestRoutesAppointmentRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractPestRoutesRepository;
    use ExtendsAbstractExternalRepository;

    public PestRoutesAppointmentRepository $appointmentRepository;

    public function setUp(): void
    {
        parent::setUp();

        $modelMapper = new PestRoutesAppointmentToExternalModelMapper();
        $appointmentParametersFactory = new AppointmentParametersFactory();

        $this->appointmentRepository = new PestRoutesAppointmentRepository($modelMapper, $appointmentParametersFactory);
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->appointmentRepository;
    }

    public function test_it_creates_appointment()
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->callSequense('appointments', 'create')
            ->resource(AppointmentsResource::class)
            ->willReturn($this->getTestAppointmentId())
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->appointmentRepository->createAppointment($this->getTestCreateAppointmentDTO());

        self::assertEquals($this->getTestAppointmentId(), $result);
    }

    public function test_create_throws_internal_server_error_http_exception()
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new InternalServerErrorHttpException())
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(InternalServerErrorHttpException::class);

        $this->appointmentRepository->createAppointment($this->getTestCreateAppointmentDTO());
    }

    public function test_it_updates_appointment()
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(AppointmentsResource::class)
            ->callSequense('appointments', 'update')
            ->willReturn($this->getTestAppointmentId())
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->appointmentRepository->updateAppointment($this->getTestUpdateAppointmentDto());

        self::assertEquals($this->getTestAppointmentId(), $result);
    }

    public function test_update_throws_internal_server_error_http_exception()
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new InternalServerErrorHttpException())
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(InternalServerErrorHttpException::class);

        $this->appointmentRepository->updateAppointment($this->getTestUpdateAppointmentDto());
    }

    public function test_it_cancels_appointment(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->resource(AppointmentsResource::class)
            ->callSequense('appointments', 'cancel')
            ->willReturn(1)
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $this->appointmentRepository->cancelAppointment(AppointmentData::getTestEntityData()->first());
    }

    public function test_it_throws_exception_when_appointment_cancellation_fails(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new InternalServerErrorHttpException())
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(AppointmentNotCancelledException::class);

        $this->appointmentRepository->cancelAppointment(AppointmentData::getTestEntityData()->first());
    }

    public function test_it_searches_upcoming_appointments(): void
    {
        $appointmentsCollection = AppointmentData::getTestData(2);
        $appointmentsPestRoutesCollection = new Collection($appointmentsCollection->toArray());
        $expectedCollection = AppointmentData::getTestEntityData(
            $appointmentsCollection->count(),
            ...array_map(
                fn (Appointment $appointment) => ['appointmentID' => $appointment->id],
                $appointmentsCollection->toArray()
            )
        );

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(AppointmentsResource::class)
            ->callSequense('appointments', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchAppointmentsParams $params): bool {
                    $array = $params->toArray();

                    return $array['officeID'] === $this->getTestOfficeId()
                        && count($array['status']) === 1
                        && $array['status'][0]->value === AppointmentStatus::Pending->value
                        && $array['customerIDs'] === [$this->getTestAccountNumber()]
                        && Carbon::now()->startOfDay()->getTimestamp() === Carbon::parse(
                            $array['dateStart'],
                            DateTimeConverter::PEST_ROUTES_TIMEZONE
                        )->getTimestamp();
                }
            )
            ->willReturn($appointmentsPestRoutesCollection)
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $searchResult = $this->appointmentRepository
            ->office($this->getTestOfficeId())
            ->getUpcomingAppointments($this->getTestAccountNumber());

        self::assertEquals($expectedCollection, $searchResult);
    }

    public function test_it_finds_single_appointment_without_pagination(): void
    {
        /** @var AppointmentModel $appointmentModel */
        $appointmentModel = AppointmentData::getTestEntityData()->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(AppointmentsResource::class)
            ->callSequense('appointments', 'find')
            ->methodExpectsArgs('find', [$this->getTestAppointmentId()])
            ->willReturn(
                AppointmentData::getTestData(1, ['appointmentID' => $appointmentModel->id])->first()
            )
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->appointmentRepository
            ->office($this->getTestOfficeId())
            ->find($this->getTestAppointmentId());

        self::assertEquals($appointmentModel, $result);
    }

    public function test_it_finds_many_appointment_with_pagination(): void
    {
        $appointments = AppointmentData::getTestEntityData();

        /** @var AppointmentModel $appointmentModel */
        $appointmentModel = $appointments->first();

        $page = random_int(1, 5);
        $pageSize = random_int(20, 30);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(AppointmentsResource::class)
            ->callSequense('appointments', 'includeData', 'search', 'paginate')
            ->methodExpectsArgs(
                'search',
                function (SearchAppointmentsParams $params): bool {
                    $array = $params->toArray();

                    return $array['officeID'] === $this->getTestOfficeId()
                        && $array['appointmentIDs'] === [$this->getTestAppointmentId()];
                }
            )
            ->methodExpectsArgs(
                'paginate',
                [$page, $pageSize]
            )
            ->willReturn(new Collection(
                AppointmentData::getTestData(1, ['appointmentID' => $appointmentModel->id])->toArray()
            ))
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->appointmentRepository
            ->office($this->getTestOfficeId())
            ->paginate($page, $pageSize)
            ->findMany($this->getTestAppointmentId());

        self::assertEquals($appointments, $result);
    }

    public function test_find_throws_office_not_set(): void
    {
        $this->expectException(OfficeNotSetException::class);

        $this->appointmentRepository
            ->find($this->getTestAppointmentId());
    }

    public function test_find_throws_entity_not_found_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(AppointmentsResource::class)
            ->callSequense('appointments', 'find')
            ->willThrow(new ResourceNotFoundException())
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(EntityNotFoundException::class);

        $this->appointmentRepository
            ->office($this->getTestOfficeId())
            ->find($this->getTestAppointmentId());
    }

    public function test_it_loads_related_service_type(): void
    {
        /** @var AppointmentModel $appointmentModel */
        $appointments = AppointmentData::getTestEntityData(
            2,
            ['type' => ServiceTypeData::PREMIUM],
            ['type' => ServiceTypeData::QUARTERLY_SERVICE],
        );

        $serviceTypes = ServiceTypeData::getTestEntityDataOfTypes(
            ServiceTypeData::PREMIUM,
            ServiceTypeData::QUARTERLY_SERVICE
        );
        $serviceTypeModel1 = $serviceTypes->first();
        $serviceTypeModel2 = $serviceTypes->last();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(AppointmentsResource::class)
            ->callSequense('appointments', 'includeData', 'search', 'all')
            ->willReturn(new Collection(
                AppointmentData::getTestData(
                    2,
                    ['type' => ServiceTypeData::PREMIUM],
                    ['type' => ServiceTypeData::QUARTERLY_SERVICE],
                )->toArray()
            ))->mock();

        $serviceTypeRepositoryMock = Mockery::mock(PestRoutesServiceTypeRepository::class)->makePartial();
        $serviceTypeRepositoryMock->setContext(new RepositoryContext());
        $this->instance(ServiceTypeRepository::class, $serviceTypeRepositoryMock);

        $serviceTypeRepositoryMock
            ->shouldReceive('find')
            ->withArgs([ServiceTypeData::PREMIUM])
            ->andReturn($serviceTypeModel1)
            ->once();

        $serviceTypeRepositoryMock
            ->shouldReceive('find')
            ->withArgs([ServiceTypeData::QUARTERLY_SERVICE])
            ->andReturn($serviceTypeModel2)
            ->once();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $searchDto = new SearchAppointmentsDTO(
            officeId: $this->getTestOfficeId(),
        );

        $result = $this->appointmentRepository
            ->office($this->getTestOfficeId())
            ->withRelated(['serviceType'])
            ->search($searchDto);

        $relatedServiceTypeModel1 = $result->first()->serviceType;
        $relatedServiceTypeModel2 = $result->last()->serviceType;

        self::assertEquals($serviceTypeModel1, $relatedServiceTypeModel1);
        self::assertEquals($serviceTypeModel2, $relatedServiceTypeModel2);
    }

    public function test_it_loads_related_documents(): void
    {
        /** @var AppointmentModel $appointmentModel */
        $appointmentModel = AppointmentData::getTestEntityData()->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(AppointmentsResource::class)
            ->callSequense('appointments', 'find')
            ->willReturn(
                AppointmentData::getTestData(1, ['appointmentID' => $appointmentModel->id])->first()
            )
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $documentRepositoryMock = Mockery::mock(PestRoutesDocumentRepository::class)->makePartial();
        $documentRepositoryMock->setContext(new RepositoryContext());
        $this->instance(DocumentRepository::class, $documentRepositoryMock);
        $documentsCollection = DocumentData::getTestEntityData(
            2,
            ['appointmentID' => $appointmentModel->id],
            ['appointmentID' => $appointmentModel->id]
        );

        $documentRepositoryMock
            ->shouldReceive('searchBy')
            ->withArgs(['appointmentId', [$appointmentModel->id]])
            ->andReturn($documentsCollection)
            ->once();

        /** @var AppointmentModel $result */
        $result = $this->appointmentRepository
            ->office($this->getTestOfficeId())
            ->withRelated(['documents'])
            ->find($this->getTestAppointmentId());

        $relatedDocuments = $result->documents;

        self::assertEquals($documentsCollection, $relatedDocuments);
    }

    public function test_it_throws_exception_if_wrong_relation_name_is_given(): void
    {
        $appointment = AppointmentData::getTestData()->first();

        $subject = Mockery::mock(
            PestRoutesAppointmentRepository::class,
            [
                new PestRoutesAppointmentToExternalModelMapper(),
                new AppointmentParametersFactory(),
            ]
        )->makePartial();
        $subject->shouldAllowMockingProtectedMethods();
        $subject->setContext(new RepositoryContext());
        $subject->shouldReceive('findNative')
            ->andReturn($appointment);

        $this->expectException(RelationNotFoundException::class);
        $subject
            ->office($this->getTestOfficeId())
            ->withRelated(['wrongRelationName'])
            ->find($this->getTestAppointmentId());
    }

    public function test_it_loads_nothing_if_find_result_is_empty(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(AppointmentsResource::class)
            ->callSequense('appointments', 'includeData', 'search', 'all')
            ->willReturn(new Collection([]))
            ->mock();

        $serviceTypeRepositoryMock = Mockery::mock(PestRoutesServiceTypeRepository::class)->makePartial();
        $serviceTypeRepositoryMock->setContext(new RepositoryContext());
        $this->instance(ServiceTypeRepository::class, $serviceTypeRepositoryMock);

        $serviceTypeRepositoryMock
            ->shouldReceive('find')
            ->never();

        $this->appointmentRepository->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->appointmentRepository
            ->office($this->getTestOfficeId())
            ->withRelated(['serviceType'])
            ->search(new SearchAppointmentsDTO(
                officeId: $this->getTestOfficeId()
            ));

        self::assertEquals(new LaravelCollection([]), $result);
    }

    public function test_it_searches_by_customer_id(): void
    {
        $appointments = AppointmentData::getTestData();
        $customerId = $this->getTestAccountNumber();

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(AppointmentsResource::class)
            ->methodExpectsArgs(
                'search',
                fn (SearchAppointmentsParams $params) => $params->toArray() === [
                        'officeID' => $this->getTestOfficeId(),
                        'officeIDs' => [$this->getTestOfficeId()],
                        'customerIDs' => [$customerId],
                        'includeData' => 0,
                    ]
            )
            ->callSequense('appointments', 'includeData', 'search', 'all')
            ->willReturn(new PestRoutesSDKCollection($appointments->all()))
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($clientMock);

        $result = $this->appointmentRepository
            ->office($this->getTestOfficeId())
            ->searchBy('customerId', [$customerId]);

        $this->assertCount($appointments->count(), $result);
    }

    private function getTestUpdateAppointmentDto(): UpdateAppointmentDTO
    {
        return new UpdateAppointmentDTO(
            officeId: $this->getTestOfficeId(),
            appointmentId: $this->getTestAppointmentId(),
            routeId: $this->getTestRouteId(),
            start: Carbon::now()->addDays(3)->setTime(8, 0),
            end: Carbon::now()->addDays(3)->setTime(13, 0),
            duration: 25,
            notes: 'Notes'
        );
    }

    private function getTestCreateAppointmentDTO(): CreateAppointmentDTO
    {
        return new CreateAppointmentDTO(
            officeId: $this->getTestOfficeId(),
            accountNumber: $this->getTestAccountNumber(),
            typeId: ServiceTypeData::RESERVICE,
            routeId: $this->getTestRouteId(),
            start: Carbon::now()->addDays(3)->setTime(8, 0),
            end: Carbon::now()->addDays(3)->setTime(13, 0),
            duration: 20,
            notes: 'Notes',
            subscriptionId: $this->getTestSubscriptionId(),
        );
    }

    private function getTestSearchAppointmentDto(): SearchAppointmentsDTO
    {
        return SearchAppointmentsDTO::from([
            'officeId' => $this->getTestOfficeId(),
            'accountNumber' => [$this->getTestAccountNumber()],
        ]);
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestAppointmentId(),
            $this->getTestAppointmentId() + 1,
        ];

        /** @var LaravelCollection<int, Appointment> $appointments */
        $appointments = AppointmentData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(AppointmentsResource::class)
            ->callSequense('appointments', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchAppointmentsParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeID'] === $this->getTestOfficeId()
                        && $array['officeIDs'] === [$this->getTestOfficeId()]
                        && $array['appointmentIDs'] === $ids;
                }
            )
            ->willReturn(new PestRoutesSDKCollection($appointments->all()))
            ->mock();

        $this->appointmentRepository->setPestRoutesClient($clientMock);

        $result = $this->appointmentRepository
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($appointments->count(), $result);
    }
}
