<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\Appointment\ShowUpcomingAppointmentsAction;
use App\Enums\Resources;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\AppointmentData;
use Tests\Data\ServiceTypeData;
use Tests\Traits\TestAuthorizationMiddleware;

class AppointmentControllerUpcomingTest extends AppointmentController
{
    use TestAuthorizationMiddleware;

    private function getUpcomingRoute(int|null $limit = null): string
    {
        $params = ['accountNumber' => $this->getTestAccountNumber()];

        if ($limit !== null) {
            $params = array_merge($params, [
                'limit' => $limit,
            ]);
        }

        return route('api.v2.customer.appointments.upcoming', $params);
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
                        '/api/v2/customer/%d/appointments/upcoming%s',
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

}
