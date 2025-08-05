<?php

declare(strict_types=1);

namespace tests\Unit\MagicLink\Guards;

use App\DTO\MagicLink\ValidationErrorDTO;
use App\FusionAuth\FusionAuthJwtGuard;
use App\MagicLink\Guards\MagicLinkGuard;
use App\MagicLink\MagicLink;
use App\MagicLink\Providers\MagicLinkAuthEloquentUserProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class MagicLinkGuardTest extends TestCase
{
    private const USER_EMAIL = 'test@example.com';
    private const TOKEN = 'eyJlIjoidGVzdG92eWFra3BsZWFzZWlnbm9yZUBnbWFpbC5jb20iLCJ4IjoxNzE0MTIzNjU5LCJzIjoiZWQ4NzMwNmUxYTMyZGY4MDJkY2RiNThhOGIxOWM0MzkifQ';

    /** @var MagicLink|\Mockery\MockInterface */
    protected $mlp;

    /** @var Request|\Mockery\MockInterface */
    protected $request;

    /** @var MagicLinkAuthEloquentUserProvider */
    protected $provider;

    /** @var FusionAuthJwtGuard */
    protected $guard;

    public function setUp(): void
    {
        parent::setUp();

        $this->mlp = Mockery::mock(MagicLink::class);
        $this->provider = Mockery::mock(MagicLinkAuthEloquentUserProvider::class);
        $this->request = Mockery::mock(Request::class);
        $this->guard = new MagicLinkGuard($this->mlp, $this->provider, $this->request);
    }

    public function test_it_should_get_the_authenticated_user_if_a_valid_token_is_provided_and_user_exists(): void
    {
        $user = User::factory()->make(['email' => self::USER_EMAIL]);
        $payload = ['e' => self::USER_EMAIL];

        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturn(MagicLinkGuard::TYPE)
            ->once();
        $this->request->shouldReceive('bearerToken')
            ->andReturn(self::TOKEN);
        $this->mlp->shouldReceive('decode')
            ->with(self::TOKEN)->andReturn($payload);
        $this->provider->shouldReceive('findUserByEmail')
            ->once()
            ->with(self::USER_EMAIL)
            ->andReturn($user);

        $this->assertSame(self::USER_EMAIL, $this->guard->user()->email);
        $this->assertTrue($this->guard->check());
    }

    public function test_it_should_create_and_get_the_authenticated_user_if_a_valid_token_is_provided(): void
    {
        $user = User::factory()->make(['email' => self::USER_EMAIL]);
        $payload = ['e' => self::USER_EMAIL];

        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturn(MagicLinkGuard::TYPE)
            ->once();
        $this->request->shouldReceive('bearerToken')
            ->andReturn(self::TOKEN);
        $this->mlp->shouldReceive('decode')
            ->with(self::TOKEN)->andReturn($payload);
        $this->provider->shouldReceive('findUserByEmail')
            ->once()
            ->with(self::USER_EMAIL)
            ->andReturn(null);
        $this->provider->shouldReceive('getModelFromMagicLinkPayload')
            ->once()
            ->with($payload)
            ->andReturn($user);

        $this->assertSame(self::USER_EMAIL, $this->guard->user()->email);
        $this->assertTrue($this->guard->check());
    }

    public function test_it_returns_null_on_invalid_payload(): void
    {
        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturn(MagicLinkGuard::TYPE)
            ->twice();
        $this->request->shouldReceive('bearerToken')
            ->andReturn(self::TOKEN);
        $this->mlp->shouldReceive('decode')
            ->with(self::TOKEN)->andReturn(null);

        $this->assertNull($this->guard->user());
        $this->assertFalse($this->guard->check());
    }

    public function test_it_returns_null_if_no_xauthtype_header_set(): void
    {
        $this->request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->andReturnNull()
            ->once();

        $this->assertNull($this->guard->user());
    }

    public function test_it_returns_true_on_validate(): void
    {
        $this->assertTrue($this->guard->validate());
    }

    public function test_it_returns_validation_error(): void
    {
        $error = new ValidationErrorDTO(
            ValidationErrorDTO::INVALID_TOKEN_CODE,
            ValidationErrorDTO::INVALID_TOKEN_MESSAGE
        );
        $this->mlp->shouldReceive('getValidationError')
            ->andReturn($error);

        $validationError = $this->guard->getValidationError();
        $this->assertEquals(ValidationErrorDTO::INVALID_TOKEN_MESSAGE, $validationError->message);
        $this->assertEquals(ValidationErrorDTO::INVALID_TOKEN_CODE, $validationError->code);
    }
}
