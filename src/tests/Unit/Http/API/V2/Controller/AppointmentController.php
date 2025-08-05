<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\Appointment\CancelAppointmentAction;
use App\Actions\Appointment\CreateAppointmentAction;
use App\Actions\Appointment\SearchAppointmentsAction;
use App\Actions\Appointment\ShowAppointmentsHistoryAction;
use App\Actions\Appointment\UpdateAppointmentAction;
use App\Enums\FlexIVR\Window;
use App\Exceptions\Entity\RelationNotFoundException;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Models\External\AppointmentModel;
use App\Services\AccountService;
use App\Services\AppointmentService;
use Exception;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\AppointmentData;
use Tests\Data\ServiceTypeData;
use Tests\Traits\ExpectedV2ResponseData;
use Tests\Traits\TestAuthorizationMiddleware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Http\API\V1\Controller\ApiTestCase;

class AppointmentController extends ApiTestCase
{
    use ExpectedV2ResponseData;
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    protected const DATE_START = '2022-07-23';
    protected const DATE_END = '2022-08-30';
    protected const VALIDATION_MESSAGE_DATE_START = 'The date start does not match the format Y-m-d.';
    protected const VALIDATION_MESSAGE_DATE_END = 'The date end does not match the format Y-m-d.';
    protected const VALIDATION_MESSAGE_COMPARE_DATES = 'The date end must be a date after or equal to date start.';
    protected const VALIDATION_MESSAGE_INVALID_STATUS = 'The selected status.0 is invalid.';
    protected const VALIDATION_MESSAGE_INVALID_STATUS_TYPE = 'The status must be an array.';
    protected const CREATED_APPOINTMENT_ID = 100;
    protected const NOTES = 'Notes';
    protected const DURATION = 20;
    protected const DURATION_SUBSTRACTION = 5;
    protected const DURATION_ADDITION = 10;

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

    /**
     * @return array{spot_id: int, window: string, is_aro_spot: bool}
     *
     * @throws Exception if random_int fails
     */
    protected function getRandomAppointmentRequestData(): array
    {
        return [
            'spot_id' => $this->getTestSpotId(),
            'window' => random_int(0, 1) === 1 ? Window::AM->value : Window::PM->value,
            'is_aro_spot' => random_int(0, 1) === 1,
            'notes' => self::NOTES,
        ];
    }

    /**
     * @throws RelationNotFoundException
     */
    protected function getAppointment(): AppointmentModel
    {
        $serviceType = ServiceTypeData::getTestEntityData()->first();

        /** @var AppointmentModel $appointment */
        $appointment = AppointmentData::getTestEntityData()->first();
        $appointment->setRelated('serviceType', $serviceType);
        $appointment->setRelated('documents', new Collection());

        return $appointment;
    }

    /**
     * @return iterable<string, array<array<string, mixed>, string>>
     */
    public function invalidRequestProvider(): iterable
    {
        yield 'no spot' => [
            'request' => [
                'window' => Window::AM->value,
                'is_aro_spot' => true,
                'notes' => self::NOTES,
            ],
            'error' => 'The spot id field is required.',
        ];
        yield 'no window' => [
            'request' => [
                'spot_id' => $this->getTestSpotId(),
                'is_aro_spot' => false,
                'notes' => self::NOTES,
            ],
            'error' => 'The window field is required.',
        ];
        yield 'no is_aro_spot' => [
            'request' => [
                'spot_id' => $this->getTestSpotId(),
                'window' => Window::PM->value,
                'notes' => self::NOTES,
            ],
            'error' => 'The is aro spot field is required.',
        ];
        yield 'notes too short' => [
            'request' => [
                'spot_id' => $this->getTestSpotId(),
                'window' => Window::PM->value,
                'is_aro_spot' => false,
                'notes' => 'T',
            ],
            'error' => 'The notes must be at least 3 characters.',
        ];
        yield 'Test Note' => [
            'request' => [
                'window' => self::NOTES,
                'spot_id' => self::NOTES,
                'is_aro_spot' => self::NOTES,
                'notes' => self::NOTES,
            ],
            'error' => 'The selected window is invalid. (and 3 more errors)',
        ];
    }
}
