<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Interfaces\Repository\CustomerRepository;
use App\Interfaces\Repository\OfficeRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\TestCase;

final class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private const USER_EXTERNAL_ID = 'auth0|638a07d78779a00e526a4ce4';
    private const FUSIONAUTH_ID = '6ecfafe1-14d6-4608-a2a8-9318bf17a472';
    private const USER_EMAIL = 'test@example.com';

    protected MockInterface|CustomerRepository $customerRepository;
    protected MockInterface|OfficeRepository $officeRepositoryMock;
    protected UserService $userService;
    protected array $officeIds;

    public function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = Mockery::mock(CustomerRepository::class);
        $this->officeRepositoryMock = Mockery::mock(OfficeRepository::class);

        $this->userService = new UserService(
            $this->customerRepository,
            $this->officeRepositoryMock
        );

        $this->officeIds = range(1, 100);
    }

    /**
     * @dataProvider externalIdDataProvider
     */
    public function test_it_finds_user_by_email_and_id(
        string $externalIdValue,
        string  $externalIdName,
    ): void {
        $createdUser = User::factory()->create([
            'email' => self::USER_EMAIL,
            User::AUTH0COLUMN => self::USER_EXTERNAL_ID,
            User::FUSIONCOLUMN => self::FUSIONAUTH_ID,
        ]);
        $foundUser = $this->userService->findUserByEmailAndExtId(
            self::USER_EMAIL,
            $externalIdValue,
            $externalIdName
        );

        $this->assertEquals($createdUser->id, $foundUser->id);
        $this->assertEquals(self::USER_EMAIL, $foundUser->email);
    }

    /**
     * @dataProvider externalIdDataProvider
     */
    public function test_it_creates_user_with_external_id(
        string $externalIdValue,
        string  $externalIdName,
    ): void {
        $customers = CustomerData::getTestEntityData(
            2,
            ['email' => self::USER_EMAIL],
            ['email' => self::USER_EMAIL],
        );

        $this->customerRepository
            ->shouldReceive('office')
            ->with(0)
            ->andReturnSelf()
            ->atLeast();

        $this->mockGetAllOfficeIds();
        $this->customerRepository
            ->shouldReceive('searchActiveCustomersByEmail')
            ->with(self::USER_EMAIL, $this->officeIds)
            ->andReturn($customers)
            ->atLeast();

        $createdUser = $this->userService->createOrUpdateUserWithExternalId(
            $externalIdValue,
            self::USER_EMAIL,
            $externalIdName
        );

        $this->assertDatabaseHas('users', [
            $externalIdName => $externalIdValue,
            'email' => self::USER_EMAIL,
        ]);

        $this->assertEquals(self::USER_EMAIL, $createdUser->email);
        $this->assertEquals($externalIdValue, $createdUser->$externalIdName);

        $this->assertCount($customers->count(), $createdUser->accounts);

        /** @var CustomerModel $customer */
        foreach ($customers as $idx => $customer) {
            $this->assertDatabaseHas('accounts', [
                'user_id' => $createdUser->id,
                'account_number' => $customer->id,
                'office_id' => $customer->officeId,
            ]);

            $this->assertEquals($createdUser->accounts->get($idx)->office_id, $customer->officeId);
            $this->assertEquals($createdUser->accounts->get($idx)->account_number, $customer->id);
        }
    }

    /**
     * @dataProvider externalIdDataProvider
     */
    public function test_it_updates_existing_user_with_external_id(
        string $externalIdValue,
        string  $externalIdName,
    ): void {
        $randomExternalId = Str::random(10);
        $randomFusionId = Str::random(10);
        User::factory()->create([
            'email' => self::USER_EMAIL,
            User::AUTH0COLUMN => $randomExternalId,
            User::FUSIONCOLUMN => $randomFusionId,
            'first_name' => 'a',
            'last_name' => 'b',
        ]);

        $this->test_it_creates_user_with_external_id($externalIdValue, $externalIdName);

        $this->assertDatabaseMissing('users', [
            'email' => self::USER_EMAIL,
            User::AUTH0COLUMN => $randomExternalId,
            User::FUSIONCOLUMN => $randomFusionId,
        ]);
    }

    /**
     * @dataProvider externalIdDataProvider
     */
    public function test_it_returns_null_when_user_does_not_exist(
        string $externalIdValue,
        string  $externalIdName,
    ): void {
        $this->customerRepository
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->mockGetAllOfficeIds();
        $this
            ->customerRepository
            ->expects('searchActiveCustomersByEmail')
            ->with(self::USER_EMAIL, $this->officeIds)
            ->once()
            ->andReturn(new Collection());

        $this->assertNull($this->userService->createOrUpdateUserWithExternalId(
            $externalIdValue,
            self::USER_EMAIL,
            $externalIdName,
        ));
    }

    public function test_sync_user_accounts_tries_to_create_new_accounts_from_pestroutes_and_uses_sync_countdown(): void
    {
        $countdown = 1000;
        Config::set('cache.custom_ttl.accounts_sync_countdown', $countdown);

        $this->customerRepository
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->mockGetAllOfficeIds();
        $this->customerRepository
            ->expects('searchActiveCustomersByEmail')
            ->with(self::USER_EMAIL, $this->officeIds)
            ->once()
            ->andReturn(new Collection());

        $user = User::factory()->create(['email' => self::USER_EMAIL]);
        Cache::expects('has')
            ->withArgs(['ASC_' . $user->id])
            ->twice()
            ->andReturn(false, true);
        Cache::expects('put')
            ->withArgs(['ASC_' . $user->id, true, $countdown])
            ->once();
        $this->userService->syncUserAccounts($user);
        $this->userService->syncUserAccounts($user);
    }

    public function test_update_accounts_creates_new_accounts_from_pestroutes(): void
    {
        $customers = CustomerData::getTestData(3);

        $this->customerRepository
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->mockGetAllOfficeIds();
        $this
            ->customerRepository
            ->expects('searchActiveCustomersByEmail')
            ->with(self::USER_EMAIL, $this->officeIds)
            ->once()
            ->andReturn($customers);

        /** @var User $user */
        $user = User::factory()->create(['email' => self::USER_EMAIL]);
        $user->accounts()->create([
            'account_number' => $customers[0]->id,
            'office_id' => $customers[0]->officeId,
        ]);

        $this->assertCount(1, $user->accounts);

        $this->userService->updateUserAccounts($user);

        $this->assertCount(3, $user->accounts);

        foreach ($customers as $customer) {
            $this->assertTrue($user->hasAccountNumber($customer->id));
            $this->assertDatabaseHas(
                Account::class,
                [
                    'user_id' => $user->getKey(),
                    'account_number' => $customer->id,
                    'office_id' => $customer->officeId,
                ]
            );
        }
    }

    public function test_update_user_accounts_changes_user_id_if_customer_changed_email(): void
    {
        $oldEmail = 'old@email.com';

        $customers = CustomerData::getTestData(
            2,
            ['email' => self::USER_EMAIL],
            ['email' => self::USER_EMAIL],
        );

        /** @var User $user1 */
        $user1 = User::factory()->create(['email' => $oldEmail]);
        /** @var User $user1 */
        $user2 = User::factory()->create(['email' => self::USER_EMAIL]);

        $this->customerRepository
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->mockGetAllOfficeIds();
        $this
            ->customerRepository
            ->expects('searchActiveCustomersByEmail')
            ->with(self::USER_EMAIL, $this->officeIds)
            ->once()
            ->andReturn($customers);

        $user1->accounts()->create([
            'account_number' => $customers[0]->id,
            'office_id' => $customers[0]->officeId,
        ]);

        $user2->accounts()->create([
            'account_number' => $customers[1]->id,
            'office_id' => $customers[1]->officeId,
        ]);

        $this->userService->updateUserAccounts($user2);

        $user1->load('accounts');
        $user2->load('accounts');

        $this->assertCount(0, $user1->accounts);
        $this->assertCount(2, $user2->accounts);
    }

    public function test_update_user_accounts_removes_user_account(): void
    {
        $customers = CustomerData::getTestData(
            2,
            ['email' => self::USER_EMAIL],
            ['email' => self::USER_EMAIL],
        );

        /** @var User $user */
        $user = User::factory()->create(['email' => self::USER_EMAIL]);

        $this->customerRepository
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->mockGetAllOfficeIds();
        $this->customerRepository
            ->expects('searchActiveCustomersByEmail')
            ->with(self::USER_EMAIL, $this->officeIds)
            ->once()
            ->andReturn($customers);

        $user->accounts()->create([
            'account_number' => $customers[0]->id,
            'office_id' => $customers[0]->officeId,
        ]);
        $user->accounts()->create([
            'account_number' => $customers[1]->id,
            'office_id' => $customers[1]->officeId,
        ]);
        // deleted account
        $user->accounts()->create([
            'account_number' => $customers[0]->id + 1,
            'office_id' => $customers[0]->officeId,
        ]);

        $this->userService->updateUserAccounts($user);

        $user->load('accounts');

        $this->assertCount(2, $user->accounts);
    }

    public function test_update_user_accounts_updates_office_id(): void
    {
        $customers = CustomerData::getTestData(1, ['email' => self::USER_EMAIL]);

        /** @var User $user */
        $user = User::factory()->create(['email' => self::USER_EMAIL]);

        $this->customerRepository
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->mockGetAllOfficeIds();
        $this->customerRepository
            ->expects('searchActiveCustomersByEmail')
            ->with(self::USER_EMAIL, $this->officeIds)
            ->once()
            ->andReturn($customers);

        $user->accounts()->create([
            'account_number' => $customers[0]->id,
            'office_id' => $customers[0]->officeId + 1,
        ]);

        $this->userService->updateUserAccounts($user);

        $user->load('accounts');

        $this->assertCount(1, $user->accounts);
    }

    /**
     * @dataProvider externalIdDataProvider
     */
    public function test_create_user_with_external_id_changes_user_id_if_customer_changed_email(
        string $externalIdValue,
        string  $externalIdName,
    ): void {
        $oldEmail = 'old@email.com';

        $customers = CustomerData::getTestData(
            2,
            ['email' => self::USER_EMAIL],
            ['email' => self::USER_EMAIL],
        );

        /** @var User $user */
        $user = User::factory()->create(['email' => $oldEmail]);

        for ($i = 0; $i < $customers->count(); $i++) {
            $user->accounts()->create([
                'account_number' => $customers[$i]->id,
                'office_id' => $customers[$i]->officeId,
            ]);
        }

        $this->customerRepository
            ->shouldReceive('office')
            ->andReturnSelf();

        $this->mockGetAllOfficeIds();
        $this
            ->customerRepository
            ->shouldReceive('searchActiveCustomersByEmail')
            ->with(self::USER_EMAIL, $this->officeIds)
            ->atLeast()
            ->andReturn($customers);

        $this->userService->createOrUpdateUserWithExternalId($externalIdValue, self::USER_EMAIL, $externalIdName);

        /** @var User $newUser */
        $newUser = User::where('email', self::USER_EMAIL)->first();
        $this->assertCount($customers->count(), $newUser->accounts()->get());
    }

    private function mockGetAllOfficeIds(): void
    {
        $this->officeRepositoryMock
            ->shouldReceive('getAllOfficeIds')
            ->atLeast()
            ->andReturn($this->officeIds);
    }


    public function externalIdDataProvider(): array
    {
        return [
            [
                'externalIdValue' => self::USER_EXTERNAL_ID,
                'externalIdName' => User::AUTH0COLUMN,
            ],
            [
                'externalIdValue' => self::FUSIONAUTH_ID,
                'externalIdName' => User::FUSIONCOLUMN,
            ],
        ];
    }
}
