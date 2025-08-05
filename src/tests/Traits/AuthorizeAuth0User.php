<?php

namespace Tests\Traits;

use App\Models\Account;
use App\Models\User;
use Auth0\Laravel\Traits\ActingAsAuth0User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

trait AuthorizeAuth0User
{
    use ActingAsAuth0User;
    use RandomIntTestData;
    use RefreshDatabase;

    private static string $auth0ExternalId = 'auth0';
    private static string $auth0Email = 'test@example.com';

    private function createAndLogInAuth0User(): User
    {
        $this->actingAsAuth0User([
            'sub' => self::$auth0ExternalId,
            'email' => self::$auth0Email,
            'email_verified' => true,
        ]);

        $user = User::factory()->create([
            'external_id' => self::$auth0ExternalId,
            'email' => self::$auth0Email,
        ]);
        $this->setUserSynced($user);

        return $user;
    }

    private function createAndLogInAuth0UserWithAccount(): User
    {
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
        $user = $this->createAndLogInAuth0User();

        $user->accounts()->save($account);

        return $user;
    }

    private function setUserSynced(User $user): void
    {
        Cache::expects('has')
            ->withArgs(['ASC_' . $user->id])
            ->zeroOrMoreTimes()
            ->andReturn(true);
    }
}
