<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\OfficeRepository;
use App\Models\User;
use App\Services\CustomerService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\CustomerData;
use Tests\Traits\GetPestRoutesCustomer;
use Tests\Traits\HasHttpResponses;
use Tests\Traits\TestAuthorizationMiddleware;

final class UserControllerTest extends ApiTestCase
{
    use GetPestRoutesCustomer;
    use HasHttpResponses;
    use RefreshDatabase;
    use TestAuthorizationMiddleware;

    private const EXTERNAL_ID = 'AUTH0-12345678';
    private const EMAIL = 'test@example.com';
    private const UPDATED_DATES = [
        '2023-03-25 12:00:00',
        '2023-03-26 12:00:00',
        '2023-03-27 12:00:00',
        '2023-03-28 12:00:00',
    ];
    private const TEST_DATE = '2023-03-26 10:00:00';
    private const TEST_API_KEY = '1234567';

    protected MockInterface|CustomerService $customerServiceMock;
    protected MockInterface|UserService $userServiceMock;

    protected MockInterface|CustomerRepository $customerRepositoryMock;
    protected MockInterface|OfficeRepository $officeRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->customerServiceMock = Mockery::mock(CustomerService::class);
        $this->instance(CustomerService::class, $this->customerServiceMock);

        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $this->officeRepositoryMock = Mockery::mock(OfficeRepository::class);

        $this->userServiceMock = Mockery::mock(UserService::class, [
            $this->customerRepositoryMock,
            $this->officeRepositoryMock,
        ])->makePartial();

        $this->instance(UserService::class, $this->userServiceMock);
    }

    public function test_get_accounts_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->getJson($this->getUserAccountsRoute()));
    }

    private function getUserAccountsRoute(): string
    {
        return route('api.user.accounts');
    }

    public function test_get_accounts_returns_list_of_user_accounts_for_existing_user(): void
    {
        $appUser = $this->createAndLogInAuth0UserWithAccount();

        $customersCollection = CustomerData::getTestEntityData(2);

        $this->customerServiceMock
            ->shouldReceive('getActiveCustomersCollectionForUser')
            ->withArgs(fn (User $user) => $user->id === $appUser->id)
            ->once()
            ->andReturn($customersCollection);

        $this->userServiceMock
            ->shouldReceive('findUserByEmailAndExtId')
            ->with($appUser->email, $appUser->external_id, User::AUTH0COLUMN)
            ->once()
            ->andReturn($appUser);

        $this->userServiceMock
            ->shouldReceive('updateUserAccounts')
            ->withArgs([$appUser])
            ->once();

        $response = $this->getJson($this->getUserAccountsRoute());

        $this->makeResponseAssertion($response, $customersCollection);
    }

    public function test_get_accounts_returns_list_of_user_accounts_for_created_user(): void
    {
        $customersCollection = CustomerData::getTestEntityData(
            2,
            ['email' => self::EMAIL],
            ['email' => self::EMAIL],
        );

        $officeIds = range(1, 100);
        $this->officeRepositoryMock
            ->shouldReceive('getAllOfficeIds')
            ->andReturn($officeIds);

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->shouldReceive('searchActiveCustomersByEmail')
            ->withArgs([self::EMAIL, $officeIds])
            ->andReturn($customersCollection);

        $this->customerServiceMock
            ->shouldReceive('getActiveCustomersCollectionForUser')
            ->withArgs(fn (User $user) => $user->email === self::EMAIL)
            ->once()
            ->andReturn($customersCollection);

        $this->userServiceMock
            ->shouldReceive('updateUserAccounts')
            ->withArgs(fn (User $user) => $user->email === self::EMAIL);

        $this->actingAsAuth0User(['sub' => self::EXTERNAL_ID, 'email' => self::EMAIL, 'email_verified' => true]);

        $response = $this->getJson($this->getUserAccountsRoute());

        $this->makeResponseAssertion($response, $customersCollection);
    }

    private function makeResponseAssertion(TestResponse $response, Collection $customersCollection): void
    {
        $response
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($customersCollection) {
                $json = $json
                    ->where('links.self', '/api/v1/user/accounts')
                    ->count('data', $customersCollection->count());

                foreach ($customersCollection as $key => $customer) {
                    $json = $json
                        ->where("data.$key.id", (string) $customersCollection->get($key)->id)
                        ->where("data.$key.type", 'Customer')
                        ->where("data.$key.attributes.officeId", $customersCollection->get($key)->officeId);
                }

                return $json;
            });
    }

    public function test_get_accounts_returns_error_for_unknown_user(): void
    {
        $officeIds = range(1, 100);
        $this->officeRepositoryMock
            ->shouldReceive('getAllOfficeIds')
            ->andReturn($officeIds);

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->customerRepositoryMock
            ->shouldReceive('searchActiveCustomersByEmail')
            ->withArgs([self::EMAIL, $officeIds])
            ->once()
            ->andReturn(new Collection());

        $this->actingAsAuth0User(['sub' => self::EXTERNAL_ID, 'email' => self::EMAIL, 'email_verified' => true]);

        $this->getJson($this->getUserAccountsRoute())
            ->assertStatus(Response::HTTP_PRECONDITION_FAILED);
    }
}
