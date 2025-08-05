<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Enums\Resources;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Models\External\DocumentModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\Component\JsonApi\Objects\ResourceObject;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\AppointmentData;
use Tests\Data\DocumentData;
use Tests\Traits\TestAuthorizationMiddleware;

class AppointmentControllerHistoryTest extends AppointmentController
{
    use TestAuthorizationMiddleware;

    private function getHistoryRoute(): string
    {
        return route('api.v2.customer.appointments.history', ['accountNumber' => $this->getTestAccountNumber()]);
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
}
