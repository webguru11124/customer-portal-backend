<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\Actions\Appointment\CancelAppointmentAction;
use App\Actions\Appointment\CreateAppointmentAction;
use App\Actions\Appointment\FindAppointmentAction;
use App\Actions\Appointment\SearchAppointmentsAction;
use App\Actions\Appointment\ShowAppointmentsHistoryAction;
use App\Actions\Appointment\ShowUpcomingAppointmentsAction;
use App\Actions\Appointment\UpdateAppointmentAction;
use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Enums\Resources;
use App\Exceptions\Appointment\AppointmentNotCancelledException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Models\External\DocumentModel;
use App\Services\AccountService;
use App\Services\AppointmentService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\Component\JsonApi\Objects\ResourceObject;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment;
use Carbon\Carbon;
use Exception;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\AppointmentData;
use Tests\Data\DocumentData;
use Tests\Data\ServiceTypeData;
use Tests\Traits\ExpectedV1ResponseData;
use Tests\Traits\TestAuthorizationMiddleware;
use Throwable;

class AppointmentControllerTest extends ApiTestCase
{
    use ExpectedV1ResponseData;
    use TestAuthorizationMiddleware;

    private const DATE_START = '2022-07-23';
    private const DATE_END = '2022-08-30';
    private const VALIDATION_MESSAGE_DATE_START = 'The date start does not match the format Y-m-d.';
    private const VALIDATION_MESSAGE_DATE_END = 'The date end does not match the format Y-m-d.';
    private const VALIDATION_MESSAGE_COMPARE_DATES = 'The date end must be a date after or equal to date start.';
    private const VALIDATION_MESSAGE_INVALID_STATUS = 'The selected status.0 is invalid.';
    private const VALIDATION_MESSAGE_INVALID_STATUS_TYPE = 'The status must be an array.';
    private const CREATED_APPOINTMENT_ID = 100;
    private const NOTES = 'Notes';
    private const DURATION = 20;
    private const DURATION_SUBSTRACTION = 5;
    private const DURATION_ADDITION = 10;

    public MockInterface|AppointmentService $appointmentServiceMock;
    public MockInterface|AccountService $accountServiceMock;
    public MockInterface|ServiceTypeRepository $serviceTypeRepositoryMock;
    public MockInterface|CreateAppointmentAction $createAppointmentActionMock;
    public MockInterface|SearchAppointmentsAction $searchAppointmentsActionMock;
    public MockInterface|UpdateAppointmentAction $updateAppointmentActionMock;
    public MockInterface|CancelAppointmentAction $cancelAppointmentActionMock;
    public MockInterface|ShowAppointmentsHistoryAction $showAppointmentsHistoryActionMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->appointmentServiceMock = Mockery::mock(AppointmentService::class);
        $this->instance(AppointmentService::class, $this->appointmentServiceMock);

        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->instance(AccountService::class, $this->accountServiceMock);

        $this->serviceTypeRepositoryMock = Mockery::mock(ServiceTypeRepository::class);
        $this->instance(ServiceTypeRepository::class, $this->serviceTypeRepositoryMock);

        $this->createAppointmentActionMock = Mockery::mock(CreateAppointmentAction::class);
        $this->instance(CreateAppointmentAction::class, $this->createAppointmentActionMock);

        $this->searchAppointmentsActionMock = Mockery::mock(SearchAppointmentsAction::class);
        $this->instance(SearchAppointmentsAction::class, $this->searchAppointmentsActionMock);

        $this->updateAppointmentActionMock = Mockery::mock(UpdateAppointmentAction::class);
        $this->instance(UpdateAppointmentAction::class, $this->updateAppointmentActionMock);

        $this->cancelAppointmentActionMock = Mockery::mock(CancelAppointmentAction::class);
        $this->instance(CancelAppointmentAction::class, $this->cancelAppointmentActionMock);

        $this->showAppointmentsHistoryActionMock = Mockery::mock(ShowAppointmentsHistoryAction::class);
        $this->instance(ShowAppointmentsHistoryAction::class, $this->showAppointmentsHistoryActionMock);
    }

    private function getSearchRoute(): string
    {
        return route('api.customer.appointments.search', ['accountNumber' => $this->getTestAccountNumber()]);
    }

    private function getCreateRoute(): string
    {
        return route('api.customer.appointments.create', ['accountNumber' => $this->getTestAccountNumber()]);
    }

    private function getUpdateRoute(): string
    {
        return route('api.customer.appointments.update', [
            'accountNumber' => $this->getTestAccountNumber(),
            'appointmentId' => $this->getTestAppointmentId(),
        ]);
    }

    protected function getCancelRoute(): string
    {
        return route('api.user.accounts.appointments.cancel', [
            'accountNumber' => $this->getTestAccountNumber(),
            'appointmentId' => $this->getTestAppointmentId(),
        ]);
    }

    private function getHistoryRoute(): string
    {
        return route('api.customer.appointments.history', ['accountNumber' => $this->getTestAccountNumber()]);
    }

    private function getUpcomingRoute(int|null $limit = null): string
    {
        $params = ['accountNumber' => $this->getTestAccountNumber()];

        if ($limit !== null) {
            $params = array_merge($params, [
                'limit' => $limit,
            ]);
        }

        return route('api.customer.appointments.upcoming', $params);
    }

    private function getFindRoute(): string
    {
        $params = [
            'accountNumber' => $this->getTestAccountNumber(),
            'appointmentId' => $this->getTestAppointmentId(),
        ];

        return route('api.customer.appointments.find', $params);
    }

    /**
     * @dataProvider validSearchDataProvider
     */
    public function test_search_valid_request($requestData): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $appointmentsCollection = AppointmentData::getTestEntityData(
            3,
            [
                'type' => ServiceTypeData::QUARTERLY_SERVICE,
                'duration' => self::DURATION,
                'date' => Carbon::now()->format('Y-m-d'),
            ],
            ['duration' => self::DURATION],
            ['duration' => self::DURATION],
        );

        $appointmentsCollection = $appointmentsCollection->map(
            function (AppointmentModel $appointment) {
                $serviceType = ServiceTypeData::getTestEntityDataOfTypes($appointment->serviceTypeId)->first();

                return $appointment->setRelated('serviceType', $serviceType);
            }
        );

        $this->searchAppointmentsActionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (SearchAppointmentsDTO $dto) => $dto->accountNumber === [$this->getTestAccountNumber()]
                    && $dto->officeId === $this->getTestOfficeId()
            )
            ->once()
            ->andReturn($appointmentsCollection);

        $bottom = self::DURATION - self::DURATION_SUBSTRACTION;
        $top = self::DURATION + self::DURATION_ADDITION;
        $expectedDurationPhrase = "$bottom-$top min (times may vary)";

        $this->postJson($this->getSearchRoute(), $requestData)
            ->assertOk()
            ->assertJson(
                function (AssertableJson $json) use ($appointmentsCollection, $expectedDurationPhrase) {
                    $json
                        ->where('links.self', sprintf(
                            '/api/v1/customer/%d/appointments',
                            $this->getTestAccountNumber()
                        ))
                        ->has('data', $appointmentsCollection->count());

                    foreach ($appointmentsCollection as $idx => $appointment) {
                        $json
                            ->where("data.$idx.id", (string) $appointmentsCollection->get($idx)->id)
                            ->where("data.$idx.type", Resources::APPOINTMENT->value)
                            ->where("data.$idx.attributes.duration", $expectedDurationPhrase)
                            ->where("data.$idx.attributes.serviceTypeName", $appointmentsCollection->get($idx)->serviceTypeName)
                            ->where("data.$idx.attributes.canBeCanceled", $appointmentsCollection->get($idx)->canBeCanceled())
                            ->where("data.$idx.attributes.canBeRescheduled", $appointmentsCollection->get($idx)->canBeRescheduled())
                            ->has("data.$idx.attributes.rescheduleMessage")
                            ->etc();
                    }

                    return $json;
                }
            );
    }

    /**
     * @return iterable<string, array<int, array<string, mixed>>>
     */
    public function validSearchDataProvider(): iterable
    {
        yield 'all params' => [[
            'date_start' => self::DATE_START,
            'date_end' => self::DATE_END,
            'status' => [0],
        ]];
        yield 'empty params' => [[
        ]];
        yield 'empty days' => [[
            'date_start' => ' ',
            'date_end' => '',
        ]];
        yield 'multiple  status' => [[
            'status' => [0, 1, 2],
        ]];
    }

    /**
     * @dataProvider invalidSearchDataProvider
     */
    public function test_search_invalid_request($requestData, $expected): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $response = $this
            ->postJson($this->getSearchRoute(), $requestData);

        $response->assertUnprocessable();
        self::assertStringContainsString($expected, $response->getContent());
    }

    /**
     * @return iterable<string, array<int, string|array<string, mixed>>>
     */
    public function invalidSearchDataProvider(): iterable
    {
        yield 'invalid start' => [
            [
                'date_start' => '2022/07/23',
            ],
            json_encode([
                'date_start' => [self::VALIDATION_MESSAGE_DATE_START],
            ]),
        ];
        yield 'invalid end' => [
            [
                'date_end' => '2022-08-333',
            ],
            json_encode([
                'date_end' => [
                    self::VALIDATION_MESSAGE_DATE_END,
                ],
            ]),
        ];
        yield 'invalid both dates' => [
            [
                'date_start' => '2022/07/23',
                'date_end' => '2022-08-333',
            ],
            json_encode([
                'date_start' => [self::VALIDATION_MESSAGE_DATE_START],
                'date_end' => [
                    self::VALIDATION_MESSAGE_DATE_END,
                    self::VALIDATION_MESSAGE_COMPARE_DATES,
                ],
            ]),
        ];
        yield 'start more than end' => [
            [
                'date_start' => self::DATE_END,
                'date_end' => self::DATE_START,
            ],
            json_encode([
                'date_end' => [self::VALIDATION_MESSAGE_COMPARE_DATES],
            ]),
        ];
        yield 'status not in appointment statuses range' => [
            [
                'status' => [random_int(100, 999)],
            ],
            json_encode([
                'status.0' => [self::VALIDATION_MESSAGE_INVALID_STATUS],
            ]),
        ];
        yield 'int status' => [
            [
                'status' => random_int(0, 2),
            ],
            json_encode([
                'status' => [self::VALIDATION_MESSAGE_INVALID_STATUS_TYPE],
            ]),
        ];
    }

    public function test_search_throws_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->searchAppointmentsActionMock
            ->shouldReceive('__invoke')
            ->andThrow(new InternalServerErrorHttpException())
            ->once();

        $response = $this
            ->postJson($this->getSearchRoute(), [
                'date_start' => self::DATE_START,
                'date_end' => self::DATE_END,
            ]);

        $this->assertErrorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_search_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->postJson(
            $this->getSearchRoute(),
            [
                'date_start' => self::DATE_START,
                'date_end' => self::DATE_END,
            ]
        ));
    }

    public function test_search_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->postJson(
            $this->getSearchRoute(),
            [
                'date_start' => self::DATE_START,
                'date_end' => self::DATE_END,
            ]
        )->assertNotFound();
    }

    public function test_create_appointment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->createAppointmentActionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (Account $account, int $spotId, string $notes) => $account->account_number === $this->getTestAccountNumber()
                    && $spotId === $this->getTestSpotId()
                    && $notes === self::NOTES
            )
            ->once()
            ->andReturn(self::CREATED_APPOINTMENT_ID);

        $requestData = [
            'spotId' => $this->getTestSpotId(),
            'notes' => self::NOTES,
        ];

        $response = $this
            ->putJson($this->getCreateRoute(), $requestData);

        $response->assertCreated();
        $response->assertExactJson($this->getResourceCreatedExpectedResponse(
            Resources::APPOINTMENT->value,
            self::CREATED_APPOINTMENT_ID
        ));
    }

    public function test_create_throws_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->accountServiceMock
            ->shouldReceive('getOfficeIdByAccountNumber')
            ->andThrow(Exception::class);

        $response = $this
            ->putJson($this->getCreateRoute(), [
                'spotId' => 45234523,
                'notes' => self::NOTES,
            ]);

        $this->assertErrorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_create_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->putJson(
            $this->getCreateRoute(),
            [
                'spotId' => $this->getTestSpotId(),
                'notes' => self::NOTES,
            ]
        ));
    }

    public function test_create_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->putJson(
            $this->getCreateRoute(),
            [
                'spotId' => $this->getTestSpotId(),
            ]
        )->assertNotFound();
    }

    public function test_update_appointment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->updateAppointmentActionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (
                    Account $account,
                    int $appointmentId,
                    int $spotId,
                    string $notes
                ) => $account->office_id === $this->getTestOfficeId()
                    && $account->account_number === $this->getTestAccountNumber()
                    && $appointmentId === $this->getTestAppointmentId()
                    && $spotId === $this->getTestSpotId()
                    && $notes === self::NOTES
            )
            ->andReturn($this->getTestAppointmentId());

        $this->patchJson($this->getUpdateRoute(), [
            'spotId' => $this->getTestSpotId(),
            'notes' => self::NOTES,
        ])
            ->assertOk()
            ->assertExactJson($this->getResourceUpdatedExpectedResponse(
                self::URL_PREFIX . $this->getTestAccountNumber() . '/appointment/' . $this->getTestAppointmentId(),
                Resources::APPOINTMENT->value,
                $this->getTestAppointmentId()
            ));
    }

    public function test_update_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->patchJson(
            $this->getUpdateRoute(),
            [
                'spotId' => $this->getTestSpotId(),
                'notes' => self::NOTES,
            ]
        ));
    }

    public function test_update_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->patchJson($this->getUpdateRoute(), ['spotId' => $this->getTestSpotId()])
            ->assertNotFound();
    }

    /**
     * @dataProvider updateInvalidDateProvider
     */
    public function test_update_throws_validation_errors(array $requestData, array $invalidFields): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $response = $this
            ->patchJson($this->getUpdateRoute(), $requestData);

        $response->assertUnprocessable();
        $response->assertJsonStructure(
            $this->getExpectedValidationErrorResponseStructure($invalidFields)
        );
    }

    public function updateInvalidDateProvider(): array
    {
        return [
            [
                [
                    'spotId' => 'inavalid ID',
                    'notes' => 23324,
                ],
                ['spotId', 'notes'],
            ],
        ];
    }

    public function test_update_throws_error(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->accountServiceMock
            ->shouldReceive('getOfficeIdByAccountNumber')
            ->andThrow(Exception::class);

        $this->patchJson($this->getUpdateRoute(), [
            'spotId' => $this->getTestSpotId(),
            'notes' => self::NOTES,
        ])
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJsonStructure(['errors']);
    }

    public function test_cancel_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this
            ->deleteJson($this->getCancelRoute())
            ->assertNotFound();
    }

    public function test_cancel_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->deleteJson($this->getCancelRoute()));
    }

    public function test_cancel_appointment_cancels_appointment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->cancelAppointmentActionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (Account $account, int $appointmentId) => $account->account_number === $this->getTestAccountNumber()
                    && $appointmentId === $this->getTestAppointmentId()
            )
            ->once();

        $this
            ->deleteJson($this->getCancelRoute())
            ->assertNoContent();
    }

    /**
     * @dataProvider appointmentCancellationExceptionProvider
     */
    public function test_cancel_appointment_shows_cancellation_error(Throwable $exception, int $responseCode): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->cancelAppointmentActionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (Account $account, int $appointmentId) => $account->account_number === $this->getTestAccountNumber()
                    && $appointmentId === $this->getTestAppointmentId()
            )
            ->once()
            ->andThrow($exception);

        $this
            ->deleteJson($this->getCancelRoute())
            ->assertStatus($responseCode);
    }

    public function appointmentCancellationExceptionProvider(): array
    {
        return [
            'Entity Not Found' => [
                new EntityNotFoundException(),
                Response::HTTP_NOT_FOUND,
            ],
            'Cancellation failed' => [
                new AppointmentNotCancelledException(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ],
        ];
    }

    public function test_history_searches_history_appointments(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $appointmentsCollection = AppointmentData::getTestEntityData();
        /** @var AppointmentModel $appointment */
        $appointment = $appointmentsCollection->first();

        $documentsCollection = DocumentData::getTestEntityData(1, ['appointmentID' => $appointment->id]);
        /** @var DocumentModel $document */
        $document = $documentsCollection->first();
        $documentArray = $document->toArray();
        unset($documentArray['id']);
        $documentResource = ResourceObject::make(Resources::DOCUMENT->value, $document->id, $documentArray);
        $appointment->setRelated('documents', $documentsCollection);

        $appointmentArray = $appointment->toArray();
        unset($appointmentArray['id']);
        $appointmentResource = ResourceObject::make(Resources::APPOINTMENT->value, $appointment->id, $appointmentArray);

        $this->showAppointmentsHistoryActionMock
            ->shouldReceive('__invoke')
            ->withArgs(fn (Account $account) => $account->account_number === $this->getTestAccountNumber())
            ->once()
            ->andReturn($appointmentsCollection);

        $responseData = [
            [
                'type' => Resources::APPOINTMENT->value,
                'id' => (string) $appointment->id,
                'attributes' => $appointmentResource->getAttributes(),
                'relationships' => [
                    'documents' => [
                        'data' => [
                            [
                                'type' => Resources::DOCUMENT->value,
                                'id' => (string) $document->id,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $responseIncluded = [
            $documentResource->toArray(),
        ];

        $expected = json_decode(json_encode($this->getExpectedSearchWithRelatedResponse(
            self::URL_PREFIX . $this->getTestAccountNumber() . '/appointments/history',
            $responseData,
            $responseIncluded
        )), true);

        $this->getJson($this->getHistoryRoute())
            ->assertOk()
            ->assertExactJson($expected);
    }

    public function test_history_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->getJson($this->getHistoryRoute()));
    }

    public function test_history_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->getJson($this->getHistoryRoute())->assertNotFound();
    }

    public function test_history_shows_internal_server_error_http_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->showAppointmentsHistoryActionMock
            ->shouldReceive('__invoke')
            ->andThrow(new InternalServerErrorHttpException());

        $response = $this->getJson($this->getHistoryRoute());

        $this->assertErrorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @dataProvider upcomingDataProvider
     */
    public function test_upcoming_searches_upcoming_appointments(int|null $providedLimit = null): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        /** @var MockInterface|ShowUpcomingAppointmentsAction $showUpcomingAppointmentsActionMock */
        $showUpcomingAppointmentsActionMock = Mockery::mock(ShowUpcomingAppointmentsAction::class);
        $this->instance(ShowUpcomingAppointmentsAction::class, $showUpcomingAppointmentsActionMock);

        $appointmentsCollection = AppointmentData::getTestEntityData();
        $appointmentsCollection = $appointmentsCollection->map(
            function (AppointmentModel $appointment) {
                $serviceType = ServiceTypeData::getTestEntityDataOfTypes($appointment->serviceTypeId)->first();

                return $appointment->setRelated('serviceType', $serviceType);
            }
        );

        /** @var Appointment $appointment */
        $appointment = $appointmentsCollection->first();
        $showUpcomingAppointmentsActionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (Account $account, int $limit) => $account->account_number === $this->getTestAccountNumber()
                && $limit === (int) $providedLimit
            )
            ->once()
            ->andReturn($appointmentsCollection);

        $this->getJson($this->getUpcomingRoute($providedLimit))
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('links.self', sprintf(
                        '/api/v1/customer/%d/appointments/upcoming%s',
                        $this->getTestAccountNumber(),
                        $providedLimit !== null ? '?limit=' . $providedLimit : ''
                    ))
                    ->where('data.0.id', (string) $appointment->id)
                    ->where('data.0.type', Resources::APPOINTMENT->value)
                    ->where('data.0.attributes.scheduledStartTime', function ($value) {
                        return is_string($value) && preg_match('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}-\d{4}/', $value);
                    })
                    ->has('data', $appointmentsCollection->count())
                    ->etc()
            );
    }

    /**
     * @return iterable<int, array<int, int|null>>
     */
    public function upcomingDataProvider(): iterable
    {
        yield [null];
        yield [0];
        yield [1];
        yield [2];
    }

    public function test_upcoming_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->getJson($this->getUpcomingRoute()));
    }

    public function test_upcoming_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->getJson($this->getUpcomingRoute())->assertNotFound();
    }

    public function test_upcoming_shows_internal_server_error_http_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $showUpcomingAppointmentsActionMock = Mockery::mock(ShowUpcomingAppointmentsAction::class);
        $this->instance(ShowUpcomingAppointmentsAction::class, $showUpcomingAppointmentsActionMock);

        $showUpcomingAppointmentsActionMock
            ->shouldReceive('__invoke')
            ->andThrow(new InternalServerErrorHttpException());

        $response = $this->getJson($this->getUpcomingRoute());

        $this->assertErrorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_find_appointment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        /** @var MockInterface|FindAppointmentAction $findAppointmentActionMock */
        $findAppointmentActionMock = Mockery::mock(FindAppointmentAction::class);
        $this->instance(FindAppointmentAction::class, $findAppointmentActionMock);

        /** @var AppointmentModel $appointment */
        $appointment = AppointmentData::getTestEntityData()->first();
        $serviceType = ServiceTypeData::getTestEntityDataOfTypes($appointment->serviceTypeId)->first();
        $appointment->setRelated('serviceType', $serviceType);

        $findAppointmentActionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (Account $account, int $appointmentId) => $account->account_number === $this->getTestAccountNumber()
                    && $appointmentId === (int) $this->getTestAppointmentId()
            )
            ->andReturn($appointment)
            ->once();

        $this->getJson($this->getFindRoute())
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('links.self', sprintf(
                        '/api/v1/customer/%d/appointment/%d',
                        $this->getTestAccountNumber(),
                        $this->getTestAppointmentId()
                    ))
                    ->where('data.id', (string) $appointment->id)
                    ->where('data.type', Resources::APPOINTMENT->value)
                    ->has('data.attributes.serviceTypeName')
                    ->has('data.attributes.duration')
                    ->has('data.attributes.canBeCanceled')
                    ->has('data.attributes.canBeRescheduled')
                    ->etc()
            );
    }

    public function test_find_shows_entity_not_found_exception_if_no_data_is_present(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        /** @var MockInterface|FindAppointmentAction $findAppointmentActionMock */
        $findAppointmentActionMock = Mockery::mock(FindAppointmentAction::class);
        $this->instance(FindAppointmentAction::class, $findAppointmentActionMock);

        $findAppointmentActionMock
            ->shouldReceive('__invoke')
            ->andThrow(new EntityNotFoundException());

        $response = $this->getJson($this->getFindRoute());

        $this->assertErrorResponse($response, Response::HTTP_NOT_FOUND);
    }
}
