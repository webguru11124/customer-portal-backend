<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\Account\AccountNotFoundException;
use App\Models\Account;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\GetPestRoutesCustomer;
use Tests\Traits\RandomIntTestData;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;
    use GetPestRoutesCustomer;
    use RandomIntTestData;

    private const EMAIL = 'test@example.com';

    protected AccountService $accountService;
    protected Account $account;

    public function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create(['email' => self::EMAIL]);
        $this->account = Account::factory()->create(
            [
                'user_id' => $user->id,
                'office_id' => $this->getTestOfficeId(),
                'account_number' => $this->getTestAccountNumber(),
            ]
        );

        $this->accountService = new AccountService();
    }

    public function test_if_finds_account_by_account_number(): void
    {
        $result = $this->accountService->getAccountByAccountNumber($this->getTestAccountNumber());

        self::assertInstanceOf(Account::class, $result);
        self::assertEquals($this->account->attributesToArray(), $result->attributesToArray());
    }

    public function test_it_throws_account_not_found_exception(): void
    {
        $this->expectException(AccountNotFoundException::class);

        $this->accountService->getAccountByAccountNumber($this->getTestAccountNumber() + 1);
    }
}
