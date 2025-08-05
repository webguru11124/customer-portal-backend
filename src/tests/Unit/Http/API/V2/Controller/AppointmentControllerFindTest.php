<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\Appointment\FindAppointmentAction;
use App\Enums\Resources;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\AppointmentData;
use Tests\Data\ServiceTypeData;
use Tests\Traits\TestAuthorizationMiddleware;

class AppointmentControllerFindTest extends AppointmentController
{
    use TestAuthorizationMiddleware;

    private function getFindRoute(): string
    {
        $params = [
            'accountNumber' => $this->getTestAccountNumber(),
            'appointmentId' => $this->getTestAppointmentId(),
        ];

        return route('api.v2.customer.appointments.find', $params);
    }

    public function test_find_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->getJson($this->getFindRoute()));
    }

    public function test_find_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->getJson($this->getFindRoute())->assertNotFound();
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
                        '/api/v2/customer/%d/appointments/%d',
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
