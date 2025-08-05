<?php

declare(strict_types=1);

namespace Tests\Unit\FusionAuth;

use App\MagicLink\Providers\MagicLinkAuthEloquentUserProvider;
use App\MagicLink\Guards\MagicJwtAuthGuard;
use App\Models\User;
use Illuminate\Http\Request;
use Mockery;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\Payload;
use Tests\TestCase;

class MagicJwtAuthGuardTest extends TestCase
{
    private const TOKEN = 'eyJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhY21lLmNvbSIsImlhdCI6MTcxNDQwOTM4OSwiZXhwIjoxNzE0NDEyOTg5LCJuYmYiOjE3MTQ0MDkzODksImp0aSI6IjZXUmFVQ1hLSjZiTU1RT00iLCJzdWIiOiJlbXB0eSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjciLCJlbWFpbCI6InRlc3Rvdnlha2twbGVhc2VpZ25vcmVAZ21haWwuY29tIn0.Ar25NTei9xD-yCqbHcIcANlVB5MMck_uvppXg8frsg0';
    private const USER_EMAIL = 'test@example.com';

    /**
     * @var \Tymon\JWTAuth\JWT|\Mockery\MockInterface
     */
    protected $jwt;

    /**
     * @var MagicLinkAuthEloquentUserProvider
     */
    protected $provider;

    /** @var Request|\Mockery\MockInterface */
    protected $request;

    /**
     * @var MagicJwtAuthGuard
     */
    protected $guard;

    public function setUp(): void
    {
        parent::setUp();

        $this->jwt = Mockery::mock(JWT::class);
        $this->provider = Mockery::mock(MagicLinkAuthEloquentUserProvider::class);
        $this->request = Mockery::mock(Request::class);
        $this->guard = new MagicJwtAuthGuard($this->jwt, $this->provider, $this->request);
    }

    public function test_it_should_get_the_authenticated_user_if_a_valid_token_is_provided_and_user_exists(): void
    {
        $user = User::factory()->make([
            'email' => self::USER_EMAIL,
        ]);

        $payload = Mockery::mock(Payload::class);
        $payload->shouldReceive('offsetGet')->once()->with('email')->andReturn(self::USER_EMAIL);

        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturn(MagicJwtAuthGuard::TYPE)
            ->once();
        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(self::TOKEN);
        $this->jwt->shouldReceive('check')->once()->with(true)->andReturn($payload);
        $this->jwt->shouldReceive('checkSubjectModel')
            ->once()
            ->with(User::class)
            ->andReturn(true);

        $this->provider->shouldReceive('getModel')
            ->once()
            ->andReturn(User::class);
        $this->provider->shouldReceive('findUserByEmail')
            ->once()
            ->with(self::USER_EMAIL)
            ->andReturn($user);

        $this->assertSame(self::USER_EMAIL, $this->guard->user()->email);
        $this->assertTrue($this->guard->check());
    }

    public function test_it_should_create_and_get_the_authenticated_user_if_a_valid_token_is_provided(): void
    {
        $user = User::factory()->make([
            'email' => self::USER_EMAIL,
        ]);

        $payload = Mockery::mock(Payload::class);
        $payload->shouldReceive('offsetGet')->once()->with('email')->andReturn(self::USER_EMAIL);

        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturn(MagicJwtAuthGuard::TYPE)
            ->once();
        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(self::TOKEN);
        $this->jwt->shouldReceive('check')->once()->with(true)->andReturn($payload);
        $this->jwt->shouldReceive('getPayload')->once()->andReturn($payload);
        $this->jwt->shouldReceive('checkSubjectModel')
            ->once()
            ->with(User::class)
            ->andReturn(true);

        $this->provider->shouldReceive('getModel')
            ->once()
            ->andReturn(User::class);
        $this->provider->shouldReceive('findUserByEmail')
            ->once()
            ->with(self::USER_EMAIL)
            ->andReturn(null);

        $this->provider->shouldReceive('getModelFromPayload')
            ->once()
            ->with($payload)
            ->andReturn($user);

        $this->assertSame(self::USER_EMAIL, $this->guard->user()->email);
    }

    public function test_it_returns_null_on_empty_payload(): void
    {
        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturn(MagicJwtAuthGuard::TYPE)
            ->once();

        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(self::TOKEN);
        $this->jwt->shouldReceive('check')->once()->with(true)->andReturn(null);
        $this->jwt->shouldReceive('getPayload')->once()->andReturn(null);

        $this->assertNull($this->guard->user());
    }

    public function test_it_returns_null_on_jwtexception(): void
    {
        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturn(MagicJwtAuthGuard::TYPE)
            ->once();

        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(self::TOKEN);
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
