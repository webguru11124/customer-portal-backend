<?php

declare(strict_types=1);

namespace Tests\Unit\FusionAuth;

use App\FusionAuth\FusionAuthJwtGuard;
use App\FusionAuth\Providers\FusionAuthEloquentUserProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Mockery;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\Payload;
use Tests\TestCase;

class FusionAuthJwtGuardTest extends TestCase
{
    private const FUSION_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IjdjNjhjY2VlNyJ9.eyJhdWQiOiJiODRjY2FmOS1jMWM3LTRiYTgtODJjOS1lMjYzYmY5YjE1MmEiLCJleHAiOjE3MTM0MjgwMjMsImlhdCI6MTcxMzQyNDQyMywiaXNzIjoiYWNtZS5jb20iLCJzdWIiOiI2ZWNmYWZlMS0xNGQ2LTQ2MDgtYTJhOC05MzE4YmYxN2E0NzIiLCJqdGkiOiJlYzEyNzNmNC1mNDI4LTQzNjktOTdhYy0yZGU3MTViMTY4MTYiLCJhdXRoZW50aWNhdGlvblR5cGUiOiJQQVNTV09SRCIsImVtYWlsIjoidGVzdG92eWFra3BsZWFzZWlnbm9yZUBnbWFpbC5jb20iLCJlbWFpbF92ZXJpZmllZCI6dHJ1ZSwicHJlZmVycmVkX3VzZXJuYW1lIjoiVGVzdFBsZWFzZUlnbm9yZSIsImFwcGxpY2F0aW9uSWQiOiJiODRjY2FmOS1jMWM3LTRiYTgtODJjOS1lMjYzYmY5YjE1MmEiLCJyb2xlcyI6W10sImF1dGhfdGltZSI6MTcxMzQyNDQyMywidGlkIjoiZmQxYTQwMTItMjllYS00ZmJmLWFhNjYtM2Q4OGZiY2VhN2VjIn0.VBbyo8DpURzKlhr9cL8iC5ao6-NAJvUCJx_4bkRtqbA';
    private const USER_EXTERNAL_ID = 'auth0|638a07d78779a00e526a4ce4';
    private const FUSIONAUTH_ID = '6ecfafe1-14d6-4608-a2a8-9318bf17a472';
    private const USER_EMAIL = 'test@example.com';

    /**
     * @var \Tymon\JWTAuth\JWT|\Mockery\MockInterface
     */
    protected $jwt;

    /**
     * @var FusionAuthEloquentUserProvider
     */
    protected $provider;

    /** @var Request|\Mockery\MockInterface */
    protected $request;

    /**
     * @var FusionAuthJwtGuard
     */
    protected $guard;

    public function setUp(): void
    {
        parent::setUp();

        $this->jwt = Mockery::mock(JWT::class);
        $this->provider = Mockery::mock(FusionAuthEloquentUserProvider::class);
        $this->request = Mockery::mock(Request::class);
        $this->guard = new FusionAuthJwtGuard($this->jwt, $this->provider, $this->request);
    }

    public function test_it_should_get_the_authenticated_user_if_a_valid_token_is_provided_and_user_exists(): void
    {
        $user = User::factory()->make([
            'email' => self::USER_EMAIL,
            User::AUTH0COLUMN => self::USER_EXTERNAL_ID,
            User::FUSIONCOLUMN => self::FUSIONAUTH_ID,
        ]);

        $payload = Mockery::mock(Payload::class);
        $payload->shouldReceive('offsetGet')->once()->with('sub')->andReturn(self::FUSIONAUTH_ID);

        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturn(FusionAuthJwtGuard::TYPE)
            ->once();
        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(self::FUSION_TOKEN);
        $this->jwt->shouldReceive('check')->once()->with(true)->andReturn($payload);
        $this->jwt->shouldReceive('checkSubjectModel')
            ->once()
            ->with(User::class)
            ->andReturn(true);

        $this->provider->shouldReceive('getModel')
            ->once()
            ->andReturn(User::class);
        $this->provider->shouldReceive('findUserByFusionAuthId')
            ->once()
            ->with(self::FUSIONAUTH_ID)
            ->andReturn($user);

        $this->assertSame(self::USER_EMAIL, $this->guard->user()->email);
        $this->assertSame(self::FUSIONAUTH_ID, $this->guard->user()->fusionauth_id);
        $this->assertSame(self::USER_EXTERNAL_ID, $this->guard->userOrFail()->external_id);
        $this->assertTrue($this->guard->check());
    }

    public function test_it_should_create_and_get_the_authenticated_user_if_a_valid_token_is_provided(): void
    {
        $user = User::factory()->make([
            'email' => self::USER_EMAIL,
            User::AUTH0COLUMN => self::USER_EXTERNAL_ID,
            User::FUSIONCOLUMN => self::FUSIONAUTH_ID,
        ]);

        $payload = Mockery::mock(Payload::class);
        $payload->shouldReceive('offsetGet')->once()->with('sub')->andReturn(self::FUSIONAUTH_ID);

        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturn(FusionAuthJwtGuard::TYPE)
            ->once();
        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(self::FUSION_TOKEN);
        $this->jwt->shouldReceive('check')->once()->with(true)->andReturn($payload);
        $this->jwt->shouldReceive('getPayload')->once()->andReturn($payload);
        $this->jwt->shouldReceive('checkSubjectModel')
            ->once()
            ->with(User::class)
            ->andReturn(true);

        $this->provider->shouldReceive('getModel')
            ->once()
            ->andReturn(User::class);
        $this->provider->shouldReceive('findUserByFusionAuthId')
            ->once()
            ->with(self::FUSIONAUTH_ID)
            ->andReturn(null);

        $this->provider->shouldReceive('getModelFromPayload')
            ->once()
            ->with($payload)
            ->andReturn($user);

        $this->assertSame(self::USER_EMAIL, $this->guard->user()->email);
        $this->assertSame(self::FUSIONAUTH_ID, $this->guard->user()->fusionauth_id);
    }

    public function test_it_returns_null_on_empty_payload(): void
    {
        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturn(FusionAuthJwtGuard::TYPE)
            ->once();

        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(self::FUSION_TOKEN);
        $this->jwt->shouldReceive('check')->once()->with(true)->andReturn(null);
        $this->jwt->shouldReceive('getPayload')->once()->andReturn(null);

        $this->assertNull($this->guard->user());
    }

    public function test_it_returns_null_on_jwtexception(): void
    {
        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturn(FusionAuthJwtGuard::TYPE)
            ->once();

        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(self::FUSION_TOKEN);
        $this->jwt->shouldReceive('check')->once()->with(true)->andReturn(null);
        $this->jwt->shouldReceive('getPayload')->once()->andThrow(new JWTException());

        $this->assertNull($this->guard->user());
    }

    public function test_it_returns_null_if_no_xauthtype_header_set(): void
    {
        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturnNull()
            ->once();

        $this->assertNull($this->guard->user());
    }
}
