<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class UserModelTest extends TestCase
{
    use refreshDatabase;

    private const ACCOUNT_NUMBER = 12345678;

    public function test_has_account_number(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->accounts()->save(Account::factory()->make(['account_number' => self::ACCOUNT_NUMBER]));

        $this->assertTrue($user->hasAccountNumber(self::ACCOUNT_NUMBER));
    }

    public function test_user_does_not_have_account_number(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->accounts()->save(Account::factory()->make());

        $this->assertFalse($user->hasAccountNumber(self::ACCOUNT_NUMBER));
    }

    public function test_user_does_not_have_account_number_with_empty_accounts_list(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->assertFalse($user->hasAccountNumber(self::ACCOUNT_NUMBER));
    }

    public function test_user_cannot_have_password_and_remember_me(): void
    {
        $user = User::factory()->make();

        $user->setRememberToken('some value');
        $this->assertSame('', $user->getAuthPassword());
        $this->assertSame('', $user->getRememberToken());
        $this->assertSame('', $user->getRememberTokenName());
    }

    public function test_user_has_jwt_identifier(): void
    {
        $user = User::factory()->make();

        $this->assertSame('cp_user', $user->getJWTIdentifier());
    }

    public function test_user_has_jwt_custom_claims(): void
    {
        $url = 'https://auth.stg.goaptive.com';
        Config::set('auth.fusionauth.url', $url);
        $user = User::factory()->make();

        $this->assertSame($url, $user->getJWTCustomClaims()['iss']);
        $this->assertSame($user->email, $user->getJWTCustomClaims()['email']);
    }
}
