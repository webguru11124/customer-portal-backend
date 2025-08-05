<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\MagicLink;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

final class MagicLinkTest extends TestCase
{
    private const EMAIL = 'test@example.com';
    private const TOKEN = 'eyJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhY21lLmNvbSIsImlhdCI6MTcxNDQwOTM4OSwiZXhwIjoxNzE0NDEyOTg5LCJuYmYiOjE3MTQ0MDkzODksImp0aSI6IjZXUmFVQ1hLSjZiTU1RT00iLCJzdWIiOiJlbXB0eSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjciLCJlbWFpbCI6InRlc3Rvdnlha2twbGVhc2VpZ25vcmVAZ21haWwuY29tIn0.Ar25NTei9xD-yCqbHcIcANlVB5MMck_uvppXg8frsg0';

    protected Guard|Mockery\MockInterface $magicLinkGuardMock;

    public function test_it_continues_when_magic_link_user_found(): void
    {
        $user = User::factory()->make([
            'email' => self::EMAIL,
            'email_verified' => true,
        ]);
        $this->magicLinkGuardMock = Mockery::mock(Guard::class);
        $this->magicLinkGuardMock->expects('user')
            ->once()
            ->andReturn($user);
        $authFactoryMock = Mockery::mock(AuthFactory::class);
        $authFactoryMock->expects('guard')
            ->with('magicjwtguard')
            ->once()
            ->andReturn($this->magicLinkGuardMock);
        $this->instance(AuthFactory::class, $authFactoryMock);
        Auth::expects('shouldUse')
            ->with('magicjwtguard')
            ->once();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->once()
            ->andReturn('MagicLink');
        $request->shouldReceive('bearerToken')
            ->once()
            ->andReturn(self::TOKEN);
        $middleware = new MagicLink();

        $middleware->handle($request, fn (Request $r) => $this->assertSame($request, $r));
    }

    public function test_it_continues_when_no_magic_link_user_found(): void
    {
        $this->magicLinkGuardMock = Mockery::mock(Guard::class);
        $this->magicLinkGuardMock->expects('user')
            ->never();
        $authFactoryMock = Mockery::mock(AuthFactory::class);
        $authFactoryMock->expects('guard')
            ->with('magicjwtguard')
            ->never();
        $this->instance(AuthFactory::class, $authFactoryMock);
        Auth::expects('shouldUse')
            ->with('magicjwtguard')
            ->never();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->once()
            ->andReturn('Auth0');

        $middleware = new MagicLink();

        $middleware->handle($request, fn (Request $r) => $this->assertSame($request, $r));
    }
}
