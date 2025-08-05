<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Database;

use App\Models\Account;
use App\Models\User;
use App\Repositories\Database\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use RandomIntTestData;

    private const EMAIL = 'test@example.com';

    public function test_user_exists_return_false_with_unknown_user(): void
    {
        $repository = new UserRepository();

        $this->assertFalse($repository->userExists(self::EMAIL));
    }

    public function test_user_exists_returns_true_for_known_user(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        $repository = new UserRepository();

        $this->assertTrue($repository->userExists(self::EMAIL));
    }

    public function test_get_user_returns_valid_user(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        $repository = new UserRepository();

        $this->assertInstanceOf(User::class, $repository->getUser(self::EMAIL));
        $this->assertNull($repository->getUser('test' . self::EMAIL));
    }

    public function test_delete_user_with_accounts_returns_true_for_existing_user_with_accounts(): void
    {
        $user = User::factory()->create(['email' => self::EMAIL]);

        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);

        $user->accounts()->save($account);

        $repository = new UserRepository();

        $this->assertTrue($repository->deleteUserWithAccounts(self::EMAIL));
    }

    public function test_delete_user_with_accounts_returns_true_for_existing_user_without_accounts(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        $repository = new UserRepository();

        $this->assertTrue($repository->deleteUserWithAccounts(self::EMAIL));
    }

    public function test_delete_user_with_accounts_returns_false_for_not_existing_user_without_accounts(): void
    {
        $repository = new UserRepository();

        $this->assertFalse($repository->deleteUserWithAccounts(self::EMAIL));
    }
}
