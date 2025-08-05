<?php

declare(strict_types=1);

namespace Tests\Unit\MagicLink\Providers;

use App\MagicLink\Providers\MagicLinkAuthEloquentUserProvider;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tymon\JWTAuth\Payload;

class MagicLinkAuthEloquentUserProviderTest extends TestCase
{
    use RefreshDatabase;

    private const USER_EMAIL = 'test@example.com';

    public function test_it_gets_user_from_payload(): void
    {
        $payload['e'] = self::USER_EMAIL;

        $hasher = Mockery::mock(Hasher::class);
        $userProvider = new MagicLinkAuthEloquentUserProvider($hasher, User::class);
        $user = $userProvider->getModelFromMagicLinkPayload($payload);

        $this->assertEquals(self::USER_EMAIL, $user->email);
        $this->assertEquals('', $user->first_name);
        $this->assertEquals('', $user->last_name);
    }

    public function test_it_finds_user_by_email(): void
    {
        User::factory()->create([
            'email' => self::USER_EMAIL,
        ]);

        $userProvider = new MagicLinkAuthEloquentUserProvider(App::get('hash'), User::class);
        $fusionUser = $userProvider->findUserByEmail(self::USER_EMAIL);

        $this->assertEquals(self::USER_EMAIL, $fusionUser->email);
    }

    public function test_it_gets_user_from_jwt_payload(): void
    {
        $payload = Mockery::mock(Payload::class);
        $payload->shouldReceive('get')->once()->with('email')->andReturn(self::USER_EMAIL);

        $hasher = Mockery::mock(Hasher::class);
        $userProvider = new MagicLinkAuthEloquentUserProvider($hasher, User::class);
        $user = $userProvider->getModelFromPayload($payload);

        $this->assertEquals(self::USER_EMAIL, $user->email);
        $this->assertEquals('', $user->first_name);
        $this->assertEquals('', $user->last_name);
    }
}
