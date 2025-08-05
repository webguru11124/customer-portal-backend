<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Enums\Resources;
use App\Models\External\AppointmentModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Carbon\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\AppointmentData;
use Tests\Data\ServiceTypeData;
use Tests\Traits\TestAuthorizationMiddleware;

class AppointmentControllerSearchTest extends AppointmentController
{
    use TestAuthorizationMiddleware;

    /**
     * @param array<string, scalar|scalar[]> $queryParams
     *
     * @return string
     */
    protected function getSearchRoute(array $queryParams): string
    {
        return route(
            'api.v2.customer.appointments.search',
            array_merge(['accountNumber' => $this->getTestAccountNumber()], $queryParams)
        );
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
        $expectedQueryString = http_build_query($requestData, encoding_type: PHP_QUERY_RFC3986);

        $this->getJson($this->getSearchRoute($requestData))
            ->assertOk()
            ->assertJson(
                function (AssertableJson $json) use ($appointmentsCollection, $expectedDurationPhrase, $expectedQueryString) {
                    $json
                        ->where('links.self', sprintf(
                            '/api/v2/customer/%d/appointments%s',
                            $this->getTestAccountNumber(),
                            $expectedQueryString ? "?$expectedQueryString" : '',
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
            ->getJson($this->getSearchRoute($requestData));

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
            ->getJson($this->getSearchRoute([
                'date_start' => self::DATE_START,
                'date_end' => self::DATE_END,
            ]));

        $this->assertErrorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_search_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->getJson(
            $this->getSearchRoute([
                'date_start' => self::DATE_START,
                'date_end' => self::DATE_END,
            ])
        ));
    }

    public function test_search_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->getJson(
            $this->getSearchRoute([
                'date_start' => self::DATE_START,
                'date_end' => self::DATE_END,
            ])
        )->assertNotFound();
    }
}
