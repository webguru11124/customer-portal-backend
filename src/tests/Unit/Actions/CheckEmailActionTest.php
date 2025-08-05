<?php

namespace Tests\Unit\Actions;

use App\Actions\CheckEmailAction;
use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Interfaces\Auth0\UserService as Auth0UserService;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\OfficeRepository;
use App\Interfaces\Repository\UserRepository;
use App\Models\External\CustomerModel;
use App\Models\User;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\AppointmentData;
use Tests\Data\CustomerData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class CheckEmailActionTest extends TestCase
{
    use RandomIntTestData;

    private const EMAIL = 'test@test.com';
    private const EXTERNAL_ID = 'auth0|638a07d78779a00e526a4ce4';
    private const FUSIONAUTH_ID = '6ecfafe1-14d6-4608-a2a8-9318bf17a472';

    private const AUTH_AUTH0 = 'Auth0';

    protected CheckEmailAction $subject;
    protected MockInterface|CustomerRepository $customerRepositoryMock;
    protected MockInterface|UserRepository $userRepositoryMock;
    protected MockInterface|Auth0UserService $auth0UserServiceMock;
    protected MockInterface|OfficeRepository $officeRepositoryMock;
    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->userRepositoryMock = Mockery::mock(UserRepository::class);
        $this->auth0UserServiceMock = Mockery::mock(Auth0UserService::class);
        $this->officeRepositoryMock = Mockery::mock(OfficeRepository::class);
        $this->appointmentRepositoryMock = Mockery::mock(AppointmentRepository::class);

        $this->subject = new CheckEmailAction(
            $this->customerRepositoryMock,
            $this->userRepositoryMock,
            $this->auth0UserServiceMock,
            $this->officeRepositoryMock,
            $this->appointmentRepositoryMock
        );
    }

    /**
     * @dataProvider provideCheckEmailData
     */
    public function test_check_returns_email_status(
        array|null $customerData,
        array|null $appointmentData,
        bool $magicUserExists,
        bool $authUserExists,
        bool|null $userHasRegisteredResponse,
        int $deleteTimes,
        array $expectedData
    ): void {
        $officeIds = [1, 2, 3];

        $customers = $customerData
            ? CustomerData::getTestEntityData(count($customerData), ...$customerData)
            : new Collection();

        $appointments = $appointmentData
            ? AppointmentData::getTestEntityData(count($appointmentData), ...$appointmentData)
            : new Collection();

        $this->officeRepositoryMock
            ->shouldReceive('getAllOfficeIds')
            ->once()
            ->andReturn($officeIds);

        $this->customerRepositoryMock
            ->shouldReceive('searchActiveCustomersByEmail')
            ->with(self::EMAIL, $officeIds, null)
            ->once()
            ->andReturn($customers);

        /** @var CustomerModel $customer */
        $customer = $customers->first();

        if ($customer !== null) {
            $this->appointmentRepositoryMock
                ->shouldReceive('office')
                ->with($customer->officeId)
                ->times(count($customerData))
                ->andReturnSelf();

            $this->appointmentRepositoryMock
                ->shouldReceive('search')
                ->withArgs(
                    fn (SearchAppointmentsDTO $dto) => $dto->officeId === $customer->officeId
                    && $dto->accountNumber === [$customer->id]
                    && $dto->status === [AppointmentStatus::Completed]
                )
                ->times(count($customerData))
                ->andReturn($appointments);
        }

        $user = null;
        if ($magicUserExists) {
            $userData = [
                'email' => self::EMAIL,
                User::AUTH0COLUMN => null,
                User::FUSIONCOLUMN => null,
            ];
            if ($authUserExists) {
                $userData[User::AUTH0COLUMN] = self::EXTERNAL_ID;
            }
            $user = User::factory()->make($userData);
        }

        $this->userRepositoryMock
            ->expects('getUser')
            ->with(self::EMAIL)
            ->once()
            ->andReturn($user);

        $this->auth0UserServiceMock
            ->expects('isRegisteredEmail')
            ->times($userHasRegisteredResponse === null ? 0 : 1)
            ->with(self::EMAIL)
            ->andReturn($userHasRegisteredResponse);

        $this->userRepositoryMock
            ->expects('deleteUserWithAccounts')
            ->times($deleteTimes)
            ->with(self::EMAIL);

        $result = ($this->subject)(self::EMAIL, self::AUTH_AUTH0);

        self::assertEquals(json_encode($expectedData), $result->toJson());
    }

    public function provideCheckEmailData(): array
    {
        return [
            'PR user does not exists' => [
                'customerData' => null,
                'appointmentData' => null,
                'magicUserExists' => false,
                'authUserExists' => false,
                'userHasRegisteredResponse' => null,
                'deleteTimes' => 0,
                'expectedData' => [
                    'exists' => false,
                    'has_logged_in' => false,
                    'has_registered' => null,
                    'completed_initial_service' => false,
                    'status' => 0
                ],
            ],
            'PR User exists logged via Magiclink no Auth0 account and has initial appointment' => [
                'customerData' => [['customerID' => $this->getTestAccountNumber()]],
                'appointmentData' => [['customerID' => $this->getTestAccountNumber()]],
                'magicUserExists' => true,
                'authUserExists' => false,
                'userHasRegisteredResponse' => false,
                'deleteTimes' => 0,
                'expectedData' => [
                    'exists' => true,
                    'has_logged_in' => false,
                    'has_registered' => false,
                    'completed_initial_service' => true,
                    'status' => 1
                ],
            ],
            'PR User exists logged via Magiclink and Auth0 and has initial appointment' => [
                'customerData' => [['customerID' => $this->getTestAccountNumber()]],
                'appointmentData' => [['customerID' => $this->getTestAccountNumber()]],
                'magicUserExists' => true,
                'authUserExists' => true,
                'userHasRegisteredResponse' => null,
                'deleteTimes' => 0,
                'expectedData' => [
                    'exists' => true,
                    'has_logged_in' => true,
                    'has_registered' => true,
                    'completed_initial_service' => true,
                    'status' => 1
                ],
            ],
            'PR User exists logged via Magiclink and Auth0 and has no initial appointment completed' => [
                'customerData' => [['customerID' => $this->getTestAccountNumber()]],
                'appointmentData' => null,
                'magicUserExists' => true,
                'authUserExists' => true,
                'userHasRegisteredResponse' => null,
                'deleteTimes' => 0,
                'expectedData' => [
                    'exists' => true,
                    'has_logged_in' => true,
                    'has_registered' => true,
                    'completed_initial_service' => false,
                    'status' => 1
                ],
            ],
            'PR user exists but has never logged in and verified email' => [
                'customerData' => [['customerID' => $this->getTestAccountNumber()]],
                'appointmentData' => [['customerID' => $this->getTestAccountNumber()]],
                'magicUserExists' => false,
                'authUserExists' => false,
                'userHasRegisteredResponse' => true,
                'deleteTimes' => 0,
                'expectedData' => [
                    'exists' => true,
                    'has_logged_in' => false,
                    'has_registered' => true,
                    'completed_initial_service' => true,
                    'status' => 1
                ],
            ],
            'PR user exists but has never logged in and not verified email' => [
                'customerData' => [['customerID' => $this->getTestAccountNumber()]],
                'appointmentData' => [['customerID' => $this->getTestAccountNumber()]],
                'magicUserExists' => false,
                'authUserExists' => false,
                'userHasRegisteredResponse' => false,
                'deleteTimes' => 0,
                'expectedData' => [
                    'exists' => true,
                    'has_logged_in' => false,
                    'has_registered' => false,
                    'completed_initial_service' => true,
                    'status' => 1
                ],
            ],
            'Auth0 user changed email' => [
                'customerData' => null,
                'appointmentData' => null,
                'magicUserExists' => true,
                'authUserExists' => true,
                'userHasRegisteredResponse' => null,
                'deleteTimes' => 1,
                'expectedData' => [
                    'exists' => false,
                    'has_logged_in' => false,
                    'has_registered' => null,
                    'completed_initial_service' => false,
                    'status' => 0
                ],
            ],
        ];
    }
}
