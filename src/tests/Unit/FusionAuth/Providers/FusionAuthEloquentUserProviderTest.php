<?php

declare(strict_types=1);

namespace Tests\Unit\FusionAuth\Providers;

use App\FusionAuth\Providers\FusionAuthEloquentUserProvider;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tymon\JWTAuth\Payload;

class FusionAuthEloquentUserProviderTest extends TestCase
{
    use RefreshDatabase;
    private const FUSION_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IjdjNjhjY2VlNyJ9.eyJhdWQiOiJiODRjY2FmOS1jMWM3LTRiYTgtODJjOS1lMjYzYmY5YjE1MmEiLCJleHAiOjE3MTM0MjgwMjMsImlhdCI6MTcxMzQyNDQyMywiaXNzIjoiYWNtZS5jb20iLCJzdWIiOiI2ZWNmYWZlMS0xNGQ2LTQ2MDgtYTJhOC05MzE4YmYxN2E0NzIiLCJqdGkiOiJlYzEyNzNmNC1mNDI4LTQzNjktOTdhYy0yZGU3MTViMTY4MTYiLCJhdXRoZW50aWNhdGlvblR5cGUiOiJQQVNTV09SRCIsImVtYWlsIjoidGVzdG92eWFra3BsZWFzZWlnbm9yZUBnbWFpbC5jb20iLCJlbWFpbF92ZXJpZmllZCI6dHJ1ZSwicHJlZmVycmVkX3VzZXJuYW1lIjoiVGVzdFBsZWFzZUlnbm9yZSIsImFwcGxpY2F0aW9uSWQiOiJiODRjY2FmOS1jMWM3LTRiYTgtODJjOS1lMjYzYmY5YjE1MmEiLCJyb2xlcyI6W10sImF1dGhfdGltZSI6MTcxMzQyNDQyMywidGlkIjoiZmQxYTQwMTItMjllYS00ZmJmLWFhNjYtM2Q4OGZiY2VhN2VjIn0.VBbyo8DpURzKlhr9cL8iC5ao6-NAJvUCJx_4bkRtqbA';
    private const USER_EXTERNAL_ID = 'auth0|638a07d78779a00e526a4ce4';
    private const FUSIONAUTH_ID = '6ecfafe1-14d6-4608-a2a8-9318bf17a472';
    private const USER_EMAIL = 'test@example.com';

    public function test_it_gets_user_from_payload(): void
    {
        $payload = Mockery::mock(Payload::class);
        $payload->shouldReceive('get')->once()->with('email')->andReturn(self::USER_EMAIL);
        $payload->shouldReceive('get')->once()->with('sub')->andReturn(self::FUSIONAUTH_ID);

        $hasher = Mockery::mock(Hasher::class);
        $userProvider = new FusionAuthEloquentUserProvider($hasher, User::class);
        $user = $userProvider->getModelFromPayload($payload);
        $this->assertEquals(self::USER_EMAIL, $user->email);
        $this->assertEquals(self::FUSIONAUTH_ID, $user->fusionauth_id);
    }

    public function test_it_finds_user_by_fusion_auth_id(): void
    {
        User::factory()->create([
            'email' => self::USER_EMAIL,
            User::AUTH0COLUMN => self::USER_EXTERNAL_ID,
            User::FUSIONCOLUMN => self::FUSIONAUTH_ID,
        ]);

        $userProvider = new FusionAuthEloquentUserProvider(App::get('hash'), User::class);
        $fusionUser = $userProvider->findUserByFusionAuthId(self::FUSIONAUTH_ID);

        $this->assertEquals(self::USER_EMAIL, $fusionUser->email);
        $this->assertEquals(self::FUSIONAUTH_ID, $fusionUser->fusionauth_id);
    }
}
